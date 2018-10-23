<?php
namespace app\common\model;
use think\Db;
use think\Cache;
use think\Model;

class Robot extends Model
{
    //机器人账户user_ids缓存key
    const ROBOT_USER_IDS_CACHE_KEY='robot_user_ids_cache';

    //机器人参数缓存key
    const ROBOT_PARAM_CACHE_KEY='robot_param_cache';

    //最小完成时限
    const MIN_FINISH_TIME=60;

    //最大完成时限
    const MAX_FINISH_TIME=604800;

    //默认机器人参数
    const DEFAULT_ROBOT_PARAM=[
        'user_put_video_event_param'=>[
            'like_min'=>10,
            'like_max'=>20,
            'comment_min'=>2,
            'comment_max'=>4,
            'finish_time'=>18000,
        ],
        'user_action_event_param'=>[
            'like_min'=>2,
            'like_max'=>5,
            'comment_min'=>1,
            'comment_max'=>3,
            'forward_min'=>1,
            'forward_max'=>5,
            'finish_time'=>3600,
        ],
        'user_long_time_inactivity_event_param'=>[
            'like_min'=>5,
            'like_max'=>10,
            'comment_min'=>2,
            'comment_max'=>5,
            'forward_min'=>2,
            'forward_max'=>8,
            'finish_time'=>72000,
        ]
    ];

    /**
     * 初始化机器人账户用户ids缓存
     */
    public static function initRobotUserIdsCache()
    {
        $cache_key=self::ROBOT_USER_IDS_CACHE_KEY;
        if(Cache::has($cache_key)){
            Cache::rm($cache_key);
        }
        $redis=Cache::init()->handler();
        Db::name('user')
            ->field(['id'])
            ->where(['is_robot'=>['eq',1]])
            ->chunk(100,function ($robot_users) use ($redis,$cache_key){
                foreach ($robot_users as $robot_user){
                    $redis->sAdd(get_cache_prefix().$cache_key,$robot_user['id']);
                }
            });
    }

    /**
     * 增加一个机器人user_id到缓存
     * @param $robot_user_id
     */
    public static function addIdToRobotUserIdsCache($robot_user_id)
    {
        $cache_key=self::ROBOT_USER_IDS_CACHE_KEY;
        $redis=Cache::init()->handler();
        $redis->sAdd(get_cache_prefix().$cache_key,$robot_user_id);
    }

    /**
     * 从缓存删除一个机器人user_id
     * @param $robot_user_id
     */
    public static function delIdFromRobotUserIdsCache($robot_user_id)
    {
        $cache_key=self::ROBOT_USER_IDS_CACHE_KEY;
        $redis=Cache::init()->handler();
        $redis->sRem(get_cache_prefix().$cache_key,$robot_user_id);
    }

    /**
     * 随机获取多个机器人user_id
     * @param int $number  要取的数量
     * @return array
     */
    public static function randomGetMultiRobotUserId($number)
    {
        $cache_key=self::ROBOT_USER_IDS_CACHE_KEY;
        if (!Cache::has($cache_key)){
            self::initRobotUserIdsCache();
        }
        $redis=Cache::init()->handler();
        return $redis->sRandMember(get_cache_prefix().$cache_key,$number);
    }

    /**
     * 用户是否为机器人
     * @param $user_id
     */
    public static function isRobot($user_id)
    {
        $cache_key=self::ROBOT_USER_IDS_CACHE_KEY;
        if (!Cache::has($cache_key)){
            self::initRobotUserIdsCache();
        }
        $redis=Cache::init()->handler();
        return $redis->sIsMember(get_cache_prefix().$cache_key,$user_id);
    }

    /**
     * 初始化机器人参数缓存
     */
    public static function initRobotParamCache()
    {
        $data=Db::name('robot_param')->field(['user_put_video_event_param','user_action_event_param','user_long_time_inactivity_event_param'])->order('id','desc')->find();
        if ($data){
            $data['user_put_video_event_param']=unserialize($data['user_put_video_event_param']);
            $data['user_action_event_param']=unserialize($data['user_action_event_param']);
            $data['user_long_time_inactivity_event_param']=unserialize($data['user_long_time_inactivity_event_param']);
        }else{
            $data=self::DEFAULT_ROBOT_PARAM;
        }
        $cache_key=self::ROBOT_PARAM_CACHE_KEY;
        Cache::set($cache_key,$data);
    }

    /**
     * 获取机器人参数
     */
    public static function getRobotParam()
    {
        $cache_key=self::ROBOT_PARAM_CACHE_KEY;
        if (!Cache::has($cache_key)){
            self::initRobotParamCache();
        }
        return Cache::get($cache_key);
    }

