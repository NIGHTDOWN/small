<?php
namespace app\admin\model;
use think\Cache;
use think\Model;
use app\common\model\Category as CategoryCommonModel;
use app\common\model\VideoStatDay as VideoStatDayCommonModel;

class VideoStatDay extends Model
{
    /**
     * 新增数据
     * (每天凌晨保存前一天的数据,定时任务)
     */
    public function add()
    {
        $yesterday=strtotime('-1 day');

        $yesterdayStart=strtotime(date('Y-m-d 00:00:00',$yesterday));
        $yesterdayEnd=strtotime(date('Y-m-d 23:59:59',$yesterday));
        $date=date('Y_m_d',$yesterday);
        $cache_prefix=get_cache_prefix();
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        $categoryArray=CategoryCommonModel::getCategoryArray('video');
        $data=[];
        foreach ($categoryArray as $category){
            $categoryId=$category['id'];
            $cacheKey=$cache_prefix.VideoStatDayCommonModel::VIDEO_STAT_DAY_PRE.$date.'_'.$categoryId;
            $exists=$redis->exists($cacheKey);
            if ($exists){
                //缓存存在
                $dayData=VideoStatDayCommonModel::getStat($categoryId,$date);
                $data[]=[
                    'category_id'=>$categoryId,
                    'view_total'=>$dayData['view']??0,
                    'like_total'=>$dayData['like']??0,
                    'comment_total'=>$dayData['comment']??0,
                    'upload_total'=>$dayData['upload']??0,
                    'time'=>$yesterdayStart,
                ];
            }else{
                //缓存不存在
                $viewTotal=0;
                $likeTotal=model('admin/UserVideoLike')
                    ->alias('vl')
                    ->join('video v','vl.video_id = v.id','left')
                    ->where([
                        'vl.time'=>['between',[$yesterdayStart,$yesterdayEnd]],
                        'v.category_id'=>['=',$categoryId],
                    ])
                    ->count();
                $commentTotal=model('admin/VideoComment')
                    ->alias('vc')
                    ->join('video v','vc.video_id = v.id','left')
                    ->where([
                        'vc.create_time'=>['between',[$yesterdayStart,$yesterdayEnd]],
                        'v.category_id'=>['=',$categoryId],
                    ])
                    ->count();
                $uploadTotal=model('admin/Video')
                    ->where([
                        'create_time'=>['between',[$yesterdayStart,$yesterdayEnd]],
                        'category_id'=>['=',$categoryId],
                    ])
                    ->count();

                $data[]=[
                    'category_id'=>$categoryId,
                    'view_total'=>$viewTotal,
                    'like_total'=>$likeTotal,
                    'comment_total'=>$commentTotal,
                    'upload_total'=>$uploadTotal,
                    'time'=>$yesterdayStart,
                ];
            }
        }
        return $this->insertAll($data);
    }

    /**
     * 获取今天数据
     * @param int $categoryId
     * @return array
     */
    public function getTodayData($categoryId=0)
    {
        $data=[
            'view_total'=>0,
            'like_total'=>0,
            'comment_total'=>0,
            'upload_total'=>0,
        ];
        $date=date('Y_m_d');
        $cache_prefix=get_cache_prefix();
        /** @var \Redis $redis */
        $redis=Cache::init()->handler();
        if ($categoryId){
            $categoryArray[]=['id'=>$categoryId];
        }else{
            $categoryArray=CategoryCommonModel::getCategoryArray('video');
        }
        foreach ($categoryArray as $category){
            $categoryId=$category['id'];
            $cacheKey=$cache_prefix.VideoStatDayCommonModel::VIDEO_STAT_DAY_PRE.$date.'_'.$categoryId;
            $exists=$redis->exists($cacheKey);
            if ($exists) {
                //缓存存在
                $dayData = VideoStatDayCommonModel::getStat($categoryId, $date);
                $data['view_total'] += $dayData['view']??0;
                $data['like_total'] += $dayData['like']??0;
                $data['comment_total'] += $dayData['comment']??0;
                $data['upload_total'] += $dayData['upload']??0;
            }
        }
        return $data;
    }
}