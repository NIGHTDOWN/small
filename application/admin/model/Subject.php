<?php

namespace app\admin\model;

use think\Model;

class Subject extends Model
{
    // 表名
    protected $name = 'subject';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'new_join_time_text',
        'create_time_text',
        'update_time_text'
    ];


    /**
     * 状态
     * @var array
     */
    public static $status = [
        'DELETE' => -1,
        'HIDE' => 0,
        'SHOW' => 1,
    ];

    /**
     * 状态
     * @var array
     */
    public static $statusText = [
        -1 => '删除',
        0 => '禁用',
        1 => '正常',
    ];

    public function getNewJoinTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['new_join_time']) ? $data['new_join_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setNewJoinTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    /**
     * 选择列表
     * @param $param
     * @return array
     * @throws \think\exception\DbException
     */
    public function selectList($param)
    {
        $where = [];
        if (isset($param['selected'])) {
            // 选中的
            $where['id'] = $param['selected'];
        } else {
            // 初始化的
            $where['status'] = self::$status['SHOW'];
            if (isset($param['keyword']) && ! empty($param['keyword'])) {
                $where['subject_name'] = ['like', '%' . trim($param['keyword']) . '%'];
            }
        }

        // 数据
        $total = $this->where($where)->count();
        $list = $this
            ->field(['id', 'subject_name'])
            ->where($where)
            ->order('weight', 'desc')
            ->limit($param['page'] * $param['page_size'], $param['page_size'])
            ->select() ?: [];

        return ['list' => $list, 'total' => $total];
    }

}
