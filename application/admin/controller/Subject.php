<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Subject extends Backend
{

    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'subject_name';

    /**
     * Subject模型对象
     * @var \app\admin\model\Subject
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Subject;

    }

    /**
     * 列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            // 通用搜索中subject_name应为like

            $param = [];
            $param['order_field'] = $sort;
            $param['order_direction'] = $order;
            $param['offset'] = $offset;
            $param['page_size'] = $limit;

            $model = model('Subject');
            $list = $model->getList($param, $where);
            if ($list) {
                return json($list);
            } else {
                $this->error($model->getError());
            }
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['subject_name', 'status']);
            // 校验
            $valRes = $this->validate($data, 'Subject.add');
            if ($valRes !== true) {
                $this->error($valRes);
            }
            // 添加
            $model = model('Subject');
            $res = $model->add($data);
            if (!$res) {
                $this->error($model->getError());
            } else {
                $this->success();
            }
        }

        $status = model('Subject')::$statusText;
        unset($status['-1']);
        $this->assign('status', $status);
        return $this->view->fetch();
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($ids = NULL)
    {
        if (empty($ids)) {
            $this->error(__('Invalid parameter'));
        }
        $model = model('Subject');
        if ($this->request->isPost()) {
            $data = $this->request->only(['subject_name', 'status']);

            // 校验
            $valRes = $this->validate($data, 'Subject.edit');
            if ($valRes !== true) {
                $this->error($valRes);
            }
            // 更新
            $res = $model->edit($data, $ids);
            if (!$res) {
                $this->error($model->getError());
            } else {
                $this->success();
            }
        }
        $data = $model->getRow($ids);
        $status = model('Subject')::$statusText;
        unset($status['-1']);
        $this->assign('status', $status);
        $this->assign('row', $data);
        return $this->view->fetch();
    }


}
