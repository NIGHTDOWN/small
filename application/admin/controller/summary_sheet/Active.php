<?php

namespace app\admin\controller\summary_sheet;

use app\common\controller\Backend;
use think\Console;
use think\Db;

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
            $list = $model->getList($where, $field, $channel, $column, $timeData, $param);
            return json($list);
        }
        return $this->fetch('summarysheet/active/index');
    }

    /**
     * 导出 TODO 优化
     */
    public function export()
    {
//        Console::call('MachineOperateStatistics');exit;
        $model = model('SummarySheet');
        $model->exportList(
            "ss.id, sum(active) as active, max(active_rate) as active_rate,
            max(week_active_rate) as week_active_rate, max(month_active_rate) as month_active_rate,
            FROM_UNIXTIME(day_time, '%Y-%m-%d') as day",
            'day desc',
            'day',
            ['日期', '日启动量', '日活跃度', '周活跃度', '月活跃度'],
            ['active', 'active_rate', 'week_active_rate', 'month_active_rate'],
            '活跃数据表.xls'
        );
    }

    /**
     * 操作类型
     */
    public function operateType()
    {
        return $this->operate;
    }

}
