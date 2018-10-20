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

    /**
     * 基本配置
     */
    protected function configure()
    {
        $this->setName('MachineOperateStatistics')->setDescription('新增统计数据');
    }

    /**
     * 执行
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function execute(Input $input, Output $output)
    {
        // 渠道
        $channel = Db::name('channel')->field('id, channel_name')->column('id, channel_name');

        // 时间
        $timeStart = strtotime(date('y-m-d', time())) - 86400;
        $timeEnd = $timeStart + 86399;

        // 取渠道和版本分组
        $data = Db::name('MachineOperate')
            ->field('channel_id, version_id, FROM_UNIXTIME(create_time, "%Y-%m-%d") day_time')
            ->where(['create_time' => ['between', [$timeStart, $timeEnd]]])
            ->group('day_time, channel_id, version_id')
            ->select();

        if (!empty($data)) {
            $operate = model('MachineOperate')->operate;
            unset($operate['unknow']);
            $list = array_map(function ($v) use ($channel, $operate) {
                // 搜索条件
                $time = strtotime($v['day_time']);
                $map = [
                    'create_time' => ['between', [$time, $time + 86399]],
                    'channel_id' => $v['channel_id'],
                    'version_id' => $v['version_id']
                ];
                // 统计激活注册等数据
                $v['day_time'] = strtotime($v['day_time']);
                $v['create_time'] = time();
                foreach ($operate as $key => $val) {
                    $v[$key] = Db::name('machine_operate')->where($map)->where(['operate' => $val])->count();
                    if ($key == 'activate') {
                        // 总激活量
                        $v['activate_total'] = Db::name('machine_operate')->count();
                    } elseif ($key == 'register') {
                        $v['register_total'] = Db::name('user')->count();
                    }
                }
                return $v;
            }, $data);
            Db::name('summary_sheet')->insertAll($list);
            echo "成功添加" . count($list) . "条数据";
        } else {
            echo "没有新增数据";
        }
    }
}