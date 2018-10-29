<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\SmsSendTask as SmsSendTaskCommonModel;

class SmsSendTask extends Validate
{
    protected $field=[
        'sms_template_code'=>'短信ID',
        'user_range'=>'用户范围',
        'target_user_ids'=>'用户IDS',
        'is_now'=>'是否立即发送',
        'send_time'=>'发送时间',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'sms_template_code'=>'require',
        'user_range'=>['require','in'=>SmsSendTaskCommonModel::USER_RANGE,'checkTargetUserIds'],
        'target_user_ids'=>['regex'=>'^[0-9|,]+$'],
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
        'add'  => ['sms_template_code','user_range','target_user_ids','is_now','send_time'],
        'edit' => [],
    ];

    protected function checkTargetUserIds($value,$rule,$data)
    {
        if ($value==SmsSendTaskCommonModel::USER_RANGE['portion']){
            if (!isset($data['target_user_ids'])||!$data['target_user_ids']){
                return '选择部分用户时必须填写用户ID';
            }
        }
        return true;
    }
    
}
