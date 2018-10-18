<?php

namespace app\common\model;

// use WSJ\WQiniu;
use think\Db;

class Activity
{
    /**
     * 状态
     */
    public static $status = [
        'DELETE' => -1,
        'HIDE' => 0,
        'DISPLAY' => 1,
    ];

    /**
     * 状态名称
     */
    public static $statusText = [
        -1 => '删除',
        0 => '隐藏',
        1 => '显示',
    ];

    /**
     * 活动状态
     */
    public static $activityStatus = [
        'NOT_START' => 1,
        'ONGOING' => 2,
        'FINISHED' => 3,
    ];

    /**
     * 活动状态名称
     */
    public static $activityStatusText = [
        1 => '未开始',
        2 => '进行中',
        3 => '已结束',
    ];

    public static $matchText = [
        'video_play' => '播放',
        'video_like' => '点赞',
        'video_apply' => '评论',
    ];

    public static $topDataStatusText = [
        '0' => '视频审核未通过',
        '1' => '视频审核通过',
        '-1' => '已删除'
    ];


    public static $topDataStatus = [
        'NOPASS' => 0,
        'PASS' => 1,
        'DELETE' => -1
    ];

    const ACTIVITY_SETTING_PRE = 'activity_setting_pre_';

    const ACTIVITY_TOP_DATA_PRE = 'activity_top_data_pre_';

    /**
     * 获取远程图片存储空间
     * @return mixed
     */
    public static function getRemoteImgBucket()
    {
        return config('qiniu.other_image_bkt');
    }

    /**
     * 获取远程图片访问域名
     */
    public static function getRemoteImgDomain()
    {
        return config('qiniu.other_image_bkt_domain');
    }

    /**
     * 获取远程图片访问协议
     * @return mixed
     */
    public static function getRemoteImgProtocol()
    {
        return config('qiniu.other_image_bkt_protocol');
    }

    /**
     * 删除远程活动图片资源
     * @param $image
     */
    public function deleteRemoteActivityImageFile($image)
    {
        if ($image) {
            $bucket = self::getRemoteImgBucket();
            WQiniu::delete($bucket, $image);
        }
    }

    /**
     * 获取图片访问url
     * @param $cover_img
     * @return string
     */
    public static function getImageUrl($cover_img)
    {
        $cover_img_url = '';
        if ($cover_img) {
            $cover_img_url = self::getRemoteImgProtocol() . '://' . self::getRemoteImgDomain() . '/' . $cover_img;
        }
        return $cover_img_url;
    }

    /**
     * 判断视频是否是活动视频
     */
    public static function getIsActivityVideo($video_id)
    {
        $map[] = ['start_time', 'elt', time()];
        $map[] = ['end_time', 'egt', time()];
        $map[] = ['video_id', 'eq', $video_id];
        $data = Db::name('activity_top_data')->where($map)->select();
        return $data ? 1 : 0;
    }
}