    /**
     * 用户发布视频之后
     * @param $video_id
     * @param $video_user_id
     */
    public static function afterUserPutVideo($video_id,$video_user_id)
    {
        if (self::isRobot($video_user_id)){
            return;
        }
        //获取参数
        $param_key='user_put_video_event_param';
        $event_param=self::getRobotParam()[$param_key];
        //随机数量点赞任务
        $like_number=mt_rand($event_param['like_min'],$event_param['like_max']);
        self::likeTask($video_id,$like_number,$event_param['finish_time']);
        //随机数量评论任务
        $comment_number=mt_rand($event_param['comment_min'],$event_param['comment_max']);
        self::commentTask($video_id,$comment_number,$event_param['finish_time']);
    }

    /**
     * 用户行为之后
     * @param int $action_type 行为类型  1点赞  2评论
     * @param $action_user_id
     * @param $video_id
     * @param $video_user_id
     */
    public static function afterUserAction($action_type,$action_user_id,$video_id,$video_user_id)
    {
        if (self::isRobot($action_user_id)){
            return;
        }
        if (self::isRobot($video_user_id)){
            return;
        }
        $param_key='user_action_event_param';
        $event_param=self::getRobotParam()[$param_key];
        if ($action_type===1){
            //随机数量点赞任务
            $like_number=mt_rand($event_param['like_min'],$event_param['like_max']);
            self::likeTask($video_id,$like_number,$event_param['finish_time']);
        }elseif ($action_type===2) {
            //随机数量评论任务
            $comment_number = mt_rand($event_param['comment_min'], $event_param['comment_max']);
            self::commentTask($video_id, $comment_number, $event_param['finish_time']);
        }
    }

    /**
     * 点赞任务
     * @param $video_id
     * @param $like_number
     * @param $finish_time
     * @param int $type
     */
    public static function likeTask($video_id,$like_number,$finish_time,$type=0)
    {
        //随机获取需要的机器人账号user_ids
        $robot_user_ids=self::randomGetMultiRobotUserId($like_number);
        //随机时间点
        $now=time();
        $time_array=[];
        for ($i=0;$i<count($robot_user_ids);$i++){
            $time_array[]=mt_rand($now+self::MIN_FINISH_TIME,$now+$finish_time);
        }
        //点赞任务入队列
        for ($i=0;$i<$like_number;$i++){
            $robot_user_id=array_pop($robot_user_ids);
            if (!$robot_user_id){
                return;
            }
            if($type==1){
                $param =[
                    'video_id'=>$video_id,
                    'user_id'=>$robot_user_id,
                ];
                self::performAction(1,$param);
            }else{
                $data=[
                    'action' => 'robotPerformAction',
                    'params' => [
                        'action_type' => 1,
                        'param'=>[
                            'video_id'=>$video_id,
                            'user_id'=>$robot_user_id,
                        ]
                    ],
                ];
                publish_message($data,array_pop($time_array));
            }
        }
    }

    /**
     * 评论任务
     * @param $video_id
     * @param $comment_number
     * @param $finish_time
     * @param int $type
     */
    public static function commentTask($video_id,$comment_number,$finish_time,$type=0)
    {
        //随机获取评论库的id
        $comment_library_id=CommentLibrary::randomGetMultiCommentLibraryId($comment_number);

        $comment_number=count($comment_library_id);
        //随机获取需要的机器人账号user_ids
        $robot_user_ids=self::randomGetMultiRobotUserId($comment_number);
        //随机时间点
        $now=time();
        $time_array=[];
        for ($i=0;$i<count($robot_user_ids);$i++){
            $time_array[]=mt_rand($now+self::MIN_FINISH_TIME,$now+$finish_time);
        }
        //评论入队列
        for ($i=0;$i<$comment_number;$i++){
            $robot_user_id=array_pop($robot_user_ids);
            if (!$robot_user_id){
                return;
            }
            if($type){
                $param =[
                    'video_id'=>$video_id,
                    'user_id'=>$robot_user_id,
                    'comment_id'=>array_pop($comment_library_id),
                ];
                self::performAction(2,$param);
            }else{
                $data=[
                    'action' => 'robotPerformAction',
                    'params' => [
                        'action_type' => 2,
                        'param'=>[
                            'video_id'=>$video_id,
                            'user_id'=>$robot_user_id,
                            'comment_id'=>array_pop($comment_library_id),
                        ]
                    ],
                ];
                publish_message($data,array_pop($time_array));
            }
        }
    }

    /**
     * 执行动作
     * (由队列调起)
     * @param int $action_type 类型 1点赞 2评论 3转发
     * @param array $param 参数
     */
    public static function performAction($action_type,$param)
    {
        if ($action_type===1){
            //点赞
            self::likeAction($param);
        }elseif ($action_type===2){
            //评论
            self::commentAction($param);
        }
    }

    /**
     * 点赞
     * @param $param
     */
    private static  function likeAction($param)
    {
        $video_id = $param['video_id'];
        $user_id = $param['user_id'];
    }

    /**
     * 评论
     * @param $param
     */
    private  static function commentAction($param)
    {
        $video_id = $param['video_id'];
        $user_id = $param['user_id'];
        $comment_id = $param['comment_id'];
    }
}