<?php

namespace app\admin\controller\mission;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Mission extends Backend
{

    protected $modelSceneValidate=true;

    protected $modelValidate=true;

    protected $noNeedRight=['tableBaseData'];

    /**
     * Mission模型对象
     * @var \app\admin\model\Mission
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Mission;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function tableBaseData()
    {
        $data=[];
        $data['statusList']=$this->model->getStatusList();
        $data['repeatTypeList']=$this->model->getRepeatTypeList();
        return $data;
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
                    dump("AAA");
                    die();
                    $result = $this->model->allowField(['mission_group','title','mission_explain','mission_tag','repeat_type','bonus_setting','bonus_limit','quantity_condition','status'])->save($params);
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
        $this->assign('statusList',$this->model->getStatusList());
        $this->assign('repeatTypeList',$this->model->getRepeatTypeList());
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
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
                    $result = $row->allowField(['title','mission_explain','bonus_setting','bonus_limit','quantity_condition','status'])->save($params);
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
        $this->assign('statusList',$this->model->getStatusList());
        $this->assign('repeatTypeList',$this->model->getRepeatTypeList());
        return $this->view->fetch();
    }

    /**
     * 开启/关闭
     * @param null $ids
     * @param $action
     */
    public function onOff($ids = NULL,$action)
    {
        if ($this->request->isAjax()){
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
                $result = $row->allowField(true)->save(['status'=>$action?1:0]);
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
     * 更新缓存
     */
    public function updateCache()
    {
        if ($this->request->isAjax()){
            $ret=$this->model->updateCache();
            $this->success();
        }
    }
}
