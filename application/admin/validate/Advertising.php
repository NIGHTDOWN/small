<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\AdvertisingType as AdCommonModel;

class Advertising extends Validate
{
    protected $field=[
        'type_id'=>'类型ID',
        'title'=>'广告名称',
        'image'=>'广告图',
        'url'=>'链接地址',
        'order_sort'=>'排序',
        'start_time'=>'开始时间',
        'end_time'=>'结束时间',
        'status'=>'状态',
    ];

    /**
     * 验证规则
     */
    protected $rule = [
        'type_id'=>'require|integer',
        'title'=>'require',
        'image'=>'url',
        'url'=>'url',
        'order_sort'=>'require|integer',
        'start_time'=>'require|date',
        'end_time'=>'date|checkEndTime',
        'status'=>['require','in'=>AdCommonModel::STATUS],
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
        'add'  => ['type_id','title','image','url','order_sort','status','start_time','end_time'],
        'edit' => ['type_id','title','image','url','order_sort','status','start_time','end_time'],
    ];


    protected function checkEndTime($value,$rule,$data)
    {
        if (strtotime($value)<=strtotime($data['start_time'])){
            return '结束时间必须大于开始时间';
        }
        return true;
    }
    
}
