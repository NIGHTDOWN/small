<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use think\Db;

/**
 * 视频发布计划
 *
 * @icon fa fa-circle-o
 */
class Putplan extends Backend
{
    
    /**
     * VideoPutPlan模型对象
     * @var \app\admin\model\VideoPutPlan
     */
    protected $model = null;


    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\VideoPutPlan;

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
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field(['id', 'title', 'status', 'plan_time', 'put_time', 'create_time'])
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($this->request->isPost()) {
            $id = input('post.ids');
            $result = $this->model->del($id);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    public function play($ids = '')
    {
        if ($ids) {
            $info = Db::name('VideoPutPlan')->where(['id' => $ids])->find();
            if (!$info) {
                $this->error('ID错误');
            }

            $palyUrl = $this->model->getVideoPayUrl($info['key'], $info['status']);
            $this->view->assign("palyUrl", $palyUrl);
            return $this->view->fetch();
        }
    }

    public function upload_relation_table()
    {
        if ($this->request->isPost()) {
            $file = input('post.file_name');
            /** @var \app\admin\model\VideoPutPlanUploadRecord $model */
            $model = model('VideoPutPlanUploadRecord');
            $result = $model->add(['file_name' => $file]);
            if ($result) {
                $this->success();
            } else {
                $this->error($model->getError());
            }
        }

        return $this->view->fetch();
    }

    public function set_param()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['interval_time_min', 'interval_time_max', 'start_time']);
            $ret = $this->model->setParam($data);
            if ($ret) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }

        $this->view->assign("data", $this->model::getParam());
        return $this->view->fetch();
    }

    /**
     * 批量开始
     */
    public function batch_start()
    {
        $ids = input('ids/a');
        $ret = $this->model->batchStart($ids);
        if ($ret) {
            $this->success();
        } else {
            $this->error($this->model->getError());
        }
    }

    /**
     * 批量取消
     */
    public function batch_cancel()
    {
        $ids = input('ids/a');
        $ret = $this->model->batchCancel($ids);
        if ($ret) {
            $this->success();
        } else {
            $this->error($this->model->getError());
        }
    }

}
