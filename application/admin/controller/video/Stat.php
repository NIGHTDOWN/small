<?php
namespace app\admin\controller\video;
use app\common\controller\Backend;

class Stat extends Backend
{
    /**
     * 查看
     */
    public function index()
    {
        //历史总数据
        $data=model('VideoStatDay')
            ->field([
                'sum(upload_total) as history_upload_total',
                'sum(view_total) as history_view_total',
                'sum(like_total) as history_like_total',
                'sum(comment_total) as history_comment_total',
            ])
            ->find();
        $this->view->assign("data", $data);
        return $this->view->fetch();
    }

    /**
     * 图表数据
     * @return array
     */
    public function chartData()
    {
        if ($this->request->isAjax()) {
            $filter = $this->request->get("filter", '');
            $filter = (array)json_decode($filter, TRUE);
            $filter = $filter ? $filter : [];
            $dateRange=$filter['date_range']??'';
            $categoryId=$filter['category_id']??0;
            if ($dateRange){
                $dateRange=explode(' - ',$dateRange);
                $dateRange[0]=strtotime($dateRange[0]);
                $dateRange[1]=strtotime($dateRange[1]);
            }else{
                $dateRange=[strtotime('-1 week'),time()];
            }
            if (($dateRange[1]-$dateRange[0])<(24*3600)){
                $dateRangeArray[]=date('Y-m-d',$dateRange[1]);
            }else{
                $dateRangeArray=get_day_in_range($dateRange,'Y-m-d',24*3600);
            }
            $where=[
                'time'=>['between',[strtotime("$dateRangeArray[0] 00:00:00"),strtotime($dateRangeArray[count($dateRangeArray)-1]." 23:59:59")]],
            ];
            if ($categoryId){
                $where['category_id']=['=',$categoryId];
            }
            /** @var \app\admin\model\VideoStatDay $model */
            $model=model('VideoStatDay');
            $data=$model
                ->where($where)
                ->group('time')
                ->column('sum(view_total) as view_total,sum(like_total) as like_total,sum(comment_total) as comment_total,sum(upload_total) as upload_total','time');

            $list=[];
            foreach ($dateRangeArray as $date){
                if (isset($data[strtotime($date)])){
                    $list[$date]=$data[strtotime($date)];
                }else{
                    if ($date==date('Y-m-d')){
                        $list[$date]=$model->getTodayData($categoryId);
                    }else{
                        $list[$date]=[
                            'view_total'=>0,
                            'like_total'=>0,
                            'comment_total'=>0,
                            'upload_total'=>0,
                        ];
                    }
                }
            }

            return ['rows'=>['data'=>[
                'view'=>['name'=>'播放','list'=>array_column($list,'view_total')],
                'like'=>['name'=>'点赞','list'=>array_column($list,'like_total')],
                'comment'=>['name'=>'评论','list'=>array_column($list,'comment_total')],
                'upload'=>['name'=>'上传','list'=>array_column($list,'upload_total')],
            ],'date_list'=>$dateRangeArray]];
        }
    }
}