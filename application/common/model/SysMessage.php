<?php
namespace app\common\model;

use think\Model;
use wsj\WQiniu;

class SysMessage extends Model
{
    /** 状态 */
    const STATUS=[
        'no_send'=>0,
        'done_send'=>1,
        'wait_send'=>2,
    ];

    const STATUS_TEXT=[
        0=>'未发送',
        1=>'已发送',
        2=>'等待发送',
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

    /**
     * 删除图片文件
     * @param $cover_img
     * @return mixed
     */
    public static function deleteCoverImgFile($cover_img)
    {
        $url=config('site.other_url');
        $key=str_replace("$url/",'',$cover_img);
        $bucket=config('site.other_bucket');
        return WQiniu::delete($bucket,$key);
    }
}