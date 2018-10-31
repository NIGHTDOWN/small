<?php

namespace app\admin\controller\summarysheet;

use app\common\controller\Backend;
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
        if ($this->request->isAjax()) {
            $model = model('SummarySheet');
            // 搜索条件
            $param = json_decode(input('filter'),  true);
            list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);
            // 数据
//            $list = $model->getList($where, $field, $channel, $column, $timeData, $param);
            $list = $model->activeChannelList(0, $where, $field, $channel, $column, $timeData, $param);

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
        $param = json_decode(input('filter'), true);
//        if (isset($param['show_time'])) unset($param['show_time']);
        list($param, $field, $column, $channel, $where, $timeData) = $model->filter($param);

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