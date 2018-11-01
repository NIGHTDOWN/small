<?php

namespace app\admin\controller\summarysheet;

use app\common\controller\Backend;
use think\Console;
use think\Db;

/**
 * 新增数据统计管理
 *
 * @icon fa fa-circle-o
 */
class Summarysheet extends Backend
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
        'active' => '启动量',
        'active_rate' => '活跃度',
        'wastage' => '流失用户',
        'wastage_rate' => '流失率'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\SummarySheet;

    }

    /**
     * 列表
     * @return string|\think\response\Json
     * @throws \think\Exception
     */
    public function index()
    {
        $model = model('SummarySheet');
        if ($this->request->isAjax()) {
            // 搜索条件
            $param = json_decode(input('filter'),  true);
            list($param, $field, $column, $channel, $where, $timeData) = $this->filter($param);
            // 数据
//            $list = $model->getList($where, $field, $channel, $column, $timeData, $param);
            $list = $model->activeChannelList(0, $where, $field, $channel, $column, $timeData, $param);
            if (isset($param['operate_type']) && strpos($param['operate_type'], 'rate') !== false) {
                $list['rows']['rate'] = '%';
            } else {
                $list['rows']['rate'] = '';
            }

            return json($list);
        }
        $data = $model->getRow('register_total, activate_total', 'create_time desc');
        $this->assign('data', $data);
        return $this->view->fetch('summarysheet/summarysheet/index');
    }

    /**
     * 导出
     */
    public function export()
    {
        $model = model('SummarySheet');
        // 搜索条件
        $param = json_decode(input('filter'), true);
        list($param, $field, $column, $channel, $where, $timeData) = $this->filter($param);

        $flag = input('flag') ?? 1;
        if ($flag == 2) {
            // 导出总报表
            if (isset($where['channel_id'])) unset($where['channel_id']);
            $model->exportAll($where, $timeData);
        } elseif ($flag == 3) {
            // 普通导出
            $model->exportList(
                $field,
                'day desc',
                'day',
                ['日期', $this->operate[$column]],
                [$column]
            );
        } else {
            // 按渠道导出
            $lists = $model->activeChannelList(1, $where, $field, $channel, $column, $timeData, $param);
            $dataArray = [];
            // 标题
            $dataObj = array_keys($lists);
            array_unshift($dataObj, '日期');
            // 行数据
            $dataArray[] = $dataObj;
            if (isset($param['show_time']) && $param['show_time'] == 2) {
                $timeType = '月';
            } elseif (isset($param['show_time']) && $param['show_time'] == 1) {
                $timeType = '周';
                $timeData = $model->timeData($timeData);
            } else {
                $timeType = '日';
            }
            // 组装数据
            foreach ($timeData as $k => $v) {
                $temp = [$v];
                foreach ($lists as $lk => $lv) {
                    $temp[] = $lv[$k];
                }
                $dataArray[] = $temp;
            }

            $model->export($dataArray, "渠道{$this->operate[$column]}{$timeType}报表.xls");
            exit;
        }
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

    /**
     * 用户数据统计计划任务
     */
    public function test()
    {
        Console::call('MachineOperateStatistics');
    }

    /**
     * 条件
     * @param $param
     * @return array
     */
    public function filter($param)
    {
        $model = model('SummarySheet');
        $where = [];
        // 搜索字段
        $param['operate_type'] = isset($param['operate_type']) ? $param['operate_type'] : 'active';
        if (strpos($param['operate_type'], 'rate')) {
            $field = ['max(' . $param['operate_type'] . ') as ' . $param['operate_type']];
        } else {
            $field = ['sum(' . $param['operate_type'] . ') as ' . $param['operate_type']];
        }
        $column = $param['operate_type'];

        // 展示方式:日周月
        $param['show_time'] = isset($param['show_time']) ? $param['show_time'] : 0;
        if (isset($param['show_time'])) {
            $field[] = 'FROM_UNIXTIME(day_time, ' . $model->showTime[$param['show_time']] . ') day';
        } else {
            $field[] = 'FROM_UNIXTIME(day_time, ' . $model->showTime[0] . ') day';
        }

        // 时间
        if (isset($param['day'])) {
            // 有时间筛选
            $where['day_time'] = $param['day'];
            if (strpos($where['day_time'], ' - ') === false) {
                $this->error('时间格式不正确');
            }
            $where['day_time'] = explode(' - ', $where['day_time']);
            $start = strtotime(date('Y-m-d 0:0:0', strtotime($where['day_time'][0])));
            $end = strtotime(date('Y-m-d 23:59:59', strtotime($where['day_time'][1])));
        } else {
            if ($param['show_time'] == 0) { // 天
                // 默认展示一周内的数据
                $start = strtotime(date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)));
                $end = $start + 24 * 60 * 60 * 7 - 1;
            } elseif ($param['show_time'] == 1) { // 周
                // 默认展示一个月内的数据
                $start = strtotime(date('Y-m'));
                $end = strtotime(date('Y-m') . ' +1 month -1 day');
            } else { // 月
                // 默认展示一个年内的数据
                $start = strtotime(date('Y').'-1-1');
                $end = strtotime(date('Y').'-12-31 23:59:59');
            }
        }
        $end > time() && $end =  time(); // 不能超过当前时间
        $timeData = get_day_in_range(
            [$start, $end],
            $model->showTimeFormat[$param['show_time']],
            $model->showTimeSec[$param['show_time']]
        );
        $where['day_time'] = ['between', [$start, $end]];

        // 渠道
        $channel = [];
        if (!empty($param['channel_id'])) {
            $where['channel_id'] = $param['channel_id'];
            $channel[] = $param['channel_id'];
        }
        return [$param, $field, $column, $channel, $where, $timeData];
    }
}