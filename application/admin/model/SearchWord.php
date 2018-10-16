<?php

namespace app\admin\model;

use think\Model;
use app\common\model\SearchWord as SearchWordCommonModel;

class SearchWord extends Model
{
    // 表名
    protected $name = 'search_word';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
    ];
    
    public function getStatusList()
    {
        return SearchWordCommonModel::STATUS_TEXT;
    }

    public function getOrderSortList()
    {
        return SearchWordCommonModel::ORDER_SORT_TEXT;
    }



    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }
}
