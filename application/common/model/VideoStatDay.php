<?php
namespace app\common\model;

use think\Cache;
use think\Model;

class VideoStatDay extends Model
{
    /** 统计缓存前缀,拼接日期和分类id使用 */
    const VIDEO_STAT_DAY_PRE='video_stat_day_';

    /**
     * 统计增加
     * @param int $categoryId 分类id
     * @param string $action like,comment,view,upload
     * @return float
     */
    public static function statInc($categoryId,$action)
    {
        $date=date('Y_m_d');
        $cacheKey=get_cache_prefix().self::VIDEO_STAT_DAY_PRE.$date.'_'.$categoryId;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        $exists=$redis->exists($cacheKey);
        $ret=$redis->zIncrBy($cacheKey,1,$action);
        if (!$exists){
            $redis->expireAt($cacheKey,strtotime(date('Y-m-d 23:59:59',strtotime('+1 day'))));
        }
        return $ret;
    }

    /**
     * 获取统计
     * 默认取当前日期的数据
     * @param int $categoryId 分类id
     * @param string $date
     * @return array
     */
    public static function getStat($categoryId,$date='')
    {
        if (!$date){
            $date=date('Y_m_d');
        }
        $cacheKey=get_cache_prefix().self::VIDEO_STAT_DAY_PRE.$date.'_'.$categoryId;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        return $redis->zRange($cacheKey,0,-1,true);
    }
}