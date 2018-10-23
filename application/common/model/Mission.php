<?php
/**
 * Created by PhpStorm.
 * User: Flyn
 * Date: 2018/6/25
 * Time: 13:56
 */

namespace app\common\model;

use think\Cache;
use think\Model;

class Mission extends Model
{
    const MISSION_CACHE                         = 'mission_cache';
    const MISSION_CACHE_LOCK_PREFIX             = 'mission_cache_lock_';
    const USER_DAILY_MISSION_PREFIX             = 'mission_user_daily_';
    const USER_DAILY_BONUS                      = 'daily_bonus';
    const USER_DAILY_BONUS_KEY                  = 'daily_bonus';
    /** @var  \Redis */
    private static $cacheRedis;

    public static function runMission($name, $user_id,$cs='')
    {
        $win_coin = ['coin' => 0, 'title' => ''];
        if($name != '')
        {
            $mission_setting = self::getSetting($name);
            if($mission_setting)
            {
                if(method_exists('app\common\model\Mission', $name . '_process'))
                {
                    $win_coin = call_user_func_array(array('self', $name . '_process'), [$user_id, $mission_setting]);
                }else{
                    switch ($mission_setting['repeat_type'])
                    {
                        case 1:
                            $win_coin = self::singleMission($name, $user_id, $mission_setting);
                            break;
                        case 2:
                            $win_coin = self::dailyMission($name, $user_id, $mission_setting);
                            break;
                        case 3:
                            $win_coin = self::repeatMission($name, $user_id, $mission_setting);
                            break;
                    }
                }
                if($win_coin['coin'] > 0)
                {
                    self::addBonus($user_id, $win_coin['title'], $win_coin['coin'], ['mission_tag'=>$name,'cs'=>$cs]);
                }
            }
        }
        return $win_coin['coin'];
    }

    private static function addBonus($user_id, $reason, $amount, $params = [])
    {
        UserCoin::inCoin($user_id, $reason, $amount, $params);
        $day_time = strtotime(date('Y-m-d 00:00:00', time())) + 86400;
        $cache_key = self::cacheKey(self::USER_DAILY_MISSION_PREFIX . $user_id);
        $daily_bonus = self::cacheRedis()->zincrby($cache_key, $amount, self::USER_DAILY_BONUS);
        if($daily_bonus == $amount)
        {
            self::cacheRedis()->expireat($cache_key, $day_time);
        }
        $sub_mission = 'sub_' . $params['mission_tag'];
        $sub_mission_setting = self::getSetting($sub_mission);
        if($sub_mission_setting)
        {
            $user_info = User::get($user_id);
            if($user_info->invitation_user_id != 0) {
                self::subMission($sub_mission, $user_info->invitation_user_id, $sub_mission_setting);
            }
        }
    }

    private static function subMission($name, $user_id, $setting)
    {

        if(method_exists('app\common\model\Mission', $name . '_process'))
        {
            $win_coin = call_user_func_array(array('self', $name . '_process'), [$user_id, $setting]);
        }else{
            switch ($setting['repeat_type']) {
                case 1:
                    $win_coin = self::singleMission($name, $user_id, $setting);
                    break;
                case 2:
                    $win_coin = self::dailyMission($name, $user_id, $setting);
                    break;
                case 3:
                    $win_coin = self::repeatMission($name, $user_id, $setting);
                    break;
            }
        }
        if ($win_coin['coin'] > 0) {
            self::addBonus($user_id, $win_coin['title'], $win_coin['coin'], ['mission_tag' => $name]);
        }
    }

    public static function getBonus($user_id)
    {
        $cache_key = self::cacheKey(self::USER_DAILY_MISSION_PREFIX . $user_id);
        $bonus_total = self::cacheRedis()->zscore($cache_key, self::USER_DAILY_BONUS);
        if(!$bonus_total)
        {
            $bonus_total = 0;
        }
        return $bonus_total;
    }

