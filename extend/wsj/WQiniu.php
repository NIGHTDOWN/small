<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/2
 * Time: 10:40
 */
//七牛操作类
namespace wsj;
use app\common\logic\ActivityTop;
use app\common\logic\Robot;
use app\common\logic\User;
use app\common\model\Video;
use \Qiniu\Auth;
use \Qiniu\Storage\BucketManager;
use \Qiniu\Processing\PersistentFop;
use Qiniu\Storage\UploadManager;
use think\Db;
use think\exception\ErrorException;
use think\facade\Log;
use Yurun\Until\Lock\Redis;

class WQiniu{
    public static $qiniuConfig;

    public static $qiniuAuthInstance;

    public static $buketManagerInstance;

    public static $uploadManagerInstance;

    public static $PersistentFopInstance;

    public static $originalVideoInfos = [];

    public static $originalVideoUrls = [];

    public static $networkingProtocol = 'http';

    public static $publicVideoFormat = 'mp4';

    public static $privateVideoFormat = 'm3u8';

    public static $privateSecretKey = 'dH8jqShxWCJNpwlx';

    public static $privateVideoCachePrefix = 'private_video_';

    public static $publicVideoBitRate = "2m";

    public static $privateVideoBitRates = [
        0 => '1m',
        1 => '1.5m',
        2 => '3m',
        3 => '5m',
    ];

    public static $videoType = [
             0 => '小视频',
             1 => '链接库',
    ];

    public function __construct($config = [])
    {
        self::loadConfig($config);
    }

    public static function loadConfig($config = []){
        if($config){
            self::$qiniuConfig = $config;
        }elseif (!self::$qiniuConfig){
            self::$qiniuConfig = config('qiniu.');
        }
        return self::$qiniuConfig;
    }

    public static function getAuthInstance($config = []){
        self::loadConfig($config);
        if(!self::$qiniuAuthInstance) self::$qiniuAuthInstance = new Auth(self::$qiniuConfig['ak'],self::$qiniuConfig['sk']);
        return self::$qiniuAuthInstance;
    }

    public static function getBuketManagerInstance($config=[]){
        if(!self::$buketManagerInstance) self::$buketManagerInstance = new BucketManager(self::getAuthInstance($config));
        return self::$buketManagerInstance;
    }

    public static function getUploadManagerInstance($config=[]){
        if(!self::$uploadManagerInstance) self::$uploadManagerInstance = new UploadManager(self::getAuthInstance($config));
        return self::$uploadManagerInstance;
    }

    public static function getPersistentFopInstance($config=[]){
        if(!self::$PersistentFopInstance) self::$PersistentFopInstance = new PersistentFop(self::getAuthInstance($config));
        return self::$PersistentFopInstance;
    }

    public static function getPipeline(){
        $pipelines = [
            'shortvideo',
            'shortvideo1',
            'shortvideo2',
            'avvod-pipeline',
        ];
        return $pipelines[mt_rand(0,count($pipelines)-1)];
    }

    public static function getDomain(){
        $domain=config('site.video_coding_call');
        return $domain;
    }

    public static function copy($from_bucket, $from_key, $to_bucket, $to_key){
        return self::getBuketManagerInstance()->copy($from_bucket, $from_key, $to_bucket, $to_key);
    }

    public static function delete($bucket, $key){
        return self::getBuketManagerInstance()->delete($bucket,$key);
    }

    /**
     * 批量删除资源
     * @param string $bucket 存储空间
     * @param array $keys 文件key数组
     * @return array 处理结果或错误信息(可能发生部分完成情况)
     */
    public static function batchDelete($bucket,$keys)
    {
        $bucketManager=self::getBuketManagerInstance();
        $ops = $bucketManager->buildBatchDelete($bucket, $keys);
        list($ret, $err) = $bucketManager->batch($ops);
        if ($err) {
            return $err;
        } else {
            return $ret;
        }
    }

