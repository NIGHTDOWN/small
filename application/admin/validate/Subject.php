<?php

namespace app\admin\validate;

use think\Validate;

class Subject extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'page' => 'require|gt:0',
        'page_size' => 'require|between:1,100',
        'order_direction' => 'require|in:0,1',
        'order_field' => 'require|in:id,recommend,create_time,update_time',

        'id' => 'require|gt:0',
        'subject_name' => 'require|length:1,16',
        'recommend' => 'require|gt:-1|checkRecommendNum',
        'status' => 'require|in:0,1',
        'order_sort' => 'require|gt:-1',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'subject_name.require' => '视频主题名称不能为空',
        'subject_name.length' => '视频主题名称长度限制1-16字',
        'status.require' => '非法状态',
        'status.in' => '非法状态',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add' => ['subject_name', 'status'],
        'edit' => ['subject_name', 'status'],
    ];

}
