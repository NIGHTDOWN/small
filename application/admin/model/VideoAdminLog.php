<?php
namespace app\admin\model;
use think\Model;

class VideoAdminLog extends Model
{
    const TYPE = [
        'delete' => -1,
        'hide' => 0,
        'display' => 1,
        'check_pass' => 5,
        'check_no_pass' => 6,
    ];

    const TYPE_TYPE = [
        -1 => '删除',
        0 => '隐藏',
        1 => '显示',
        5 => '审核通过',
        6 => '审核不通过',
    ];

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
}