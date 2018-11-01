<?php

namespace app\common\library;

use app\common\model\User;
use app\common\model\UserRule;
use fast\Random;
use think\Cache;
use think\Config;
use think\Db;
use think\Hook;
use think\Request;
use think\Validate;

class Auth
{

    protected static $instance = null;
    protected $_error = '';
    protected $_logined = FALSE;
    protected $_user = NULL;
    protected $_userid = 0;
    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 604800;
    //Token刷新阈值
    protected $refresh_time = 86400;
    protected $requestUri = '';
    protected $rules = [];
    //默认配置
    protected $config = [];
    protected $options = [];
    protected $allowFields = ['id', 'username', 'nickname', 'mobile', 'avatar', 'score', 'status'];
    protected static $userinfo_prefix = 'ui';
    protected static $userinfo_keeptime = 86400;
    /** 请求来源 */
    const REQUEST_FROM_TYPE=[
        'APP'       =>0,
        'H5'        =>1,
        'PGC'       =>2,
        'TOOLS'     =>3,
        'APPLET'    =>4,
    ];

    public function __construct($options = [])
    {
        if ($config = Config::get('user'))
        {
            $this->options = array_merge($this->config, $config);
        }
        $this->options = array_merge($this->config, $options);
    }

    /**
     * 
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance))
        {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    public function initUserData()
    {
        if($this->_userid && !$this->_user)
        {
            $this->_user = User::get($this->_userid);
        }
    }

    /**
     * 获取User模型
     * @return User
     */
    public function getUser()
    {
        $this->initUserData();
        return $this->_user;
    }

    /**
     * 兼容调用user模型的属性
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $this->initUserData();
        return $this->_user ? $this->_user->$name : NULL;
    }

    /**
     * 根据Token初始化
     *
     * @param string       $token    Token
     * @return boolean
     */
    public function init($token)
    {
        if ($this->_logined)
        {
            return TRUE;
        }
        if ($this->_error)
            return FALSE;
        $data = Token::get($token);
        if (!$data)
        {
            return FALSE;
        }
        $user_id = intval($data['user_id']);
        if ($user_id > 0)
        {
            if($data['expired_in'] < $this->refresh_time)
            {
                Token::refresh($token, $this->keeptime);
            }
            $this->_userid = $user_id;
            $user = $this->getUserinfo();
//            $user = User::get($user_id);
            if (!$user)
            {
                $this->setError('Account not exist');
                return FALSE;
            }
            if ($user['status'] != 'normal')
            {
                $this->setError('Account is locked');
                return FALSE;
            }
//            $this->_user = $user;
            $this->_logined = TRUE;
            $this->_token = $token;

            //初始化成功的事件
            Hook::listen("user_init_successed", $this->_userid);

            return TRUE;
        }
        else
        {
            $this->setError('You are not logged in');
            return FALSE;
        }
    }

    /**
     * 注册用户
     *
     * @param string $username  用户名
     * @param string $password  密码
     * @param string $email     邮箱
     * @param string $mobile    手机号
     * @param array $extend    扩展参数
     * @return boolean
     */
    public function register($username, $password, $email = '', $mobile = '', $extend = [])
    {
        // 检测用户名或邮箱、手机号是否存在
        if (User::getByUsername($username))
        {
            $this->setError('Username already exist');
            return FALSE;
        }
        if ($email && User::getByEmail($email))
        {
            $this->setError('Email already exist');
            return FALSE;
        }
        if ($mobile && User::getByMobile($mobile))
        {
            $this->setError('Mobile already exist');
            return FALSE;
        }

        $ip = request()->ip();
        $time = time();

        $data = [
            'username' => $username,
            'password' => $password,
            'email'    => $email,
            'mobile'   => $mobile,
            'level'    => 1,
            'score'    => 0,
            'avatar'   => '',
        ];
        $params = array_merge($data, [
            'nickname'  => $username,
            'jointime'  => $time,
            'joinip'    => $ip,
            'logintime' => $time,
            'loginip'   => $ip,
            'prevtime'  => $time,
            'status'    => 'normal'
        ]);
        $params['password'] = self::createPassword($password);
        $params = array_merge($params, $extend);

        ////////////////同步到Ucenter////////////////
        if (defined('UC_STATUS') && UC_STATUS)
        {
            $uc = new \addons\ucenter\library\client\Client();
            $user_id = $uc->uc_user_register($username, $password, $email);
            // 如果小于0则说明发生错误
            if ($user_id <= 0)
            {
                $this->setError($user_id > -4 ? 'Username is incorrect' : 'Email is incorrect');
                return FALSE;
            }
            else
            {
                $params['id'] = $user_id;
            }
        }

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try
        {
            $user = User::create($params);
            Db::commit();

            // 此时的Model中只包含部分数据
            $this->_user = User::get($user->id);

            //设置Token
            $this->_token = Random::uuid();
            Token::set($this->_token, $user->id, $this->keeptime);

            //注册成功的事件
            Hook::listen("user_register_successed", $this->_user);

            return TRUE;
        }
        catch (Exception $e)
        {
            $this->setError($e->getMessage());
            Db::rollback();
            return FALSE;
        }
    }

