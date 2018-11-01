<?php
namespace app\common\model;
use think\Db;
use think\Model;

class ActivityTop extends Model
{
    const TOP_DATA_STATUS_TEXT=[
        '0'=>'视频审核未通过',
        '1'=>'视频审核通过',
        '-1'=>'已删除'
    ];

    const TOP_DATA_STATUS=[
        'no_pass'=>0,
        'pass'=>1,
        'delete'=>-1
    ];

    /**
     * 启动排行数据
     * @param $video_id
     */
    public static function doTopData($video_id){
        $activity_top_data  = [
            'status'=>self::TOP_DATA_STATUS['pass'],
            'update_time'=>time()
        ];
        Db::name('activity_top_data')->where(['video_id'=>$video_id])->update($activity_top_data);
    }

    /**
     * 冻结排行数据
     * @param $video_id
     */
    public static function hideTopData($video_id){
        $activity_top_data  = [
            'status'=>self::TOP_DATA_STATUS['no_pass'],
            'update_time'=>time()
        ];
        Db::name('activity_top_data')->where(['video_id'=>$video_id])->update($activity_top_data);
    }

    /**
     * 删除排行数据
     * @param $video_id
     */
    public static function delTopData($video_id){
        Db::name('activity_top_data')->where(['video_id'=>$video_id])->delete();
    }
}