<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/31
 * Time: 18:03
 */

namespace app\api\model;

use think\Model;
use app\common\library\Sms as Smslib;

class Sms extends Model
{
    static $template = [
        'register' => 'sms_register',
        'update_password' => 'sms_update_password',
        'binding_mobile' => 'sms_binding_mobile',
        'set_pay_password' => 'sms_set_pay_password',
        'unbind_third_account' => 'sms_unbind_third_account',
        'unbind_burse_account' => 'sms_unbind_burse_account',
        'update_mobile' => 'sms_update_mobile',
        'update_pay_password' => 'sms_update_pay_password',
        'code_login' => 'sms_code_login'
    ];

    static $templateMsg = [
        'register' => '注册验证码',
        'update_password' => '修改/重置登录密码',
        'binding_mobile' => '绑定手机号码',
        'set_pay_password' => '首次设置支付密码',
        'unbind_third_account' => '解绑第三方账户',
        'unbind_burse_account' => '解绑提现账号',
        'update_mobile' => '修改手机号码',
        'update_pay_password' => '忘记支付密码',
        'code_login' => '验证码登录'
    ];

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
        $ret = Smslib::send($mobile, NULL, self::$template['code_login']);
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
        $ret = Smslib::send($mobile, NULL, self::$template['register']);
        if ($ret) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 忘记登录密码
     * @param $mobile
     * @return bool
     */
    public function UserForgetPwd($mobile)
    {
        if (!$mobile) {
            $this->error = 1805;
            return false;
        }
        if (!$this->is_mobile($mobile)) {
            $this->error = 1805;
            return false;
        }
        $ret = Smslib::send($mobile, NULL, self::$template['update_password']);
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
