<?php

namespace app\common\model;

use think\Cache;
use think\Model;
use wsj\ali\ElasticSearch;
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
    // 追加属性
    protected $append = [
        'url',
    ];

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

    /** 是否机器人 */
    const IS_ROBOT=[
        'yes'=>0,
        'no'=>1,
    ];

    const IS_ROBOT_TEXT=[
        0 => '否',
        1 => '是',
    ];

    /** 用户缓存key前缀 */
    const USER_CACHE_KEY_PRE='api_cache_user_';

    /** 用户缓存有效时间 */
    const USER_CACHE_LIFE_TIME=2592000;
    /**
     * 获取个人URL
     * @param   string  $value
     * @param   array   $data
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        return "/u/" . $data['id'];
    }

    /**
     * 获取头像
     * @param   string    $value
     * @param   array     $data
     * @return string
     */
    public function getAvatarAttr($value, $data)
    {
        return $value ? $value : '/assets/img/avatar.png';
    }

    /**
     * 获取会员的组别
     */
    public function getGroupAttr($value, $data)
    {
        return UserGroup::get($data['group_id']);
    }

    /**
     * 获取验证字段数组值
     * @param   string    $value
     * @param   array     $data
     * @return  object
     */
    public function getVerificationAttr($value, $data)
    {
        $value = array_filter((array) json_decode($value, TRUE));
        $value = array_merge(['email' => 0, 'mobile' => 0], $value);
        return (object) $value;
    }

    /**
     * 设置验证字段
     * @param mixed $value
     * @return string
     */
    public function setVerificationAttr($value)
    {
        $value = is_object($value) || is_array($value) ? json_encode($value) : $value;
        return $value;
    }

    /**
     * 变更会员积分
     * @param int $score    积分
     * @param int $user_id  会员ID
     * @param string $memo  备注
     */
    public static function score($score, $user_id, $memo)
    {
        $user = self::get($user_id);
        if ($user)
        {
            $before = $user->score;
            $after = $user->score + $score;
            $level = self::nextlevel($after);
            //更新会员信息
            $user->save(['score' => $after, 'level' => $level]);
            //写入日志
            ScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo]);
        }
    }

    /**
     * 根据积分获取等级
     * @param int $score 积分
     * @return int
     */
    public static function nextlevel($score = 0)
    {
        $lv = array(1 => 0, 2 => 30, 3 => 100, 4 => 500, 5 => 1000, 6 => 2000, 7 => 3000, 8 => 5000, 9 => 8000, 10 => 10000);
        $level = 1;
        foreach ($lv as $key => $value)
        {
            if ($score >= $value)
            {
                $level = $key;
            }
        }
        return $level;
    }

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
     * 删除头像文件
     * @param $head_img
     * @return bool
     */
    public static function deleteHeadImgFile($head_img)
    {
        $url=config('site.avatar_url');
        $key=str_replace("$url/",'',$head_img);
        $bucket=config('site.avatar_bucket');
        return WQiniu::delete($bucket,$key);
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
        $fields=['id','user_name','nickname','head_img','mobile','create_time','single_mission','invitation_user_id','pay_password','group_id'];
        $user_data=[];
        foreach ($fields as $field){
            if (isset($base_data[$field])){
                if ($field=='pay_password'){
                    $user_data['pay_password']=$base_data['pay_password']?1:0;
                }elseif ($field=='group_id'){
                    $user_data['user_grade']=$base_data['group_id'];
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
     * 是否存在用户缓存
     * @param $user_id
     * @return bool
     */
    public static function existUserCache($user_id)
    {
        $cache_key=self::USER_CACHE_KEY_PRE.$user_id;
        return Cache::has($cache_key);
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

    /**
     * 增加es
     * @param $user_id
     * @param $nickname
     * @return bool|int
     */
    public static function addEs($user_id,$nickname)
    {
        $data=[
            'id'=>$user_id,
            'nickname'=>$nickname,
        ];
        $elastic_search = new ElasticSearch();
        return $elastic_search->name('user')->insert($data);
    }

    /**
     * 更新es
     * @param $user_id
     * @param $data
     * @return int|bool
     */
    public static function updateEs($user_id,$data)
    {
        $elastic_search = new ElasticSearch();
        return $elastic_search->name('user')->update($user_id,$data);
    }

    /**
     * 删除es
     * @param $user_id
     * @return bool|int
     */
    public static function delEs($user_id)
    {
        $elastic_search = new ElasticSearch();
        return $elastic_search->name('user')->delete($user_id);
    }
}
