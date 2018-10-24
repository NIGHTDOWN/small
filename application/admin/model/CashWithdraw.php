<?php

namespace app\admin\model;

use think\Model;
use think\Db;

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
        // 'apply_time_text',
        // 'admin_time_text',
        // 'pay_time_text'
    ];

    const STATUS_TEXT  = [
        0 => '运营待审核',
        1 => '已打款' ,
        2 => '运营审核未通过' ,
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
    
    // public function getApplyTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['apply_time']) ? $data['apply_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }


    // public function getAdminTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['admin_time']) ? $data['admin_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }


    // public function getPayTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['pay_time']) ? $data['pay_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    // protected function setApplyTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }

    // protected function setAdminTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }

    // protected function setPayTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }


}
