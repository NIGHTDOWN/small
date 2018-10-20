<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class SummarySheet extends Model
{
    // 表名
    protected $name = 'summary_sheet';

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
    public function getList($where = [])
    {
        // 时间区间
        if (isset($where['day_time'])) {
            $timeData = getDayInRange($where['day_time']);
            $where['day_time'] = ['between', [$where['day_time'][0], $where['day_time'][1]]];
        } else {
            // 默认展示一周内的数据
            $timeData = getWeek();
        }

//        // 渠道
//        $channel = Db::name('channel')->field('id, channel_name')->column('id, channel_name');

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
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function echart($where = [])
    {
        // 时间区间
        if (isset($where['day_time'])) {
            $timeData = getDayInRange($where['day_time']);
            $where['day_time'] = ['between', [$where['day_time'][0], $where['day_time'][1]]];
        } else {
            // 默认展示一周内的数据
            $timeData = getWeek();
        }

//        // 渠道
//        $channel = Db::name('channel')->field('id, channel_name')->column('id, channel_name');

        // app机器操作记录表
        $list = Db::name('summary_sheet')
            ->field('register, activate, activate_total, FROM_UNIXTIME(day_time, "%Y-%m-%d") day')
            ->where($where)
            ->order('day_time asc')
            ->select() ?: [];

        $activate = [];
        $register = [];
//        $activateTotal = [];
        $data = [];
        // 按天分组
        foreach ($timeData as $v => $k) {
            foreach ($list as $key => $val) {
                if ($val['day'] == $k) {
                    $data[$k][] = $val;
                }
            }
        }

        // 取具体的数据列表
        $keyArr = array_keys($data);

        foreach ($timeData as $v => $k) {
            if (!in_array($k, $keyArr)) {
                // 当天没有数据
                $activate[] = 0;
                $register[] = 0;
//                $activateTotal[] = 0;
            } else {
                foreach ($data as $key => $val) {
                    if ($key == $k) {
                        $activate[] = array_sum(array_column($val, 'activate'));
                        $register[] = array_sum(array_column($val, 'register'));
//                        $activateTotal[] = $val[0]['activate_total'];
                    }
                }
            }
        }

        return ['rows' => [
            'list' => $list,
            'operate_data' => [
                'activate' => $activate,
                'register' => $register,
//                'activate_total' => $activateTotal,
                'time_data' => $timeData
            ]
        ], 'total' => 0];
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
     * @return array
     */
    public function channelList()
    {
        return Db::name('channel')->column('id, channel_name') ?: [];
    }


}
