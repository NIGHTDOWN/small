<?php

namespace app\api\controller\v1;

use app\common\controller\Api;

class UserAccount  extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /*
     * 注册验证码
     */
    public function registerSms()
    {
        if ($this->request->isPost()) {
            $mobile = input('mobile');
            /** @var \app\api\model\Sms $logic */
            $logic = model('Sms');
            $result = $logic->UserRegister($mobile);
            if ($result) {
                $this->apiResult();
            } else {
                $this->apiResult($logic->getError());
            }
        }
    }

    /**
     * 发送验证码(忘记登录密码)
     */
    public function forgetSms()
    {
        if ($this->request->isPost()) {
            $mobile = input('mobile');
            /** @var \app\api\model\Sms $logic */
            $logic = model('Sms');
            $rs = $logic->UserForgetPwd($mobile);
            if ($rs) {
                $this->apiResult();
            } else {
                $this->apiResult($logic->getError());
            }
        }
    }

    /**
     * 登陆验证码
     */
    public function loginSms()
    {
        if ($this->request->isPost()) {
            $mobile = input('post.mobile/s');
            /** @var \app\api\model\Sms $logic */
            $logic = model('Sms');
            $ret = $logic->sendSmsCode($mobile, 'code_login');
            if ($ret) {
                $this->apiResult();
            } else {
                $this->apiResult($logic->getError());
            }
        }
    }

    /**
     * 手机号验证码登陆
     */
    public function loginBySms()
    {
        if ($this->request->isPost()) {
            $mobile = input('post.mobile/s');
            $sms_code = input('post.sms_code/s');
            $channel = input('f/s','');
            $cs = input('post.cs/s','');
            /** @var \app\api\model\User $logic */
            $logic = model('User');
            $data = $logic->loginBySms($mobile, $sms_code, $channel, $cs);
            if ($data) {
                $this->apiReturn($data);
            }else{
                $this->apiResult($logic->getError());
            }

        }
    }
}