    public static function getUploadNotifyUrl(){
        return self::getDomain().url('api/QiniuNotify/upload');
    }

    public static function getScreenShotNotifyUrl(){
        return self::getDomain().url('api/QiniuNotify/screenshot');
    }

    public static function getTranscodePublicVideoNotifyUrl(){
        return self::getDomain().url('api/QiniuNotify/transcodePublicVideo');
    }

    public static function getTranscodePrivateVideoNotifyUrl(){
        return self::getDomain().url('api/QiniuNotify/transcodePrivateVideo');
    }

    public static function getSaveOriginalVideoNotifyUrl(){
        return self::getDomain().url('api/QiniuNotify/saveOriginalVideo');
    }

    public static function getJoinParams($params){
        if(!count($params)) return '';
        $temp = [];
        foreach($params as $k => $v) $temp[] = "{$k}/{$v}";
        return implode('/',$temp);
    }

    public static function verifyUploadNotifyAuth(){
        return  self::verifyNotifyAuth(self::getUploadNotifyUrl());
    }

    public static function verifyNotifyAuth($url){
        //获取回调的body信息
        $callbackBody = request()->getInput();
//回调的contentType
        $contentType = 'application/x-www-form-urlencoded';
//回调的签名信息，可以验证该回调是否来自七牛
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
//七牛回调的url，具体可以参考：http://developer.qiniu.com/docs/v6/api/reference/security/put-policy.html
        return $isQiniuCallback = self::getAuthInstance()->verifyCallback($contentType, $authorization, $url, $callbackBody);
    }

    public static function createKey(){
        return md5(uniqid().get_rand_string(12));
    }

    public static function getUploadToken($bucket = '', $key = null, $expires = 3600, $policy = null, $strictPolicy = true){
        return  self::getAuthInstance()->uploadToken($bucket, $key, $expires , $policy, $strictPolicy);
    }

    public static function getUploadHeadImgToken(){
        self::loadConfig();
        return self::getUploadToken(self::$qiniuConfig['headimg_bkt']);
    }

    public function getOriginalVideoUploadData($user_id = 0 ,$key = null ,$expires = 3600)
    {
        if(!$key) $key = self::createKey();
        $policy = [
            'callbackUrl' => self::getUploadNotifyUrl(),
            'callbackBody' => 'name=$(fname)&hash=$(etag)&type=video&key='.$key.'&user_id='.$user_id,
        ];
        return [
            'token' => self::getUploadToken(
                self::$qiniuConfig['public_video_bkt'],
                null,$expires,$policy
            ),
            'key' => $key,
        ];
    }

    public function getCoverImgUploadData($user_id = 0 ,$key = null ,$expires = 3600)
    {
        $key = $key ?? self::createKey();
        $policy = [
            'callbackUrl' => self::getUploadNotifyUrl(),
            'callbackBody' => 'name=$(fname)&hash=$(etag)&type=cover_img&key='.$key.'&user_id='.$user_id,
        ];
        return [
            'token' => self::getUploadToken(
                self::$qiniuConfig['screenshot_bkt'],
                null,$expires,$policy
            ),
            'key' => $key,
        ];
    }


    public function getOriginalVideoUrl($key){
        if(isset(self::$originalVideoUrls[$key])) return self::$originalVideoUrls[$key];
        return self::$originalVideoUrls[$key] =
            (self::$qiniuConfig['public_video_bkt_protocol'] ? self::$qiniuConfig['public_video_bkt_protocol'] : 'http') .'://'.
            self::$qiniuConfig['public_video_bkt_domain'] .'/'.
            $key;
    }

    public function getOriginalVideoInfo($key){
        if(isset(self::$originalVideoInfos[$key])) return self::$originalVideoInfos[$key];
        try {
            $rs = file_get_contents($this->getOriginalVideoUrl($key) . '?avinfo');
        } catch (\Exception $e) {
            $rs = false;
        }
        return $rs ? self::$originalVideoInfos[$key] = json_decode($rs,true) : $rs;
    }