    /**
     * 用户登录
     *
     * @param string    $account    账号,用户名、邮箱、手机号
     * @param string    $password   密码
     * @return boolean
     */
    public function login($account, $password)
    {
        $field = Validate::is($account, 'email') ? 'email' : (Validate::regex($account, '/^1\d{10}$/') ? 'mobile' : 'username');
        $user = User::get([$field => $account]);
     
        if (!$user)
        {
            $this->setError('Account is incorrect');
            return FALSE;
        }

//        if ($user->status != 'normal') // !=必定是false 
        if ($user->status != '1')
        {
        	  
            $this->setError('Account is locked');
            return FALSE;
        }

        if (!self::verifyPassword($password, $user->password))
        {
            $this->setError('Password is incorrect');
            return FALSE;
        }

        //直接登录会员
        $this->direct($user->id);

        return TRUE;
    }

    /**
     * 注销
     *
     * @return boolean
     */
    public function logout()
    {
        if (!$this->_logined)
        {
            $this->setError('You are not logged in');
            return false;
        }
        //设置登录标识
        $this->_logined = FALSE;
        //删除Token
        Token::delete($this->_token);
        //注销成功的事件
        Hook::listen("user_logout_successed", $this->_userid);
        return TRUE;
    }

    /**
     * 修改密码
     * @param string    $newpassword        新密码
     * @param string    $oldpassword        旧密码
     * @param bool      $ignoreoldpassword  忽略旧密码
     * @return boolean
     */
    public function changepwd($newpassword, $oldpassword = '', $ignoreoldpassword = false)
    {
        if (!$this->_logined)
        {
            $this->setError('You are not logged in');
            return false;
        }
        //判断旧密码是否正确
        $this->initUserData();
        if (self::verifyPassword($oldpassword, $this->_user->password) || $ignoreoldpassword)
        {
            $newpassword = self::createPassword($newpassword);
            $this->_user->save(['password' => $newpassword]);

            Token::delete($this->_token);
            //修改密码成功的事件
            Hook::listen("user_changepwd_successed", $this->_user);
            return true;
        }
        else
        {
            $this->setError('Password is incorrect');
            return false;
        }
    }

