<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/31
 * Time: 14:35
 */
namespace app\api\model;

use think\Db;
use think\Model;
use app\common\model\User AS UserModel;
use wsj\ali\ElasticSearch;

class User extends Model
{
    /**
     * 短信验证码登陆
     * @param $mobile
     * @param $sms_code
     * @param string $channel
     * @param string $cs
     * @return array|bool
     */
    public function loginBySms($mobile, $sms_code, $channel = '', $cs = '')
    {
        if (!$sms_code) {
            $this->error = 1806;
            return false;
        }
        if (!$mobile) {
            $this->error = 1805;
            return false;
        }
        if (!is_mobile($mobile)) {
            $this->error = 1805;
        }
        $sms_id = \app\common\library\Sms::check($mobile, $sms_code, \app\api\model\Sms::$template['code_login']);
        if (!$sms_id) {
            $this->error = 1807;
            return false;
        }
        $user = $this->getByMobile($mobile);
        if (!$user) {
            //没有用户,创建新的
            $user_id = $this->createUser($mobile, '', '', '', $channel);
            $user = $this->getCacheUserData($user_id);
        } else {
            $user = $this->getCacheUserData(0, $user);
        }
        if (!$user) {
            $this->error = 1808;
            return false;
        }

        // 登陆认证通过后
        // 改用框架登录规则
        $auth = new \app\common\library\Auth();
        $auth->direct($user['id']);
        $data = $auth->getUserinfo();
        // $data = $this->loginAuthPassAfter($user,LoginLog::LOGIN_TYPE['mobile_sms'], $cs);
        // $sms_model->invalidate($sms_id);
        return $data;
    }

    /**
     * 创建用户
     * @param string $mobile
     * @param string $password
     * @param bool $is_oauth
     * @param string $head_img
     * @param string $channel
     * @return bool|int|string
     */
    public function createUser($mobile = '', $password = '', $is_oauth = false, $head_img = '', $channel = '')
    {
        $now = time();
        $data = [
            'user_name' => UserModel::createUserName(),
            'nickname' => UserModel::createNickname(),
            'password' => $password ? UserModel::createPassword($password) : '',
            'head_img' => $head_img,
            'mobile' => $mobile,
            'bind_mobile' => $mobile,
            'create_time' => $now,
            'is_valid_mobile' => 1,
            'status' => UserModel::STATUS['normal'],
            'channel' => $channel,
        ];
        if ($is_oauth) {
            $data['is_valid_mobile'] = 0;
        }
        Db::startTrans();
        try {
            $user_id = Db::name('user')->insertGetId($data);
            // 用户钱包
            Db::name('user_burse')->insert(['user_id' => $user_id]);
            // es
            $this->addEs($user_id, $data['nickname']);
            Db::commit();
        } catch (\ErrorException $e) {
            Db::rollback();
            return false;
        }
        //关注小印象官方账号
        model('FollowUser')->followOfficialAccount($user_id);
        return $user_id;
    }

    /**
     * 获取
     * @param $user_id
     * @param array $base_data
     * @return array|bool
     */
    public function getCacheUserData($user_id,$base_data=[])
    {
        if ($base_data) {
            $data = $base_data;
        } else {
            $data = $this->getInfo($user_id);
        }
        if (!$data || $data['status'] != UserModel::STATUS['normal']) {
            return false;
        }
        $user = [
            'id' => $data['id'],
            'user_name' => $data['user_name'],
            'nickname' => $data['nickname'],
            'head_img' => $data['head_img'],
            'mobile' => $data['mobile'],
            'create_time' => $data['create_time'],
            'single_mission' => $data['single_mission'],
            'invitation_user_id' => $data['invitation_user_id'],
            'pay_password' => empty($data['pay_password']) ? 1 : 0,
            'user_grade' => $data['group_id']
        ];
        return $user;
    }

    /**
     * 获取一行
     * @param $user_id
     * @return array|null
     */
    public function getInfo($user_id)
    {
        $data = Db::name('user')
            ->master()
            ->where([
                'id' => ['=', $user_id],
                'status' => ['<>', UserModel::STATUS['delete']]
            ])
            ->find();

        return $data;
    }

    /**
     * 增加es
     * @param $user_id
     * @param $nickname
     */
    public function addEs($user_id, $nickname)
    {
        $data = [
            'id' => $user_id,
            'nickname' => $nickname,
        ];
        $elastic_search = new ElasticSearch();
        $elastic_search->name('user')->insert($data);
    }

    /**
     * 获取一行,根据手机号
     * @param $mobile
     * @return array|null
     */
    public function getByMobile($mobile)
    {
        $data = Db::name('user')
            ->master()
            ->where([
                'mobile' => ['=', $mobile],
                'status' => ['<>', UserModel::STATUS['delete']]
            ])
            ->find();

        return $data;
    }

    /**
     * 获取官方账号user_id
     */
    public function getOfficialAccount()
    {
        return config('site.official_account_id');
    }
}
