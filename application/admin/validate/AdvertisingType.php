<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\AdvertisingType as AdTypeCommonModel;

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
        'type'=>'require|unique:advertising_type',
        'status'=>['require','in'=>AdTypeCommonModel::STATUS],
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
