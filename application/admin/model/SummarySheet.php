<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class SummarySheet extends Model
{
    // 表名
    protected $name = 'summary_sheet';

    /**
     * 展示方式
     */
    public $showTime = [
        '0' => "'%Y-%m-%d'",
        '1' => "'%Y-%u'",
        '2' => "'%Y-%m'"
    ];

    /**
     * 展示方式
     */
    public $showTimeFormat = [
        '0' => "Y-m-d",
        '1' => "Y-W",
        '2' => "Y-m"
    ];

    /**
     * 展示方式
     */
    public $showTimeSec = [
        '0' => 24*60*60,
        '1' => 24*7*60*60,
        '2' => 24*31*60*60
    ];

    /**
     * 操作
     * @var array
     */
    public $operateText = ['激活量', '注册量'];

    /**
     * 列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList2($where = [])
    {
        // 时间区间
        if (isset($where['day_time'])) {
            $timeData = get_day_in_range($where['day_time']);
            $where['day_time'] = ['between', [$where['day_time'][0], $where['day_time'][1]]];
        } else {
            // 默认展示一周内的数据
            $timeData = get_week();
        }

        // app机器操作记录表
        $list = Db::name('summary_sheet')
            ->field('register, activate, activate_total, register_total, FROM_UNIXTIME(day_time, "%Y-%m-%d") day')
            ->where($where)
            ->order('day_time asc')
            ->select() ?: [];

        $data = [];
        // 按天分组
        foreach ($timeData as $v => $k) {
            foreach ($list as $key => $val) {
                if ($val['day'] == $k) {
//                    $data[$k][] = $val;
                    $data[$k]['activate'] = !isset($data[$k]['activate'])
                        ? $val['activate'] : $data[$k]['activate'] + $val['activate'];
                    $data[$k]['register'] = !isset($data[$k]['register'])
                        ? $val['register'] : $data[$k]['register'] + $val['register'];
                    $data[$k]['activate_total'] = !isset($data[$k]['activate_total'])
                        ? $val['activate_total'] : $data[$k]['activate'] + $val['activate_total'];
                    $data[$k]['register_total'] = !isset($data[$k]['register_total'])
                        ? $val['register_total'] : $data[$k]['activate'] + $val['register_total'];
                }
            }
//            $data[$k]['day'] = $k;
        }
        $keyArr = array_keys($data);
        foreach ($timeData as $v => $k) {
            if (!in_array($k, $keyArr)) {
                // 当天没有数据的补零
                $data[$k]['activate'] = 0;
                $data[$k]['register'] = 0;
                $data[$k]['activate_total'] = 0;
                $data[$k]['register_total'] = 0;
            }
            $data[$k]['day'] = $k;
        }
        // 重置键名
        sort($data);

        return ['rows' => $data, 'total' => 0];
    }

    /**
     * 列表
     * @param array $where
     * @param array $field
     * @param array $channel
     * @param string $column
     * @param array $timeData
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($where = [], $field = [], $channel = [], $column = '', $timeData = [])
    {
        // app机器操作记录表
        $field[] = 'channel_id';
        $list = $this->allRow($where, $field);

        // 按天分组
        $data = [];
        foreach ($timeData as $v => $k) {
            foreach ($list as $key => $val) {
                if ($val['day'] == $k) {
                    $data[$k][] = $val;
                }
            }
        }

        // 取具体的数据列表
        $result = [];
        $keyArr = array_keys($data);
        foreach ($timeData as $v => $k) {
            if (!in_array($k, $keyArr)) {
                // 当天没有数据
                $result[$column][] = 0; // 单类型
//                foreach ($field as $fk => $fv) {// 多类型
//                    $field[$fk][] = 0;
//                }
            } else {
                foreach ($data as $key => $val) {
                    if ($key == $k) {
                        $result[$column][] = array_sum(array_column($val, $column));
//                        foreach ($field as $fk => $fv) {
//                            $field[$fk][] = array_sum(array_column($val, $fk));
//                        }
                    }
                }
            }
        }

        return [
            'rows' => [
                'list' => $list,
                'operate_data' => $result,
                'time_data' => $timeData
            ],
            'total' => 0];
    }

    /**
     * 版本列表
     * @return array
     */
    public function appVersionList()
    {
        return Db::name('app_version')->column('id, app_version') ?: [];
    }

    /**
     * 渠道列表
     * @param $channel
     * @return array
     */
    public function channelList($channel = [])
    {
        return Db::name('channel')
            ->field('id, channel_name')
            ->where(! empty($channel) ? ['id' => ['in', $channel]] : [])
            ->column('id, channel_name') ?: []; // 渠道
    }

    /**
     * 获取数据列表按天分组
     * @param array $where
     * @param array $field
     * @param array $channel
     * @param string $column
     * @param array $timeData
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function listGroupDay($where = [], $field = [], $channel = [], $column = '', $timeData = [])
    {
        // app机器操作记录表
        $field[] = 'channel_id';
        $list = $this->allRow($where, $field);

        // 按天分组
        $data = [];
        foreach ($timeData as $v => $k) {
            foreach ($list as $key => $val) {
                if ($val['day'] == $k) {
                    $data[$k][] = $val;
                }
            }
        }
        return $data;
    }

    /**
     * 渠道活跃用户统计列表 TODO 优化
     * @param int $export 导出
     * @param array $where
     * @param array $field
     * @param array $channel
     * @param string $column
     * @param array $timeData
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function activeChannelList($export = 0, $where = [], $field = [], $channel = [], $column = '', $timeData = [])
    {
        // app机器操作记录表
        $field[] = 'channel_id';
        $list = $this->allRow($where, $field);
//        dump($list);exit;
        $listChannel = array_column($list, 'channel_id');

        // 按渠道分组
        $channel = $this->channelList($channel);
        $data = [];
        foreach ($channel as $k => $v) {
            if (! in_array($v, $listChannel)) {
                $data[$v] = [];
            }
            // TODO 以下是否放在else里面
            foreach ($list as $key => $val) {
                if ($val['channel_id'] == $k) {
                    $data[$v][] = $val;
                }
            }
        }
        // 取具体的数据列表 TODO 三个列表都合并一起
        $result = [];
        if (empty($data)) {
            foreach ($timeData as $tk => $tv) {
                foreach ($channel as $ck => $cv) {
                    $result[$cv][] = 0;
                }
            }
        } else {
            foreach ($timeData as $tk => $tv) {
                // 按日期分组导出
                foreach ($data as $k => $v) {
                    $keyArr = array_column($v, 'day');
                    // 如果元素内不包含当前日期就默认为0
                    if (!in_array($tv, $keyArr)) {
                        $result[$k][] = 0;
//                    $result[$k][$field][] = 0;
                    }
                    foreach ($v as $vk => $vv) {
                        if ($vv['day'] == $tv) {
                            $result[$k][] = $vv[$column];
//                        $result[$k][$field][] = $vv[$field];
                        }
                    }
                }
            }
        }

        if ($export) {
            return $result;
        } else {
            return [
                'rows' => [
                    'list' => $list,
                    'operate_data' => $result,
                    'time_data' => $timeData
                ], 'total' => 0];
        }
    }

    /**
     * 所有数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function allRow($where, $field = '', $order = 'day_time asc')
    {
        empty($field) && $field = 'register, activate, activate_total, active, FROM_UNIXTIME(day_time, "%Y-%m-%d") day';
        $data = Db::name('summary_sheet')
            ->field($field)
            ->where($where)
            ->order($order)
            ->group('day')
//            ->fetchSql()
            ->select() ?: [];
//        dump($data);exit;
        return $data;
    }

    /**
     * 条件
     * @param $param
     * @return array
     */
    public function filter($param)
    {
        $where = [];
        $param['operate_type'] = isset($param['operate_type']) ? $param['operate_type'] : 'active';
        $param['show_time'] = isset($param['show_time']) ? $param['show_time'] : 0;
        $field = ['sum(' . $param['operate_type'] . ') as ' . $param['operate_type']];
        $column = $param['operate_type'];
        // 展示方式
        if (isset($param['show_time'])) {
            $field[] = 'FROM_UNIXTIME(day_time, ' . $this->showTime[$param['show_time']] . ') day';
        } else {
            $field[] = 'FROM_UNIXTIME(day_time, ' . $this->showTime[0] . ') day';
        }
        // 时间
        if (isset($param['day'])) {
            $where['day_time'] = $param['day'];
            if (strpos($where['day_time'], ' - ') === false) {
                $this->error('时间格式不正确');
            }
            $where['day_time'] = explode(' - ', $where['day_time']);
            // TODO 优化
            $start = strtotime(date('Y-m-d 0:0:0', strtotime($where['day_time'][0])));
            $end = strtotime(date('Y-m-d 23:59:59', strtotime($where['day_time'][1])));
            $timeData = get_day_in_range(
                [$start, $end],
                $this->showTimeFormat[$param['show_time']],
                $this->showTimeSec[$param['show_time']]
            );
            $where['day_time'] = ['between', [$start, $end]];
        } else {
            if ($param['show_time'] == 0) { // 天
                // 默认展示一周内的数据
//                $timeData = get_week();
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
            $timeData = get_day_in_range(
                [$start, $end],
                $this->showTimeFormat[$param['show_time']],
                $this->showTimeSec[$param['show_time']]
            );
            $where['day_time'] = ['between', [$start, $end]];
        }
        // 渠道
        $channel = [];
        if (!empty($param['channel_id'])) {
            $where['channel_id'] = $param['channel_id'];
            $channel[] = $param['channel_id'];
        }
        return [$param, $field, $column, $channel, $where, $timeData];
    }

    /**
     * 导出
     * @param $dataArray
     * @param $fileName
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function export($dataArray, $fileName)
    {
        $filename = $fileName;
        import('sys.PHPExcel', EXTEND_PATH);
        import('sys.PHPExcel.Writer.Excel5.php', EXTEND_PATH);

        $obj_phpexcel = new \PHPExcel();
        $N = 1;
        foreach ($dataArray as $line => $data_obj) {
            $A = 'A';
            foreach ($data_obj as $key => $val) {
                $obj_phpexcel->getActiveSheet()->setCellValue($A . $N, $val);
                $obj_phpexcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
                $obj_phpexcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(15);
                $A++;//纵列
            }
            $N++;//行数
        }

        $obj_Writer = new \PHPExcel_Writer_Excel5($obj_phpexcel);
        ob_end_clean();
        // 设置请求头
        header("Content-Type: application/force-download;charset=utf-8");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $filename . '"');
        header("Content-Transfer-Encoding: binary");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $obj_Writer->save('php://output');
    }

}
