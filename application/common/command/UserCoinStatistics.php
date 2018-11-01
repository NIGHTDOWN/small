<?php
/**
 * Created by PhpStorm.
 * User: liangshuisheng
 * Date: 2018/9/19
 * Time: 16:36
 */
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

/**
 * 统计每天产生的金币: 一天一条
 * Class UserCoinStatistics
 * @package app\common\command
 */
class UserCoinStatistics extends Command
{
    /**
     * 基本配置
     */
    protected function configure()
    {
        $this->setName('UserCoinStatistics')->setDescription('统计每天产生的金币数');
    }

    /**
     * 执行
     */
    protected function execute(Input $input, Output $output)
    {
        // 当天产生的金币总数
        $startTime = strtotime(date('Y-m-d'), time());
        $endTime = $startTime + 86399;
        $count = Db::name('user_coin')
            ->where([
                'type' => ['=', 1],
                'create_time' => ['between', [$startTime, $endTime]]
            ])->sum('amount') ?: 0;
        // 是否已存在
        $map = ['create_time' => ['between', [$startTime, $endTime]]];
        $exist = Db::name('user_coin_statistics')->where($map)->column('id');
        // 添加到金币统计表
        $success = Db::name('user_coin_statistics')->insert(['count' => $count, 'create_time' => time()]);
        // 删除当天的旧数据
        $success && $exist && count($exist) > 0 &&  Db::name('user_coin_statistics')
            ->where($map)
            ->where('id', 'in', $exist)
            ->delete();
        echo 'done';
    }
}