    private static function sign_in_process($user_id, $setting)
    {
        $name = 'sign_in';
        $can_sign_in = true;
        $lock_key = mt_rand(0, 99999999);
        self::userCacheLock($user_id, $lock_key);
        $time = time();
        $user_info = Cache::get(User::USER_CACHE_KEY_PRE.$user_id);
        $get_coin = ['coin' => 0, 'title' => ''];
        if(isset($user_info['user']['single_mission']) && !empty($user_info['user']['single_mission']))
        {
            $user_single_mission = json_decode($user_info['user']['single_mission'], true);
            if(isset($user_single_mission[$name]['time']))
            {
                if(date('Ymd', $user_single_mission[$name]['time']) == date('Ymd', $time))
                {
                    $can_sign_in = false;
                }elseif(strtotime(date('Ymd 00:00:00', $time)) - strtotime(date('Ymd 00:00:00', $user_single_mission[$name]['time'])) != 86400){
                    $user_single_mission[$name]['count'] = 0;
                }
            }else{
                $user_single_mission[$name]['count'] = 0;
            }
        }else{
            $user_single_mission = [];
            $user_single_mission[$name]['count'] = 0;
        }
        if($can_sign_in)
        {
            $user_single_mission[$name]['count']++;
            $user_single_mission[$name]['time'] = $time;

            if($setting['max_quantity'] >= $user_single_mission[$name]['count'])
            {
                if(isset($setting['mission_list'][$user_single_mission[$name]['count']]))
                {
                    $get_coin = self::calculateCoin($setting['mission_list'][$user_single_mission[$name]['count']]);
                }
            }else{
                if(isset($setting['mission_list'][0]))
                {
                    $get_coin = self::calculateCoin($setting['mission_list'][0]);
                }
            }
            User::update([
                'id' => $user_id,
                'single_mission' => json_encode($user_single_mission)
            ]);
            User::updateUserCache($user_id);
        }
        self::userCacheUnlock($user_id, $lock_key);
        return $get_coin;
    }

    /**
     * 分享视频任务
     * @param $user_id
     * @param $setting
     * @return array
     */
    private static function share_video_reads_process($user_id, $setting)
    {
        $share_time = input('time/d',0);  //分享视频时间
        $video_id = input('id/d');  //分享视频id
        $get_coin = ['coin' => 0, 'title' => ''];
        //缺少参数,无奖励
        if(!$share_time || !$video_id){
            return $get_coin;
        }
        //分享时间不是当天的,无奖励
        $time = time();
        if (date('Ymd', $share_time) != date('Ymd', $time)){
            return $get_coin;
        }
        $cache_key = self::cacheKey(self::USER_DAILY_MISSION_PREFIX . $user_id);
        $key_exist=self::cacheRedis()->exists($cache_key);
        $name = 'share_video_reads';
        $mission_fin = $name.'_fin';   //任务完成标志
        if (self::cacheRedis()->zscore($cache_key, $mission_fin)){
            return $get_coin;
        }
        //任务缓存时间,晚24点失效
        $day_time = strtotime(date('Y-m-d 00:00:00', $time)) + 86400;
        $mission_bonus_coin_total_key = $name.'_bonus_coin_total';   //获得奖金总计
        $video_read_count_key = $name . '_' . $video_id;   //某个视频被查看次数记录
        //获取已获得奖金总计
        $mission_bonus_coin_total = self::cacheRedis()->zscore($cache_key, $mission_bonus_coin_total_key);
        //视频查看次数+1
        $video_read_count=self::cacheRedis()->zincrby($cache_key, 1, $video_read_count_key);
        if ($video_read_count==$setting['max_quantity']){
            //达到奖励条件
            //计算获得金币
            $get_coin = self::calculateCoin($setting['mission_list'][$setting['max_quantity']],$mission_bonus_coin_total);
            //任务总获取金币数增加
            $mission_bonus_coin_total=self::cacheRedis()->zincrby($cache_key, $get_coin['coin'], $mission_bonus_coin_total_key);
            //如果有设置奖励上限,获得大于等于上限时,设置任务已完成
            if ($setting['mission_list'][$setting['max_quantity']]['bonus_limit']&&$mission_bonus_coin_total>=$setting['mission_list'][$setting['max_quantity']]['bonus_limit']){
                self::cacheRedis()->zincrby($cache_key, 1, $mission_fin);
            }
        }
        if (!$key_exist){
            //设置过期时间
            self::cacheRedis()->expireat($cache_key,$day_time);
        }
        return $get_coin;
    }

    /**
     * 关注用户任务
     * @param $user_id
     * @param $setting
     * @return array
     */
    private static function follow_user_process($user_id,$setting)
    {
        $name='follow_user';
        $lock_key = mt_rand(0, 99999999);
        self::userCacheLock($user_id, $lock_key);
        $user_info = Cache::get(User::USER_CACHE_KEY_PRE.$user_id);
        $get_coin = ['coin' => 0, 'title' => ''];
        if(isset($user_info['user']['single_mission']) && !empty($user_info['user']['single_mission']))
        {
            $user_single_mission = json_decode($user_info['user']['single_mission'], true);
        }else{
            $user_single_mission = [];
        }
        if(!isset($user_single_mission[$name]))
        {
            $user_single_mission[$name] = 0;
        }
        if($setting['max_quantity'] > $user_single_mission[$name])
        {
            $user_single_mission[$name]++;
            if(isset($setting['mission_list'][$setting['max_quantity']]))
            {
                $get_coin = self::calculateCoin($setting['mission_list'][$setting['max_quantity']]);
            }
            User::update([
                'id' => $user_id,
                'single_mission' => json_encode($user_single_mission)
            ]);
            User::updateUserCache($user_id);
        }
        self::userCacheUnlock($user_id, $lock_key);
        return $get_coin;
    }

