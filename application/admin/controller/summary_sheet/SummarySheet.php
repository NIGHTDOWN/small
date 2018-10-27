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
        $model->exportList(
            "sum(register) as register, sum(activate) as activate, max(register_total) as register_total,
            max(activate_total) as activate_total, FROM_UNIXTIME(day_time, '%Y-%m-%d') as day",
            'day desc',
            'day',
            ['日期', '注册量', '激活量', '总注册量', '总激活量'],
            ['register', 'activate', 'register_total', 'activate_total'],
            '新增统计数据表.xls'
        );
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