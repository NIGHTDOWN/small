<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\SysMessage as SysMessageCommonModel;

class SysMessage extends Validate
{
    protected $field=[
        'message'=>'消息',
        'cover_img'=>'图片',
        'link'=>'链接',
        'user_range'=>'用户范围',
        'is_now'=>'立即发送参数',
        'send_time'=>'发送时间',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'message'=>'require',
        'cover_img'=>'url',
        'link'=>'url',
        'user_range'=>['require','in'=>SysMessageCommonModel::USER_RANGE,'checkTargetUserIds'],
        'is_now'=>['require','in'=>[0,1]],
        'send_time'=>'require|date',
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
        'add'  => ['message','cover_img','link','user_range','is_now','send_time'],
        'edit' => ['message','cover_img','link'],
    ];

    protected function checkTargetUserIds($value,$rule,$data)
    {
        if ($value==SysMessageCommonModel::USER_RANGE['portion']){
            if (!isset($data['target_user_ids'])||!$data['target_user_ids']){
                return '选择部分用户时必须填写用户ID';
            }
        }
        return true;
    }

}
