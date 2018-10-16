<?php

namespace app\common\model;

use WSJ\WQiniu;
use think\Db;

class Subject
{
    public static $qiniuSubjectBucket = 'subject_img_bkt';    //七牛空间配置项
    public static $qiniuSubjectDomain = 'subject_img_bkt_domain';    //七牛域名配置项
    public static $qiniuSubjectProtocol = 'subject_img_bkt_protocol';   //七牛协议配置项

    /**
     * 获取封面图url
     * @param $key
     * @return string
     */
    public static function getCoverImgUrl($key)
    {
        if ($key) {
            $WQiniuConfig = WQiniu::getConfig();
            $key = $WQiniuConfig[self::$qiniuSubjectProtocol] . '://' . $WQiniuConfig[self::$qiniuSubjectDomain] . '/' . $key;
        }
        return $key;
    }

}