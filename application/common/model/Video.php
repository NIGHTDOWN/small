<?php
namespace app\common\model;
use think\Cache;
use think\Db;
use think\Model;
use wsj\ali\ElasticSearch;
use wsj\WQiniu;

class Video extends Model
{
    /** 状态 */
    const STATUS = [
        'delete' => -1,
        'hide' => 0,
        'display' => 1,
        'robot_fail' => 2,
//        'violation' => 3,
//        'frozen' => 5,
//        'complain' => 6,
//        'appealing' => 7,
        'robot_success' => 8,
        'check_no_pass' => 9,
        'draft' => 10,
    ];

    const STATUS_TEXT = [
        -1 => '删除',
        0 => '未发布',
        1 => '已发布',
        2 => '机器审核未通过',
//        3 => '违规',
//        5 => '冻结中',
//        6 => '用户申诉',
//        7 => '用户申诉',
        8 => '机器审核通过',
        9 => '审核不通过',
        10 => '草稿',
    ];

    //视频分类列表输出参数
    const TOP_VIDEO_LIST_PREFIX     = 'random:tv_list_';
    const USER_VIDEO_LIST_PREFIX    = 'random:uv_list_';
    const USER_VIDEO_LIST_EXPIRE    = 86400;
    const TOP_VIDEO_QUANTITY        = 4;

    /**
     * 增加es
     * @param int $video_id
     * @param string $video_title
     * @param array $tags
     * @return array|bool
     */
    public static function addEs($video_id,$video_title,$tags=[])
    {
        if (!$tags){
            $subject_ids=Db::name('subject_video')
                ->where([
                    'video_id'=>['=',$video_id],
                    'type'=>['=',0]
                ])
                ->column('subject_id');
            if ($subject_ids){
                $tags=Db::name('subject')->where(['id'=>['in',$subject_ids]])->column('subject_name');
            }
        }
        $es_data=[
            'id'=>$video_id,
            'title'=>$video_title,
            'tags'=>$tags,
        ];
        $elastic_search=new ElasticSearch();
        return $elastic_search->name('video')->insert($es_data);
    }

    /**
     * 删除es
     * @param int $video_id
     * @return bool|int
     */
    public static function delEs($video_id)
    {
        $elastic_search=new ElasticSearch();
        return $elastic_search->name('video')->delete($video_id);
    }

    /**
     * 更新es
     * @param int $video_id
     * @param array $data
     * @return array|bool
     */
    public static function updateEs($video_id,$data)
    {
        $elastic_search=new ElasticSearch();
        return $elastic_search->name('video')->update($video_id,$data);
    }

    /**
     * 增加置顶到缓存
     * @param int $video_id
     * @param int $category_id
     * @return int
     */
    public static function addTopVideoToCache($video_id, $category_id)
    {
        $cache_key = self::TOP_VIDEO_LIST_PREFIX . $category_id;
        $cache = Cache::init();
        /** @var \Redis $redis */
        $redis=$cache->handler();
        return $redis->sAdd($cache_key, $video_id);
    }

    /**
     * 从缓存删除置顶
     * @param int $video_id
     * @param int $category_id
     * @return int
     */
    public static function delTopVideoFromCache($video_id, $category_id)
    {
        $cache_key = self::TOP_VIDEO_LIST_PREFIX . $category_id;
        $cache = Cache::init();
        /** @var \Redis $redis */
        $redis=$cache->handler();
        return $redis->sRem($cache_key, $video_id);
    }

    /**
     * 删除封面文件
     * @param $cover_img
     * @return mixed
     */
    public static function deleteCoverImgFile($cover_img)
    {
        $url=config('site.cover_url');
        $key=str_replace("$url/",'',$cover_img);
        $bucket=config('site.cover_bucket');
        return WQiniu::delete($bucket,$key);
    }

    /**
     * 获取播放地址
     * @param int $key
     * @param int $status
     * @return string
     */
    public static function getPublicUrl($key,$status)
    {
        if (in_array($status,[self::STATUS['hide'],self::STATUS['display']])){
            $url=config('site.original_url').'/'.$key.'.mp4';
        }else{
            $url=config('site.public_url').'/'.$key;
        }
        return $url;
    }
}