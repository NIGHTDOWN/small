<?php
/**
 * Created by PhpStorm.
 * User: liangshuisheng
 * Date: 2018/9/19
 * Time: 16:36
 */

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

/**
 * 统计激活安装等数据
 * Class UserCoinStatistics
 * @package app\common\command
 */
class MachineOperateStatistics extends Command
{
    protected $model = null;

    protected $operateStatus = [];

    /**
     * 基本配置
     */
    protected function configure()
    {
        $this->setName('MachineOperateStatistics')->setDescription('新增统计数据');
    }

    /**
     * 执行 TODO 查询放到循环外去
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function execute(Input $input, Output $output)
    {
        $model = model('SummarySheet');
        $this->operateStatus = $model->operateStatus;
        // 渠道
        $channel = Db::name('channel')->field('id, channel_name')->column('id, channel_name');
        // 时间
        $timeStart = strtotime(date('y-m-d', time())) - 86400;
        $timeEnd = $timeStart + 86399;
        // 取渠道和版本分组
        $data = $this->data(['create_time' => ['between', [$timeStart, $timeEnd]]]);
        // 总激活量
        $machineCount = $this->machineOperateCount(['operate' => $this->operateStatus['activate']]);
        // 总注册量
        $usersCount = $this->machineOperateCount(['operate' => $this->operateStatus['register']]);
        if (!empty($data)) {
            $operate = model('MachineOperate')->operate;
            unset($operate['unknow']);
            $list = array_map(function ($v) use ($channel, $operate, $machineCount, $usersCount) {
                unset($v['id']);
                // 搜索条件
                $v['day_time'] = strtotime($v['day_time']);
                $map = [
                    'create_time' => ['between', [$v['day_time'], $v['day_time'] + 86399]],
                    'channel_id' => $v['channel_id'],
                    'version_id' => $v['version_id']
                ];
                // 根据类型统计相关数据
                foreach ($operate as $key => $val) {
                    $v[$key] = $this->machineOperateCount(array_merge($map, ['operate' => $val]));
//                    $v[$key] = Db::name('machine_operate')->where($map)->where(['operate' => $val])->count();
                }
                $v['create_time'] = time();
                $v['activate_total'] = $machineCount; // 总激活量
                $v['register_total'] = $usersCount; // 总注册量
                $v['active_rate'] =  $v['activate_total'] > 0 ? $v['active'] / $v['activate_total'] : 0; // 活跃度
                $v['wastage'] = $this->wastage([
                    ['mp.create_time' => ['<', $v['day_time'] - (60 * 24 * 60 * 60)]],
                    ['mp.create_time' => ['>', $v['day_time']]],
                    'mp.id is null'
                ]);
                $v['wastage_rate'] = $machineCount > 0 ? $v['wastage'] / $machineCount : 0; // 流失率
                // 周月数据
                $ext = $this->ext($v, $machineCount);
                return ['base' => $v, 'ext' => $ext];
            }, $data);

            $extAll = [];
            $num = 0;
            foreach ($list as $k => $v) {
                $id = Db::name('summary_sheet')->insertGetId($v['base']);
                if (! empty($id)) {
                    ++$num;
                    $v['ext']['ss_id'] = $id;
                    $extAll[] = $v['ext'];
                }
            }
            ! empty($extAll) && Db::name('summary_sheet_ext')->insertAll($extAll);

            echo "成功添加" . $num . "条数据";
        } else {
            echo "没有新增数据";
        }
    }


    /**
     * 扩展数据处理
     * @param $v
     * @param $machineCount
     * @return array
     * @throws \think\exception\DbException
     */
    private function ext($v, $machineCount)
    {
        // 周数据
        $day = date("Y-m-d", $v['day_time']);
        $weekEnd = strtotime($day . " sunday") + 86399;
        $weekStart = $weekEnd - (6 * 24 * 60 * 60);
        $weekMap = ['create_time' => ['between', [$weekStart, $weekEnd]]];
        $ext = [];
        $ext['week_register'] = $this->machineOperateCount(
            array_merge($weekMap, ['operate' => $this->operateStatus['register']])
        );
        $ext['week_activate'] = $this->machineOperateCount(
            array_merge($weekMap, ['operate' => $this->operateStatus['activate']])
        );
        $ext['week_active'] = $this->machineOperateCount(
            array_merge($weekMap, ['operate' => $this->operateStatus['active']])
        );
        $ext['week_active_rate'] = $v['activate_total'] > 0 ? $ext['week_active'] / $v['activate_total'] : 0;
        $ext['week_wastage'] = $this->wastage([
            ['mp.create_time' => ['<', $weekStart - (60 * 24 * 60 * 60)]],
            ['mp.create_time' => ['>', $weekEnd]],
            'mp.id is null'
        ]);
        $ext['week_wastage_rate'] = $machineCount > 0 ? $ext['week_wastage'] / $machineCount : 0;
        // 月数据
        $monthStart = date('Y-m-01', $v['day_time']);
        $monthEnd = strtotime($monthStart . '+1 month -1 day') + 86399;
        $monthStart = strtotime($monthStart);
        $monthMap = ['create_time' => ['between', [$weekStart, $weekEnd]]];
        $ext['month_register'] = $this->machineOperateCount(
            array_merge($monthMap, ['operate' => $this->operateStatus['register']])
        );
        $ext['month_activate'] = $this->machineOperateCount(
            array_merge($monthMap, ['operate' => $this->operateStatus['activate']])
        );
        $ext['month_active'] = $this->machineOperateCount(
            array_merge($monthMap, ['operate' => $this->operateStatus['active']])
        );
        $ext['month_active_rate'] = $v['activate_total'] > 0 ? $ext['week_active'] / $v['activate_total'] : 0;
        $ext['month_wastage'] = $this->wastage([
            ['mp.create_time' => ['<', $monthStart - (60 * 24 * 60 * 60)]],
            ['mp.create_time' => ['>', $monthEnd]],
            'mp.id is null'
        ]);
        $ext['month_wastage_rate'] = $machineCount > 0 ? $ext['week_wastage'] / $machineCount : 0;

        return $ext;
    }


    /**
     * 渠道和版本
     * @param $map
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function data($map)
    {
        $data = Db::name('MachineOperate')
            ->field('id, channel_id, version_id, FROM_UNIXTIME(create_time, "%Y-%m-%d") day_time')
            ->where($map)
            ->group('day_time, channel_id, version_id')
            ->select() ?: [];
        return $data;
    }

    /**
     * 流失量
     * @param $map
     * @return mixed
     * @throws \think\exception\DbException
     */
    private function wastage($map)
    {
        $sql = Db::name('machine')->alias('m')
            ->field('m.id')
            ->join('machine_operate mp', 'm.id = mp.machine_id', 'left');
        foreach ($map as $v) {
            $sql->whereOr($v);
        }
        $data = $sql->group('m.id')->buildSql();// 流失量: 过去60天启动过的数量
        return Db::query('select count(1) as tp_count from ' . $data . ' as a')[0]['tp_count'];
    }

    /**
     * 激活量
     * @param $map
     * @return mixed
     * @throws \think\exception\DbException
     */
    private function machineOperateCount($map)
    {
        $sql = Db::name('machine_operate')->where($map)->group('machine_id')->buildSql();
        return Db::query('select count(1) as tp_count from ' . $sql . ' as a')[0]['tp_count'];
    }

}