<?php
namespace app\common\model;

use think\Model;

class SmsSendTask extends Model
{
    /** 状态 */
    const STATUS=[
        'no_send'=>0,
        'wait_send'=>1,
        'done_send'=>2,
    ];

    const STATUS_TEXT=[
        0=>'未发送',
        1=>'等待发送',
        2=>'已发送',
    ];

    /** 用户范围 */
    const USER_RANGE=[
        'all'=>0,
        'portion'=>1,
    ];

    const USER_RANGE_TEXT=[
        0=>'全部用户',
        1=>'部分用户',
    ];
}