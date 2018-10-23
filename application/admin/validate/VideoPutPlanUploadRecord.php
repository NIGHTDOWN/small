<?php

namespace app\admin\validate;

use think\Validate;

class VideoPutPlanUploadRecord extends Validate
{
    /**
     * 验证规则
     */
    protected  $rule = [
        'page'              => 'require|integer|gt:0',
        'page_size'         => 'require|integer|between:1,100',
        'order_direction'   => 'require|integer|in:0,1',
        'order_field'       => 'require|in:id',
        'keyword'           => 'string',
        'status'            => 'integer|in:0,1,2',
        'file_name'         => 'require|string',
        'file_key'          => 'require|string',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'page.require'              => '非法页码',
        'page.integer'              => '非法页码',
        'page.gt'                   => '非法页码',
        'page_size.require'         => '单页条数合法范围1-100整数',
        'page_size.integer'         => '单页条数合法范围1-100整数',
        'page_size.between'         => '单页条数合法范围1-100整数',
        'order_direction.require'   => '非法排序方向',
        'order_direction.integer'   => '非法排序方向',
        'order_direction.in'        => '非法排序方向',
        'order_field.require'       => '非法排序字段',
        'order_field.in'            => '非法排序字段',
        'keyword.string'            => '搜索关键字必须是字符串',
        'status.integer'            => '状态值必须为整数',
        'status.in'                 => '状态值不正确',
        'file_name.require'         => '缺少文件名称',
        'file_name.string'          => '文件名称不正确',
        'file_key.require'          => '缺少文件key',
        'file_key.string'           => '文件key不正确',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'list' => ['page', 'page_size', 'order_direction', 'order_field', 'keyword', 'status'],
        'add' => ['file_name', 'file_key'],
    ];
    
}
