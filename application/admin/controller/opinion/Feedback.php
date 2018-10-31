<?php

namespace app\admin\controller\opinion;

use app\common\controller\Backend;

/**
 * 意见反馈
 *
 * @icon fa fa-circle-o
 */
class Feedback extends Backend
{
    
    /**
     * Feedback模型对象
     * @var \app\admin\model\opinion\Feedback
     */
    protected $model = null;
    // 关联搜索
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OpinionFeedback;

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
            $map = ['parent_id' => 0];
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with('user')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('user')
                ->where($where)
                ->where($map)
                ->order([
                    'opinion_feedback.reply_status' => 'ASC',
                    'opinion_feedback.create_time' => 'DESC'
                ])
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $key => $value) {
                 $value->visible(['id', 'content', 'create_time', 'reply_status']);
                $value->visible(['user.nickname']);
                $value->getRelation('user')->visible(['nickname']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 回复
     * 
     * @param  string $ids [description]
     * @return [type]      [description]
     */
    public function reply($ids = '')
    {
        if ($ids) {
            if ($this->request->isPost()) {
                // $this->success();
                $content = input('post.content/s', '');
                $image = input('post.image/s', '');
                $pid = input('post.parent_id/d', '');
                $user_id = \think\Session::get('admin.id');
                $result = $this->model->addFeedback($user_id, $content, $image, $pid);
                if ($result) {
                    $this->success();
                } else {
                    $this->error($this->model->getError());
                }
            }

            $param = [
                'type' => \app\admin\model\FeedbackDefaultReply::TYPE['FEEDBACK'],
                'status' => \app\admin\model\FeedbackDefaultReply::STATUS['ENABLED']
            ];
            $defaultList = model('FeedbackDefaultReply')->getList($param);
            $this->view->assign('default_list', $defaultList);
            $this->view->assign('parent_id', $ids);
            return $this->view->fetch();
        }
    }

    /**
     * 详情
     * 
     * @return [type] [description]
     */
    public function detail($ids)
    {
        if ($ids) {
            if ($this->request->isPost()) {
                $param = $this->request->only(['order_field','order_direction','limit','page']);
                $param['id'] = $ids;
                $data = $this->model->getDetail($param);
                if ($data) {
                    $this->success('成功', '', $data);
                } else {
                    $this->error($this->model->getError());
                }
            }

            $this->view->assign('ids', $ids);
            return $this->view->fetch();
        }
    }

    /**
     *  文案列表
     * 
     * @return [type] [description]
     */
    public function default_list()
    {
        if ($this->request->isPost()) {
            $param = [
                'type' => \app\admin\model\FeedbackDefaultReply::TYPE['FEEDBACK'],
                'status' => \app\admin\model\FeedbackDefaultReply::STATUS['ENABLED']
            ];
            $list = model('FeedbackDefaultReply')->getList($param);
            return $this->success('成功', '', $list);
        }
        $this->view->assign('module', $this->request->module());
        $this->view->assign('controller', str_replace('.', '/', $this->request->controller()));
        return $this->view->fetch('cash/operate/default_list');
    }

    /**
     * 添加文案
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $content = input('content');
            $model = model('FeedbackDefaultReply');
            $params = [
                'content' => $content,
                'type' => \app\admin\model\FeedbackDefaultReply::TYPE['FEEDBACK'],
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
     * 编辑文案
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
