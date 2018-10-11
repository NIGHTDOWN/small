<?php

namespace app\admin\model;

use think\Model;

class Video extends Model
{
    // 表名
    protected $name = 'video';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'process_done_time_text',
        'create_time_text',
        'update_time_text'
    ];

    public static $status = [
        'DELETE' => -1,
        'HIDE' => 0,
        'DISPLAY' => 1,
        'ROBOT_FAILD' => 2,
        'VIOLATION' => 3,
        'FROZEN' => 5,
        'COMPLAIN' => 6,
        'APPEALING' => 7,
        'ROBOT_SUCCESS' => 8,
        'CHECK_NO_PASS' => 9,
        'DRAFT' => 10,
    ];
    
    const STATUSTEXT = [
        -1 => '删除',
        0 => '未发布',
        1 => '已发布',
        2 => '机器审核未通过',
        3 => '违规', //人工巡查 用户举报
        5 => '冻结中', //原始视频被删除 或者违规了 (相对于转载的视频)
        6 => '用户申诉',
        7 => '用户申诉',
        8 => '机器审核通过',
        9 => '审核不通过',
        10 => '草稿',
    ];

    
    public function check_video($video_id, $status, $remark)
    {
        // 第三方相关操作没写
        $data = [
           'update_time'=>time()
        ];

        if ($status == 1) {
            $data['status'] = self::$status['DISPLAY'];
        } else {
            $data['status'] = self::$status['CHECK_NO_PASS'];
        }

        $this->where(['id' => $video_id])->save($date);
        return true;
    }
    
    public function getProcessDoneTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['process_done_time']) ? $data['process_done_time'] : '');
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

    protected function setProcessDoneTimeAttr($value)
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


}
