<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class UserCoinStatistics extends Model
{

    // 表名
    protected $name = 'user_coin_statistics';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';

    /**
     * 获取金币数
     * @param $param
     * @return bool|int
     */
    public function getCoinStatistics($param)
    {
        $map = [];
        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            $map['create_time'] = ['between', [$param['start_time'], $param['end_time']]];
        }
        return Db::name('user_coin_statistics')->where($map)->sum('count') ?: 0;
    }

}
