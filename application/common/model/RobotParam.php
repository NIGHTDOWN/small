<?php

namespace app\common\model;

use think\Model;
use think\Db;
use think\Cache;

/**
 * 会员积分日志模型
 */
class RobotParam Extends Model
{

    // 表名
    protected $name = 'robot_param';
    // 定义时间戳字段名
    // protected $createTime = 'createtime';
    protected $updateTime = 'update_time';

    // 机器人账户user_ids缓存key
    const ROBOT_USER_IDS_CACHE_KEY = 'robot_user_ids_cache';

    // 机器人参数缓存key
    const ROBOT_PARAM_CACHE_KEY = 'robot_param_cache';

    // 最小完成时限
    const MIN_FINISH_TIME = 60;

    // 最大完成时限
    const MAX_FINISH_TIME = 604800;

    // 默认机器人参数
    const DEFAULT_ROBOT_PARAM = [
        'user_put_video_event_param' => [
            'like_min' => 10,
            'like_max' => 20,
            'comment_min' => 2,
            'comment_max' => 4,
            'finish_time' => 18000,
        ],
        'user_action_event_param' => [
            'like_min' => 2,
            'like_max' => 5,
            'comment_min' => 1,
            'comment_max' => 3,
            'forward_min' => 1,
            'forward_max' => 5,
            'finish_time' => 3600,
        ],
        'user_long_time_inactivity_event_param' => [
            'like_min' => 5,
            'like_max' => 10,
            'comment_min' => 2,
            'comment_max' => 5,
            'forward_min' => 2,
            'forward_max' => 8,
            'finish_time' => 72000,
        ]
    ];

    /**
     * 初始化机器人参数缓存
     */
    public static function initRobotParamCache()
    {
        $data = Db::name('robot_param')->field(['user_put_video_event_param', 'user_action_event_param', 'user_long_time_inactivity_event_param'])->order('id', 'desc')->find();
        if ($data) {
            $data['user_put_video_event_param'] = unserialize($data['user_put_video_event_param']);
            $data['user_action_event_param'] = unserialize($data['user_action_event_param']);
            $data['user_long_time_inactivity_event_param'] = unserialize($data['user_long_time_inactivity_event_param']);
        }else{
            $data = self::DEFAULT_ROBOT_PARAM;
        }
        $cache_key = self::ROBOT_PARAM_CACHE_KEY;
        Cache::set($cache_key, $data);
    }

    /**
     * 获取机器人参数
     */
    public static function getRobotParam()
    {
        $cache_key = self::ROBOT_PARAM_CACHE_KEY;
        if (!Cache::has($cache_key)) {
            self::initRobotParamCache();
        }
        return Cache::get($cache_key);
    }
}
