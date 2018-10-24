<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Coin extends Backend
{
    
    /**
     * UserCoin模型对象
     * @var \app\admin\model\UserCoin
     */
    protected $model = null;

    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\UserCoin;

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
            // 筛选时间格式化
            $timeData = input('get.filter');
            $end_time = '';
            $start_time  = '';
            if (!strlen($timeData) > 3) {
                $timeData = json_decode($timeData, true)['create_time'];
                $timeData = explode(' - ', $timeData);
                $start_time = $timeData[0];
                $end_time = $timeData[1];
            }
            // end
            
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with('user')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('user')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $key => $value) {
                $type = $value->type == 1 ? '+' : '-';
                $value->amount = $type . $value->amount;
                $value->visible(['id', 'amount', 'reason', 'create_time', 'update_time', 'status']);
                $value->visible(['user']);
                $value->getRelation('user')->visible(['nickname']);
            }

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        
        // 金币统计
        $goldTotal = $this->goldTotal();
        $data = [
            'coin_user_total' => \app\common\model\CoreValue::getCoinValue('coin_total'), // 用户持有金币数
            'raise_coin_total' => $goldTotal['statistics_total'], // 产生金币数
            'consume_total' => $goldTotal['consume_total'], // 消费金币数
            'withdraw_total' => $goldTotal['withdraw_total'], // 提现金币数 
        ];
        $this->view->assign('data', $data);
        return $this->view->fetch();
    }

    public function set_param()
    {
        $data = [];
        // 获取金币价值
        $data['coin_to_price'] = \app\common\model\CoreValue::getCoinToPrice();
        // 获取首次提现最低金额
        $data['first_coin_to_price'] = \app\common\model\CoreValue::getFirstCoinToPrice();
        // 获取后续提现最低金额
        $data['normal_coin_to_price'] = \app\common\model\CoreValue::getNormalCoinToPrice();
        // 获取当月可提现总额
        $data['total_coin_to_price'] = \app\common\model\CoreValue::getTotalCoinToPrice();
        // 获取当月最大提现次数
        $data['total_coin_to_price_num'] = \app\common\model\CoreValue::getTotalCoinToPriceNum();
        // 获取需绑定身份证提现金额
        $data['need_id_card_price'] = \app\common\model\CoreValue::getNeedIdCardPrice();
        // 获取延迟到账时间
        $data['delay_pay'] = \app\common\model\CoreValue::getDelayPay();

        $this->view->assign('data', $data);
        return $this->view->fetch();
    }

    /**
     * 设置金币价值//一元兑换xxxx金币
     */
    public function setCoinToPrice()
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['coin']);
            $result = $this->model->setCoinToPrice($param);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    /**
     * 设置首次提现最低金额
     */
    public function setFirstCoinToPrice()
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['price']);
            $result = $this->model->setFirstCoinToPrice($param);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    /**
     * 设置后续提现最低金额
     */
    public function setNormalCoinToPrice()
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['price']);
            $result = $this->model->setNormalCoinToPrice($param);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    /**
     * 设置可提现总额
     */
    public function setTotalCoinToPrice()
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['price']);
            $result = $this->model->setTotalCoinToPrice($param);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    /**
     * 设置每月可提现次数
     */
    public function setTotalCoinToPriceNum()
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['price']);
            $result = $this->model->setTotalCoinToPriceNum($param);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    /**
     * 设置xx元需填身份证
     */
    public function setNeedIdCardPrice()
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['price']);
            $result = $this->model->setNeedIdCardPrice($param);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    /**
     * 设置延迟到账时间
     */
    public function setDelayPay(){
        if ($this->request->isPost()) {
            $param = $this->request->only(['delay']);
            $result = $this->model->setDelayPay($param);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    /**
     * 获取金币统计
     */
    protected function goldTotal($start_time = '', $end_time = '')
    {
        $param = [];
        // 时间段
        $param['start_time'] = strtotime($start_time) ?: '';
        $param['end_time'] = strtotime($end_time) ?: '';

        // 消费金币数
        $consumeTotal = $this->model->getConsumeTotal($param);
        if ($consumeTotal === false) {
            $this->error($this->model->getError());
        }

        // 提现金币数
        $withdrawModel = model('CashWithdraw');
        $withdrawTotal = $withdrawModel->getWithdrawTotal($param);
        if ($withdrawTotal === false) {
            $this->error($withdrawModel->getError());
        }

        // 历史产生金币总数: 可根据时间段筛选
        $statisticsModel = model('UserCoinStatistics');
        $statisticsTotal = $statisticsModel->getCoinStatistics($param);
        if ($statisticsTotal === false) {
            $this->error($statisticsModel->getError());
        }

        return [
            'consume_total' => $consumeTotal,
            'withdraw_total' => $withdrawTotal,
            'statistics_total' => $statisticsTotal
        ];
    }

}
