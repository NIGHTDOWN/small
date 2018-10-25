<?php

namespace app\admin\controller\summary_sheet;

use app\common\controller\Backend;

/**
 * 新增数据统计管理
 *
 * @icon fa fa-circle-o
 */
class SummarySheet extends Backend
{

    /**
     * SummarySheet模型对象
     * @var \app\admin\model\SummarySheet
     */
    protected $model = null;

    /**
     * 类型
     * @var array
     */
    public $operate = [
        'activate' => '激活量',
        'register' => '注册量',
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\SummarySheet;

    }

//    /**
//     * 列表
//     * @return string|\think\response\Json
//     * @throws \think\Exception
//     */
//    public function index()
//    {
//        if ($this->request->isAjax()) {
//            // 展示列表图形
//            return $this->addEchart();
//
//            // TODO 这里用于导出
//            $model = model('SummarySheet');
//            // 搜索条件
//            $where = json_decode(input('filter'),  true);
//            if (isset($where['day_time'])) {
//                if (strpos($where['day_time'], ' - ') === false) {
//                    $this->error('时间格式不正确');
//                }
//                $where['day_time'] = explode(' - ', $where['day_time']);
//                $where['day_time'][0] = strtotime(date('Y-m-d 0:0:0', strtotime($where['day_time'][0])));
//                $where['day_time'][1] = strtotime(date('Y-m-d 23:59:59', strtotime($where['day_time'][1])));
//            }
//            // 数据
//            $list = $model->getList($where);
//
//            return json($list);
//        }
//        return $this->view->fetch('summarysheet/summarysheet/index');
//    }

    /**
     * 列表
     * @return string|\think\response\Json
     * @throws \think\Exception
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $model = model('SummarySheet');
            // 搜索条件
            $param = json_decode(input('filter'),  true);
            list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);
            // 数据
            $list = $model->getList($where, $field, $channel, $column, $timeData, $param);
            return json($list);
        }
        return $this->view->fetch('summarysheet/summarysheet/index');
    }

    /**
     * 导出
     */
    public function export()
    {
        $model = model('SummarySheet');
        // 搜索条件
        $param = json_decode(input('filter'),  true);
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);

        // 数据
        $field[] = 'sum(register) as register';
        $field[] = 'sum(activate) as activate';
        $field[] = 'sum(register_total) as register_total';
        $field[] = 'sum(activate_total) as activate_total';
        $lists = $model->listGroupDay($where, $field, $channel, $column, $timeData);

        // 整合导出数据
        $result = [];
        $dayArr = array_keys($lists);
        $result[] = ['日期', '注册量', '激活量', '总注册量', '总激活量'];
        if (isset($param['show_time']) && $param['show_time'] == 1) {
            $weekDay = $model->timeData($timeData);
        } else {
            $weekDay = $timeData;
        }
        foreach ($timeData as $k => $v) {
            if (!in_array($v, $dayArr)) {
                $temp = [$weekDay[$k], 0, 0, 0, 0];
            } else {
                $temp = [$weekDay[$k]];
                foreach ($lists as $lk => $lv) {
                    if ($v == $lk) {
                        $temp[] = array_sum(array_column($lv, 'register'));
                        $temp[] = array_sum(array_column($lv, 'activate'));
                        $temp[] = array_sum(array_column($lv, 'register_total'));
                        $temp[] = array_sum(array_column($lv, 'activate_total'));
                    }
                }
            }
            $result[] = $temp;
        }
        $model->export($result, '新增统计数据日报表.xls');
        exit;
    }

    /**
     * app版本列表
     * @return array
     */
    public function versionList()
    {
        return model('SummarySheet')->appVersionList();
    }

    /**
     * 渠道
     * @return array
     */
    public function channelList()
    {
        return model('SummarySheet')->channelList();
    }

    /**
     * 操作类型
     */
    public function operateType()
    {
        return $this->operate;
    }

}