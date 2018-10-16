<?php

namespace app\admin\model;

use think\Model;
use app\common\model\AdvertisingType as AdTypeCommonModel;

class AdvertisingType extends Model
{
    // 表名
    protected $name = 'advertising_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
    ];

    public function getStatusList()
    {
        $status_list=AdTypeCommonModel::STATUS_TEXT;
        unset($status_list[-1]);
        return $status_list;
    }

    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        return AdTypeCommonModel::getStatusText($value);
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

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    /**
     * 获取列表
     */
    public function getList()
    {
        $list=$this->where('status','=',AdTypeCommonModel::STATUS['open'])->column('type','id');
        return $list;
    }
}