    public function getOriginalVideoParams($key){
        try{
            $video_info = $this->getOriginalVideoInfo($key);
            if(!$video_info) return false;
            foreach($video_info['streams'] as $stream){
                if(strtolower($stream['codec_type']) == 'video'){
                       return [
                           'bit_rate' => $stream['bit_rate'],
                           'duration' => $stream['duration'],
                           'width' => $stream['width'],
                           'height' => $stream['height'],
                       ];
                }
            }
            return false;
        }catch (ErrorException $e){
            return false;
        }
    }

    public function checkOriginalVideo($key,$video_type=0){
        if($video_type == 0){
            $video_info = Db::name('video')->master()->where('key',$key)->find();
            $vid = $video_info['id'];
            Db::name('video')->where('id','eq',$vid)->update(['process_status' => 1]);
        }else {
            $video_info = Db::name('resource_video')->master()->where('key',$key)->find();
            $vid =$video_info['resource_id'];
            Db::name('resource_video')->where('resource_id','eq',$vid)->update(['process_status' => 1]);
        }
        $duration = tofloat2($video_info['duration']);
        $middle_sec = $duration / 2;
        $secs = [0,intval($middle_sec),floor($duration)-1];
        $prefopids = [];
        $data = [];
        $screenshot_data=[];
        foreach($secs as $k => $s){
            $file_name = $key."_".md5($vid.'_'.$video_type).'_'.$k.".jpg";
            $fops = "vframe/jpg/offset/{$s}|saveas/" . \Qiniu\base64_urlSafeEncode(self::$qiniuConfig['screenshot_bkt'] .':'.$file_name);
            $pipeline = self::getPipeline();
            list($id, $err)  =  self::getPersistentFopInstance()->execute(
                $video_info['save_original_bkt'] ? self::$qiniuConfig['original_video_bkt'] : self::$qiniuConfig['public_video_bkt'],
                $key,
                $fops,
                $pipeline,
                self::getScreenShotNotifyUrl()
            );
            if($id){
                $prefopids[] = $id;
                $data[] = [
                    'persistent_id' => $id,
                    'pipeline' => $pipeline,
                    'create_time' => time(),
                    'status' => 0
                ];
                $screenshot_data[] = [
                    'vid' => $vid,
                    'video_type' => $video_type,
                    'persistent_id' => $id,
                    'file_name' => $file_name,
                    'order_sort' => $k,
                    'status' => 0,
                ];
            }
        }
        if($screenshot_data) Db::name('screenshot')->insertAll($screenshot_data);
        if($data) Db::name('pipeline')->insertAll($data);
        return $prefopids;
    }

    /**
     * 获取码率(单位m)
     * @param string $key
     * @return float $bit_rate
     */
    public function getBitRate($key = ''){
        $video_info = $this->getOriginalVideoInfo($key);
        return $video_info['format']['bit_rate'] / 1024 /1000;
    }

    public static function checkPicSex($url){
        $rs = file_get_contents($url."?qpulp");
        $rs = json_decode($rs,true);
//        return $rs && ($rs['code'] == 0) && ($rs['result']['label'] > 0) && ($rs['result']['score'] >= 0.2) ? true : false;
        return $rs && ($rs['code'] == 0) && ($rs['result']['label'] > 0) ? true : false;
    }

    public static function checkPicDanger($url){
        $rs = file_get_contents($url."?qterror");
        $rs = json_decode($rs,true);
//        return $rs && ($rs['code'] == 0) && ($rs['result']['label'] == 0) && ($rs['result']['score'] >= 0.2) ? true :false;
        return $rs && ($rs['code'] == 0) && ($rs['result']['label'] == 0) ? true :false;
    }

    public static function checkPicQpolitician($url){
        $rs = file_get_contents($url.'?qpolitician');
        $rs = json_decode($rs,true);
        return $rs && ($rs['code'] == 0) && !isset($rs['result']['detections'][0]['value']['name']) ? true : false;
    }

