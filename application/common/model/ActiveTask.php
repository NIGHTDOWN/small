<?php
namespace app\common\model;
use think\Db;
use think\Cache;
use think\Model;

class ActiveTask extends Model
{
    //任务参数缓存key
    const ACTIVE_PARAM_CACHE_KEY='active_param_cache';

    //默认参数
    const DEFAULT_ACTIVE_PARAM=[
        'user_active_sign_in'=>1,
        'user_active_video_share'=>1,
        'user_active_video_play'=>1,
        'user_active_video_comment'=>1,
        'user_active_video_upload'=>1,
        'user_active_video_hot_refresh'=>1,
        'user_active_video_sort_refresh'=>1,
        'user_active_video_foward'=>1,
        'user_active_video_like'=>1,
        'user_active_video_dislike'=>1,
        'user_active_video_serach'=>1,
        'user_active_at_user'=>1,
        'user_active_follow_user'=>1,
    ];


    /**
     * 初始化参数缓存
     */
    public static function initActiveParamCache()
    {
        $column = array_keys(self::DEFAULT_ACTIVE_PARAM);
        $data=Db::name('core_value')->where('name','in',$column)->column('value','name');
        if(!$data){
            $data=self::DEFAULT_ACTIVE_PARAM;
        }
        $cache_key=self::ACTIVE_PARAM_CACHE_KEY;
        Cache::set($cache_key,$data);
    }

    /**
     * 获取参数
     */
    public static function getActiveParam()
    {
        $cache_key=self::ACTIVE_PARAM_CACHE_KEY;
        if (!Cache::has($cache_key)){
            self::initActiveParamCache();
        }
        return Cache::get($cache_key);
    }



}