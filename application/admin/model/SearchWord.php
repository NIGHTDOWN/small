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

    /**
     * 设置排序值
     * @param $id
     * @param $order_sort
     * @return false|int
     */
    public function setOrderSort($id,$order_sort)
    {
        return $this->save(['order_sort'=>$order_sort,],['id'=>['=',$id]]);
    }

    /**
     * 编辑状态
     * @param $id
     * @param $status
     * @return false|int
     */
    public function editStatus($id,$status)
    {
        return $this->save(['status'=>$status,],['id'=>['=',$id]]);
    }
}
