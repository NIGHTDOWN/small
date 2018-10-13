<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Activity as CommonActivity;

/**
 * 活动
 *
 * @icon fa fa-circle-o
 */
class Activity extends Backend
{

    /**
     * Activity模型对象
     * @var \app\admin\model\Activity
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Activity;

    }

    /**
     * 列表
     */
    public function index()
    {
//        $param = $this->request->request(['page' => 1, 'page_size' => 20, 'order_direction' => 1, 'order_field' => 'id', 'keyword' => '']);

//        // 校验数据 TODO
//        $valRes = $this->validate($param, 'Activity.list');
//        if ($valRes !== true) {
//            $this->error($valRes);
//        }
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $param = [];
            $param['order_field'] = $sort;
            $param['order_direction'] = $order;
            $param['offset'] = $offset;
            $param['page_size'] = $limit;
            // 列表
            $model = model('Activity');
            $list = $model->getList($param);
            $result = array("total" => $list['total'], "rows" => $list['data']);
            return json($result);
        }

        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['title', 'activity_details', 'share_details', 'start_time', 'end_time',
                'subject_id', 'reward_setting', 'order_sort', 'activity_rule', 'cover_image', 'image']);
            $param['image'] = !empty($param['image']) ? explode(',', $param['image']) : [];
            $param['create_admin_id'] = $this->auth->getUserinfo()['id'];

            // TODO 关联主题和奖励方案未完成

            // 数据校验 TODO
//        $checkRes = $this->validate($param, 'Activity.add');
//        if ($checkRes !== true) {
//            $this->error($checkRes);
//        }

            if (!empty($param)) {
                $model = model('Activity');
                $result = $model->add($param);
                if (!$result) {
                    $this->error($model->getError());
                } else {
                    $this->success();
                }
            }
        }
//        $this->assign('');
        return $this->fetch();
    }

    /**
     * 删除
     * @param string $ids
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function del($ids = "")
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        $model = model('Activity');
        $result = $model->del($ids, $this->auth->getUserInfo()['id']);
        if (!$result) {
            $this->error(__($model->getError()));
        } else {
            $this->success();
        }
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function edit($ids = NULL)
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $param = $this->request->only(['title', 'activity_details', 'share_details', 'start_time', 'end_time',
                'subject_id', 'reward_setting', 'order_sort', 'activity_rule', 'cover_image', 'image']);
            $param['image'] = !empty($param['image']) ? explode(',', $param['image']) : [];

            // 数据校验 TODO
//        $checkRes = $this->validate($param, 'Activity.add');
//        if ($checkRes !== true) {
//            $this->error($checkRes);
//        }

            if (!empty($param)) {
                $param['id'] = $ids;
                $param['update_time'] = time();
                $param['last_edit_admin_id'] = $this->auth->getUserInfo()['id'];
                $param['image'] = serialize($param['image']);
                $param['reward_setting'] = isset($param['reward_setting']) ? json_encode($param['reward_setting']) : '';
                $model = model('Activity');
                $result = $model->edit($param);
                if (!$result) {
                    $this->error($model->getError());
                } else {
                    $this->success();
                }
            }
        }

        // 查询该活动信息
        $model = model('Activity');
        $row = $model->getRow($ids);
        if (!$row) {
            $this->error(__($model->getError()));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 主题选择列表
     */
    public function subjectSelectList()
    {
        $param = $this->request->only(['page' => 1, 'page_size' => 20, 'keyword' => '']);
        $data = model('Subject')->selectList($param);
        $this->apiReturn($data);
    }

    /**
     * 编辑排序 TODO 方法名
     * @param null $ids
     * @return string
     * @throws \think\Exception
     */
    public function edit_sort($ids = null)
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['order_sort']);
            if (!$ids || !isset($param['order_sort'])) {
                $this->error(__('Invalid parameters'));
            }
            $param['id'] = $ids;
            $model = model('Activity');
            $param['update_time'] = time();
            $param['last_edit_admin_id'] = $this->auth->getUserInfo()['id'];
            $model->editSort($param);
            $this->success();
        }
        return $this->view->fetch();
    }

    /**
     * 隐藏
     * @param $ids
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function hide($ids)
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        $model = model('Activity');
        $data = [
            'status' => CommonActivity::$status['HIDE'],
            'last_edit_admin_id' => $this->auth->getUserInfo()['id'],
            'update_time' => time(),
        ];
        $res = $model->hide($ids, $data);
        if ($res) {
            $this->success();
        } else {
            $this->error(__($model->getError()));
        }
    }

    /**
     * 显示
     * @param $ids
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function show($ids)
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        $data = [
            'last_edit_admin_id' => $this->auth->getUserInfo()['id'],
            'update_time' => time(),
            'status' => CommonActivity::$status['DISPLAY']
        ];
        $model = model('Activity');
        $res = $model->show($ids, $data);
        if ($res) {
            $this->success();
        } else {
            $this->error(__($model->getError()));
        }
    }
}
