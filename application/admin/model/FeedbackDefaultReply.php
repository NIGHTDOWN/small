<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class FeedbackDefaultReply extends Model
{
    // 表名
    protected $name = 'feedback_default_reply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    const STATUS=[
        'ENABLED'=>0,
        'DELETE'=>1
    ];

    const STATUS_TXET=[
        0=>'正常',
        1=>'已删除',
    ];

    const TYPE=[
        'FEEDBACK'=>0,
        'CASH'=>1
    ];

    const TYPE_TEXT=[
        0=>'吐槽默认文案',
        1=>'提现默认文案',
    ];
    

    /**
     * 文案列表
     * 
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getList($param)
    {
        $where = [];
        if (isset($param['type']) && is_numeric($param['type'])) {
            $where['type'] = ['=', $param['type']];
        }
        if (isset($param['status']) && is_numeric($param['status'])) {
            $where['status'] = ['=', $param['status']];
        }
        
        // 数据查询
        $list = Db::name('feedback_default_reply')
            ->field(['id','content','status','create_time','update_time'])
            ->where($where)
            ->order('id', 'desc')
            ->select();
        // 数据处理
        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
        }

        return $list;
    }

}
