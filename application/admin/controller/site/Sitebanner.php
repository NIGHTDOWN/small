<?php

namespace app\admin\controller\site;

use app\admin\model\SiteBannerType;
use app\common\controller\Backend;

/**
 * 官网轮播图
 *
 * @icon fa fa-circle-o
 */
class SiteBanner extends Backend
{
    
    /**
     * SiteBanner模型对象
     * @var \app\admin\model\SiteBanner
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\SiteBanner;
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
                ->where($where)
                ->where('status','neq',-1)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('SiteBannerType')
                ->where($where)
                ->where('status','neq',-1)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key =>$value){
                $list[$key]['status_text']  =$this->model::STATUS_TXET[$value['status']];
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

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
        $this->assign('type_list',$this->allType());
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
        $this->assign('type_list',$this->allType());
        $this->assign('status_list',$this->model::STATUS_TXET);
        $this->assign('client_list',$this->model::CLIENT_TYPE_TEXT);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 所有类型
     */
    public function allType()
    {
        $model=model('SiteBannerType');
        $data=$model->allType();
        return $data;
    }

    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}
