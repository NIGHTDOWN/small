<?php
namespace app\common\model;
use think\Model;

class HotVideo extends Model
{
    /**
     * 状态
     */
    const STATUS=[
        'cancel'=>0,
        'normal'=>1,
    ];

    const STATUS_TEXT=[
        0=>'取消',
        1=>'正常',
    ];


}