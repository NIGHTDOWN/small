<?php
namespace app\common\model;
use think\Cache;
use think\Db;
use think\Model;

class CashWithdraw extends Model
{

    const MIN_WITHDRAW_MONEY = 1; // 最低提现金额一元

    const USER_WITHDRAW_NUM_CACHE_PRE = 'user_apply_withdraw_num_';

    const USER_WITHDRAW_COUNT_CACHE_PRE = 'user_apply_withdraw_count_';

    const USER_APPLY_WITHDRAW_LOCK_KEY_PRE = 'USER_APPLY_WITHDRAW_LOCK_';

    const USER_APPLY_WITHDRAW_LOCK_MAX_TIME = 60;

    private static $cacheRedis;

    const STATUS_TEXT  = [
        0 => '审核中',
        1=> '已打款' ,
        2 => '审核未通过' ,
        3 => '已到账' ,
        4 => '打款失败',
        5 => '审核通过',
        6 => '运营已审核',
        7 =>'财务审核不通过'
    ];

    const STATUS = [
        'AUDITING' => 0,
        'PAYING' => 1,
        'AUDIT_FAIL' => 2,
        'PAY_SUCCESS' => 3,
        'PAY_FAIL'  => 4,
        'AUDIT_SUCCESS' => 5,
        'OPERATIVE'=>6,
        'OPERATE_FAIL'=>7
    ];

    const PAYMENT_TEXT = [
        0 => '支付宝',
        1 => '微信',
    ];
    const ORDER_SN_PRE = 'XYX';

    const ORDER_SN_LENGTH = 20;

    protected $insert = ['order_sn','status','apply_time'];

    protected $append = [
        'payment_text',
        'apply_time_text','admin_time_text','pay_time_text',
        'status_text',
    ];


    public function getApplyTimeTextAttr($v,$data = [])
    {
        return $data['apply_time'] ? date('Y-m-d H:i',$data['apply_time']) : '';
    }

    public function getAdminTimeTextAttr($v,$data = [])
    {
        return $data['admin_time'] ? date('Y-m-d H:i',$data['admin_time']) : '';
    }

    public function getPayTimeTextAttr($v,$data = [])
    {
        return $data['pay_time'] ? date('Y-m-d H:i',$data['pay_time']) : '';
    }

    public function getPaymentTextAttr($v,$data = [])
    {
        return self::PAYMENT_TEXT[$data['payment']];
    }

    public function getStatusTextAttr($v,$data = [])
    {
        return self::STATUS_TEXT[$data['status']];
    }

    public static function createOrderSn()
    {
        while (1)
        {
            $order_sn = self::ORDER_SN_PRE . time();
            if (strlen($order_sn) < self::ORDER_SN_LENGTH) {
                $order_sn .= get_rand_string(self::ORDER_SN_LENGTH - strlen($order_sn),0,1);
            }
            if (!self::master()->where('order_sn','eq',$order_sn)->count()) {
                return $order_sn;
            }
        }
    }


    public function setOrderSnAttr()
    {
        return self::createOrderSn();
    }

    public function setApplyTimeAttr()
    {
        return time();
    }

    public function setStatusAttr(){
        return 0;
    }

    /**
     * 订单计算
     * @param $user_id
     * @param $stock_num
     * @param int $apply_time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function calculate($user_id,$apply_price,$apply_time=0)
    {
        // $price = Stock::getWithdrawPrice($apply_time);
        $amount =$apply_price;
        if ($amount < 1) goto end;
        $service_charge = self::getPlatformServiceCharge($amount);
        $tax = self::getTax($user_id ,$amount);
        $money = $amount - $service_charge - $tax;
        $money = round($money,2);
        end:
        return [
            'amount' => $amount,
            'money' => $money ?? 0,
            'service_charge' => $service_charge ?? 0,
            'tax' => $tax ?? 0,
        ];
    }

    /**
     * 平台服务费计算
     * @param int $amount
     * @return int
     */
    public static function getPlatformServiceCharge($amount)
    {
        return 0;
    }

    /**
     * 计算本次应扣税额
     * @param int $user_id
     * @param int $amount
     */
    public static function getTax($user_id = 0 ,$amount = 0)
    {
        $now = time();
        $map = [
            'apply_time' =>['egt' ,strtotime(date('Y-m-1'))],
            'apply_time' =>['elt' ,$now],
            'user_id'=>['eq' ,$user_id],
            'status'=>['IN' ,[self::STATUS['PAYING'],self::STATUS['PAY_SUCCESS']]]
        ];
        $rs = Db::name('cash_withdraw')
            ->master()
            ->where($map)
            ->field(['SUM(amount) AS amount','SUM(tax) AS tax'])
            ->select();
        $rs = array_pop($rs);
        $month_amount = $rs['amount'] ?? 0;
        $month_tax = $rs['tax'] ?? 0;
        $total_tax = self::tax($amount + $month_amount);
        return $total_tax - $month_tax;
    }

    public static function tax($amount_total)
    {
        if ($amount_total <= 800) return 0;
        $to_tax_amount_total = $amount_total < 4000 ?  ($amount_total - 800) : ($amount_total * 0.8);
        switch ($amount_total){
            case ($amount_total <= 20000) :
                $tax_total = $to_tax_amount_total * 0.2;
                break;
            case ($amount_total <= 50000) :
                $tax_total = $to_tax_amount_total * 0.3 - 2000;
                break;
            default:
                $tax_total = $to_tax_amount_total * 0.4 - 7000;
                break;
        }
        return $tax_total;
    }