    public static function checkScreentshot($persistent_id,$config = []){
        self::loadConfig($config);
        $obj = Db::name('screenshot')->master()->where('persistent_id',$persistent_id)->find();
        $vid = $obj['vid'];
        $video_type = $obj['video_type'];
        $video_info = Db::name( $video_type == 0 ? 'video' : 'resource_video')->master()->find($vid);
        $url ='http://'. self::$qiniuConfig['screenshot_source_domain'] . '/' . $obj['file_name'];
        $data = [
            'sex' => $video_info['save_original_bkt']  ?  1 : self::checkPicSex($url),
            'danger' =>  $video_info['save_original_bkt']  ?  1 : self::checkPicDanger($url),
            'qpolitician' =>  $video_info['save_original_bkt']  ?  1 : self::checkPicQpolitician($url),
            'time' => time(),
            'status' => 1,
        ];
        $cache_config = config('cache.');
        if(strtolower($cache_config['type']) == 'redis'){
            $redis_config  = [];
            if(isset($cache_config['host'])) $redis_config['host']  = $cache_config['host'];
            if(isset($cache_config['port'])) $redis_config['port']  = $cache_config['port'];
            if(isset($cache_config['password'])) $redis_config['password']  = $cache_config['password'];
            $lock_key = 'screenshot_lock_'.$video_type.$vid;
            $lock = new Redis($lock_key,$redis_config);
            $lock->lock();
        }
        Db::name('screenshot')->where('id','eq',$obj['id'])->update($data);
        $count = Db::name('screenshot')->master()->where([
            ['vid','eq',$vid],
            ['video_type','eq',$video_type],
            ['status','eq',1],
        ])->count();
        if(isset($lock)) $lock->unlock();
        if($count >= 3){

            if ( $video_type == 0 ) {
                Db::name('video')->where('id','eq',$vid)->update(['process_status' => 0]);
            } else {
                Db::name('resource_video')->where('resource_id','eq',$vid)->update([
                    'process_status' => 0,
                ]);
            }

            if (!$video_info['exists_cover_img']) {
                $cover_imgs = Db::name('screenshot')
                    ->master()
                    ->where([
                        ['vid','eq',$vid],
                        ['video_type','eq',$video_type],
                    ])
                    ->order('order_sort ASC')
                    ->column('file_name');
                if($video_type == 0){
                    //小视频
                    Db::name('video_extend')->where('video_id','eq',$vid)->update(['cover_imgs' => serialize($cover_imgs),]);
                }else{
                    //链接库视频
                    Db::name('resource')->where('id','eq',$vid)->update(['cover_img'=>$cover_imgs[0]]);
                    Db::name('resource_video')->where('resource_id','eq',$vid)->update([
                        'cover_imgs' =>serialize($cover_imgs),
                    ]);
                }
            }else{
                if ($video_info['save_original_bkt']) {
                    $cover_imgs = ($video_type == 0) ? Db::name('video_extend')->where('video_id','eq',$vid)->value('cover_imgs') : $video_info['cover_imgs'];
                    Video::displayCoverImgs($video_info['exists_cover_img'],$cover_imgs);
                }
            }

            $miss_count = Db::name('screenshot')->master()->where([
                ['sex|danger|qpolitician','eq','0'],
                ['vid','eq',$vid],
                ['video_type','eq',$video_type],
            ])->count();
            if($miss_count){
                //不合法
                if($video_type == 0){
                    Db::name('video')->where('id','eq',$vid)->update([
                        'process_status' => 0,
                        'status' => Video::$status['ROBOT_FAILD']
                    ]);
                }else{
                    Db::name('resource_video')->where('resource_id','eq',$vid)->update([
                        'process_status' => 0,
                        'status' => Video::$status['ROBOT_FAILD']
                    ]);
                }
            }else{
                //合法
                if($video_type == 0){
                    $video_status = Video::$status['ROBOT_SUCCESS'];
                    $user_info = Db::name('user')->where(['id' => $video_info['user_id'], 'status' => 1])->find();
                    if ($user_info && $user_info['type'] == \app\common\model\User::TYPE['BIGV']) {
                        $video_status = Video::$status['DISPLAY'];
                        self::transcodeVideo($vid);
                        ActivityTop::doTopData($vid);//启动排行数据
                    }
                    Db::name('video')->where('id','eq',$vid)->update([
                        'process_status' => 0,
                        'status' => $video_status
                    ]);

                }else{
                    Db::name('resource_video')->where('resource_id','eq',$vid)->update([
                        'process_status' => 0,
                        'status' => Video::$status['ROBOT_SUCCESS']
                    ]);
                }
            }
        }
    }

