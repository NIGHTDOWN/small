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
            /** @var \app\api\model\User $logic */
            $logic = model('User');
            $result = $logic->UserRegister($mobile);
            if ($result) {
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
            /** @var \app\api\model\User $logic */
            $logic = model('User');
            $ret = $logic->getLoginSms($mobile);
            if ($ret) {
                $this->apiResult();
            } else {
                $this->apiResult($logic->getError());
            }
        }
    }
}
