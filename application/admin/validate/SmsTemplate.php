<?php

namespace app\admin\validate;

use think\Validate;

class SmsTemplate extends Validate
{
    protected $field=[
        'template_code'=>'模板ID',
        'template_content'=>'模板内容',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'template_code'=>'require|unique:sms_template',
        'template_content'=>'require',
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
        'add'  => ['template_code','template_content'],
        'edit' => ['template_code','template_content'],
    ];
    
}
