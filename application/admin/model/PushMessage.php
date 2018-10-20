<?php

namespace app\admin\model;

use think\Model;
use app\common\model\PushMessage as PushMessageCommonModel;
use think\Validate;

class PushMessage extends Model
{
    // 表名
    protected $name = 'push_message';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'action',
        'action_param',
        'msg_type',
        'send_time_text'
    ];
    

    
    public function getIsNowList()
    {
        return ['0' => __('Is_now 0'),'1' => __('Is_now 1')];
    }     

    public function getStatusList()
    {
        return PushMessageCommonModel::STATUS_TEXT;
    }     

    public function getUserRangeList()
    {
        return PushMessageCommonModel::USER_RANGE_TEXT;
    }

    public function getActionList()
    {
        return PushMessageCommonModel::ACTION_TEXT;
    }

    public function getIsNowTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['is_now']) ? $data['is_now'] : '');
        $list = $this->getIsNowList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSendTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['send_time']) ? $data['send_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getActionAttr($value, $data)
    {
        $value='';
        if (isset($data['param'])&&$data['param']){
            $data['param']=unserialize($data['param']);
            $value=$data['param']['appAction'];
        }
        return $value;
    }

    public function getActionParamAttr($value, $data)
    {
        $value='';
        if (isset($data['param'])&&$data['param']){
            $data['param']=unserialize($data['param']);
            if ($data['param']['appAction']=='openWeb'){
                $value=$data['param']['appActionParam']['url'];
            }elseif ($data['param']['appAction']=='playVideo'){
                $value=$data['param']['appActionParam']['video_id'];
            }
        }
        return $value;
    }

    public function getMsgTypeAttr($value, $data)
    {
        $value=0;
        if (isset($data['param'])&&$data['param']){
            $data['param']=unserialize($data['param']);
            $value=$data['param']['type'];
        }
        return $value;
    }

    protected function setSendTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function extend()
    {
        return $this->hasOne('PushMessageRange','message_id','id',[],'left')->setEagerlyType(0);
    }

    /**
     * 添加
     * @param $data
     * @return false|int
     */
    public function add($data)
    {
        //替换中文逗号为英文逗号,用户可能输入错误
        if (isset($data['target_user_ids'])&&$data['target_user_ids']){
            $data['target_user_ids']=str_replace('，',',',$data['target_user_ids']);
        }
        //处理参数
        $data['param']='';
        if ($data['action']=='openWeb'){
            if(!Validate::is($data['action_param'],'url')) {
                $this->error='链接格式不合法';
                return false;
            }
            $data['param']=serialize([
                'type'=>$data['msg_type'],
                'appAction'=>'openWeb',
                'appActionParam'=>[
                    'url'=>$data['action_param'],
                ]
            ]);
        }elseif ($data['action']=='playVideo'){
            if(!Validate::is($data['action_param'],'integer')) {
                $this->error='视频id不合法';
                return false;
            }
            $data['param']=serialize([
                'type'=>$data['msg_type'],
                'appAction'=>'playVideo',
                'appActionParam'=>[
                    'video_id'=>$data['action_param'],
                ]
            ]);
        }else{
            $this->error='跳转类型错误';
            return false;
        }

        $data['admin_id']=session('admin.id');
        $data['status']=PushMessageCommonModel::STATUS['no_send'];
        $this->startTrans();
        try{
            $ret=$this->allowField(['title','message','is_now','send_time','param','admin_id','status'])->save($data);
            if (!$ret){
                return false;
            }
            $extendData['data']=$data['target_user_ids'];
            $extendData['type']=$data['user_range'];
            if (!$this->extend()->save($extendData)){
                exception('用户ID保存失败');
            }
            $this->commit();
            return true;
        }catch (\Exception $e){
            $this->error=$e->getMessage();
            $this->rollback();
            return false;
        }

    }

    /**
     * 编辑
     * @param $data
     * @return bool
     */
    public function edit($data)
    {
        //替换中文逗号为英文逗号,用户可能输入错误
        if (isset($data['target_user_ids'])&&$data['target_user_ids']){
            $data['target_user_ids']=str_replace('，',',',$data['target_user_ids']);
        }
        //处理参数
        $data['param']='';
        if ($data['action']=='openWeb'){
            if(!Validate::is($data['action_param'],'url')) {
                $this->error='链接格式不合法';
                return false;
            }
            $data['param']=serialize([
                'type'=>$data['msg_type'],
                'appAction'=>'openWeb',
                'appActionParam'=>[
                    'url'=>$data['action_param'],
                ]
            ]);
        }elseif ($data['action']=='playVideo'){
            if(!Validate::is($data['action_param'],'integer')) {
                $this->error='视频id不合法';
                return false;
            }
            $data['param']=serialize([
                'type'=>$data['msg_type'],
                'appAction'=>'playVideo',
                'appActionParam'=>[
                    'video_id'=>$data['action_param'],
                ]
            ]);
        }else{
            $this->error='跳转类型错误';
            return false;
        }
        return $this->allowField(['title','message','param'])->save($data);
    }

    /**
     * 发送
     */
    public function send()
    {
        if ($this->getAttr('status')!=PushMessageCommonModel::STATUS['no_send']){
            $this->error='消息已设置发送';
            return false;
        }
        if (!$this->getAttr('is_now')){
            if ($this->getAttr('send_time')<time()){
                $this->error='超过定时时间,不可发送';
            }
        }

//        $queue_id=publish_message([
//            'action'=>'pushMessage',
//            'params'=>[
//                'message_id'=>$this->getAttr('id'),
//            ],
//        ],$this->getAttr('is_now')?0:$this->getAttr('send_time'));
//
//        if (!$queue_id){
//            $this->error='发送失败';
//            return false;
//        }
        //todo 测期间不入队列
        $queue_id='test';

        $this->setAttr('queue_id',$queue_id);
        $this->setAttr('status',PushMessageCommonModel::STATUS['wait_send']);
        return $this->save();
    }

    /**
     * 推送
     * @return bool|int
     */
    public function push()
    {
        if ($this->getAttr('status')!=PushMessageCommonModel::STATUS['wait_send']){
            $this->error='status error';
            return false;
        }
        if ($this->getAttr('extend')->getAttr('type')==PushMessageCommonModel::USER_RANGE['all']){
            $userId=0;
        }else{
            $userId=array_filter(array_unique(explode(',',$this->getAttr('extend')->getAttr('data') ?? '')));
            if (!$userId){
                $this->error='user ids require';
                return false;
            }
        }

        $param=$this->getAttr('param');
        $extra = $param ? unserialize($param) : null;

//        $rs = PushMessageCommonModel::jPush(
//            $this->getAttr('title') ,
//            special_chars_decode($this->getAttr('message') ) ,
//            $userId ,
//            $extra
//        );
        //todo 测试期间,不推送
        $rs['body']['msg_id']='test'.$this->getAttr('id');

        if(!$rs || !isset($rs['body']['msg_id'])) {
            $this->error='push fail';
            return false;
        }

        return $this->save(['task_id'=>$rs['body']['msg_id'],'status'=>PushMessageCommonModel::STATUS['done_send']]);
    }

}
