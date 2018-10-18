<?php

namespace app\admin\model;

use think\Model;

use think\Db;

use think\Session;

class Appversion extends Model
{
    // 表名
    protected $name = 'app_version';
    
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
    
    public static function getAdmin($data){
        return  Db::name('admin')->where('id', 'IN', $data)->column('username', 'id');
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


    public function edit($data)
    {
        $id = $data['id'];
        $app_version = $data['app_version'];
        $app_version_code = $data['app_version_code'];
        $update_describe = $data['update_describe'];
        $update_type = $data['update_type'];
        $input_down_list = $data['down_list'];
        $status = $data['status'];
        $admin_id = Session::get('admin')['id'];
        $db = Db::name('app_version');
        $now=time();
        return  $db->where('id','eq',$id)->update([
            'app_version' => $app_version,
            'app_version_code' => $app_version_code,
            'update_describe' => $update_describe,
            'update_type' => $update_type,
            'last_mod_admin_id' => $admin_id,
            'down_list' => $input_down_list,
            'update_time' => $now,
            'status'=>$status,
        ]);
    }

    public function add($data)
    {
        $app_version = $data['app_version'];
        $app_version_code = $data['app_version_code'];
        $update_describe = $data['update_describe'];
        $update_type = $data['update_type'];
        $input_down_list = $data['down_list'];
        $status = $data['status'];

        $admin_id = Session::get('admin')['id'];
        $db = Db::name('app_version');
        $now=time();
        return $db->insertGetId([
            'app_version' => $app_version,
            'app_version_code' => $app_version_code,
            'update_describe' => $update_describe,
            'update_type' => $update_type,
            'create_admin_id' => $admin_id,
            'down_list' => $input_down_list,
            'create_time'=>$now,
            'update_time' => $now,
            'status'=>$status,
        ]);
    }


}
