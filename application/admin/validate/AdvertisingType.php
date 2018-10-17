<?php

namespace app\admin\validate;

use think\Validate;

class AdvertisingType extends Validate
{
    protected $field=[
        'type'=>'类型',
        'status'=>'状态',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'type'=>'unique:advertising_type',
        'status'=>'integer|in:0,1',
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
        'add'  => ['type','status'],
        'edit' => ['type','status'],
    ];
    
}
