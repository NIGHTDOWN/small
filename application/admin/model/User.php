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
        'head_img_url',
    ];

    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            //如果有修改密码
            if (isset($changed['password'])) {
                if ($changed['password']) {
                    $row->password = \app\common\library\Auth::instance()->createPassword($changed['password']);
                } else {
                    unset($row->password);
                }
            }
        });
    }

    public function getGenderList()
    {
        return ['1' => __('Male'), '0' => __('Female')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        return UserCommonModel::getStatusText($value);
    }

    public function getIsRobotTextAttr($value, $data)
    {
        $value = $value ? $value : $data['is_robot'];
        return $value?'是':'否';
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['type'];
        return UserCommonModel::getTypeText($value);
    }

    public function getHeadImgUrlAttr($value, $data)
    {
        $value = $value ? $value : $data['head_img'];
        return UserCommonModel::getHeadImgUrl($value);
    }

    public function burse()
    {
        return $this->belongsTo('UserBurse', 'id', 'user_id', [], 'LEFT')->setEagerlyType(0);
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
                    UserCommonModel::deleteHeadImgFile($old_head_img);
                }
            }else{
                unset($data['head_img']);
            }
        }
        try{
            $this->allowField(['nickname','head_img','password','mobile','type','status'])->update($data);
            if ($data['status']==UserCommonModel::STATUS['normal']){
                //更新用户缓存
                UserCommonModel::updateUserCache($data['id'],['nickname'=>$data['nickname'],'head_img'=>$data['head_img'],'mobile'=>$data['mobile'],'type'=>$data['type']]);
            }else{
                //删除用户缓存
                UserCommonModel::deleteUserCache($data['id']);
            }
            return true;
        }catch (\Exception $e){
            $this->error='失败';
            return false;
        }
    }

    /**
     * 编辑vip
     * @param int $id
     * @param int $action 0取消 1设置
     * @return bool
     */
    public function editVip($id,$action)
    {
        $data=[
            'type'=>$action?UserCommonModel::TYPE['vip']:UserCommonModel::TYPE['normal']
        ];
        try{
            $this->where('id','=',$id)->update($data);
            //更新用户缓存
            UserCommonModel::updateUserCache($id,$data);
            return true;
        }catch (\Exception $e){
            $this->error='失败';
            return false;
        }
    }

}
