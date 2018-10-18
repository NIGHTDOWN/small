<?php

namespace app\admin\model;

use think\Model;

use think\Db;

class SiteBannerType extends Model
{
    // 表名
    protected $name = 'site_banner_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
        'create_time_text',
        'update_time_text'
    ];

    /**
     * 建立与 Image 表的关联模型（一对一）
     * @return \think\model\relation\BelongsTo
     */


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

    public function allType(){
        $data=Db::name('site_banner_type')->where('status','not in',[SiteBanner::TYPE_STATUS['DELETE'],SiteBanner::TYPE_STATUS['DISABLED']])->field(['id','type'])->order('id','desc')->column('type','id');
        return $data;
    }


}
