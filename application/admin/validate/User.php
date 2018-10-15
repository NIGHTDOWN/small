<?php

namespace app\admin\validate;

use think\Validate;

class User extends Validate
{
    protected $field=[
        'id'=>'用户ID',
        'nickname'=>'昵称',
        'head_img'=>'头像',
        'password'=>'密码',
        'mobile'=>'手机号',
        'type'=>'用户类型',
        'status'=>'状态',
    ];
    /**
     * 验证规则
     */
    protected $rule = [
        'id'=>'require|integer',
        'nickname'=>'require|chsDash|length:4,12|unique:user',
        'head_img'=>'',
        'password'=>'alphaNum|length:6,16',
        'mobile'=>'checkMobile|unique:user',
        'type'=>'require|in:1,2',
        'status'=>'require|in:0,1',
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
        'edit' => ['nickname','head_img','password','mobile','type','status'],
        'edit_vip' => ['type'],
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
