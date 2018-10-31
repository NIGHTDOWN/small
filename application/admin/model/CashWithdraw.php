<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use think\Cache;
use think\Queue;

class CashWithdraw extends Model
{
    // 表名
    protected $name = 'cash_withdraw';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'checkbox'
        // 'apply_time_text',
        // 'admin_time_text',
        // 'pay_time_text'
    ];

    const STATUS_TEXT  = [
        0 => '运营待审核',
        1 => '已打款',
        2 => '运营审核未通过',
        3 => '已到账' ,
        4 => '打款失败',
        5 => '财务审核通过',
        6 => '运营已审核',
        7 => '财务审核未通过'
    ];

    const STATUS = [
        'AUDITING'      => 0,
        'PAYING'        => 1,
        'AUDIT_FAIL'    => 2,
        'PAY_SUCCESS'   => 3,
        'PAY_FAIL'      => 4,
        'AUDIT_SUCCESS' => 5,
        'OPERATIVE'     => 6,
        'OPERATE_FAIL'  => 7
    ];

    const PAYMENT_TEXT = [
        0 => '支付宝',
        1 => '微信',
    ];
    const ORDER_SN_PRE = 'XYX';

    const ORDER_SN_LENGTH = 20;

    const COIN_TO_PRICE = 1;

    const CASH_ORDER_PAY_QUEUE_NAME = 'cash_order_pay';

    const ADOPT_LOCK_CACHE_KEY = 'cash_order_adopt_lock';

    const ADOPT_LOCK_CACHE_LIFE = 20;

    const DELAY_PAY_ORDER = 86400;

    
    public function user()
    {
        return $this->belongsTo('user', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 提现金币数: 已到账的数据
     * @param array $param  参数
     * @return bool|int
     */
    public function getWithdrawTotal($param)
    {
        $map = ['status' => ['=', self::STATUS['PAY_SUCCESS']]]; // 已到账状态
        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            $map[] = ['apply_time', 'between', [$param['start_time'], $param['end_time']]];
        }
        $withdrawTotal = Db::name('cash_withdraw')->field("sum(price * apply_price) sum_count")->where($map)->find() ?: [];

        return isset($withdrawTotal['sum_count']) ? abs($withdrawTotal['sum_count']) : 0;
    }

    /**
     * 拒绝
     *
     * @param array $ids
     * @param int $admin_id
     * @param $msg
     * @param bool $type
     * @return int
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function refuse(Array $ids, $admin_id = 0, $msg, $type = false)
    {
        if (!$msg) {
            $this->error = '请输入不通过原因';
            return false;
        }
        if ($type === false) {
            $this->error = '缺少type';
            return false;
        }
        $updateData = [
            'comment' => $msg,
            'admin_time' => time()
        ];
        // 1-运营，2-财务
        if ($type == 1) {
            $updateData['operator_id'] = $admin_id;
            $updateData['status'] = self::STATUS['AUDIT_FAIL'];
        } elseif ($type == 2) {
            $updateData['admin_id'] = $admin_id;
            $updateData['status'] = self::STATUS['OPERATE_FAIL'];
        } else {
            $this->error = 'type类型错误';
            return false;
        }
        $map = [
            'id' => ['in', $ids],
            'status' => ['in', [self::STATUS['OPERATIVE'], self::STATUS['AUDITING']]]
        ];
        $datas = Db::name('cash_withdraw')->where($map)->select();
        if (count($datas) <= 0) {
            $this->error = '审核失败';
            return false;
        }

        foreach ($datas as $order) {
            Db::name('cash_withdraw')->where('id', 'eq', $order['id'])->update($updateData);
            $ret = UserCoin::unFreezeCoin($order['user_id'], $order['log_id']);
            if (!$ret['success']) {
                $this->error = '审核失败';
                return false;
            }
        }
        return true;
    }

    /**
     * 运营审核通过
     *
     * @param array $ids
     * @param int $admin_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function operatorAdopt(Array $ids = [], $admin_id = 0)
    {
        $map = [
            'id' => ['IN', $ids],
            'status' => ['eq', self::STATUS['AUDITING']]
        ];

        $datas = Db::name('cash_withdraw')->where($map)->select();
        foreach ($datas as $order) {
            Db::name('cash_withdraw')->where('id', 'eq', $order['id'])->update(
                [
                    'operator_id' => $admin_id,
                    'admin_time' => time(),
                    'status' => self::STATUS['OPERATIVE'],
                ]);
        }

        return true;
    }

    public function adopt(Array $ids = [], $admin_id = 0)
    {
        if (!$ids) {
            $this->error = '审批数量错误';
            return false;
        }
        if (Cache::has(self::ADOPT_LOCK_CACHE_KEY)) {
            $this->error = '有审批正在操作中';
            return false;
        }
        cache(self::ADOPT_LOCK_CACHE_KEY, 1, self::ADOPT_LOCK_CACHE_LIFE);
        $map = [
            'id' => ['IN', $ids],
            'status' => ['eq', self::STATUS['OPERATIVE']]
        ];
        $orders  = Db::name('cash_withdraw')
            ->master()
            ->where($map)
            ->select();

        if (count($orders) != count($ids)) {
            $this->error = '审批数量错误';
            return false;
        }

        foreach ($orders as $order) {
            Queue::push('app\common\job\CashOrderPay', ['order_id' => $order['id']], self::CASH_ORDER_PAY_QUEUE_NAME);

            Db::name('cash_withdraw')->where('id','eq', $order['id'])->update(
                [
                    'admin_id' => $admin_id,
                    'admin_time' => time(),
                    'status' => self::STATUS['AUDIT_SUCCESS'],
                ]);
        }

        Cache::rm(self::ADOPT_LOCK_CACHE_KEY);
        return true;
    }
}
