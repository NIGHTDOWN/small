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
//        list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);
        if (isset($param['show_time'])) unset($param['show_time']);
        if (isset($param['operate_type'])) unset($param['operate_type']);

        // 数据
        // 日启动量
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter(
            array_merge($param, ['show_time' => 0]));
        $list['active'] = $model->activeList(1, $where, $field, $channel, $column, $timeData);
        // 日活跃度
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter(
            array_merge($param, ['show_time' => 0, 'operate_type' => 'active_rate']));
        $list['active_rate'] = $model->activeList(1, $where, $field, $channel, $column, $timeData);
        // 周活跃度
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter(
            array_merge($param, ['show_time' => 1, 'operate_type' => 'active_rate']));
        dump($timeData);exit;
        dump($list);exit;
    }

    /**
     * 操作类型
     */
    public function operateType()
    {
        return $this->operate;
    }

}
