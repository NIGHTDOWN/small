<?php

// 公共助手函数

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name)
            return $name;
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }

}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int $size 大小
     * @param string $delimiter 分隔符
     * @return string
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++)
            $size /= 1024;
        return round($size, 2) . $delimiter . $units[$i];
    }

}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int $time 时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }

}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time 时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }

}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string $url 资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $url = preg_match("/^https?:\/\/(.*)/i", $url) ? $url : \think\Config::get('upload.cdnurl') . $url;
        if ($domain && !preg_match("/^(http:\/\/|https:\/\/)/i", $url)) {
            if (is_bool($domain)) {
                $public = \think\Config::get('view_replace_str.__PUBLIC__');
                $url = rtrim($public, '/') . $url;
                if (!preg_match("/^(http:\/\/|https:\/\/)/i", $url)) {
                    $url = request()->domain() . $url;
                }
            } else {
                $url = $domain . $url;
            }
        }
        return $url;
    }

}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param    string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === FALSE) {
                return FALSE;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        } elseif (!is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE) {
            return FALSE;
        }
        fclose($fp);
        return TRUE;
    }

}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname 目录
     * @param bool $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname))
            return false;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }

}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest 目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }

}

if (!function_exists('mb_ucfirst')) {

    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }

}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items 数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields)
            return $items;
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = isset($v['field']) ? $v['field'] : $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = isset($v['display']) ? $v['display'] : str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = isset($v['primary']) ? $v['primary'] : '';
            $v['column'] = isset($v['column']) ? $v['column'] : 'name';
            $v['model'] = isset($v['model']) ? $v['model'] : '';
            $v['table'] = isset($v['table']) ? $v['table'] : '';
            $v['name'] = isset($v['name']) ? $v['name'] : str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = $model->where($primary, 'in', $ids[$v['field']])->column("{$primary},{$v['column']}");
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $v[$fieldsArr[$n]['display']] = implode(',', array_intersect_key($result[$n], $curr));
                }
            }
        }
        return $items;
    }

}

if (!function_exists('var_export_short')) {

    /**
     * 返回打印数组结构
     * @param string $var 数组
     * @param string $indent 缩进字符
     * @return string
     */
    function var_export_short($var, $indent = "")
    {
        switch (gettype($var)) {
            case "string":
                return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
            case "array":
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? "" : var_export_short($key) . " => ")
                        . var_export_short($value, "$indent    ");
                }
                return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
            case "boolean":
                return $var ? "TRUE" : "FALSE";
            default:
                return var_export($var, TRUE);
        }
    }

}

/**
 * 获取随机字符串
 * @param int $length  长度
 * @param int $type   类型  0 混合 1 数字 2 字母
 * @param int $upper  大写  0 小写 1 大写
 * @return string
 */
function get_rand_string($length,$type=0,$upper = 0){
    $leters1 = range('a','z');
    $leters2 = range('0','9');
    $letters = [];
    if($type == 0){
        $letters = array_merge($leters1,$leters2);
    }elseif ($type == 1){
        $letters = $leters2;
    }elseif ($type == 2){
        $letters = $leters1;
    }
    $pos = [];
    $max = count($letters) - 1;
    for($i = 0; $i < $length;$i++){
        $pos[] = mt_rand(0,$max);
    }
    $temp = [];
    foreach($pos as $v){
        $temp[] = $letters[$v];
    }
    $temp = implode('',$temp);
    if($upper) $temp = strtoupper($temp);
    return $temp;
}

/**
 * 格式化时间
 * @param int $time
 * @return string
 */
function format_time($time){
    $time=(int)$time;
    $t=time()-$time;
    $f=array(
        '604800'=>'一周',
        '86400'=>'天',
        '3600'=>'小时',
        '60'=>'分钟',
        '1'=>'秒'
    );
    foreach ($f as $k=>$v)    {
        if (0 !=$c=floor($t/(int)$k)) {
            if ($k=='604800'){
                $str=date('Y-m-d',$time);
            }else{
                $str=$c.$v.'前';
            }
            return $str;
        }
    }
}

/**
 * 创建密码
 * @param string $pwd  密码
 * @return bool|string
 */
function create_password($pwd){
    return  password_hash($pwd, PASSWORD_DEFAULT);
}


/**
 * 验证密码
 * @param string $pwd  密码
 * @param string $hash_pwd  加密串
 * @return bool
 */
function verify_password($pwd,$hash_pwd){
    return password_verify($pwd,$hash_pwd);
}


/**
 * 消息入队列
 * @param array $msg_body  数据
 * @param int $time  延迟生效时间
 * @return bool
 */
function publish_message($msg_body,$time = 0){
    $data = serialize($msg_body);
    $config = config('ali.message_service');
    $queue_name = $config['queue'][mt_rand(0,count($config['queue'])-1)];
    $client = new \Aliyun\MNS\Client($config['end_point'],$config['access_key'],$config['access_secret']);
    $queue = $client->getQueueRef($queue_name);
    $now = time();
    if ( ($time > 0) && ($time >  $now ) ){
        $request = new \Aliyun\MNS\Requests\SendMessageRequest($data,$time - $now);
    }else{
        $request = new \Aliyun\MNS\Requests\SendMessageRequest($data);
    }
    try
    {
        $res = $queue->sendMessage($request);
        return $res->getMessageId();
    }
    catch (Aliyun\MNS\Exception\MnsException $e)
    {
        // 4. 可能因为网络错误，或MessageBody过大等原因造成发送消息失败，这里CatchException并做对应的处理。
        return false;
    }
}

/**
 * 安全显示手机号码
 * @param string $mobile
 * @return string
 */
function mobile_safe_display($mobile = '')
{
    return mb_substr($mobile,0,3) . str_repeat('*',5) . mb_substr($mobile,strlen($mobile)-3);
}

/**
 * 安全显示真实姓名
 * @param string $real_name
 * @return string
 */
function real_name_safe_display($real_name = '')
{
    $len = mb_strlen($real_name);
    if (!$len) return $real_name;
    $first = mb_substr($real_name,0,1);
    return $first . str_repeat('*',mb_strlen($real_name) - 1);
}

/**
 * 安全显示邮箱
 * @param string $email
 * @return string
 */
function email_safe_display($email = '' )
{
    $rs = explode('@',$email);
    $str = $rs[0];
    $len = mb_strlen($str);
    if($len < 3 ) return $email;
    return mb_substr($str,0,2).str_repeat('*',$len - 2) . '@'.$rs[1];
}

/**
 * 特殊字符反转义
 * @param string $value
 * @return string
 */
function special_chars_decode($value)
{
    return htmlspecialchars_decode($value);
}

/**
 * 是否为手机号
 * @param string $mobile
 * @return bool
 */
function is_mobile($mobile){
    return preg_match("/^[1][3-9][0-9]{9}$/",$mobile) ? true : false;
}

/**
 * 获取缓存前缀
 * @return mixed
 */
function get_cache_prefix()
{
    return config('cache.redis')['prefix'];
}