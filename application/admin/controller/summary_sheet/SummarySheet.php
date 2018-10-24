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
            // 展示列表图形
            return $this->addEchart();

            // TODO 这里用于导出
            $model = model('SummarySheet');
            // 搜索条件
            $where = json_decode(input('filter'),  true);
            if (isset($where['day_time'])) {
                if (strpos($where['day_time'], ' - ') === false) {
                    $this->error('时间格式不正确');
                }
                $where['day_time'] = explode(' - ', $where['day_time']);
                $where['day_time'][0] = strtotime(date('Y-m-d 0:0:0', strtotime($where['day_time'][0])));
                $where['day_time'][1] = strtotime(date('Y-m-d 23:59:59', strtotime($where['day_time'][1])));
            }
            // 数据
            $list = $model->getList($where);

            return json($list);
        } else {
            return $this->view->fetch('summarysheet/summarysheet/index');
        }
    }

    /**
     * 列表
     * @return string|\think\response\Json
     * @throws \think\Exception
     */
    public function addEchart()
    {
        if ($this->request->isAjax()) {
            $model = model('SummarySheet');
            // 搜索条件
            $where = json_decode(input('filter'),  true);
            if (! empty($where['operate_type'])) {
                $operateType = [$where['operate_type']];
                unset($where['operate_type']);
            } else {
                $operateType = $model->operateText;
            }
            if (isset($where['day'])) {
                $where['day_time'] = $where['day'];
                unset($where['day']);
                if (strpos($where['day_time'], ' - ') === false) {
                    $this->error('时间格式不正确');
                }
                $where['day_time'] = explode(' - ', $where['day_time']);
                $where['day_time'][0] = strtotime(date('Y-m-d 0:0:0', strtotime($where['day_time'][0])));
                $where['day_time'][1] = strtotime(date('Y-m-d 23:59:59', strtotime($where['day_time'][1])));
            }

            // 数据
            $list = $model->echart($where);
            $list['rows']['operate_data']['operate_type'] = $operateType;
            return json($list);
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
        return model('SummarySheet')->operateText;
    }

}