    private static function singleMission($name, $user_id, $setting)
    {
        $lock_key = mt_rand(0, 99999999);
        self::userCacheLock($user_id, $lock_key);
        $user_info = Cache::get(User::USER_CACHE_KEY_PRE.$user_id);
        $get_coin = ['coin' => 0, 'title' => ''];
        if(isset($user_info['user']['single_mission']) && !empty($user_info['user']['single_mission']))
        {
            $user_single_mission = json_decode($user_info['user']['single_mission'], true);
        }else{
            $user_single_mission = [];
        }
        if(!isset($user_single_mission[$name]))
        {
            $user_single_mission[$name] = 0;
        }

        if($setting['max_quantity'] > $user_single_mission[$name])
        {
            $user_single_mission[$name]++;
            if(isset($setting['mission_list'][$user_single_mission[$name]]))
            {
                $get_coin = self::calculateCoin($setting['mission_list'][$user_single_mission[$name]]);
            }
            User::update([
                'id' => $user_id,
                'single_mission' => json_encode($user_single_mission)
            ]);
            User::updateUserCache($user_id);
        }
        self::userCacheUnlock($user_id, $lock_key);
        return $get_coin;
    }

    private static function dailyMission($name, $user_id, $setting)
    {
        $get_coin = ['coin' => 0, 'title' => ''];
        $time = time();
        $day_time = strtotime(date('Y-m-d 00:00:00', $time)) + 86400;
        $key_exist = false;
        $mission_fin = $name . '_fin';
        $mission_extra = $name . '_bonus_0';
        $cache_key = self::cacheKey(self::USER_DAILY_MISSION_PREFIX . $user_id);
        if(!self::cacheRedis()->zscore($cache_key, $mission_fin)) {
            if (self::cacheRedis()->exists($cache_key)) {
                $key_exist = true;
                $bonus_count = self::cacheRedis()->zscore($cache_key, $name);
                if (!$bonus_count) {
                    $bonus_count = 0;
                }
            } else {
                $bonus_count = 0;
            }
            if ($bonus_count < $setting['max_quantity']) {
                $bonus_count = self::cacheRedis()->zincrby($cache_key, 1, $name);
                if (isset($setting['mission_list'][$bonus_count])) {
                    $get_coin = self::calculateCoin($setting['mission_list'][$bonus_count]);
                }
                if (!$key_exist) self::cacheRedis()->expireat($cache_key, $day_time);
            } elseif (isset($setting['mission_list'][0])) {
                if ($setting['mission_list'][0]['bonus_limit'] > 0) {
                    $mission_extra_count = self::cacheRedis()->zscore($cache_key, $mission_extra);
                    if (empty($mission_extra_count)) {
                        $mission_extra_count = 0;
                    }
                }
                $get_coin = self::calculateCoin($setting['mission_list'][0], $mission_extra_count??0);
                if ($get_coin['coin'] > 0) {
                    $mission_extra_count = self::cacheRedis()->zincrby($cache_key, $get_coin['coin'], $mission_extra);
                    if ($mission_extra_count >= $setting['mission_list'][0]['bonus_limit']) {
                        self::cacheRedis()->zadd($cache_key, 1, $mission_fin);
                    }
                    if (!$key_exist) self::cacheRedis()->expireat($cache_key, $day_time);
                }
            } else {
                self::cacheRedis()->zadd($cache_key, 1, $mission_fin);
                if (!$key_exist) self::cacheRedis()->expireat($cache_key, $day_time);
            }
        }
        return $get_coin;
    }

