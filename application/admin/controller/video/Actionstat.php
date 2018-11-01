<?php
namespace app\admin\controller\video;
use app\common\controller\Backend;

class Actionstat extends Backend
{
    /**
     * 查看
     */
    public function index()
    {
        $dateRange=$this->request->param('date');
        $categoryId=$this->request->param('category_id');
        $channel=$this->request->param('channel');
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
        /** @var \app\admin\model\VideoActionStat $model */
        $model=model('VideoActionStat');

        if (count($dateRangeArray)===1&&($dateRangeArray[0]===date('Y-m-d'))||$dateRangeArray[0]===date('Y-m-d',strtotime('-1 day'))){
            //日期选择范围选择一天,今天或昨天,最小颗粒小时
            if ($dateRangeArray[0]===date('Y-m-d')){
                $list=$model->getHour('today',$categoryId,$channel);
            }else{
                $list=$model->getHour('yesterday',$categoryId,$channel);
            }

            $hourRangeArray=range(0,23);
            //补全数据
            foreach ($hourRangeArray as $hour){
                //没有数据的,填充0
                if (!isset($list[$hour])){
                    $list[$hour]=[
                        'view_total'=>0,
                        'like_total'=>0,
                        'comment_total'=>0,
                        'share_total'=>0,
                    ];
                }
            }
            $columns=$hourRangeArray;
        }else{
            //其他,最小颗粒天
            if ($categoryId||$channel){
                //有筛选,sum聚合查询
                if ($categoryId){
                    $where['category_id']=['=',$categoryId];
                }
                if ($channel){
                    $where['channel']=['=',$channel];
                }
                $data=$model
                    ->where($where)
                    ->where([
                        'category_id'=>['<>',0],
                        'channel'=>['<>',''],
                    ])
                    ->group('time')
                    ->column('sum(view_total) as view_total,sum(like_total) as like_total,sum(comment_total) as comment_total,sum(share_total) as share_total','time');
            }else{
                //无筛选,查询每日总数据
                $data=$model
                    ->where($where)
                    ->where([
                        'category_id'=>['=',0],
                        'channel'=>['=',''],
                    ])
                    ->column('view_total,like_total,comment_total,share_total','time');
            }
            //补全数据
            $list=[];
            foreach ($dateRangeArray as $date){
                if (isset($data[strtotime($date)])){
                    $list[$date]=$data[strtotime($date)];
                }else{
                    if ($date==date('Y-m-d')){
                        //今天的数据从缓存取
                        $list[$date]=$model->getToday($categoryId,$channel);
                    }else{
                        //缺失的填充0
                        $list[$date]=[
                            'view_total'=>0,
                            'like_total'=>0,
                            'comment_total'=>0,
                            'share_total'=>0,
                        ];
                    }
                }
            }
            $columns=$dateRangeArray;
        }

        //格式化数据
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
                    'name'=>'分享',
                    'type'=>'line',
                    'stack'=>'总量',
                    'data'=>array_column($list,'share_total'),
                ],
            ],
            'columns'=>$columns,
        ];

        if ($this->request->isAjax()){
            $this->success('','',$stat);
        }else{
            //历史总数据
            $total=$model->getTotal();

            //分类列表
            /** @var \app\admin\model\Category $categoryModel */
            $categoryModel=model('Category');
            $categoryList=$categoryModel->getList('video');
            $categoryList[0]='选择分类';
            ksort($categoryList);

            $this->assign('total',$total);
            $this->assign('categoryList',$categoryList);
            $this->assignconfig('stat',$stat);
            return $this->view->fetch();
        }
    }
}