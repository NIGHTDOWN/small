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
        'coin_to_price'         =>'coin_to_price',
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

    public static function getCoinValue($name)
    {
        $cache_key = config('site.cache_prefix') . self::CACHE_COIN_KEYS[$name];
        $val = self::getCacheInstance()->get($cache_key);
        if($val == null)
        {
            if($name == 'today_coin_drop')
            {
                $val = 0;
            }elseif($name == 'member_total')
            {
                $val = Db::name('User')->count('*');
                self::getCacheInstance()->setex($cache_key, 600, $val);
            }else{
                $val = (int) Db::name('core_value')->where('name','eq' ,'coin')->value('value');
                self::getCacheInstance()->set($cache_key, $val);
            }
        }
        return $val;
    }

     /**
     * 这是金币价值，1元=xxxx金币
     */
    public static function setCoinToPrice($value)
    {
        $name='coin_to_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        $count=Db::name('core_value')->where('name',$name)->count();
        if ($count){
            Db::name('core_value')->where('name',$name)->update(['value'=>$value]);
        }else{
            Db::name('core_value')->insert(['name'=>$name,'value'=>$value]);
        }
        Cache::set($cache_key,$value);
    }

    /**
     * 获取金币价值
     */
    public static function getCoinToPrice()
    {
        $name='coin_to_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        if (!Cache::has($cache_key)){
            $value=Db::name('core_value')->where('name',$name)->value('value');
            if ($value===null){
                $value=self::DEFAULT_COIN_TO_PRICE;
            }
            Cache::set($cache_key,$value);
        }
        return (int)Cache::get($cache_key);
    }

    /**
     * 设置首次提现最低金额
     */
    public static function setFirstCoinToPrice($value)
    {
        $name='first_coin_to_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        $count=Db::name('core_value')->where('name',$name)->count();
        if ($count){
            Db::name('core_value')->where('name',$name)->update(['value'=>$value]);
        }else{
            Db::name('core_value')->insert(['name'=>$name,'value'=>$value]);
        }
        Cache::set($cache_key,$value);
    }

     /**
     * 获取首次提现最低金额
     */
    public static function getFirstCoinToPrice()
    {
        $name='first_coin_to_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        if (!Cache::has($cache_key)){
            $value=Db::name('core_value')->where('name',$name)->value('value');
            Cache::set($cache_key,$value);
        }
        return (int)Cache::get($cache_key);
    }

    /**
     *设置后续提现最低金额
     */
    public static function setNormalCoinToPrice($value)
    {
        $name='normal_coin_to_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        $count=Db::name('core_value')->where('name',$name)->count();
        if ($count){
            Db::name('core_value')->where('name',$name)->update(['value'=>$value]);
        }else{
            Db::name('core_value')->insert(['name'=>$name,'value'=>$value]);
        }
        Cache::set($cache_key,$value);
    }

    /**
     * 获取后续提现最低金额
     */
    public static function getNormalCoinToPrice()
    {
        $name='normal_coin_to_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        if (!Cache::has($cache_key)){
            $value=Db::name('core_value')->where('name',$name)->value('value');
            Cache::set($cache_key,$value);
        }
        return (int)Cache::get($cache_key);
    }

    /**
     * 设置当月可提现总额
     */
    public static function setTotalCoinToPrice($value)
    {
        $name='total_coin_to_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        $count=Db::name('core_value')->where('name',$name)->count();
        if ($count){
            Db::name('core_value')->where('name',$name)->update(['value'=>$value]);
        }else{
            Db::name('core_value')->insert(['name'=>$name,'value'=>$value]);
        }
        Cache::set($cache_key,$value);
    }

    /**
     * 获取当月可提现总额
     */
    public static function getTotalCoinToPrice()
    {
        $name='total_coin_to_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        if (!Cache::has($cache_key)){
            $value=Db::name('core_value')->where('name',$name)->value('value');
            Cache::set($cache_key,$value);
        }
        return (int)Cache::get($cache_key);
    }

    /**
     * 设置当月最大提现次数
     */
    public static function setTotalCoinToPriceNum($value)
    {
        $name='total_coin_to_price_num';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        $count=Db::name('core_value')->where('name',$name)->count();
        if ($count){
            Db::name('core_value')->where('name',$name)->update(['value'=>$value]);
        }else{
            Db::name('core_value')->insert(['name'=>$name,'value'=>$value]);
        }
        Cache::set($cache_key,$value);
    }

     /**
     * 获取当月最大提现次数
     */
    public static function getTotalCoinToPriceNum()
    {
        $name='total_coin_to_price_num';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        if (!Cache::has($cache_key)){
            $value=Db::name('core_value')->where('name',$name)->value('value');
            Cache::set($cache_key,$value);
        }
        return (int)Cache::get($cache_key);
    }

    /**
     * 设置需绑定身份证提现金额
     */
    public static function setNeedIdCardPrice($value)
    {
        $name='need_id_card_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        $count=Db::name('core_value')->where('name',$name)->count();
        if ($count){
            Db::name('core_value')->where('name',$name)->update(['value'=>$value]);
        }else{
            Db::name('core_value')->insert(['name'=>$name,'value'=>$value]);
        }
        Cache::set($cache_key,$value);
    }

    /**
     * 获取需绑定身份证提现金额
     */
    public static function getNeedIdCardPrice()
    {
        $name='need_id_card_price';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        if (!Cache::has($cache_key)){
            $value=Db::name('core_value')->where('name',$name)->value('value');
            Cache::set($cache_key,$value);
        }
        return (int)Cache::get($cache_key);
    }

    /**
     * 设置延迟到账时间
     */
    public static function setDelayPay($value)
    {
        $name='delay_pay';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        $count=Db::name('core_value')->where('name',$name)->count();
        if ($count){
            Db::name('core_value')->where('name',$name)->update(['value'=>$value]);
        }else{
            Db::name('core_value')->insert(['name'=>$name,'value'=>$value]);
        }
        Cache::set($cache_key,$value);
    }

    /**
     * 获取延迟到账时间
     */
    public static function getDelayPay()
    {
        $name='delay_pay';
        $cache_key=self::CACHE_COIN_KEYS[$name];
        if (!Cache::has($cache_key)){
            $value=Db::name('core_value')->where('name',$name)->value('value');
            Cache::set($cache_key,$value);
        }
        return (int)Cache::get($cache_key);
    }

}
