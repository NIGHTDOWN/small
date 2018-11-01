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

    /**
     * 订单支付
     * @param $order_id
     */
    public static function pay($order_id)
    {
        $success = false;
        $msg = '';
        $error_code = '';
        $end_order_status = CashOrderModel::STATUS['PAY_FAIL']; //最后订单状态
        $order = Db::name('cash_withdraw')
            ->master()
            ->where([
                ['id','eq',$order_id],
                ['status' ,'eq',CashOrderModel::STATUS['AUDIT_SUCCESS']]
            ])
            ->find();

        if (!$order) {
            $msg = "未处理订单ID:{$order_id}不存在";
            goto end;
        }
        $order_detail = self::calculate($order['user_id'],$order['apply_price'],$order['apply_time']);

        if ($order_detail['money'] < self::MIN_WITHDRAW_MONEY) {
            $end_order_status =  CashOrderModel::STATUS['AUDIT_FAIL'];
            $msg = "未处理订单ID:{$order_id} 提现金额小于".self::MIN_WITHDRAW_MONEY;
            goto end;
        }
        try{
            if ($order['payment'] == 0 ) {
                $config = config('pay.alipay');
                $params = new \Yurun\PaySDK\AlipayApp\Params\PublicParams;
                $params->appID = $config['appid'];
                $params->appPrivateKey = file_get_contents($config['private_key_path']);
                $params->appPublicKey = file_get_contents($config['public_key_path']);
                $pay = new \Yurun\PaySDK\AlipayApp\SDK($params);
                $request = new \Yurun\PaySDK\AlipayApp\Fund\Transfer\Request;
                $request->businessParams->out_biz_no = $order['order_sn'];
                $request->businessParams->payee_type = 'ALIPAY_LOGONID';
                $request->businessParams->payee_account = $order['payfee_account'];
                $request->businessParams->amount = $order_detail['money'];
                $request->businessParams->payee_real_name = $order['payfee_real_name'];
                $result = $pay->execute($request);
                if (!$pay->checkResult()) {
                    $msg = $pay->getError();
                    $error_code = $pay->getErrorCode();
                    ModelUserCoin::unFreezeCoin($order['user_id'],$order['log_id']);
                    goto end;
                }
                $total_coin=(int)$order['price']*(int)$order['apply_price'];
                ModelUserCoin::outFreezeCoin($order['user_id'],$order['log_id'],$total_coin);
                $result = array_shift($result);
                $pay_trace_sn = $result['order_id'];
                $payfee_account = $order['payfee_account'];
            }else {
                $openid = $order['payfee_account'];
                $config = config('pay.weipay');
                $params = new \Yurun\PaySDK\Weixin\Params\PublicParams;
                $params->appID = $config['appid'];
                $params->mch_id = $config['mch_id'];
                $params->key = $config['key'];
                $params->certPath = $config['cert_path'];
                $params->keyPath = $config['key_path'];
                $pay = new \Yurun\PaySDK\Weixin\SDK($params);
                $request = new \Yurun\PaySDK\Weixin\CompanyPay\Weixin\Pay\Request;
                $request->partner_trade_no = $order['order_sn']; // 订单号
                $request->openid = $openid;
                $request->check_name = 'FORCE_CHECK';
                $request->re_user_name = $order['payfee_real_name'];
                $request->amount = $order_detail['money'] * 100;
                $request->desc = '提现';
                $request->spbill_create_ip = '127.0.0.1';
                $result = $pay->execute($request);
                if (!$pay->checkResult()) {
                    $msg = $pay->getError();
                    $error_code = $pay->getErrorCode();
                    $ret=ModelUserCoin::unFreezeCoin($order['user_id'],$order['log_id']);
                    goto end;
                }
                $total_coin=$order['apply_price']*$order['price'];
                $ret=ModelUserCoin::outFreezeCoin($order['user_id'],$order['log_id'],$total_coin);
                $pay_trace_sn = $result['payment_no'];
                $payfee_account = $openid;
            }
        }catch (\Exception $e) {
            ModelUserCoin::unFreezeCoin($order['user_id'],$order['log_id']);
            $msg = $e->getMessage();
            goto end;
        }
        Db::name('cash_withdraw')->where('id','eq',$order_id)->update([
            'tax' => $order_detail['tax'],
            'service_charge' => $order_detail['service_charge'],
            'money' => $order_detail['money'],
            'should_money' =>$order_detail['money'],
            'amount' => $order_detail['amount'],
            'pay_trace_sn' => $pay_trace_sn,
            'payfee_account' => $payfee_account,
            'pay_time' => time(),
            'status' => CashOrderModel::STATUS['PAY_SUCCESS'],
        ]);
        $total_amount=$order['apply_price']*$order['price'];//金币价值乘以提现的金额
        $update_data = [
            'withdraw_coin' => Db::raw("`withdraw_coin`+{$total_amount}"),
            'withdraw_coin_amount' => Db::raw("`withdraw_coin_amount`+ {$order_detail['money']}"),
        ];
        $update = Db::name('user_burse')->where('user_id' ,'eq' ,$order['user_id'])->update($update_data);
        //提现成功

        self::incrUserApplyWithdrawNum($order['user_id'],$order['apply_price']);//增加当月已提现金额

        self::incrUserApplyWithdrawCount($order['user_id']);//增加当月提现次数

        //短信
        SmsSendTask::sendSms(User::getMobile($order['user_id']),SmsSendTask::TEMPLATE_CODE[2]);
        //系统消息
        send_sys_message('亲爱的小印象用户：您的提现申请已通过！提现金额将会在三天之内到账。如有疑问请咨询客服QQ：2852787060',1,$order['user_id']);
        return [
            'success' => true,
            'order_id' => $order_id,
        ];
        end:
        self::logError($order_id,$error_code,$msg,$order ? $end_order_status : null);
        if (in_array($error_code,['PAYEE_USER_INFO_ERROR','NAME_MISMATCH'],true)){
            //如果失败原因是实名认证问题,发失败短信和系统消息
            //短信
            SmsSendTask::sendSms(User::getMobile($order['user_id']),SmsSendTask::TEMPLATE_CODE[1]);
            //系统消息
            send_sys_message('亲爱的小印象用户：您的提现申请未成功到账。原因：您提现的账户信息与小印象账户实名信息不一致。感谢您的理解和支持。如有疑问请咨询客服QQ：2852787060',1,$order['user_id']);
        }
        return [
            'success' => $success,
            'order_id' => $order_id,
            'msg' => $msg
        ];
    }

    /**
     * 错误记录
     * @param $order_id
     * @param string $error_code
     * @param string $msg
     * @param int $order_status
     */
    public static function logError($order_id,$error_code='',$msg = '',$order_status = null)
    {
        $error_msg_id = Db::name('cash_order_error')->insertGetId([
            'order_id' => $order_id,
            'error_code' => $error_code,
            'msg' => $msg,
            'create_time' => time(),
        ]);
        if(is_null($order_status)) return;
        $update_data = [
            'status' => $order_status,
            'error_msg_id' => $error_msg_id,
        ];
        return Db::name('cash_withdraw')->where('id','eq',$order_id)->update($update_data);
    }

    public static function getStatusText($list){
        $error_msg_ids = array_column($list,'error_msg_id');
        if ($error_msg_ids) {
        $error_msgs = Db::name('cash_order_error')->where('id','IN',$error_msg_ids)->column('msg','order_id');
        }
        foreach ($list as $key => $row){
            if (isset($error_msgs[$row['id']])&&$error_msgs[$row['id']]!==''){
                $list[$key]['status_text']="失败(".$error_msgs[$row['id']].")";
            }elseif($row['status']==self::STATUS['AUDIT_FAIL']||$row['status']==self::STATUS['OPERATE_FAIL']){
                $list[$key]['status_text']="失败(".$row['comment'].")";
            }else{

            }
        }
        return $list;
    }
}
