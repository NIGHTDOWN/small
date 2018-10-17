<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;

    protected $searchFields = ['nickname'];


    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
    }

    /**
     * 表格基础数据
     */
    public function tableBaseData()
    {
        $data=[];
        $data['status_list']=$this->model->getStatusList();
        $data['is_robot']=$this->model->getIsRobotList();
        /** @var \app\admin\model\UserGroup $group_model */
        $group_model=model('user_group');
        $data['group_list']=$group_model->getList();
        return $data;
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['burse','userGroup'])
                    ->where($where)
                    ->where('user.status','<>','-1')
                    ->order($sort, $order)
                    ->count();
            $list = $this->model
                    ->with(['burse','userGroup'])
                    ->where($where)
                    ->where('user.status','<>','-1')
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach ($list as $k => $v)
            {
                $v->hidden(['password','pay_password']);
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
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->allowField(['nickname','head_img','password','mobile','group_id','status'])->edit($params);
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
        $this->view->assign('status_list',$this->model->getStatusList());
        /** @var \app\admin\model\UserGroup $group_model */
        $group_model=model('user_group');
        $this->view->assign('group_list',$group_model->getList());
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $row = $this->model->get($ids);
            if (!$row)
                $this->error(__('No Results were found'));
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                if (!in_array($row[$this->dataLimitField], $adminIds)) {
                    $this->error(__('You have no permission'));
                }
            }
            $result = $row->del();
            if ($result) {
                $this->success();
            } else {
                $this->error('失败');
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
