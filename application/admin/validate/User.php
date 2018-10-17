<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\User as UserCommonModel;

class User extends Validate
{
    protected $field=[
        'id'=>'用户ID',
        'nickname'=>'昵称',
        'head_img'=>'头像',
        'password'=>'密码',
        'mobile'=>'手机号',
        'group_id'=>'用户组ID',
        'status'=>'状态',
    ];
    /**
     * 验证规则
     */
    protected $rule = [
        'id'=>'require|integer',
        'nickname'=>'require|chsDash|length:4,12|unique:user',
        'head_img'=>'url',
        'password'=>'alphaNum|length:6,16',
        'mobile'=>'checkMobile|unique:user',
        'group_id'=>'require|integer',
        'status'=>['require','in'=>UserCommonModel::STATUS],
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'edit' => ['nickname','head_img','password','mobile','group_id','status'],
    ];

    protected function checkMobile($value,$rule,$data)
    {
        if (is_mobile($value)){
            return true;
        }else{
            return '手机号格式不正确';
        }
    }
    
}
