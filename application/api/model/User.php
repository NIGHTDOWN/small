<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/31
 * Time: 14:35
 */
namespace app\api\model;

use think\Model;
use app\common\library\Sms as Smslib;

class User extends Model
{
    /**
     * 获取登陆验证码
     * @param $mobile
     * @return bool
     */
    public function getLoginSms($mobile)
    {
        if (!$mobile) {
            $this->error = 1805;
            return false;
        }
        if (!$this->is_mobile($mobile)) {
            $this->error = 1805;
            return false;
        }
        $ret = Smslib::send($mobile, NULL, 'code_login');
        if ($ret) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取注册验证码
     * @param $mobile
     * @return bool
     */
    public function UserRegister($mobile)
    {
        if (!$mobile) {
            $this->error = 1805;
            return false;
        }
        if (!$this->is_mobile($mobile)) {
            $this->error = 1805;
            return false;
        }
        $ret = Smslib::send($mobile, NULL, 'register');
        if ($ret) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证手机号码
     * @param $mobile
     * @return bool
     */
    function is_mobile($mobile){
        return preg_match("/^[1][3-9][0-9]{9}$/", $mobile) ? true : false;
    }
}
