<?php

namespace app\admin\model;

use think\Model;

class HotVideo extends Model
{
    // 表名
    protected $name = 'hot_video';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        // 'create_time_text'
    ];



    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function video()
    {
        return $this->belongsTo('Video', 'video_id', 'id', [], 'INNER')->setEagerlyType(0);
    }

    // public function getCreateTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    // protected function setCreateTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }


}
