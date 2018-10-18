<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use wsj\WQiniu;

class OpinionFeedback extends Model
{
    // 表名
    protected $name = 'opinion_feedback';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        // 'create_time_text'
    ];

    /** 今日反馈历史记录缓存前缀,拼接user_id使用 */
    const TODAY_OPINION_FEEDBACK_HISTORY_CACHE_KEY_PRE='today_opinion_feedback_history_cache_';

    const TODAY_OPINION_REPLY_HISTORY_CACHE_KEY_PRE='today_opinion_reply_history_cache_';
    
    /** 类型 0用户反馈 1官方回复 */
    const TYPE=[
        'FEEDBACK'=>0,
        'REPLY'=>1,
    ];

    /** 类型说明  0用户反馈 1官方回复*/
    const TYPE_TEXT=[
        0=>'用户反馈',
        1=>'官方回复',
    ];

    /** 阅读状态  0已读  1未读*/
    const READ_STATUS=[
        'READ'=>0,
        'UNREAD'=>1,
    ];

    /** 阅读状态说明  0已读  1未读*/
    const READ_STATUS_TEXT=[
        0=>'已读',
        1=>'未读',
    ];

    /** 是否有回复  0没有  1有，此仅字段用于后台判断*/
    const REPLY_STATUS=[
        'NO_REPLY'=>0,
        'READ'=>1,
        'HAVE_REPLY'=>2,
        'ALL'=>3,
    ];

    /** 是否有回复  0没有  1有，此仅字段用于后台判断*/
    const REPLY_STATUS_TEXT=[
        0=>'没有',
        1=>'已读',
        2=>'有',
        3=>'全部'
    ];

    public function user()
    {
        return $this->belongsTo('user', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 获取图片存储空间
     */
    public static function getImageBucket()
    {
        return config('qiniu.other_image_bkt');
    }

    /**
     * 获取图片访问域名
     */
    public static function getImageDomain()
    {
        return config('qiniu.other_image_bkt_domain');
    }

    /**
     * 获取图片访问协议
     */
    public static function getImageProtocol()
    {
        return config('qiniu.other_image_bkt_protocol');
    }

    /**
     * 获取图片访问url
     * @param $image
     * @return string
     */
    public static function getImageUrl($image)
    {
        return self::getImageProtocol().'://'.self::getImageDomain().'/'.$image;
    }

    /**
     * 删除图片资源
     * @param $image
     * @return mixed
     */
    public static function delImageFile($image)
    {
        $bucket=self::getImageBucket();
        $ret=WQiniu::delete($bucket,$image);
        return $ret;
    }

    /**
     * 添加文案
     * 
     * @param Int $user_id [description]
     * @param String $content [description]
     * @param String $image   [description]
     * @param Int $pid     [description]
     */
    public function addFeedback($user_id, $content, $image, $pid)
    {
        if (!is_string($image)) {
            $this->error = '缺少id';
            return false;
        }
        if (!is_string($content)){
            $this->error = '非法id';
            return false;
        }
        if (!strlen($content)){
            $this->error = '缺少内容';
            return false;
        }
        if (mb_strlen($content) > 200) {
            $this->error = '回复内容不能大于200';
            return false;
        }
        if (!$pid) {
            $this->error = '吐槽id必须传';
            return false;
        }
        $now_time = time();
        
        //保存
        $data = [];

        $data['parent_id'] = $pid;
        $data['type'] = self::TYPE['REPLY'];
        $data['read_status'] = self::READ_STATUS['UNREAD'];
        $data['create_time'] = $now_time;
        $data['user_id'] = $user_id;
        $data['content'] = $content;
        $data['image'] = $image;
        Db::startTrans();
        $id = Db::name('opinion_feedback')->insertGetId($data);
        $status = Db::name('opinion_feedback')->where(['id' => $pid])->update(['reply_status' => self::REPLY_STATUS['HAVE_REPLY']]);
        if (!$id){
            Db::rollback();
            $this->error = 1405;
            return false;
        }
        $uid = Db::name('opinion_feedback')->where(['id' => $pid])->field('user_id')->find();
        $pre = self::TODAY_OPINION_FEEDBACK_HISTORY_CACHE_KEY_PRE;//缓存前缀，区分是添加记录还是对某条记录进行补充说明\
        $feedback_key = $pre . $uid['user_id'];
        $feedback_history = [
            'count' => 0,
            'time' => $now_time,
        ];
        cache($feedback_key, $feedback_history, 300);
        Db::commit();
        return true;
    }

    public function getDetail($param)
    {
        $id=(int)$param['id'];

        if (!$id){
            return [];
        }
        $where=[
            ['f.id|f.parent_id','=',$id],
        ];
        $data=Db::name('opinion_feedback')
            ->alias('f')
            ->join('user u','f.user_id=u.id','left')
            ->join('admin a','f.user_id=a.id','left')
            ->field('f.parent_id,f.type,f.content,f.image,f.create_time,f.user_id,f.id,u.nickname,a.admin')
            ->where($where)
            ->order($param['order_field'],$param['order_direction']?'desc':'asc')
            ->select();
        foreach ($data as $key => $value) {
            $data[$key]['create_time'] = date('Y-m-d H:i', $data[$key]['create_time']);
            $data[$key]['image'] = $data[$key]['image'] ? self::getImageUrl($data[$key]['image']) : '';
            $data[$key]['username']  = ($data[$key]['type']==0) ? $data[$key]['nickname'] : $data[$key]['admin'];
        }
       
        //更新未读为已读
        Db::name('opinion_feedback')
            ->where([
                'data' => ['=', $id],
                'type' => ['=', self::TYPE['FEEDBACK']]
            ])
            ->update([
                'read_status' =>self::READ_STATUS['READ'],
            ]);
        return $data;
    }
}
