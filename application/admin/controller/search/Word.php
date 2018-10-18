<?php

namespace app\admin\controller\search;

use app\common\controller\Backend;

/**
 * 搜索词条
 *
 * @icon fa fa-circle-o
 */
class Word extends Backend
{

    /**
     * SearchWord模型对象
     * @var \app\admin\model\SearchWord
     */
    protected $model = null;

    protected $modelValidate=true;

    protected $searchFields=['word'];

    protected $multiFields=['order_sort','status'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('SearchWord');

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 表格基础数据
     */
    public function tableBaseData()
    {
        $data=[];
        $data['status_list']=$this->model->getStatusList();
        $data['order_sort_list']=$this->model->getOrderSortList();
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
                    $result = $this->model->allowField(true)->save($params);
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
        $this->assign('status_list',$this->model->getStatusList());
        $this->assign('order_sort_list',$this->model->getOrderSortList());
        return $this->view->fetch();
    }
}