    public static function transcodeVideo($vid,$video_type = 0,$config = []){
        self::loadConfig($config);
        if($video_type == 0){
            $video = Db::name('video')
                ->master()
                ->where('id',$vid)
                ->find();
        }else{
            $video = Db::name('resource_video')
                ->master()
                ->where('resource_id',$vid)
                ->find();
        }
        if($video['process_status'] == 1) return false;
        $video_type == 0 ? Db::name('video')->where('id','eq',$vid)->update(['process_status' => 1]) : Db::name('resource_video')->where('resource_id','eq',$vid)->update(['process_status' => 1]);
        $key = $video['key'];
        $save_original_btk = $video['save_original_bkt'];
        $original_bit_rate = $video['original_bit_rate'];
        $extra = [];
//        self::transcodePublicVideo($vid,$video_type,$key,$original_bit_rate,$save_original_btk,$extra,$config);
        self::transcodePublicVideo($vid,$video_type,$video,$extra,$config);
        self::transcodePrivateVideo($vid,$video_type,$key,$original_bit_rate,$save_original_btk,$extra,$config);
        return true;
    }

    /**
     * 转公有资源视频
     * @param $vid 视频id
     * @param $video_type 视频类型
     * @param $key 视频key
     * @param $original_bit_rate 原始码率
     * @param int $use_original_bkt 是否有保存原有视频
     * @param array $extra 附加参数
     * @param array $config 配置
     */
//    public static function transcodePublicVideo($vid,$video_type,$key,$original_bit_rate,$use_original_bkt = 0,$extra=[],$config = [])
    public static function transcodePublicVideo( $vid = 0,$video_type = 0,$video_info = [],$extra = [],$config = []){
        self::loadConfig($config);
        $original_bit_rate = $video_info['original_bit_rate'];
        $save_original_bkt = $video_info['save_original_bkt'];
        $key = $video_info['key'];
        $vb = floatval($original_bit_rate) > floatval(self::$publicVideoBitRate) ? floatval(self::$publicVideoBitRate) : $original_bit_rate;
        $wm_pic_w = in_array($video_info['direction'],[3,4,5,6,0]) ? '/thumbnail/'.intval(floatval($video_info['width']) * 0.15).'x' :  '/thumbnail/'.intval(floatval($video_info['width']) * 0.3).'x';
        $watermark_picture = self::$qiniuConfig['watermark_picture']."?imageMogr2{$wm_pic_w}";
        $params = [
            'avthumb' => self::$publicVideoFormat, //封装格式
            'vcodec'  => 'libx264', //视频编码格式
            'noDomain' => 1,
            'hlsMethod' => 'qiniu-protection-10',
            'vb' => $vb.'m', // 码率
            'wmImage' => \Qiniu\base64_urlSafeEncode($watermark_picture), //视频水印
            'wmGravity' => 'NorthWest',
            'wmOffsetX' => '30',
            'wmOffsetY' => '30',
        ];
        if($extra) $params += $extra;
        $fops = self::getJoinParams($params);
        $fops.= '|saveas/'.\Qiniu\base64_urlSafeEncode(self::$qiniuConfig['public_video_bkt'] .':'.$key.'.'.self::$publicVideoFormat);//保存的文件名;
        $pipeline = self::getPipeline();
        list($persistent_id, $err)  =  self::getPersistentFopInstance()->execute(
            $save_original_bkt ?  self::$qiniuConfig['original_video_bkt'] : self::$qiniuConfig['public_video_bkt'],
            $key,
            $fops,
            $pipeline,
            self::getTranscodePublicVideoNotifyUrl()
        );
        if($persistent_id){
            Db::name('public_video')->insertGetId([
                'vid' => $vid,
                'video_type' => $video_type,
                'persistent_id' => $persistent_id,
                'bit_rate' => $vb,
                'definition' => 0,
                'status' => 0,
            ]);
        }

    }

