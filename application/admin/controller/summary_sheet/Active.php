<?php

namespace app\admin\controller\summary_sheet;

use app\common\controller\Backend;
use think\Console;

/**
 * 新增数据统计管理
 *
 * @icon fa fa-circle-o
 */
class Active extends Backend
{
    /**
     * Sheet模型对象
     * @var \app\admin\model\summary\Sheet
     */
    protected $model = null;

    /**
     * 类型
     * @var array
     */
    public $operate = [
        'active' => '启动量',
        'active_rate' => '活跃度',
        'wastage' => '流失用户',
        'wastage_rate' => '流失率'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\summary\Sheet;
    }

    /**
     * 列表页
     */
    public function index()
    {
//        Console::call('MachineOperateStatistics');exit;
        if ($this->request->isAjax()) {
            $model = model('SummarySheet');
            // 搜索条件
            $param = json_decode(input('filter'),  true);
            list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);
            // 数据
            $list = $model->activeList(0, $where, $field, $channel, $column, $timeData);
            return json($list);
        }
        return $this->fetch('summarysheet/active/index');
    }

    /**
     * 导出
     */
    public function export()
    {
        $model = model('SummarySheet');
        // 搜索条件
        $param = json_decode(input('filter'),  true);
        if (isset($param['show_time'])) unset($param['show_time']);
        if (isset($param['operate_type'])) unset($param['operate_type']);

        // 日启动量和日活跃度
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter(
            array_merge($param, ['show_time' => 0]));
        $field[] = 'sum(active_rate) as active_rate';
        $list['active'] = $model->activeList(1, $where, $field, $channel, $column, $timeData);
        $time = $timeData;
        // 周活跃度
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter(
            array_merge($param, ['show_time' => 1, 'operate_type' => 'active_rate']));
        $list['week_active_rate'] = $model->activeList(1, $where, $field, $channel, $column, $timeData);
        $weekActiveRate = $monthActiveRate = [];
        foreach ($list['week_active_rate'] as $k => $v) {
            // 具体到每个日期，方便下面遍历取值
            $weekRange = week_range(explode('-', $k)[1]);
            $weekDay = get_day_in_range($weekRange, 'Y-m-d', 24 * 60 *60);
            foreach ($weekDay as $wk => $wv) {
                $weekActiveRate[$wv] = array_sum(array_column($v, 'active_rate'));
            }
        }
        // 月活跃度
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter(
            array_merge($param, ['show_time' => 2, 'operate_type' => 'active_rate']));
        $list['month_active_rate'] = $model->activeList(1, $where, $field, $channel, $column, $timeData);
        foreach ($list['month_active_rate'] as $k => $v) {
            $start = strtotime($k . '-01');
            $end =  strtotime(date('Y-m-d 23:59:59', $start) . "+1 month -1 day");
            $monthDay = get_day_in_range([$start, $end], 'Y-m-d', 24 * 60 *60);
            foreach ($monthDay as $mk => $mv) {
                $monthActiveRate[$mv] = array_sum(array_column($v, 'active_rate'));
            }
        }
        // 整合导出数据
        $result = [];
        $result[] = ['日期', '日启动量', '日活跃度', '周活跃度', '月活跃度'];
        $keyArr = array_keys($list['active']);
        foreach ($time as $v => $k) {
            $temp = [$k];
            if (!in_array($k, $keyArr)) {
                // 当天没有数据
                $temp[] = 0;
                $temp[] = 0;
            } else {
                $temp[] = array_sum(array_column($list['active'][$k], 'active'));
                $temp[] = array_sum(array_column($list['active'][$k], 'active_rate'));
            }
            $temp[] = $weekActiveRate[$k] ?? 0;
            $temp[] = $monthActiveRate[$k] ?? 0;
            $result[] = $temp;
        }
        $model->export($result, '活跃数据表.xls');
    }

    /**
     * 操作类型
     */
    public function operateType()
    {
        return $this->operate;
    }

}
