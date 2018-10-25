<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;

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

    protected $relationSearch = true;

    protected $modelValidate = true;

    protected $modelSceneValidate = true;

    protected $noNeedRight=['tableBaseData'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Video');
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 获取表格基础数据
     */
    public function tableBaseData()
    {
        $data = [];
        $data['statusList'] = $this->model->getStatusList();
        /** @var \app\admin\model\Category $categoryModel */
        $categoryModel = model('Category');
        $categoryList = $categoryModel->getList('video');
        $categoryList[0] = '未设置';
        $data['categoryList'] = $categoryList;
        return $data;
    }


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

            $total = $this->model
                ->with(['extend', 'user', 'subjects','hotvideo'])
                ->where($where)
                ->where('video.status', '<>', -1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['extend', 'user', 'subjects','hotvideo'])
                ->where($where)
                ->where('video.status', '<>', -1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑分类
     */
    public function editCategory($ids = "")
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.setCategory' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->editCategory($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        /** @var \app\admin\model\Category $categoryModel */
        $categoryModel = model('Category');
        $this->view->assign("categoryList", $categoryModel->getList('video'));
        return $this->view->fetch();
    }

    /**
     * 设置标题
     */
    public function editTitle($ids = "")
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.editTitle' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->editTitle($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 编辑封面
     * @param string $ids
     * @return string
     */
    public function editCoverImg($ids = "")
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $cover_imgs = $this->request->post("cover_imgs/s");
            if ($cover_imgs) {
                try {
                    $result = $row->editCoverImg($cover_imgs);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 增加点赞
     */
    public function addLike($ids = "")
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $number = $this->request->post("number/d");
            if ($number) {
                try {
                    $result = $row->addLike($number);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 播放
     */
    public function play($ids = "")
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 审核通过
     * @param string $ids
     * @return string
     */
    public function checkPass($ids = "")
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (!$row)
                $this->error(__('No Results were found'));
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                if (!in_array($row[$this->dataLimitField], $adminIds)) {
                    $this->error(__('You have no permission'));
                }
            }
            try {
                $result = $row->checkPass();
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($row->getError());
                }
            } catch (\think\exception\PDOException $e) {
                $this->error($e->getMessage());
            } catch (\think\Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * 审核不通过
     * @param string $ids
     * @return string
     */
    public function checkNoPass($ids = "")
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $remark = $this->request->post("remark/s");
            if ($remark) {
                try {
                    $result = $row->checkNoPass($remark);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 上架
     * @param string $ids
     */
    public function show($ids = "")
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (!$row)
                $this->error(__('No Results were found'));
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                if (!in_array($row[$this->dataLimitField], $adminIds)) {
                    $this->error(__('You have no permission'));
                }
            }
            try {
                $result = $row->show();
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($row->getError());
                }
            } catch (\think\exception\PDOException $e) {
                $this->error($e->getMessage());
            } catch (\think\Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * 下架
     * @param string $ids
     * @return string
     */
    public function hide($ids = "")
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (!$row)
                $this->error(__('No Results were found'));
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                if (!in_array($row[$this->dataLimitField], $adminIds)) {
                    $this->error(__('You have no permission'));
                }
            }
            try {
                $result = $row->hide();
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($row->getError());
                }
            } catch (\think\exception\PDOException $e) {
                $this->error($e->getMessage());
            } catch (\think\Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $remark = $this->request->post("remark/s");
            if ($remark) {
                try {
                    $result = $row->del($remark);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 置顶
     * @param string $ids
     * @param $action
     */
    public function top($ids = "", $action)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (!$row)
                $this->error(__('No Results were found'));
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                if (!in_array($row[$this->dataLimitField], $adminIds)) {
                    $this->error(__('You have no permission'));
                }
            }
            try {
                $result = $row->top($action);
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($row->getError());
                }
            } catch (\think\exception\PDOException $e) {
                $this->error($e->getMessage());
            } catch (\think\Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * 热门
     * @param string $ids
     * @param $action
     */
    public function hot($ids = "", $action)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if (!$row)
                $this->error(__('No Results were found'));
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                if (!in_array($row[$this->dataLimitField], $adminIds)) {
                    $this->error(__('You have no permission'));
                }
            }
            try {
                $result = $row->hot($action);
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($row->getError());
                }
            } catch (\think\exception\PDOException $e) {
                $this->error($e->getMessage());
            } catch (\think\Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * 统计
     */
    public function statistics()
    {
        $data=$this->model
            ->field([
                'count(id) as history_upload_total',
                'sum(user_view_total) as history_view_total',
                'sum(user_like_total) as history_like_total',
                'sum(user_comment_total) as history_comment_total',
            ])
            ->find();
        /** @var \app\admin\model\VideoComment $commentModel */
        $commentModel=model('VideoComment');
        /** @var \app\admin\model\UserVideoLike $likeModel */
        $likeModel=model('UserVideoLike');
        $data['today_upload_total']=$this->model->getTodayUploadTotal();
        $data['today_view_total']=$this->model->getTodayViewTotal();
        $data['today_like_total']=$likeModel->getTodayTotal();
        $data['today_comment_total']=$commentModel->getTodayTotal();
        $this->view->assign("data", $data);
        return $this->view->fetch();
    }
}
