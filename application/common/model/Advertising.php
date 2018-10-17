<?php
namespace app\common\model;

use think\Model;
use wsj\WQiniu;

class Advertising extends Model
{
    /** 状态 */
    const STATUS=[
        'close'=>0,
        'open'=>1,
    ];

    /** 状态文本 */
    const STATUS_TEXT=[
        0=>'关闭',
        1=>'开启',
    ];

    /**
     * 删除图片文件
     * @param string $image 图片
     * @return mixed
     */
    public static function deleteImageFile($image)
    {
        if (!$image){
            return false;
        }
        $url=config('site.cover_url');
        $key=str_replace("$url/",'',$image);
        $bucket=config('site.cover_bucket');
        return WQiniu::delete($bucket,$key);
    }
}