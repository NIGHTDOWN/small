<?php

namespace app\admin\controller\cash;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Withdraw extends Backend
{
    
    /**
     * CashWithdraw模型对象
     * @var \app\admin\model\CashWithdraw
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\CashWithdraw;

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
        // 设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $map = [
                'cash_withdraw.status' => ['not in', [0, 2]]
            ];
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // $total = $this->model
            //     ->with('user')
            //     ->where($where)
            //     ->where($map)
            //     ->order($sort, $order)
            //     ->count();

            // $list = $this->model
            //     ->with('user')
            //     ->where($where)
            //     ->where($map)
            //     ->order($sort, $order)
            //     ->limit($offset, $limit)
            //     ->select();

            $total = Db::name('CashWithdraw')
                ->alias('cash_withdraw')
                ->join('user', 'user.id = cash_withdraw.user_id', 'LEFT')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = Db::name('CashWithdraw')
                ->alias('cash_withdraw')
                ->join('user', 'user.id = cash_withdraw.user_id', 'LEFT')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

}
