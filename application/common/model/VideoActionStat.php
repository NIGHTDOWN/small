<?php
namespace app\common\model;
use think\Cache;
use think\Db;
use think\Model;

class VideoActionStat extends Model
{
    /** 行为列表 */
    const ACTION_LIST=[
        'view',
        'like',
        'comment',
        'share',
    ];

    /** 日统计缓存key前缀,拼接日期 */
    const ACTION_STAT_DAY_PRE='video_action_stat_day_';

    /** 总统计缓存key */
    const ACTION_STAT_TOTAL='video_action_stat_total';

    /**
     * 增加日统计
     * @param string $action
     * @param int $categoryId
     * @param string $channel
     * @return float
     */
    public static function incStatDay($action,$categoryId,$channel)
    {
        $today=date('Y_m_d');
        $hour=date('H');
        $cacheKey=get_cache_prefix().self::ACTION_STAT_DAY_PRE.$today;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        $exists=$redis->exists($cacheKey);
        $ret=$redis->zIncrBy($cacheKey,1,$channel.','.$categoryId.','.$action.','.$hour);
        if (!$exists){
            $redis->expireAt($cacheKey,strtotime(date('Y-m-d 23:59:59',strtotime('+1 day'))));
        }
        self::incStatTotal($action);
        return $ret;
    }

    /**
     * 获取日统计
     */
    public static function getStatDay()
    {
        $today=date('Y_m_d');
        $cacheKey=get_cache_prefix().self::ACTION_STAT_DAY_PRE.$today;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        return $redis->zRange($cacheKey,0,-1,true);
    }

    /**
     * 获取昨日统计
     */
    public static function getStatYesterday()
    {
        $yesterday=date('Y_m_d');
        $cacheKey=get_cache_prefix().self::ACTION_STAT_DAY_PRE.$yesterday;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        return $redis->zRange($cacheKey,0,-1,true);
    }

    /**
     * 初始化总统计
     */
    public static function initStatTotal()
    {
        //已经汇总的
        $data=Db::name('video_action_stat')
            ->field([
                'sum(view_total) as view_total',
                'sum(like_total) as like_total',
                'sum(comment_total) as comment_total',
                'sum(share_total) as share_total',
            ])
            ->where([
                'category_id'=>['=',0],
            ])
            ->find();
        //今天记录到缓存的
        $todayData=self::getStatDay();
        foreach ($todayData as $key=>$value){
            if (strpos($key,'view')!==false){
                $data['view_total']+=$value;
            }elseif (strpos($key,'like')!==false){
                $data['like_total']+=$value;
            }elseif (strpos($key,'comment')!==false){
                $data['comment_total']+=$value;
            }elseif (strpos($key,'share')!==false){
                $data['share_total']+=$value;
            }
        }
        $formatData=[];
        foreach ($data as $key=>$value){
            $formatData[]=$value;
            $formatData[]=$key;
        }
        $cacheKey=get_cache_prefix().self::ACTION_STAT_TOTAL;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        return $redis->zAdd($cacheKey,...$formatData);
    }

    /**
     * 增加总统计
     * @param string $action
     * @return float
     */
    public static function incStatTotal($action)
    {
        $cacheKey=get_cache_prefix().self::ACTION_STAT_TOTAL;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        if (!$redis->exists($cacheKey)){
            self::initStatTotal();
        }
        return $redis->zIncrBy($cacheKey,1,$action);
    }

    /**
     * 获取总统计
     */
    public static function getStatTotal()
    {
        $cacheKey=get_cache_prefix().self::ACTION_STAT_TOTAL;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        if (!$redis->exists($cacheKey)){
            self::initStatTotal();
        }
        return $redis->zRange($cacheKey,0,-1,true);
    }

    /**
     * 保存昨天数据
     * (每天凌晨保存前一天的数据,定时任务)
     * @return bool|int|string
     */
    public function saveYesterday()
    {
        $yesterdayTime=strtotime('-1 day');
        $yesterday=date('Y_m_d',$yesterdayTime);
        $yesterdayStartTime=strtotime(date('Y-m-d 00:00:00',$yesterdayTime));
        $cacheKey=get_cache_prefix().self::ACTION_STAT_DAY_PRE.$yesterday;
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        $data=$redis->zRange($cacheKey,0,-1,true);
        if (!$data){
            $this->error='empty data';
            return false;
        }
        $baseData=[
            'category_id'=>0,
            'channel'=>'',
            'time'=>$yesterdayStartTime,
            'view_total'=>0,
            'like_total'=>0,
            'comment_total'=>0,
            'share_total'=>0,
        ];
        $saveData=[
            'total'=>$baseData,
        ];
        foreach ($data as $key=>$value){
            if (substr_count($key,',')!==3){
                continue;
            }
            $key=explode(',',$key);
            $channel=$key[0]?$key[0]:'default';
            $categoryId=$key[1];
            $action=$key[2];
            if (!isset($saveData[$channel.','.$categoryId])){
                $saveData[$channel.','.$categoryId]=$baseData;
                $saveData[$channel.','.$categoryId]['category_id']=$categoryId;
                $saveData[$channel.','.$categoryId]['channel']=$channel;
            }
            $saveData[$channel.','.$categoryId][$action.'_total']+=$value;
            //总计增加
            $saveData['total'][$action.'_total']+=$value;
        }
        return $this->insertAll($saveData,false,100);
    }

}