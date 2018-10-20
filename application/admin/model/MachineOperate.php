<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class MachineOperate extends Model
{
    // 表名
    protected $name = 'machine_operate';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'create_time_text'
    ];

    /**
     * 操作
     * @var array
     */
    public $operate = [
        'unknow' => 0,
        'activate' => 1,
        'register' => 2,
        'install' => 3,
        'active' => 4
    ];

    /**
     * 操作
     * @var array
     */
    protected $operateText = [
        0 => '未知',
        1 => '激活',
        2 => '注册'
    ];
}

