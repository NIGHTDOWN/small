<?php
namespace app\common\model;

use think\Db;
use think\Model;

class UserCoin extends Model
{
    /**
     * 收入金币
     * @param $user_id
     * @param $title
     * @param $amount
     * @param array $params
     * @return array
     */
    public static function inCoin($user_id, $title, $amount, $params = [])
    {
        $params['type']     = 1;
        $params = self::tradeParams($user_id, $title, $amount, $params);
        $params['verification'] = self::verificationKey($params);
        $success = true;
        $log_id = 0;
        Db::startTrans();
        try {
            $log_id = Db::name('UserCoin')->insertGetId($params);
            if(!isset($params['mission_tag']) || $params['mission_tag'] == '') {
                $user_burse_sql = 'insert into ' . Config('database.prefix') . 'user_burse (`user_id`, `coin`) values (' . $user_id . ',' . $amount . ' ) on duplicate key update `coin`=`coin` + values(`coin`)';
            }elseif($params['mission_tag'] == 'invite_register' || $params['mission_tag'] == 'sub_invite_register') {
                $user_burse_sql = 'insert into '.Config('database.prefix').'user_burse (`user_id`, `coin`, `coin_earning_total`, `invitation_earning`) values ('.$user_id.','.$amount.','.$amount.','.$amount.') on duplicate key update `coin`=`coin` + values(`coin`), `coin_earning_total`=`coin_earning_total` + values(`coin_earning_total`), `invitation_earning`=`invitation_earning` + values(`invitation_earning`)';
            }else {
                $user_burse_sql = 'insert into '.Config('database.prefix').'user_burse (`user_id`, `coin`, `coin_earning_total`) values ('.$user_id.','.$amount.','.$amount.') on duplicate key update `coin`=`coin` + values(`coin`),  `coin_earning_total`=`coin_earning_total` + values(`coin_earning_total`)';
            }
            Db::execute($user_burse_sql);
            CoreValue::incrCoin($amount);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $success = false;
        }
        return ['success' => $success, 'log' => $log_id];
    }

    /**
     * 支出金币
     * @param $user_id
     * @param $title
     * @param $amount
     * @param array $params
     * @return array
     */
    public static function outCoin($user_id, $title, $amount, $params = [])
    {
        $params['type']     = 2;
        $params = self::tradeParams($user_id, $title, $amount, $params);
        $params['verification'] = self::verificationKey($params);
        $success = true;
        $log_id = 0;
        Db::startTrans();
        try {
            $log_id = Db::name('UserCoin')->insertGetId($params);
            Db::name('UserBurse')->where('user_id', $user_id)->update(['coin' => ['dec', $amount]]);
            CoreValue::decrCoin($amount);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $success = false;
        }
        return ['success' => $success, 'log' => $log_id];
    }

    public static function freezeCoin($user_id, $title, $amount, $params = [])
    {
        $params['type']     = 2;
        $params['is_freeze']    = 1;
        $params = self::tradeParams($user_id, $title, $amount, $params);
        $params['verification'] = self::verificationKey($params);
        $success = true;
        $log_id = 0;
        Db::startTrans();
        try {
            $log_id = Db::name('UserCoin')->insertGetId($params);
            Db::name('UserBurse')->where('user_id', $user_id)->update(['coin' => ['dec', $amount], 'frozen_coin' => ['inc', $amount]]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $success = false;
        }
        return ['success' => $success, 'log' => $log_id];
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

    public static function outFreezeCoin($user_id, $log_id, $amount)
    {
        $success = true;
        $log_info = Db('UserCoin')->where(['id' => $log_id, 'is_freeze' => 1])->find();
        if($log_info['user_id'] == $user_id && abs($log_info['amount']) >= $amount)
        {
            $params['type']                 = 1;
            $params['is_freeze']            = 1;
            $params['trade_no']             = $log_info['trade_no'];
            $params                         = self::tradeParams($user_id, $log_info['reason'] . '(unfreeze)', $log_info['amount'], $params);
            $params_out_coin['type']        = 2;
            $params_out_coin['trade_no']    = $log_info['trade_no'];
            $params_out_coin                = self::tradeParams($user_id, $log_info['reason'], $amount, $params_out_coin);
            Db::startTrans();
            try {
                Db::name('UserCoin')->insert($params);
                Db::name('UserCoin')->insert($params_out_coin);
                Db::name('UserBurse')->where('user_id', $user_id)->update(['coin' => ['inc', abs($params['amount']) - $amount], 'frozen_coin' => ['dec', abs($params['amount'])]]);
                CoreValue::decrCoin($amount);
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

    private static function verificationKey($params = [])
    {
        $accept_data = array_fill_keys(['trade_no', 'user_id', 'type', 'amount', 'is_freeze', 'create_time'], 0);
        $params = array_merge($accept_data, array_intersect_key($params, $accept_data));
        ksort($params);
        return md5((Config('other.verification_key').'/'.implode('/', $params)));
    }

    public static function checkVerificationKey($params = [])
    {
        return $params['verification'] == self::verificationKey($params);
    }

    public static function buildTradeNumber($prefix = '', $in_user = 0, $out_user = 0, $time = 0)
    {
        if($time == 0)$time = time();
        return $prefix . str_pad($in_user, 10, 0, STR_PAD_LEFT) . str_pad($out_user, 10, 0, STR_PAD_LEFT) . $time . str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
    }
}