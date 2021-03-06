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
     * 类型
     * @var array
     */
    public $operate = [
        'activate' => '激活量',
        'register' => '注册量',
        'active' => '启动量',
        'active_rate' => '活跃度',
        'wastage' => '流失用户',
        'wastage_rate' => '流失率',
        'register_total' => '累计注册量',
        'activate_total' => '累计激活量',
    ];

    /**
     * 操作状态
     */
    public $operateStatus = [
        'activate' => 1,
        'register' => 2,
        'active' => 3
    ];

    /**
     * 列表
     * @param array $where
     * @param array $field
     * @param array $channel
     * @param string $column
     * @param array $timeData
     * @param array $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($where = [], $field = [], $channel = [], $column = '', $timeData = [], $param = [])
    {
        // app机器操作记录表
        $field[] = 'channel_id';
        if (isset($param['channel_id'])) {
            $group = 'channel_id, day';
        } else {
            $group = 'day';
        }
        $list = $this->allRow($where, $field, 'day_time asc', $group);

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
//                $result[$column][] = 0; // 单类型
                foreach ($column as $fk => $fv) {// 多类型
                    $result[$fv][] = 0;
                }
            } else {
                foreach ($data as $key => $val) {
                    if ($key == $k) {
//                        $result[$column][] = array_sum(array_column($val, $column));
                        foreach ($column as $fk => $fv) {
                            $result[$fv][] = array_sum(array_column($val, $fv));
                        }
                    }
                }
            }
        }
        if (isset($param['show_time']) && $param['show_time'] == 1) {
            $timeData = $this->timeData($timeData);
        }

        return [
            'rows' => [
                'list' => $list,
                'operate_data' => $result,
                'time_data' => $timeData,
                'operate_list' => $this->operate
            ],
            'total' => 0];
    }

    /**
     * 统计列表
     * @param string $field
     * @param array $where
     * @param string $order
     * @param string $group
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function summaryList($field = '', $where = [], $order = 'day_time asc', $group = '')
    {
        $res = Db::name('summary_sheet')
            ->alias('ss')
            ->field($field)
            ->join('summary_sheet_ext sse', 'ss.id = sse.ss_id')
            ->where($where)
            ->order($order)
            ->group($group)
            ->select() ?: [];
        return $res;
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
            ->where(['status' => 1])
            ->where(! empty($channel) ? ['id' => ['in', $channel]] : [])
            ->column('id, channel_name') ?: []; // 渠道
    }

    /**
     * 渠道活跃用户统计列表 TODO 优化
     * @param int $export
     * @param array $where
     * @param array $field
     * @param array $channel
     * @param string $column
     * @param array $timeData
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function activeChannelList($export = 0, $where = [], $field = [], $channel = [], $column = '', $timeData = [], $param = [])
    {
        // app机器操作记录表
        $field[] = 'channel_id';
        $list = $this->allRow($where, $field, 'day_time asc', 'day, channel_id');
        // 提取渠道
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
        // 取具体的数据列表
        $result = [];
        if (empty($data)) {
            // 没有数据
            foreach ($timeData as $tk => $tv) {
                foreach ($channel as $ck => $cv) {
                    $result[$cv][] = 0;
                }
            }
        } else {
            $isRate = strpos($column, 'rate') !== false ? 1 : 0; // 百分比
            foreach ($timeData as $tk => $tv) {
                // 按日期分组导出
                foreach ($data as $k => $v) {
                    $keyArr = array_column($v, 'day');
                    // 如果元素内不包含当前日期就默认为0
                    if (!in_array($tv, $keyArr)) {
                        $result[$k][] = 0;
                    }
                    foreach ($v as $vk => $vv) {
                        if ($vv['day'] == $tv) {
                            if ($isRate) {
                                $temp = round($vv[$column]*100, 2);
                                $result[$k][] = $export ? $temp . '%' : $export;
                            } else {
                                $result[$k][] = $vv[$column];
                            }
                        }
                    }
                }
            }
        }

        // 若是周提取时间区间
        if (isset($param['show_time']) && $param['show_time'] == 1) {
            $timeData = $this->timeData($timeData);
        }

        if ($export) {
            return $result;
        } else {
            return [
                'rows' => [
                    'list' => $result,
                    'time_data' => $timeData,
                    'operate' => $this->operate[$column]
                ], 'total' => 0];
        }
    }

    /**
     * 导出总报表
     * @param $where
     * @param $timeData
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function exportAll($where, $timeData)
    {
        // 渠道
        $channel = Db::name('channel')->field('id, channel_name')->select() ?: [];
        $channelData = [];
        foreach ($channel as $key => $val) {
            $temp = $val;
            $channelData[$val['id']] = $temp;
        }
        // 数据
        $list = Db::name('summary_sheet')
            ->alias('ss')
            ->field('channel_id, sum(activate) activate, sum(active) active, max(activate_total) activate_total, 
                FROM_UNIXTIME(day_time,"%Y-%m-%d" ) day')
            ->where($where)
            ->group('channel_id, day')
            ->select() ?: [];
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
        $result[] = ['日期', '渠道名称','激活', '启动数', '累计激活量', '日激活占比'];
        $dayArr = array_keys($data);
        foreach ($timeData as $v => $k) {
            if (!in_array($k, $dayArr)) {
                $temp = [$k, 0, 0, 0, 0, 0, 0]; // 当天没有数据
            } else {
                $temp = [$k];
                if (isset($channel[$data[$k]['channel_id']])) {
                    $cName = $channel[$data[$k]['channel_id']]['channel_name'];
                }
                $temp[] =  $cName ?? '';
                $temp[] = $data[$k]['activate'] ?? 0;
                $temp[] = $data[$k]['active'] ?? 0;
                $temp[] = $data[$k]['activate_total'] ?? 0;
                $temp[] = $data[$k]['activate_total'] > 0 ?
                    round($data[$k]['active']/$data[$k]['activate_total'], 2) * 100 . '%' : 0 . '%';
            }
            $result[] = $temp;
        }
        $this->export($result, '渠道激活量、注册量总报表.xls');
        exit;
    }

    /**
     * 所有数据
     * @param $where
     * @param string $field
     * @param string $order
     * @param $group
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function allRow($where, $field = '', $order = 'day_time asc', $group = '')
    {
        empty($field) && $field = 'register, activate, activate_total, active, FROM_UNIXTIME(day_time, "%Y-%m-%d") day';
        $data = Db::name('summary_sheet')
            ->field($field)
            ->where($where)
            ->order($order)
            ->group($group)
            ->select() ?: [];
        return $data;
    }

    /**
     * 单条数据
     * @param $field
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRow($field, $order = '')
    {
        return Db::name('summary_sheet')
            ->field($field)
            ->order($order)
            ->find() ?: [];
    }

    /**
     * 导出数据
     * @param $fieldArr
     * @param $order
     * @param $group
     * @param $firstCol
     * @param $arrKey
     * @param $name
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function exportList($fieldArr, $order, $group, $firstCol, $arrKey, $name = '')
    {
        // 搜索条件
        $param = json_decode(input('filter'),  true);
        list($param, $field, $column, $channel, $where, $timeData) = $this->filter(
            $param);
        $list = $this->summaryList($field, $where, $order, $group);

        // 按时间分组
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
        $result[] = $firstCol;
        $dayArr = array_keys($data);
        foreach ($timeData as $v => $k) {
            // 周转时间
            if ($param['show_time'] == 1) {
                $num = explode('-', $k);
                $temp = week_range($num[1] - 1, $num[0]);
                $weekDay = date('Y-m-d', $temp[0]) . '~' . date('Y-m-d', $temp[1]);
                $temp = [$weekDay];
            } else {
                $temp = [$k];
            }
            foreach ($arrKey as $ak => $av) {
                if (!in_array($k, $dayArr)) {
                    $temp[] = 0;
                } else {
                    $temp[] = strpos($column, 'rate') !== false ?
                        round($data[$k][$av]*100, 2) . '%' : $data[$k][$av];
                }
            }
            $result[] = $temp;
        }

        // 文件名
        if (empty($name)) {
            $channel = $this->channelList($channel);
            $channelName = isset($param['channel_id']) && ! empty($param['channel_id']) ? $channel[$param['channel_id']] : '';
            $name = $channelName . $this->operate[$column] . '数据表.xls';
        }
        $this->export($result, $name);
        exit;
    }

    /**
     * 导出 TODO 放到公共模块
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

    /**
     * 周转时间
     * @param $timeData
     * @return mixed
     */
    public function timeData($timeData)
    {
        if (!is_array($timeData) || empty($timeData)) {
            return [];
        }
        foreach ($timeData as $k => &$v ) {
            $num = explode('-', $v);
            $temp = week_range($num[1] - 1, $num[0]);
            $v = date('Y-m-d', $temp[0]) . '~' . date('Y-m-d', $temp[1]);
        }
        return $timeData;
    }

}
