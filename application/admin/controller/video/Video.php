<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Video extends Backend
{
    
    /**
     * Video模型对象
     * @var \app\admin\model\Video
     */
    protected $model = null;
    // 关联搜索
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Video;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 活动筛选项
            $map = [];
            $activityId = input('activity_id');
            if (!empty($activityId)) {
                $ids = Db::name('activity_top_data')->where(['activity_id' => $activityId])->column('video_id');
                $map['video.id'] = ['in', $ids];
            }

            // 排序
            ! empty($sort) && strpos($sort, 'video.') === false && $sort = 'video.' . $sort;

            $total = $this->model
                ->with('user')
                ->where($where)
                ->where('video.status', '<>', $this->model::$status['DELETE'])
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('user')
                ->where($where)
                ->where('video.status', '<>', $this->model::$status['DELETE'])
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $key => $value) {
                $value->visible(['id', 'title', 'category_id', 'user_view_total', 'user_like_total', 'user_comment_total', 'create_time', 'update_time', 'process_done_time', 'status']);
                $value->visible(['user']);
                $value->getRelation('user')->visible(['nickname']);
            }

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function categoryList()
    {
        return json(\app\common\model\Category::searchSelect());
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $this->error(__('滚犊子吧，删什么删'));
    }

    /**
     * 设置视频分类
     * 
     * @param string $ids 视频ID
     */
    public function set_category($ids = "")
    {
        if ($ids) {
            $row = Db::name('video')->where(['id' => $ids])->find();
            if ($this->request->isPost()) {
                $params = $this->request->post("row/a");
                if (!isset($params['category_id']) || !is_numeric($params['category_id'])) {
                    $this->error(__('参数错误'));
                }
                $data = [
                    'update_time' => time(),
                    'category_id' => $params['category_id']
                ];
                Db::name('video')->where(['id' => $row['id']])->update($data);
                // 推荐操作
                // if ($row['recommend']){
                //     if ($row['category_id']!=$params['category_id']){
                //         self::delTopVideoFromCache($params['video_id'],$row['category_id']);
                //     }
                //     self::addTopVideoToCache($params['video_id'],$params['category_id']);
                // }
                $this->success();
            }

            $categoryList = Db::name('category')->where(['status' => 1])->select();
            $this->view->assign("row", $row);
            $this->view->assign("category_list", $categoryList);
            return $this->view->fetch();
        }
    }

    /**
     * 审核视频
     * 
     * @param  string $ids [description]
     * @return [type]      [description]
     */
    public function check_video($ids = "")
    {
        if ($ids) {
            $row = Db::name('video')->where(['id' => $ids])->find();
            if ($this->request->isPost()) {
                $params = $this->request->post("row/a");
                $result = $this->model->checkVideo($row['id'], $params['stauts'], $params['remark']);
                if ($result) {
                    $this->success();
                } else {
                    $this->error($this->model->getError());
                }
            }

            $this->view->assign("row", $row);
            return $this->view->fetch();
        }
    }

    /**
     * 添加点赞数
     * 
     * @param string $ids [description]
     */
    public function add_like_total($ids = "")
    {
        if ($ids) {
            $row = Db::name('video')->where(['id' => $ids])->find();
            if ($this->request->isPost()) {
                $params = $this->request->post("number");
                $this->success();
            }

            $this->view->assign("row", $row);
            return $this->view->fetch();
        }
    }

    /**
     * 设置标题
     * 
     * @param string $ids [description]
     */
    public function set_title($ids = "")
    {
        if ($ids) {
            $row = Db::name('video')->where(['id' => $ids])->find();
            if ($this->request->isPost()) {
                $title = $this->request->post("title");
                $data = [
                    'title' => $title,
                    'video_id' => $row['id']
                ];
                $result = $this->model->editTitle($data);
                if ($result) {
                    $this->success();
                } else {
                    $this->error($this->model->getError());
                }
            }

            $this->view->assign("row", $row);
            return $this->view->fetch();
        }
    }

    /**
     * 播放视频
     * 
     * @param  string $ids [description]
     * @return [type]      [description]
     */
    public function play($ids = "")
    {
        if ($ids) {
            $row = Db::name('video')->where(['id' => $ids])->find();
            if (!$row) {
                $this->error('视频Id错误');
            }
            $play_url = \app\admin\model\VideoPutPlan::getVideoPayUrl($row['key'], $row['status']);
            $this->view->assign("play_url", $play_url);
            return $this->view->fetch();
        }
    }

    /**
     * 上架
     * 
     * @return [type] [description]
     */
    public function show()
    {
        $this->error();
    }

    /**
     * 下架
     * 
     * @return [type] [description]
     */
    public function hide()
    {
        $this->success();
    }

    public function host()
    {
        $this->success();
    }

    public function unhost()
    {
        $this->success();
    }

    public function edit_cover_img($ids = '')
    {
        if ($ids) {
            if ($this->request->isPost()) {
                $this->success();
            }
            return $this->view->fetch();
        }
    }

    public function aaa_bbb()
    {
        return $this->view->fetch();
    }
}