    private static function repeatMission($name, $user_id, $setting)
    {
        $get_coin = ['coin' => 0, 'title' => ''];
        $bonus_limit = $setting['mission_list'][$setting['max_quantity']]['bonus_limit'];
        $quantity_condition = $setting['max_quantity'];
        $time = time();
        $day_time = strtotime(date('Y-m-d 00:00:00', $time)) + 86400;
        $key_exist = false;
        $cache_key = self::cacheKey(self::USER_DAILY_MISSION_PREFIX . $user_id);
        $mission_extra = $name . '_bonus';
        if (self::cacheRedis()->exists($cache_key)) {
            $key_exist = true;
        }
        $mission_count = self::cacheRedis()->zincrby($cache_key, 1, $name);
        if(fmod($mission_count, $quantity_condition) == 0)
        {
            if($bonus_limit > 0)
            {
                $bonus_count = self::cacheRedis()->zscore($cache_key, $mission_extra);
                if(empty($bonus_count))
                {
                    $bonus_count = 0;
                }
                if($bonus_count < $bonus_limit)
                {
                    $get_coin = self::calculateCoin($setting['mission_list'][$setting['max_quantity']], $bonus_count);
                    if ($get_coin['coin'] > 0) {
                        $mission_extra_count = self::cacheRedis()->zincrby($cache_key, $get_coin['coin'], $mission_extra);
                        if ($mission_extra_count > $bonus_limit) {
                            $get_coin['coin'] = 0;
                        }
                    }
                }
            }else{
                $get_coin = self::calculateCoin($setting['mission_list'][$setting['max_quantity']]);
            }
        }
        if (!$key_exist) self::cacheRedis()->expireat($cache_key, $day_time);
        return $get_coin;
    }

    private static function calculateCoin($setting, $current_count = 0)
    {
        $bonus_limit = $setting['bonus_limit'];
        $title = $setting['title'];
        unset($setting['bonus_limit']);
        unset($setting['title']);
        $rand = mt_rand(1, 100);
        $coin = 0;
        foreach ($setting as $key => $value)
        {
            if($rand <= $key)
            {
                $coin = mt_rand($value[0], $value[1]);
                break;
            }
        }
        if($bonus_limit > 0)
        {
            $coin = min($coin, $bonus_limit - $current_count);
        }
        return ['coin' =>$coin, 'title' => $title];
    }

    public static function buildMissionConfig()
    {
        $data = self::all(['status' => 1]);
        $mission_array = [];
        if($data)
        {
            foreach ($data as $key => $value)
            {
                $mission_array[$value['mission_tag']]['repeat_type'] = $value['repeat_type'];
                $mission_array[$value['mission_tag']]['max_quantity'] = (isset($mission_array[$value['mission_tag']]['max_quantity'])) ? max($mission_array[$value['mission_tag']]['max_quantity'], $value['quantity_condition']) : $value['quantity_condition'];
                $bonus_array = explode('|', $value['bonus_setting']);
                $probability = 0;
                foreach ($bonus_array as $b_key => $b_value)
                {
                    $b_value = preg_split('/[\*\-]/', $b_value);
                    $probability += $b_value[0];
                    $mission_array[$value['mission_tag']]['mission_list'][$value['quantity_condition']][$probability] = [$b_value[1], $b_value[2]];
                }
                ksort($mission_array[$value['mission_tag']]['mission_list'][$value['quantity_condition']]);
                $mission_array[$value['mission_tag']]['mission_list'][$value['quantity_condition']]['bonus_limit'] = $value['bonus_limit'];
                $mission_array[$value['mission_tag']]['mission_list'][$value['quantity_condition']]['title'] = $value['title'];
            }
        }
        Cache::set(self::MISSION_CACHE, $mission_array);
        return $mission_array;
    }

    public static function refreshMissionConfig()
    {
        return Cache::rm(self::MISSION_CACHE);
    }

    private static function getSetting($name = '')
    {
        $mission_setting = Cache::get(self::MISSION_CACHE);
        if(!$mission_setting)
        {
            $mission_setting = self::buildMissionConfig();
        }
        if(isset($mission_setting[$name]))
        {
            return $mission_setting[$name];
        }
        return false;
    }

    private static function userCacheLock($user_id, $value)
    {
        do {
            $timeout = 10;
            $key = self::cacheKey(self::MISSION_CACHE_LOCK_PREFIX . $user_id);
            $isLock = self::cacheRedis()->set($key, $value, ['ex' => $timeout, 'nx']);
            if ($isLock) {
                continue;
            } else {
                usleep(50000);
            }
        } while(!$isLock);
    }

    private static function userCacheUnlock($user_id, $value)
    {
        $key = self::cacheKey(self::MISSION_CACHE_LOCK_PREFIX . $user_id);
        if (self::cacheRedis()->get($key) == $value)
        {
            self::cacheRedis()->del($key);
        }
    }

    private static function cacheRedis()
    {
        if (self::$cacheRedis) return self::$cacheRedis;
        $cache=Cache::init();
        return self::$cacheRedis = $cache->handler();
    }

    private static function cacheKey($key)
    {
        return get_cache_prefix() . $key;
    }
}