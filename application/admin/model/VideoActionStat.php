<?php
namespace app\admin\model;
use think\Model;
use app\common\model\VideoActionStat as VideoActionStatCommonModel;

class VideoActionStat extends Model
{
    /**
     * 获取今天的
     * @param $searchCategoryId
     * @param $searchChannel
     * @return array
     */
    public function getToday($searchCategoryId,$searchChannel)
    {
        $today=[
            'view_total'=>0,
            'like_total'=>0,
            'comment_total'=>0,
            'share_total'=>0,
        ];
        $data=VideoActionStatCommonModel::getStatDay();
        $channelFilter=false;
        $categoryIdFilter=false;
        if ($searchChannel){
            $channelFilter=true;
        }
        if ($searchCategoryId){
            $categoryIdFilter=true;
        }
        foreach ($data as $key=>$value){
            if (substr_count($key,',')!==3){
                continue;
            }
            $key=explode(',',$key);
            $channel=$key[0]?$key[0]:'default';
            $categoryId=$key[1];
            $action=$key[2];
            if ($channelFilter){
                if ($searchChannel!==$channel){
                    continue;
                }
            }
            if ($categoryIdFilter){
                if ($searchCategoryId!==$categoryId){
                    continue;
                }
            }
            $today[$action.'_total']+=$value;
        }
        return $today;
    }

    /**
     * 获取总的
     */
    public function getTotal()
    {
        return VideoActionStatCommonModel::getStatTotal();
    }

    /**
     * 获取小时图
     * (只支持当天和昨天)
     * @param string $date
     * @param $searchCategoryId
     * @param $searchChannel
     * @return array
     */
    public function getHour($date,$searchCategoryId,$searchChannel)
    {
        $data=[];
        if ($date==='today'){
            $data=VideoActionStatCommonModel::getStatDay();
        }elseif ($date==='yesterday'){
            $data=VideoActionStatCommonModel::getStatYesterday();
        }
        $channelFilter=false;
        $categoryIdFilter=false;
        if ($searchChannel){
            $channelFilter=true;
        }
        if ($searchCategoryId){
            $categoryIdFilter=true;
        }
        $hourData=[];
        foreach ($data as $key=>$value){
            if (substr_count($key,',')!==3){
                continue;
            }
            $key=explode(',',$key);
            $channel=$key[0]?$key[0]:'default';
            $categoryId=$key[1];
            $action=$key[2];
            $hour=$key[3];
            if ($channelFilter){
                if ($searchChannel!==$channel){
                    continue;
                }
            }
            if ($categoryIdFilter){
                if ($searchCategoryId!==$categoryId){
                    continue;
                }
            }
            if (!isset($hourData[$hour])){
                $hourData[$hour]=[
                    'view_total'=>0,
                    'like_total'=>0,
                    'comment_total'=>0,
                    'share_total'=>0,
                ];
            }
            $hourData[$hour][$action.'_total']+=$value;
        }
        return $hourData;
    }
}