<?php

namespace app\admin\model;

use think\Model;
use app\common\model\Advertising as AdCommonModel;

class Advertising extends Model
{
    // 表名
    protected $name = 'advertising';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
        'status_text',
        'start_time_text',
        'end_time_text',
        'create_time_text',
        'update_time_text'
    ];
    

    
    public function getStatusList()
    {
        $status_list=AdCommonModel::STATUS_TEXT;
        unset($status_list[-1]);
        return $status_list;
    }     


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['start_time'])&&$data['start_time'] ? $data['start_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['end_time'])&&$data['end_time'] ? $data['end_time'] : '');
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

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setStartTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setEndTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function type()
    {
        return $this->belongsTo('AdvertisingType', 'type_id', 'id', [])->setEagerlyType(1);
    }

    /**
     * 编辑
     * @param $data
     * @return false|int
     */
    public function edit($data)
    {
        $old_image=$this->getAttr('image');
        $ret=$this->allowField(['type_id','title','image','url','order_sort','start_time','end_time','status'])->save($data);
        if ($ret){
            //图片处理
            if (isset($data['image'])){
                if ($old_image&&($data['image']!=$old_image)){
                    AdCommonModel::deleteImageFile($old_image);
                }
            }
        }
        return $ret;
    }

    /**
     * 删除
     */
    public function del()
    {
        $ret=$this->delete();
        if ($ret){
            AdCommonModel::deleteImageFile($this->getAttr('image'));
        }
        return $ret;
    }

}
