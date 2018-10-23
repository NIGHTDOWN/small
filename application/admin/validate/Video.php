<?php

namespace app\admin\validate;

use think\Validate;

class Video extends Validate
{
    protected $field=[
        'title'=>'标题',
        'category_id'=>'分类ID',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'title'=>'length:0,16',
        'category_id'=>'require|integer',
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
        'add'  => [],
        'edit' => [],
        'editTitle'=>['title'],
        'setCategory'=>['category_id'],
    ];
    
}
