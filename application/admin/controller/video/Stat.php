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
        $dateRange=$this->request->param('date');
        $categoryId=$this->request->param('category_id');
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


        $stat=[
            'data'=>[
                [
                    'name'=>'播放',
                    'type'=>'line',
                    'stack'=>'总量',
                    'data'=>array_column($list,'view_total'),
                ],
                [
                    'name'=>'点赞',
                    'type'=>'line',
                    'stack'=>'总量',
                    'data'=>array_column($list,'like_total'),
                ],
                [
                    'name'=>'评论',
                    'type'=>'line',
                    'stack'=>'总量',
                    'data'=>array_column($list,'comment_total'),
                ],
                [
                    'name'=>'上传',
                    'type'=>'line',
                    'stack'=>'总量',
                    'data'=>array_column($list,'upload_total'),
                ],
            ],
            'columns'=>$dateRangeArray,
        ];

        if ($this->request->isAjax()){
            $this->success('','',$stat);
        }else{
            //历史总数据
            $data=model('VideoStatDay')
                ->field([
                    'sum(upload_total) as history_upload_total',
                    'sum(view_total) as history_view_total',
                    'sum(like_total) as history_like_total',
                    'sum(comment_total) as history_comment_total',
                ])
                ->find();

            //分类列表
            /** @var \app\admin\model\Category $categoryModel */
            $categoryModel=model('Category');
            $categoryList=$categoryModel->getList('video');
            $categoryList[0]='选择分类';
            ksort($categoryList);

            $this->assign('data',$data);
            $this->assign('categoryList',$categoryList);
            $this->assignconfig('stat',$stat);
            return $this->view->fetch();
        }
    }
}