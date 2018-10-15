<?php

namespace app\common\model;

use think\Cache;
use think\Model;
use wsj\WQiniu;

/**
 * 会员模型
 */
class User Extends Model
{
    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /** 状态 */
    const STATUS=[
        'delete'=>-1,
        'disable'=>0,
        'normal'=>1,
    ];

    /** 状态文本 */
    const STATUS_TEXT=[
        -1=>'删除',
        0=>'禁用',
        1=>'正常',
    ];

    /** 类型 */
    const TYPE=[
        'normal'=>1,
        'vip'=>2,
    ];

    /** 类型文本 */
    const TYPE_TEXT=[
        1 => '普通用户',
        2 => '大V用户',
    ];

    /** 用户缓存key前缀 */
    const USER_CACHE_KEY_PRE='api_cache_user_';

    /** 用户缓存有效时间 */
    const USER_CACHE_LIFE_TIME=2592000;

    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    public static function getStatusText($status){
        if (!in_array($status,self::STATUS)){
            return '';
        }
        return self::STATUS_TEXT[$status];
    }

    /**
     * 获取类型文本
     * @param int $type
     * @return string
     */
    public static function getTypeText($type)
    {
        if (!in_array($type,self::TYPE)){
            return '';
        }
        return self::TYPE_TEXT[$type];
    }

    /**
     * 获取头像链接
     * @param string $head_img
     * @return string
     */
    public static function getHeadImgUrl($head_img)
    {
        if ($head_img){
            return config('qiniu.headimg_bkt_protocol').'://'.config('qiniu.headimg_bkt_domain').'/'.$head_img;
        }
        return $head_img;
    }

    /**
     * 删除头像文件
     * @param $head_img
     * @return bool
     */
    public static function deleteHeadImgFile($head_img)
    {
        $bucket=config('qiniu.headimg_bkt');
        return WQiniu::delete($bucket,$head_img);
    }

    /**
     * 获取用户数据
     * @param $user_id
     * @param array $base_data
     * @return array
     */
    public static function getUserData($user_id,$base_data=[])
    {
        if (!$base_data){
            $base_data=self::get($user_id);
        }
        $fields=['id','user_name','nickname','head_img','mobile','create_time','single_mission','invitation_user_id','pay_password','type'];
        $user_data=[];
        foreach ($fields as $field){
            if (isset($base_data[$field])){
                if ($field=='pay_password'){
                    $user_data['pay_password']=$base_data['pay_password']?1:0;
                }elseif ($field=='type'){
                    $user_data['user_grade']=$base_data['type'];
                }else{
                    $user_data[$field]=$base_data[$field];
                }
            }
        }
        return $user_data;
    }

    /**
     * 更新用户缓存
     * @param int $user_id 用户ID
     * @param array $data
     * @return bool
     */
    public static function updateUserCache($user_id,$data=[])
    {
        $cache_key=self::USER_CACHE_KEY_PRE.$user_id;
        if (Cache::has($cache_key)){
            $cache_data=Cache::get($cache_key);
            if ($cache_data&&isset($cache_data['user'])&&isset($cache_data['access_token'])){
                $user_data =  self::getUserData($user_id,$data);
                foreach ($user_data as $key=>$value){
                    $cache_data['user'][$key]=$value;
                }
                return Cache::set($cache_key,$cache_data,self::USER_CACHE_LIFE_TIME);
            }
        }
        return false;
    }

    /**
     * 删除用户缓存
     * @param $user_id
     * @return bool
     */
    public static function deleteUserCache($user_id)
    {
        $cache_key=self::USER_CACHE_KEY_PRE.$user_id;
        return Cache::rm($cache_key);
    }
}
