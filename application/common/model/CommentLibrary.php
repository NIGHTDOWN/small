<?php
namespace app\common\model;
use think\Cache;
use think\Db;
use think\Model;

class CommentLibrary extends Model
{
    /** 缓存key */
    const COMMENT_LIBRARY_IDS_CACHE_KEY='comment_library_ids_cache';

    public static function initCommentLibraryIdsCache()
    {
        $cache_key=self::COMMENT_LIBRARY_IDS_CACHE_KEY;
        if(Cache::has($cache_key)){
            Cache::rm($cache_key);
        }
        $redis=Cache::init()->handler();
        Db::name('comment_library')
            ->field(['id'])
            ->chunk(100,function ($comment_library_array) use ($redis,$cache_key){
                foreach ($comment_library_array as $comment_library){
                    $redis->sAdd(config('cache.prefix').$cache_key,$comment_library['id']);
                }
            });
    }

    /**
     * 增加一个评论库id到缓存
     * @param $id
     */
    public static function addIdToCommentLibraryIdsCache($id)
    {
        $cache_key=self::COMMENT_LIBRARY_IDS_CACHE_KEY;
        $redis=Cache::init()->handler();
        $redis->sAdd(config('cache.prefix').$cache_key,$id);
    }

    /**
     * 从缓存删除一个评论库id
     * @param $id
     */
    public static function delIdFromCommentLibraryIdsCache($id)
    {
        $cache_key=self::COMMENT_LIBRARY_IDS_CACHE_KEY;
        $redis=Cache::init()->handler();
        $redis->sRem(config('cache.prefix').$cache_key,$id);
    }

    /**
     * 随机获取多个评论库id
     * @param int $number  要取的数量
     * @return array
     */
    public static function randomGetMultiCommentLibraryId($number)
    {
        $cache_key=self::COMMENT_LIBRARY_IDS_CACHE_KEY;
        if (!Cache::has($cache_key)){
            self::initCommentLibraryIdsCache();
        }
        $redis=Cache::init()->handler();
        return $redis->sRandMember(config('cache.prefix').$cache_key,$number);
    }
}