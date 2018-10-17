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
        //密码处理
        if (isset($data['password'])){
            if ($data['password']){
                $data['password']=create_password($data['password']);
            }else{
                unset($data['password']);
            }
        }
        $old_head_img=$this->getAttr('head_img');
        $ret=$this->allowField(['nickname','head_img','password','mobile','group_id','status'])->save($data);
        if ($ret){
            //头像处理
            if (isset($data['head_img'])){
                if ($data['head_img']){
                    if ($old_head_img&&($data['head_img']!=$old_head_img)){
                        $avatar_url=config('site.avatar_url');
                        $key=str_replace("$avatar_url/",'',$old_head_img);
                        UserCommonModel::deleteHeadImgFile($key);
                    }
                }
            }
            //昵称变更
            if ($data['nickname']!==$this->getAttr('nickname')){
                UserCommonModel::addEs($this->getAttr('id'),$data['nickname']);
            }
            //处理缓存
            if ($data['status']==UserCommonModel::STATUS['normal']){
                UserCommonModel::updateUserCache($this->getAttr('id'),['nickname'=>$data['nickname'],'head_img'=>$data['head_img']??'','mobile'=>$data['mobile'],'group_id'=>$data['group_id']]);
            }else{
                UserCommonModel::deleteUserCache($this->getAttr('id'));
            }
        }
        return $ret;
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
