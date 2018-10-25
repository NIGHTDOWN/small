<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class UserCoin extends Model
{
    // 表名
    protected $name = 'user_coin';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        // 'create_time_text'
    ];

    // 消费金币的活动类型
    const CONSUME_OPTIONS = [
        1 => 'xcxdzp_minus'
    ];

    
    public function user()
    {
        return $this->belongsTo('user', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 设置金币价值
     *
     * @param  Array $param  参数
     */
    public function setCoinToPrice($data)
    {
        if (!$data['coin']) {
            $this->error = '金币数量必填';
            return false;
        }
        if(!preg_match("/^[1-9][0-9]*$/",$data['coin'])){
            $this->error = '数值格式不正确';
            return false;
        }

        \app\common\model\CoreValue::setCoinToPrice($data['coin']);
        return true;
    }

    /**
     * 设置首次提现最低金额
     *
     * @param  Array $param  参数
     */
    public function setFirstCoinToPrice($data)
    {
        if (!isset($data['price'])) {
            $this->error = '数值必填';
            return false;
        }
        if(!preg_match("/^[1-9][0-9]*$/",$data['price'])){
            $this->error = '数值格式不正确';
            return false;
        }

        \app\common\model\CoreValue::setFirstCoinToPrice($data['price']);
        return true;
    }

    /**
     * 设置后续提现最低金额
     *
     * @param  Array $param  参数
     */
    public function setNormalCoinToPrice($data)
    {
        if (!isset($data['price'])) {
            $this->error = '数值必填';
            return false;
        }
        if(!preg_match("/^[1-9][0-9]*$/",$data['price'])){
            $this->error = '数值格式不正确';
            return false;
        }

        \app\common\model\CoreValue::setNormalCoinToPrice($data['price']);
        return true;
    }

    /**
     * 设置每月可提现总额
     *
     * @param  Array $param  参数
     */
    public function setTotalCoinToPrice($data)
    {
        if (!isset($data['price'])) {
            $this->error = '数值必填';
            return false;
        }
        if(!preg_match("/^[1-9][0-9]*$/",$data['price'])){
            $this->error = '数值格式不正确';
            return false;
        }

        \app\common\model\CoreValue::setTotalCoinToPrice($data['price']);
        return true;
    }

    /**
     * 设置每月可提现次数
     *
     * @param  Array $param  参数
     */
    public function setTotalCoinToPriceNum($data)
    {
        if (!isset($data['price'])) {
            $this->error = '数值必填';
            return false;
        }
        if(!preg_match("/^[1-9][0-9]*$/",$data['price'])){
            $this->error = '数值格式不正确';
            return false;
        }

        \app\common\model\CoreValue::setTotalCoinToPriceNum($data['price']);
        return true;
    }

    /**
     * 设置需填身份证提现额度
     *
     * @param  Array $param  参数
     */
    public function setNeedIdCardPrice($data)
    {
        if (!isset($data['price'])) {
            $this->error = '数值必填';
            return false;
        }
        if(!preg_match("/^[1-9][0-9]*$/",$data['price'])){
            $this->error = '数值格式不正确';
            return false;
        }

        \app\common\model\CoreValue::setNeedIdCardPrice($data['price']);
        return true;
    }

    /**
     * 设置延迟到账时间
     *
     * @param  Array $param  参数
     */
    public function setDelayPay($data)
    {
        if (!isset($data['delay'])) {
            $this->error = '数值必填';
            return false;
        }
        if(!preg_match("/^[1-9][0-9]*$/",$data['delay'])){
            $this->error = '数值格式不正确';
            return false;
        }

        \app\common\model\CoreValue::setDelayPay($data['delay']);
        return true;
    }

    /**
     * 消费金币数: 指非提现使用掉的金币数统计
     * @param array $param  参数
     * @return bool|int
     */
    public function getConsumeTotal($param)
    {
        // 非提现用掉的金币, 目前只有抽奖活动
        $map = [
            'type' => ['=', 2],
            'mission_tag' => ['in', self::CONSUME_OPTIONS]
        ];
        if (! empty($param['start_time']) && ! empty($param['end_time'])) {
            $map[] = ['create_time', 'between', [$param['start_time'], $param['end_time']]];
        }
        $consumeTotal = $this->where($map)->sum('amount');

        return ! empty($consumeTotal) ? abs($consumeTotal) : 0;
    }

    private static function tradeParams($user_id, $title, $amount, $params = [])
    {
        if(!isset($params['trade_no']))
        {
            $trade_number_prefix = isset($params['mission_tag']) ? 'MS' : 'SY';
            $params['trade_no'] = self::buildTradeNumber($trade_number_prefix, $user_id);
        }
        $params['user_id']      = $user_id;
        $params['create_time']  = time();
        $params['amount']       = $params['type'] == 1 ? abs($amount) : 0 - abs($amount);
        $params['reason']       = $title;
        return $params;
    }

    public static function unFreezeCoin($user_id, $log_id)
    {
        $success = true;
        $log_info = Db('UserCoin')->where(['id' => $log_id, 'is_freeze' => 1])->find();
        if($log_info['user_id'] == $user_id)
        {
            $params['type']         = 1;
            $params['is_freeze']    = 1;
            $params['trade_no']     = $log_info['trade_no'];
            $params = self::tradeParams($user_id, $log_info['reason'] . '(unfreeze)', $log_info['amount'], $params);
            $params['verification'] = self::verificationKey($params);
            Db::startTrans();
            try {
                Db::name('UserCoin')->insert($params);
                Db::name('UserBurse')->where('user_id', $user_id)->update(['coin' => ['inc', $params['amount']], 'frozen_coin' => ['dec', $params['amount']]]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $success = false;
            }
        }else{
            return ['success' => false, 'log' => 0];
        }
        return ['success' => $success, 'log' => $log_info['id']];
    }

    private static function verificationKey($params = [])
    {
        $accept_data = array_fill_keys(['trade_no', 'user_id', 'type', 'amount', 'is_freeze', 'create_time'], 0);
        $params = array_merge($accept_data, array_intersect_key($params, $accept_data));
        ksort($params);
        return md5((config('other.verification_key').'/'.implode('/', $params)));
    }

    public static function buildTradeNumber($prefix = '', $in_user = 0, $out_user = 0, $time = 0)
    {
        if($time == 0)$time = time();
        return $prefix . str_pad($in_user, 10, 0, STR_PAD_LEFT) . str_pad($out_user, 10, 0, STR_PAD_LEFT) . $time . str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
    }

}
