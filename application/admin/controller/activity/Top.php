<?php

namespace app\admin\controller\activity;

use app\common\controller\Backend;
use app\common\model\Activity as CommonActivity;

/**
 * 活动排行
 *
 * @icon fa fa-circle-o
 */
class Top extends Backend
{
    
    /**
     * ActivityTop模型对象
     * @var \app\admin\model\ActivityTop
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ActivityTop;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 活动排行榜
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $ids = input('ids');

//        if ($this->request->isAjax()) {
//            // 列表
//            $model = model('ActivityTop');
//            $rows = $model->getTop($ids);
//            $result = ["rows" => $rows];
//            return json($result);
//        }

        if (empty($ids)) {
            $this->error(__('Invalid parameters'));
        }
        // 列表
        $model = model('ActivityTop');
        $data = $model->getTop($ids);

        $this->view->assign('data', $data);
        return $this->view->fetch();
    }

}
