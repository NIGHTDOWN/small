<?php

namespace app\admin\model;

use think\Model;
use app\common\model\User as UserCommonModel;

class User extends Model
{

    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [
    ];

    public function getStatusList()
    {
        $status_list=UserCommonModel::STATUS_TEXT;
        unset($status_list[-1]);
        return $status_list;
    }

    public function getIsRobotList()
    {
        return UserCommonModel::IS_ROBOT_TEXT;
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        return UserCommonModel::STATUS_TEXT[$value];
    }

    public function getIsRobotTextAttr($value, $data)
    {
        $value = $value ? $value : $data['is_robot'];
        return UserCommonModel::IS_ROBOT_TEXT[$value];
    }

    public function burse()
    {
        return $this->belongsTo('UserBurse', 'id', 'user_id', [], 'LEFT')->setEagerlyType(0);
    }

    public function userGroup()
    {
        return $this->belongsTo('UserGroup', 'group_id', 'id')->setEagerlyType(1);
    }

    /**
     * 编辑
     * @param array $data
     * @return bool
     */
    public function edit($data)
    {
        $row=$this->where('id',$data['id'])->find();
        //密码处理
        if (isset($data['password'])){
            if ($data['password']){
                $data['password']=create_password($data['password']);
            }else{
                unset($data['password']);
            }
        }
        //头像处理
        if (isset($data['head_img'])){
            if ($data['head_img']){
                $old_head_img=$row->getAttr('head_img');
                if ($old_head_img&&($data['head_img']!=$old_head_img)){
                    $avatar_url=config('site.avatar_url');
                    $key=str_replace("$avatar_url/",'',$old_head_img);
                    UserCommonModel::deleteHeadImgFile($key);
                }
            }else{
                $data['head_img']=$row->getAttr('head_img');
            }
        }
        try{
            $this->allowField(['nickname','head_img','password','mobile','group_id','status'])->update($data);
            //昵称变更
            if ($data['nickname']!==$row->getAttr('nickname')){
                UserCommonModel::addEs($data['id'],$data['nickname']);
            }
            //处理缓存
            if ($data['status']==UserCommonModel::STATUS['normal']){
                UserCommonModel::updateUserCache($data['id'],['nickname'=>$data['nickname'],'head_img'=>$data['head_img']??'','mobile'=>$data['mobile'],'group_id'=>$data['group_id']]);
            }else{
                UserCommonModel::deleteUserCache($data['id']);
            }
            return true;
        }catch (\Exception $e){
            $this->error='失败';
            return false;
        }
    }

    /**
     * 删除
     * @param $id
     * @return int
     */
    public function del($id)
    {
        //删除缓存
        if (UserCommonModel::existUserCache($id)){
            $cache_ret=UserCommonModel::deleteUserCache($id);
            if (!$cache_ret){
                $this->error='删除缓存失败';
                return false;
            }
        }
        //删除es
        $es_ret=UserCommonModel::delEs($id);
        if (!$es_ret){
            $this->error='删除es失败';
            return false;
        }
        $ret = $this->where('id','=',$id)->delete();
        if (!$ret){
            $this->error='删除失败';
            return false;
        }
        return true;
    }

}