    /**
     * 初始化用户当月申请金额
     * @param $user_id
     */
    public static function initUserApplyWithdrawNumCache($user_id)
    {
        $now = time();
        $cache_key = self::USER_WITHDRAW_NUM_CACHE_PRE.$user_id;
        $mon_start_time = strtotime(date('Y-m-d'));
        $cache_life_time = $mon_start_time + 3600 *24*30 -1 - $now;
        $num = Db::name('cash_withdraw')->master()->where([
            ['user_id','eq',$user_id],['status','in',[self::STATUS['PAYING'],self::STATUS['PAY_SUCCESS'],self::STATUS['AUDIT_SUCCESS']]]
        ])->whereTime('apply_time', 'month')->sum('apply_price');
        Cache::set($cache_key,$num,$cache_life_time);
        return (int)$num;
    }

    /**
     * 增加用户已申请提现金额
     * @param int $user_id
     * @param int $num
     * @return float|mixed
     */
    public static function incrUserApplyWithdrawNum($user_id = 0,$price = 0)
    {
        $now = time();
        $cache_key = self::USER_WITHDRAW_NUM_CACHE_PRE.$user_id;
        $mon_start_time = strtotime(date('Y-m-01'));
        $cache_life_time = $mon_start_time + 3600 * 24* 30   -1 - $now;
        if (!Cache::has($cache_key)) {
            $cache_num = self::initUserApplyWithdrawNumCache($user_id);
        }else{
            $cache_num = Cache::get($cache_key);
        }
        return Cache::set($cache_key,$cache_num + $price,$cache_life_time);

    }

    /**
     * 获得用户当月已申请提现金额
     * @param int $user_id
     * @param int $num
     */
    public static function getUserApplyWithdrawNum($user_id = 0)
    {
        $cache_key = self::USER_WITHDRAW_NUM_CACHE_PRE.$user_id;
        if (!Cache::has($cache_key)) {
            return self::initUserApplyWithdrawNumCache($user_id);
        }
        return (int)Cache::get($cache_key);

    }

    /**
     * 初始化用户提现次数
     * @param $user_id
     */
    public static function initUserApplyWithdrawCountCache($user_id)
    {
        $now = time();
        $cache_key = self::USER_WITHDRAW_COUNT_CACHE_PRE.$user_id;
        $mon_start_time = strtotime(date('Y-m-01'));
        $cache_life_time = $mon_start_time + 3600 * 24* 30   -1 - $now;
        $num = Db::name('cash_withdraw')->master()->where([
            ['user_id','eq',$user_id],['status','in',[self::STATUS['PAYING'],self::STATUS['PAY_SUCCESS'],self::STATUS['AUDIT_SUCCESS']]]
        ])->whereTime('apply_time', 'month')->count();

        Cache::set($cache_key,$num,$cache_life_time);

        return $num;
    }

    /**
     * 增加用户已提现次数
     * @param int $user_id
     * @param int $num
     * @return float|mixed
     */
    public static function incrUserApplyWithdrawCount($user_id = 0,$num = 1)
    {
        $now = time();
        $cache_key = self::USER_WITHDRAW_COUNT_CACHE_PRE.$user_id;
        $mon_start_time = strtotime(date('Y-m-01'));
        $cache_life_time = $mon_start_time + 3600 * 24* 30   -1 - $now;
        if (!Cache::has($cache_key)) {
            $cache_num = self::initUserApplyWithdrawCountCache($user_id);
        }
        else{
            $cache_num = Cache::get($cache_key);
        }
        return Cache::set($cache_key,$cache_num + $num,$cache_life_time);
    }

    /**
     * 获得用户当月已申请提现次数
     * @param int $user_id
     * @param int $num
     */
    public static function getUserApplyWithdrawCount($user_id = 0)
    {
        $cache_key = self::USER_WITHDRAW_COUNT_CACHE_PRE.$user_id;
        if (!Cache::has($cache_key)) {
            return self::initUserApplyWithdrawCountCache($user_id);
        }
        return Cache::get($cache_key);
    }

    /**
     * 增加操作锁
     * @param string $key
     * @param string $value
     */
    public static function userCacheLock($key, $value)
    {
        $key = self::cacheKey($key);
        $isLock = self::cacheRedis()->set($key, $value, ['ex' => self::USER_APPLY_WITHDRAW_LOCK_MAX_TIME, 'nx']);
        return $isLock ? 1 : 0;
    }

    /**
     * 解除操作锁
     * @param string $key
     * @param string $value
     */
    public static function userCacheUnlock($key,$value)
    {
        $key = self::cacheKey($key);
        if (self::cacheRedis()->get($key) == $value)
        {
            self::cacheRedis()->del($key);
        }
    }

    private static function cacheRedis()
    {
        if (self::$cacheRedis) return self::$cacheRedis;
        return self::$cacheRedis = Cache::handler();
    }

    private static function cacheKey($key)
    {
        return Config('cache.prefix') . $key;
    }


}