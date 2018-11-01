<?php

namespace app\admin\controller\cash;

use app\common\controller\Backend;

use think\db;
/**
 *
 *
 * @icon fa fa-circle-o
 */
class Operate extends Backend
{

    /**
     * Withdraw模型对象
     * @var \app\admin\model\cash\Withdraw
     */
    protected $model = null;
    // 关联搜索
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\CashWithdraw();

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
            // 筛选状态
            $requestData = $this->request->param();
            $statusData = json_decode($requestData['filter'], true);
            if (isset($statusData['status']) && $statusData['status'] == \app\admin\model\CashWithdraw::STATUS['OPERATIVE']) {
                $statusData['status'] = [1, 2, 3, 4, 5, 6, 7];
                $requestData['filter'] = json_encode($statusData);
            }
            $this->request->get($requestData);
            // end

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with('user')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('user')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $error_msg_ids = array_column($list,'error_msg_id');
            if ($error_msg_ids) {
                $error_msgs = Db::name('cash_order_error')->where('id','IN',$error_msg_ids)->column('msg','order_id');
            }
            foreach ($list as $key => $value) {
                $value->checkbox = $value->status == \app\admin\model\CashWithdraw::STATUS['AUDITING'] ? false : true;
                $value->visible(['id', 'user_id', 'order_sn', 'apply_price', 'apply_time', 'status', 'payment','comment','error_msg_id']);
                $value->visible(['user']);
                $value->getRelation('user')->visible(['nickname']);

            }
            $list = collection($list)->toArray();
            $list = \app\admin\model\CashWithdraw::getStatusText($list);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function adopt()
    {
        if ($this->request->isPost()) {
            $ids = input('ids/a', []);
            $result = $this->model->operatorAdopt($ids, session('admin')['id']);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    public function refuse($ids)
    {
        if ($ids) {
            if ($this->request->isPost()) {
                $id = input('ids/a', []);
                $msg = input('msg/s', []);
                $type = input('type/d');

                $result = $this->model->refuse($id, session('admin')['id'], $msg, $type);
                if ($result) {
                    $this->success();
                } else {
                    $this->error($this->model->getError());
                }
            }
            $param = [
                'type' => \app\admin\model\FeedbackDefaultReply::TYPE['CASH'],
                'status' => \app\admin\model\FeedbackDefaultReply::STATUS['ENABLED']
            ];

            $defaultList = model('FeedbackDefaultReply')->getList($param);
            $this->view->assign('id', $ids);
            $this->view->assign('list', $defaultList);
            return $this->view->fetch();
        }

        $this->error('缺少ID');
    }

    /**
     * 默认文案
     *
     * @return [type] [description]
     */
    public function default_list()
    {
        if ($this->request->isPost()) {
            $param = [
                'type' => \app\admin\model\FeedbackDefaultReply::TYPE['CASH'],
                'status' => \app\admin\model\FeedbackDefaultReply::STATUS['ENABLED']
            ];
            $list = model('FeedbackDefaultReply')->getList($param);
            return $this->success('成功', '', $list);
        }

        $this->view->assign('module', $this->request->module());
        $this->view->assign('controller', str_replace('.', '/', $this->request->controller()));
        return $this->view->fetch();
    }

    /**
     * 添加提现文案
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $content = input('content');
            $model = model('FeedbackDefaultReply');
            $params = [
                'content' => $content,
                'type' => \app\admin\model\FeedbackDefaultReply::TYPE['CASH'],
            ];
            $result = $model->add($params);
            if ($result) {
                $this->success();
            } else {
                $this->error($model->getError());
            }
        }
    }

    /**
     * 更新文案
     *
     * @return [type] [description]
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $params = $this->request->only(['id', 'content']);
            $model = model('FeedbackDefaultReply');
            $result = $model->edit($params);
            if ($result) {
                $this->success();
            } else {
                $this->error($model->getError());
            }
        }
    }

    /**
     * 删除文案
     *
     * @return [type] [description]
     */
    public function delete()
    {
        if ($this->request->isPost()) {
            $id = input('post.id');
            $model = model('FeedbackDefaultReply');
            $result = $model->del($id);
            if ($result) {
                $this->success();
            } else {
                $this->error($model->getError());
            }
        }
    }
}
