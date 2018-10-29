<?php

namespace app\admin\controller\app;

use app\common\controller\Backend;

use app\common\model\AppVersion;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Version extends Backend
{
    
    /**
     * Appversion模型对象
     * @var \app\admin\model\Appversion
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Appversion;

    }

    public function index()
    {
        $map  = [];

        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where('status' , 'neq', AppVersion::STATUS['DELETE'])
                ->order($sort, $order)
                ->count();
            $page_size = input('page_size/d', 20);
            $list = $this->model
                ->where($where)
                ->where('status' , 'neq', AppVersion::STATUS['DELETE'])
                ->order('id', 'desc')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $admin_ids = array_unique(array_filter(
                array_merge(
                    array_column($list, 'create_admin_id'),
                    array_column($list, 'last_mod_admin_id')
                )
            ));
            $admins = $this->model->getAdmin($admin_ids);

            foreach ($list as $k => $v) {
                $list[$k]['create_admin'] = $admins[$v['create_admin_id']] ?? '';
                $list[$k]['last_mod_admin'] = $admins[$v['last_mod_admin_id']] ?? '';
                $list[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i', $v['create_time']) : '';
                $list[$k]['update_time'] = $v['update_time'] ? date('Y-m-d H:i', $v['update_time']) : '';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     * @param int $ids 用户id
     * @return string
     */
    public function edit($ids=null)
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
                    $params['id']=$ids;
                    //数据验证
                    $result = $row->edit($params);
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
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $this->model->validate($validate);
                    }

                    $result = $this->model->add($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $count = $this->model->del($ids);

            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}
