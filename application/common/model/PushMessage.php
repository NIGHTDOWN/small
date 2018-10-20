<?php
namespace app\common\model;
use JPush\Client as Push;
use JPush\Exceptions\APIRequestException;
use think\Model;

class PushMessage extends Model
{
    /** 状态 */
    const STATUS=[
        'no_send'=>0,
        'done_send'=>1,
        'wait_send'=>2,
    ];

    const STATUS_TEXT=[
        0=>'未发送',
        1=>'已发送',
        2=>'等待发送',
    ];

    /** 用户范围 */
    const USER_RANGE=[
        'all'=>0,
        'portion'=>1,
    ];

    const USER_RANGE_TEXT=[
        0=>'全部用户',
        1=>'部分用户',
    ];

    /** 行为 */
    const ACTION=[
        'openWeb',
        'playVideo'
    ];

    const ACTION_TEXT=[
        'openWeb'=>'链接',
        'playVideo'=>'视频',
    ];

    /**
     * 极光推送
     * @param string $title  标题
     * @param string $message  消息
     * @param int|array $userId  用户id 0 全部用户 数组 指定用户集合
     * @param array $extra
     * @return bool
     */
    public static function jPush($title = '' ,$message = '' ,$userId = 0 ,$extra = [])
    {
        $config = config('jiguang.');
        $push = new Push($config['ak'] , $config['sk']);
        $push = $push->push()->setPlatform('all');
        if ($userId == 0) {
            $push->addAllAudience();
        }else if(is_array($userId)){
            $userId = array_filter(array_unique($userId));
            if(!$userId) return false;
            $user_ids = explode(',',implode(',',$userId));
            $push = $push->addAlias($user_ids);
        }
        try{
            $extra =  $extra ?? null;
            $rs = $push->addAndroidNotification($message ,$title ,null,$extra)
                ->addIosNotification($message,null,0,null,null,$extra)
                ->options([
                    'apns_production' => $config['production']
                ])
                ->send();
            return $rs ?? false;
        }catch (APIRequestException $e){
            return false;
        }
    }
}