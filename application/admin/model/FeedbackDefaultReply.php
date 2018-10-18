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

    /**
     * 添加提现文案
     * 
     * @param String $content [description]
     */
    public function add($params)
    {
        if (!strlen($params['content'])) {
            $this->error = '内容不能为空';
            return false;
        }
        if (mb_strlen($params['content']) > 50) {
            $this->error = '内容不能大于50字';
            return false;
        }
        $count = Db::name('feedback_default_reply')->field('id')->where(['status' => self::STATUS['ENABLED']])->count();
        if ((int)$count > 4) {
            $this->error = '最多只能添加五条默认文案';
            return false;
        }
        $time = time();
        $res = Db::name('feedback_default_reply')->insert([
                'content' => $params['content'],
                'update_time' => $time,
                'create_time' => $time,
                'type' => $params['type']
            ]);
        if (!$res) {
            $this->error = '新增失败';
            return false;
        }
        return true;
    }

    /**
     * 编辑提现文案
     * 
     * @param  Array $params [description]
     * @return [type]         [description]
     */
    public function edit($params)
    {
        if (!strlen($params['content'])) {
            $this->error = '内容不能为空';
            return false;
        }
        if (mb_strlen($params['content']) > 50) {
            $this->error = '内容不能大于50字';
            return false;
        }
        $res = Db::name('feedback_default_reply')->where(['id' => $params['id']])->update([
            'content' => $params['content'],
            'update_time' => time()
        ]);
        if (!$res) {
            $this->error = '更新失败';
            return false;
        }
        return true;
    }

    /**
     *  删除文案
     * 
     * @param  int $id [description]
     * @return [type]     [description]
     */
    public function del($id)
    {
        if (!is_numeric($id)) {
            $this->error = '错误id';
            return false;
        }
        $row = Db::name('feedback_default_reply')->field(['status'])->where('id', $id)->find();
        if (!$row) {
            $this->error = '文案已删除';
            return false;
        }
        if ($row['status'] === self::STATUS['DELETE']){
            $this->error = '文案已删除';
            return false;
        }
            
        $where = [
            'id' => ['eq', $id],
            'status' => ['neq', self::STATUS['DELETE']]
        ];
        $data = [
            'status' => self::STATUS['DELETE'],
            'update_time' => time(),
        ];
        $ret = Db::name('feedback_default_reply')->where($where)->update($data);
        if (!$ret) {
            $this->error = '删除失败';
            return false;
        }

        return true;
    }

}