    /***
     * 转私有资源视频
     * @param $vid
     * @param $video_type
     * @param $key
     * @param $bit_rate
     * @param int $use_original_bkt
     * @param array $extra
     * @param array $config
     */
    public static function transcodePrivateVideo($vid,$video_type,$key,$original_bit_rate,$use_original_bkt = 0,$extra=[],$config = []){
        self::loadConfig($config);
        $vbs = [];
        foreach(self::$privateVideoBitRates as $k => $bit_rate){
               if(floatval($original_bit_rate) > floatval($bit_rate)){
                   $vbs[$k] = floatval($bit_rate);
               }
        }
        if(count($vbs) == 0){
            $vbs[] = $original_bit_rate;
        }
        if($video_type == 0){
            Db::name('video')->where('id','eq',$vid)->setField('need_private_video_transcode',count($vbs));
        }else{
            Db::name('resource_video')->where('resource_id','eq',$vid)->setField('need_private_video_transcode',count($vbs));
        }

        foreach ($vbs as $bit_rate_type => $vb){
            $params = [
                'avthumb' => self::$privateVideoFormat, //转码格式
//                'hlsKey' => \Qiniu\base64_urlSafeEncode(self::$privateSecretKey), //DRM 加密所需的 16 字节密钥
//                'hlsKeyUrl' => \Qiniu\base64_urlSafeEncode('wsj'),
//                'hlsMethod' => 'qiniu-protection-10',
                'noDomain' => 1,
                'segtime' => 5, //切片片段时间长度
                'vb' => $vb.'m',
                'pattern' => \Qiniu\base64_urlSafeEncode("{$key}_{$video_type}_{$vid}_{$bit_rate_type}_$(count)"),
//                'gop' => 600,
            ];
            if($extra) $params += $extra;
            $fops = self::getJoinParams($params);
            $fops.= '|saveas/'.\Qiniu\base64_urlSafeEncode(self::$qiniuConfig['private_video_bkt'] .':'.$key.'_'.$bit_rate_type.".".self::$privateVideoFormat);//保存的文件名;
            $pipeline = self::getPipeline();
            list($persistent_id, $err)  =  self::getPersistentFopInstance()->execute(
                $use_original_bkt ?  self::$qiniuConfig['original_video_bkt'] : self::$qiniuConfig['public_video_bkt'],
                $key,
                $fops,
                $pipeline,
                self::getTranscodePrivateVideoNotifyUrl()
            );
            if($persistent_id){
                Db::name('private_video')->insertGetId([
                    'vid' => $vid,
                    'video_type' => $video_type,
                    'persistent_id' => $persistent_id,
                    'bit_rate' => $vb,
                    'status' => 0,
                ]);
            }
        }
    }