    /**
     * 直接登录账号
     * @param int $user_id
     * @return boolean
     */
    public function direct($user_id)
    {
        $user = User::get($user_id);
        if ($user)
        {
            ////////////////同步到Ucenter////////////////
            if (defined('UC_STATUS') && UC_STATUS)
            {
                $uc = new \addons\ucenter\library\client\Client();
                $re = $uc->uc_user_login($this->user->id, $this->user->password . '#split#' . $this->user->salt, 3);
                // 如果小于0则说明发生错误
                if ($re <= 0)
                {
                    $this->setError('Username or password is incorrect');
                    return FALSE;
                }
            }

            $ip = request()->ip();
            $time = time();

            //判断连续登录和最大连续登录
            if ($user->logintime < \fast\Date::unixtime('day'))
            {
                $user->successions = $user->logintime < \fast\Date::unixtime('day', -1) ? 1 : $user->successions + 1;
                $user->maxsuccessions = max($user->successions, $user->maxsuccessions);
            }

            $user->prevtime = $user->logintime;
            //记录本次登录的IP和时间
            $user->loginip = $ip;
            $user->logintime = $time;

            $user->save();

            $this->_user = $user;
            $this->_userid = $user->id;

            $this->_token = Random::uuid();
            Token::set($this->_token, $user->id, $this->keeptime);

            $this->_logined = TRUE;

            //登录成功的事件
            Hook::listen("user_login_successed", $this->_user);
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * 检测是否是否有对应权限
     * @param string $path      控制器/方法
     * @param string $module    模块 默认为当前模块
     * @return boolean
     */
    public function check($path = NULL, $module = NULL)
    {
        if (!$this->_logined)
            return false;

        $ruleList = $this->getRuleList();
        $rules = [];
        foreach ($ruleList as $k => $v)
        {
            $rules[] = $v['name'];
        }
        $url = ($module ? $module : request()->module()) . '/' . (is_null($path) ? $this->getRequestUri() : $path);
        $url = strtolower(str_replace('.', '/', $url));
        return in_array($url, $rules) ? TRUE : FALSE;
    }

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined)
        {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取会员基本信息
     */
    public function getUserinfo()
    {
        $userinfo = array_merge($this->getUserinfoCache($this->_userid), Token::get($this->_token));
        return $userinfo;
    }

    public function getUserinfoCache($user_id)
    {
        $userinfo = [];
        if($user_id)
        {
            $redis = Cache::store("redis")->handler();
            $userinfo = $redis->get(self::getUserinfoKey($user_id));
            if(!$userinfo)
            {
                $this->initUserData();
                $data = $this->_user->toArray();
                $allowFields = $this->getAllowFields();
                $userinfo = array_intersect_key($data, array_flip($allowFields));
                $redis->setex(self::getUserinfoKey($user_id), self::$userinfo_keeptime, json_encode($userinfo));
            }else{
                $userinfo = json_decode($userinfo, true);
            }
        }
        return $userinfo;
    }

    public static function getUserinfoKey($user_id)
    {
        return self::$userinfo_prefix . ':' . $user_id;
    }

    /**
     * 获取会员组别规则列表
     * @return array
     */
    public function getRuleList()
    {
        if ($this->rules)
            return $this->rules;
        $this->initUserData();
        $group = $this->_user->group;
        if (!$group)
        {
            return [];
        }
        $rules = explode(',', $group->rules);
        $this->rules = UserRule::where('status', 'normal')->where('id', 'in', $rules)->field('id,pid,name,title,ismenu')->select();
        return $this->rules;
    }

    /**
     * 获取当前请求的URI
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI
     * @param string $uri
     */
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields()
    {
        return $this->allowFields;
    }

    /**
     * 设置允许输出的字段
     * @param array $fields
     */
    public function setAllowFields($fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 删除一个指定会员
     * @param int $user_id 会员ID
     * @return boolean
     */
    public function delete($user_id)
    {
        $user = User::get($user_id);
        if (!$user)
        {
            return FALSE;
        }

        ////////////////同步到Ucenter////////////////
        if (defined('UC_STATUS') && UC_STATUS)
        {
            $uc = new \addons\ucenter\library\client\Client();
            $re = $uc->uc_user_delete($user['id']);
            // 如果小于0则说明发生错误
            if ($re <= 0)
            {
                $this->setError('Account is locked');
                return FALSE;
            }
        }

        // 调用事务删除账号
        $result = Db::transaction(function($db) use($user_id) {
                    // 删除会员
                    User::destroy($user_id);
                    // 删除会员指定的所有Token
                    Token::clear($user_id);
                    return TRUE;
                });
        if ($result)
        {
            Hook::listen("user_delete_successed", $user);
        }
        return $result ? TRUE : FALSE;
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password  密码
     * @param string $salt      密码盐
     * @return string
     */
    public function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 创建密码
     * @param $password
     * @return bool|string
     */
    public static function createPassword($password){
        return  password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 验证密码
     * @param $password
     * @param $hash_password
     * @return bool
     */
    public static function verifyPassword($password, $hash_password){
        return password_verify($password, $hash_password);
    }
    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr)
        {
            return FALSE;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr))
        {
            return TRUE;
        }

        // 没找到匹配
        return FALSE;
    }

    /**
     * 设置会话有效时间
     * @param int $keeptime 默认为永久
     */
    public function keeptime($keeptime = 0)
    {
        $this->keeptime = $keeptime;
    }

    /**
     * 渲染用户数据
     * @param array     $datalist   二维数组
     * @param mixed     $fields     加载的字段列表
     * @param string    $fieldkey   渲染的字段
     * @param string    $renderkey  结果字段
     * @return array
     */
    public function render(&$datalist, $fields = [], $fieldkey = 'user_id', $renderkey = 'userinfo')
    {
        $fields = !$fields ? ['id', 'nickname', 'level', 'avatar'] : (is_array($fields) ? $fields : explode(',', $fields));
        $ids = [];
        foreach ($datalist as $k => $v)
        {
            if (!isset($v[$fieldkey]))
                continue;
            $ids[] = $v[$fieldkey];
        }
        $list = [];
        if ($ids)
        {
            if (!in_array('id', $fields))
            {
                $fields[] = 'id';
            }
            $ids = array_unique($ids);
            $selectlist = User::where('id', 'in', $ids)->column($fields);
            foreach ($selectlist as $k => $v)
            {
                $list[$v['id']] = $v;
            }
        }
        foreach ($datalist as $k => &$v)
        {
            $v[$renderkey] = isset($list[$v[$fieldkey]]) ? $list[$v[$fieldkey]] : NULL;
        }
        unset($v);
        return $datalist;
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }

}
