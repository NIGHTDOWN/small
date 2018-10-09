<?php

namespace app\admin\model;

use think\Model;

class VideoPutPlan extends Model
{
    // 表名
    protected $name = 'video_put_plan';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'create_time_text',
        'plan_time_text',
        'put_time_text'
    ];
    

    



    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPlanTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['plan_time']) ? $data['plan_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPutTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['put_time']) ? $data['put_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setPlanTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setPutTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


}