    public static function transcodeNotify($type = 'public'){
        $notify_data = request()->getInput();
        $id = input('post.id','');
        if($id){
            $table = $type == 'private' ? 'private_video' : "public_video";
            $video = Db::name($table)->master()->where('persistent_id','eq',$id)->find();
            if($video){
                if($video['status'] == 1) return json(['success' => true]);
                $vid = $video['vid'];
                $video_type = $video['video_type'];
                $notify_data = json_decode($notify_data,true);
                Db::name($table)->where('id',$video['id'])->update([
                    'key' => $notify_data['items'][0]['key'],
                    'status' => 1,
                ]);
                $vtable = $video_type == 0 ?  'video' : 'resource_video';
                $map = $video_type == 0 ?  [['id','eq',$vid]] : [['resource_id','eq',$vid]];
                Db::name($vtable)->where($map)->setInc($type.'_video_transcode', 1);
                $obj = $obj = Db::name($vtable)
                    ->master()
                    ->where($map)
                    ->find();
                if(
                    ( $obj['private_video_transcode'] >= $obj['need_private_video_transcode'] )
                    &&
                    ($obj['public_video_transcode'] >= 1)
                ){
                    if($video_type == 0){
                        $update['status']=1;
                        if ($obj['save_original_bkt'] == 0){
                            $update['process_done_time']=time();
                        }
                        Db::name('video')->where('id','eq',$vid)->update($update);
                    }else{
                        $update['status']=1;
                        if ($obj['save_original_bkt'] == 0){
                            $update['process_done_time']=time();
                        }
                        Db::name('resource')->where('id','eq',$vid)->update($update);
                    }
                    if($obj['save_original_bkt'] == 0){
                        self::saveOriginalVideo($obj['key']);
                    }else{
                        Db::name($vtable)->where($map)->update(['process_status' => 0]);
                    }
                }
            }
            return json(['success' => true]);
        }
    }

    public static function getPublicVideoDownUrl($key,$config = []){
        self::loadConfig($config);
        return
            self::$qiniuConfig['public_video_bkt_protocol'] .'://'.
            self::$qiniuConfig['public_video_bkt_domain']. '/' .
            $key . '.' . self::$publicVideoFormat;
    }

    public static function getPrivateVideoDownUrl($key,$bit_rate_type = 0,$config = []){
        self::loadConfig($config);
        return
            self::$qiniuConfig['private_video_bkt_protocol'] .'://'.
            self::$qiniuConfig['private_video_bkt_domain']. '/' .
            $key .'_'.$bit_rate_type . '.' . self::$privateVideoFormat;
    }

    public static function getScreenshotDownUrl($key,$config = []){
        self::loadConfig($config);
        return
            self::$qiniuConfig['screenshot_bkt_protocol'] .'://'.
            self::$qiniuConfig['screenshot_bkt_domain'].'/'.
            $key;
    }

    public static function saveOriginalVideo($key,$extra = [],$config =[]){
        self::loadConfig($config);
        $params = [
            'avthumb' => 'mp4',
            'vcodec'  => 'libx264', //视频编码格式
        ];
        if($extra)  $params += $extra;
        $fops = self::getJoinParams($params);
        $fops.= '|saveas/'.\Qiniu\base64_urlSafeEncode(self::$qiniuConfig['original_video_bkt'] .':'.$key);//保存的文件名;
        $pipeline = self::getPipeline();
        list($persistent_id, $err) = self::getPersistentFopInstance()->execute(
            self::$qiniuConfig['public_video_bkt'],
            $key,
            $fops,
            self::getPipeline(),
            self::getSaveOriginalVideoNotifyUrl()
        );
        if($persistent_id){
            $data =  [
                'persistent_id' => $persistent_id,
                'pipeline' => $pipeline,
                'create_time' => time(),
                'status' => 0
            ];
            return Db::name('pipeline')->insertGetId($data);
        }
    }

