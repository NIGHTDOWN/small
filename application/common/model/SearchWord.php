<?php
namespace app\common\model;

use think\Model;

class SearchWord extends Model
{
    /** 状态 */
    const STATUS=[
        'hide'=>0,
        'show'=>1
    ];

    const STATUS_TEXT=[
        0=>'隐藏',
        1=>'显示',
    ];

    /** 置顶 */
    const ORDER_SORT=[
        'no'=>0,
        'yes'=>1
    ];

    const ORDER_SORT_TEXT=[
        0=>'否',
        1=>'是',
    ];
}