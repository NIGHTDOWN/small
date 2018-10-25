<?php

namespace app\admin\controller\summary_sheet;

use app\common\controller\Backend;
use think\Db;

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

        // 默认导出单位为天, 渠道不选
        $param['show_time'] = 0;
        if (isset($param['channel_id'])) {
            unset($param['channel_id']);
        }
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);

        unset($field[0]);
        if (isset($param['show_time']) && $param['show_time'] == 1) { // 周导出
            // 数据
            $field[] = 'max(week_register) as register';
            $field[] = 'max(week_activate) as activate';
        } elseif (isset($param['show_time']) && $param['show_time'] == 2) { // 月导出
            // 数据
            $field[] = 'max(month_register) as register';
            $field[] = 'max(month_activate) as activate';
        } else {
            // 数据
            $field[] = 'sum(register) as register';
            $field[] = 'sum(activate) as activate';
        }
        $field[] = 'max(register_total) as register_total';
        $field[] = 'max(activate_total) as activate_total';
        $list = $model->summaryList($field, $where, 'day desc', 'day');
        // 按天分组
        $data = [];
        foreach ($timeData as $v => $k) {
            foreach ($list as $key => $val) {
                if ($val['day'] == $k) {
                    $data[$k] = $val;
                }
            }
        }

        // 整合导出数据
        $result = [];
        $result[] = ['日期', '注册量', '激活量', '总注册量', '总激活量'];
        $dayArr = array_keys($data);
        if (isset($param['show_time']) && $param['show_time'] == 1) {
            $weekDay = $model->timeData($timeData);
        } else {
            $weekDay = $timeData;
        }
        foreach ($timeData as $k => $v) {
            if (!in_array($v, $dayArr)) {
                $temp = [$weekDay[$k], 0, 0, 0, 0];
            } else {
                $temp = [
                    $weekDay[$k],
                    $data[$v]['register'],
                    $data[$v]['activate'],
                    $data[$v]['register_total'],
                    $data[$v]['activate_total']
                ];
            }
            $result[] = $temp;
        }
        $model->export($result, '新增统计数据表.xls');
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