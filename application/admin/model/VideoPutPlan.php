<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use wsj\WQiniu;

class VideoPutPlan extends Model
{
    // 默认间隔时间最小值
    const DEFAULT_INTERVAL_TIME_MIN = 60;

    // 默认间隔时间最大值
    const DEFAULT_INTERVAL_TIME_MAX = 600;

    // 状态
    const STATUS = [
        'NOT_SET' => 0,
        'SET' => 1,
        'PUT_SUCCESS' => 2,
        'PUT_FAIL' => 3,
    ];

    // 状态文本
    const STATUS_TEXT = [
        0 => '未定时',
        1 => '已定时',
        2 => '发布成功',
        3 => '发布失败',
    ];

    // 表名
    protected $name = 'video_put_plan';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        // 'create_time_text',
        // 'plan_time_text',
        // 'put_time_text'
    ];
    

    /**
     * 获取视频播放地址
     * @param $key
     * @param $status
     * @return string
     */
    public static function getVideoPayUrl($key, $status)
    {
        $pay_url = '';
        if ($key) {
            if ($status === self::STATUS['PUT_SUCCESS']) {
                $pay_url = config('qiniu.original_video_bkt_protocol').'://'.config('qiniu.original_video_bkt_domain').'/'.$key;
            } else {
                $pay_url = self::getRemoteVideoProtocol() . '://' . self::getRemoteVideoDomain() . '/' . $key;
            }
        }
        return $pay_url;
    }

    /**
     * 获取远程视频访问域名
     */
    public static function getRemoteVideoDomain()
    {
        return config('qiniu.public_video_bkt_domain');
    }

    /**
     * 获取远程视频访问协议
     * @return mixed
     */
    public static function getRemoteVideoProtocol()
    {
        return config('qiniu.public_video_bkt_protocol');
    }

    /**
     * 获取远程视频存储空间
     * @return mixed
     */
    public static function getRemoteVideoBucket()
    {
        return config('qiniu.public_video_bkt');
    }

    /**
     * 删除远程视频
     * @param $key
     * @return mixed
     */
    public static function delRemoteVideo($key)
    {
        $ret = false;
        if ($key) {
            $ret = WQiniu::delete(self::getRemoteVideoBucket(),$key);
        }
        return $ret;
    }

    /**
     * 获取参数
     */
    public static function getParam()
    {
        $data=Db::name('video_put_plan_param')->field(['interval_time_min','interval_time_max','start_time'])->order('id','desc')->find();
        if (!$data){
            $data['interval_time_min']=self::DEFAULT_INTERVAL_TIME_MIN;
            $data['interval_time_max']=self::DEFAULT_INTERVAL_TIME_MAX;
            $data['start_time']=time();
        }else{
            $now=time();
            if ($data['start_time']<$now){
                $data['start_time']=$now;
            }
        }
        return $data;
    }
    
    /**
     * 设置参数
     * 
     * @param [type] $data [description]
     */
    public function setParam($data)
    {
        $validate = new \app\admin\validate\VideoPutPlan();
        $ret = $validate->scene('set')->check($data);
        if (!$ret) {
            $this->error = $validate->getError();
            return false;
        }
        $count = Db::name('video_put_plan_param')->count();
        $data['start_time'] = strtotime($data['start_time']);
        $data['update_time'] = time();
        if ($count){
            $ret = Db::name('video_put_plan_param')->where('id',$count)->update($data);
        }else{
            $ret = Db::name('video_put_plan_param')->insert($data);
        }
        if (!$ret) {
            $this->error = '设置失败';
            return false;
        }
        return true;
    }

    /**
     * 批量开始
     * 
     * @param  [type] $ids [description]
     * @return [type]      [description]
     */
    public function batchStart($ids)
    {
        if (!is_array($ids)) {
            $this->error = '参数格式错误';
            return false;
        }
        if (!$ids){
            $this->error = '缺少id';
            return false;
        }
        $count = Db::name('video_put_plan')
            ->where([
                'id' => ['in', $ids],
                'status' => ['=', self::STATUS['NOT_SET']]
            ])
            ->count();

        if ($count != count($ids)){
            $this->error = '部分视频不是未定时状态';
            return false;
        }
        $param = self::getParam();
        $plan_time = $param['start_time'];
        $success = 0;
        Db::startTrans();
        foreach ($ids as $id) {
            $where=[
                'id' => ['=', $id],
                'status' => ['=', self::STATUS['NOT_SET']] 
            ];
            $data = [
                'status' => self::STATUS['SET'],
                'plan_time' => $plan_time,
            ];
            $ret = Db::name('video_put_plan')->where($where)->update($data);
            if ($ret) {
                $success += 1;
            }
            $interval_time = mt_rand($param['interval_time_min'], $param['interval_time_max']);
            $plan_time += $interval_time;
        }
        Db::commit();
        if ($success != count($ids)) {
            $this->error = '部分成功';
            return false;
        }
        return true;
    }

    /**
     * 批量取消
     * 
     * @param  [type] $ids [description]
     * @return [type]      [description]
     */
    public function batchCancel($ids)
    {
        if (!is_array($ids)) {
            $this->error = '参数格式错误';
            return false;
        }
        if (!$ids) {
            $this->error = '缺少id';
            return false;
        }
        $count = Db::name('video_put_plan')
            ->where([
                'id' => ['in', $ids],
                'status' => ['=', self::STATUS['SET']]
            ])
            ->count();
        if ($count != count($ids)) {
            $this->error = '部分视频不是已定时状态';
            return false;
        }
        $where = [
            'id' => ['in', $ids],
            'status' => ['=', self::STATUS['SET']]
        ];
        $data = [
            'status' => self::STATUS['NOT_SET'],
            'plan_time' => 0,
        ];
        Db::name('video_put_plan')->where($where)->update($data);
        return true;
    }

    /**
     * 删除
     * 
     * @param $id
     * @return bool
     */
    public function del($id)
    {
        $row = Db::name('video_put_plan')->field(['id','original_file','key','status'])->where('id', $id)->find();
        if (!$row){
            $this->error = '错误id';
            return false;
        }
        if ($row['status'] !== self::STATUS['NOT_SET']) {
            $this->error = '只有未定时状态可以删除';
            return false;
        }
        $where = [
            'id' => ['=', $id],
            'status' => ['=', self::STATUS['NOT_SET']]
        ];
        $ret = Db::name('video_put_plan')->where($where)->delete();
        if (!$ret) {
            $this->error = '删除失败';
            return false;
        }
        
        self::delRemoteVideo($row['key']?$row['key']:$row['original_file']);
        return true;
    }


    // public function getCreateTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }


    // public function getPlanTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['plan_time']) ? $data['plan_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }


    // public function getPutTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['put_time']) ? $data['put_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    // protected function setCreateTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }

    // protected function setPlanTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }

    // protected function setPutTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }


}
