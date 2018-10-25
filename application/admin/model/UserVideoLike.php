<?php
namespace app\admin\model;
use think\Model;

class UserVideoLike extends Model
{
    /**
     * 获取今日点赞总数
     */
    public function getTodayTotal()
    {
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $start = mktime(0,0,0,$month,$day,$year);
        $end= mktime(23,59,59,$month,$day,$year);
        $count=$this
            ->where([
                'time'=>['between',[$start,$end]],
            ])
            ->count();
        return $count;
    }
}