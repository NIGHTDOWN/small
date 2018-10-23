<?php
namespace app\admin\model;
use think\Model;

class HotVideo extends Model
{
    // 表名
    protected $name = 'hot_video';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;

}