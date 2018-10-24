<?php

namespace app\admin\validate;
use app\common\model\PushMessage as PushMessageCommonModel;
use think\Validate;

class PushMessage extends Validate
{
    protected $field=[
        'title'=>'标题',
        'message'=>'消息',
        'action'=>'跳转类型',
        'action_param'=>'参数',
        'user_range'=>'用户范围',
        'target_user_ids'=>'用户ID',
        'is_now'=>'是否立即发送',
        'send_time'=>'发送时间',
        'msg_type'=>'类型',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'title'=>'require',
        'message'=>'require',
        'action'=>['require','in'=>PushMessageCommonModel::ACTION],
        'action_param'=>'checkActionParam',
        'user_range'=>['require','in'=>PushMessageCommonModel::USER_RANGE,'checkTargetUserIds'],
        'target_user_ids'=>['regex'=>'^[0-9|,]+$'],
        'is_now'=>'require|in:0,1',
        'send_time'=>'require|data',
        'msg_type'=>'require|in:0,1',
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['title','message','action','action_param','user_range','target_user_ids','is_now','send_time','msg_type'],
        'edit' => ['title','message','action','action_param','msg_type'],
    ];

    protected function checkTargetUserIds($value,$rule,$data)
    {
        if ($value==PushMessageCommonModel::USER_RANGE['portion']){
            if (!isset($data['target_user_ids'])||!$data['target_user_ids']){
                return '选择部分用户时必须填写用户ID';
            }
        }
        return true;
    }

    protected function checkActionParam($value,$rule,$data)
    {
        if ($data['action']=='openWeb'){
            if (!self::is($value,'url')){
                return '参数不是有效的URL';
            }
        }elseif ($data['action']=='playVideo'){
            if (!self::is($value,'integer')){
                return '参数不是有效的视频ID';
            }
        }
        return true;
    }
}
