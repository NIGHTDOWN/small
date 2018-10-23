<?php
namespace app\common\model;

use think\Db;
use think\Cache;
use think\Model;

class CoreValue extends Model
{
    const CACHE_COIN_KEYS = [
        'cache_name'            => 'coin_data',
        'coin_total'            => 'coin_total',
        'today_coin_drop'       => 'today_coin_drop',
        'member_total'          => 'member_total',
        'first_coin_to_price'=>'first_coin_to_price',//首次提现最低金额
        'normal_coin_to_price'=>'normal_coin_to_price',//后续提现最低金额
        'total_coin_to_price'=>'total_coin_to_price',//当月最大提现金额
        'total_coin_to_price_num'=>'total_coin_to_price_num',//当月最大提现次数
        'need_id_card_price'    =>'need_id_card_price',//提现xx元需要上传身份证
        'delay_pay'             =>'delay_pay'
    ];

    /** @var  \Redis */
    public static $cacheInstance;

    public static function getCacheInstance()
    {
        if (self::$cacheInstance) return self::$cacheInstance;
        $cache=Cache::init();
        return self::$cacheInstance = $cache->handler();
    }

    /**
     * 增加平台金币总量
     * @param $val
     * @return mixed
     */
    public static function incrCoin($val)
    {
        self::changeCoin($val, 'inc');
        return Db::name('core_value')->where('name', 'in', 'coin,raise_coin_total')->setInc('value' ,$val);
    }

    /**
     * 减少平台金币总量
     * @param $val
     * @return mixed
     */
    public static function decrCoin($val)
    {
        self::changeCoin($val, 'dec');
        return Db::name('core_value')->where('name','eq' ,'coin')->setDec('value' ,$val);
    }

    private static function changeCoin($val, $type = 'inc')
    {
        $coin_total_key = get_cache_prefix() . self::CACHE_COIN_KEYS['coin_total'];
        if($type == 'inc')
        {
            $coin_total = self::getCacheInstance()->incrby($coin_total_key, $val);
        }else{
            $today_coin_drop_key = get_cache_prefix() . self::CACHE_COIN_KEYS['today_coin_drop'];
            $coin_total = self::getCacheInstance()->decrby($coin_total_key, $val);
            $today_coin_drop = self::getCacheInstance()->incrby($today_coin_drop_key, $val);
            if($today_coin_drop == $val)
            {
                $day_time = strtotime(date('Y-m-d 00:00:00', time())) + 86400;
                self::getCacheInstance()->expireat($today_coin_drop_key, $day_time);
            }
        }
        $val = ($type == 'inc' ? $val : 0 - $val);
        if($coin_total == $val)
        {
            self::getCacheInstance()->set($coin_total_key, Db::name('core_value')->where('name','eq' ,'coin')->value('value') + $val);
        }
    }
}
