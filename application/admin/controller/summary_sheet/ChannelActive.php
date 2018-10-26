<?php

namespace app\admin\controller\summary_sheet;

use app\common\controller\Backend;

/**
 * 新增数据统计管理
 *
 * @icon fa fa-circle-o
 */
class ChannelActive extends Backend
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
        $this->model = new \app\admin\model\summary\Sheet;

    }

    /**
     * 列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $model = model('SummarySheet');
            // 搜索条件
            $param = json_decode(input('filter'), true);
            list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);
            // 数据
            $list = $model->activeChannelList(0, $where, $field, $channel, $column, $timeData);
            return json($list);
        }
        return $this->fetch('summarysheet/channelactive/index');
    }

    /**
     * 导出
     */
    public function export()
    {
        $model = model('SummarySheet');
        // 搜索条件
        $param = json_decode(input('filter'), true);
        if (isset($param['show_time'])) unset($param['show_time']);
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);
        // 数据
        $lists = $model->activeChannelList(1, $where, $field, $channel, $column, $timeData);

        $dataArray = [];
        // 标题
        $dataObj = array_keys($lists);
        array_unshift($dataObj, '日期');
        // 行数据
        $dataArray[] = $dataObj;
        isset($param['show_time']) && $param['show_time'] == 1 && $timeData = $model->timeData($timeData);
        foreach ($timeData as $k => $v) {
            $temp = [$v];
            foreach ($lists as $lk => $lv) {
                $temp[] = $lv[$k];
            }
            $dataArray[] = $temp;
        }
        if (isset($param['show_time']) && $param['show_time'] == 2) {
            $timeType = '月';
        } elseif (isset($param['show_time']) && $param['show_time'] == 1) {
            $timeType = '周';
        } else {
            $timeType = '日';
        }

        $model->export($dataArray, "渠道{$this->operate[$column]}{$timeType}报表.xls");
        exit;
    }

    /**
     * 操作类型
     */
    public function operateType()
    {
        return $this->operate;
    }
}