    public static function saveOriginalVideoNotify($key,$config = []){
        self::loadConfig($config);
        if($row = Db::name('video')->master()->where('key','eq',$key)->find()){
            Db::name('video')->where('id','eq',$row['id'])->update([
                'process_status' => 0,
                'save_original_bkt' => 1
            ]);
            //发布视频过审任务
            \app\common\model\Mission::runMission('public_video_pass',$row['user_id']);
            publishMessage([
                'action' => 'newVideo',
                'params' => [
                    'video_id' => $row['id'],
                ],
            ]);
            //触发机器人
            $data=User::getUserIsUploadVideo($row['user_id']);
            if($data) {
                Robot::afterUserPutVideo($row['id'], $row['user_id']);
            }
        }else{
            Db::name('resource_video')
                ->master()
                ->where('key','eq',$key)
                ->update([
                    'process_status' => 0,
                    'save_original_bkt' => 1
                ]);
        }
          self::delete(self::$qiniuConfig['public_video_bkt'],$key);
    }

    public static function getConfig($key = ''){
        self::loadConfig();
        return $key ? (isset(self::$qiniuConfig[$key]) ? self::$qiniuConfig[$key] : '') : self::$qiniuConfig;
    }

    /**
     * 获取切片处理视频文件key列表
     * @param string $indexFileName 视频切片处理后的m3u8索引文件名
     * @param array $config
     * @return array|bool
     */
    public static function getSliceVideoKeyArray($indexFileName,$config = [])
    {
        if (!$indexFileName||pathinfo($indexFileName,PATHINFO_EXTENSION)!=='m3u8'){
            return false;
        }
        self::loadConfig($config);
        $sliceVideoKeyArray=[];
        //获取远程m3u8索引文件内容
//        $index=file_get_contents(self::$qiniuConfig['private_video_bkt_protocol'].'://'.self::$qiniuConfig['private_video_bkt_domain'].'/'.$indexFileName);
        $index=file_get_contents('http://'.self::$qiniuConfig['private_video_bkt_domain'].'/'.$indexFileName);
        //换行拆分
        $index=explode("\n",$index);
        //循环筛选key
        foreach ($index as $videoKey){
            //行内不包含#符号,并且以ts为后缀,则为视频切片key
            if ($videoKey&&(strpos($videoKey,'#')===false)){
                $sliceVideoKeyArray[] = substr($videoKey,1);
            }
        }
        return $sliceVideoKeyArray;
    }

    /**
     * 抓取网络文件到空间
     * @param string $url
     * @param string $bucket
     * @param string $key
     * @return string|array
     */
    public static function fetchNetworkFile($url,$bucket,$key=null)
    {
        $bucketManager = self::getBuketManagerInstance();
        list($ret, $err) = $bucketManager->fetch($url, $bucket, $key);
        if ($err !== null) {
            return '';
        } else {
            return $ret;
        }
    }

    /**
     * 移动
     * @param string $source_bucket 源空间
     * @param string $source_key 源key
     * @param string $dest_bucket 目标空间
     * @param string $dest_key 目标key
     * @param bool $force 是否强制覆盖
     * @return mixed
     */
    public static function move($source_bucket,$source_key,$dest_bucket,$dest_key,$force=false)
    {
        $bucketManager = self::getBuketManagerInstance();
        $err = $bucketManager->move($source_bucket, $source_key, $dest_bucket, $dest_key,$force);
        if ($err) {
            return false;
        }else{
            return true;
        }
    }

    /**
     * 上传文件
     * @param string $bucket  文件空间
     * @param string $file_path  文件路径
     * @param string $key 文件key
     * @return bool
     */
    public static function putFile($bucket,$file_path,$key)
    {
        $auth = self::getAuthInstance();
        $token = $auth->uploadToken($bucket);
        $upload_mgr = self::getUploadManagerInstance();
        list($ret, $err) = $upload_mgr->putFile($token, $key, $file_path);
        if ($err !== null) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取文件信息
     * @param $bucket
     * @param $key
     * @return bool
     */
    public static function stat($bucket,$key)
    {
        $bucketManager = self::getBuketManagerInstance();
        list($fileInfo, $err) = $bucketManager->stat($bucket, $key);
        if ($err) {
            return false;
        } else {
            return $fileInfo;
        }
    }
}


