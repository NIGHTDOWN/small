<?php
namespace app\common\model;

use think\Model;

class Advertising extends Model
{
    /** 状态 */
    const STATUS=[
        'delete'=>-1,
        'close'=>0,
        'open'=>1,
    ];

    /** 状态文本 */
    const STATUS_TEXT=[
        -1=>'删除',
        0=>'关闭',
        1=>'开启',
    ];
}