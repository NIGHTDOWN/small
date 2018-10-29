<?php

namespace app\admin\model;

use think\Model;
use app\common\model\SmsSendTask as SmsSendTaskCommonModel;
use wsj\ali\AliSms;

class SmsSendTask extends Model
{
    // 表名
    protected $name = 'sms_send_task';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
//        'user_range_text',
//        'is_now_text',
//        'send_time_text',
//        'status_text',
//        'create_time_text',
//        'update_time_text'
    ];
    

    
    public function getUserRangeList()
    {
        return SmsSendTaskCommonModel::USER_RANGE_TEXT;
    }     

    public function getIsNowList()
    {
        return ['0' => __('Is_now 0'),'1' => __('Is_now 1')];
    }     

    public function getStatusList()
    {
        return SmsSendTaskCommonModel::STATUS_TEXT;
    }     


    public function getUserRangeTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['user_range']) ? $data['user_range'] : '');
        $list = $this->getUserRangeList();
        return isset($list[$value]) ? $list[$value] : '';
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


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setSendTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    /**
     * 发送
     */
    public function send()
    {
        if ($this->getAttr('status')!=SmsSendTaskCommonModel::STATUS['no_send']){
            $this->error='任务已设置发送';
            return false;
        }
        if (!$this->getAttr('is_now')){
            if ($this->getAttr('send_time')<time()){
                $this->error='超过定时时间,不可发送';
            }
        }

        $queueId=publish_message([
            'action'=>'executeSmsSendTask',
            'params'=>[
                'sms_send_task_id'=>$this->getAttr('id'),
            ],
        ],$this->getAttr('is_now')?0:$this->getAttr('send_time'));
        if (!$queueId){
            $this->error='发送失败';
            return false;
        }

        $this->setAttr('queue_id',$queueId);
        $this->setAttr('status',SmsSendTaskCommonModel::STATUS['wait_send']);

        return $this->save();
    }

    /**
     * 执行发送
     * (队列调起)
     */
    public function smsToUser()
    {
        if ($this->getAttr('status')!=SmsSendTaskCommonModel::STATUS['wait_send']){
            $this->error='status error';
            return false;
        }
        if (!in_array($this->getAttr('user_range'),SmsSendTaskCommonModel::USER_RANGE)){
            $this->error='undefined user_range';
            return false;
        }
        if ($this->getAttr('user_range')==SmsSendTaskCommonModel::USER_RANGE['all']) {
            //全部用户
            $where=[];
        }else{
            //部分用户
            $target_user_ids=$this->getAttr('target_user_ids');
            if (!$target_user_ids){
                $this->error='target_user_ids require';
                return false;
            }
            $target_user_ids=array_filter(array_unique(explode(',',$target_user_ids)));
            $where[]=['id','in',$target_user_ids];

        }
        //发送短信
        $sendTotal=0;
        model('admin/User')
            ->field(['id','mobile'])
            ->where(array_merge($where,[
                ['status','<>',-1],
                ['is_robot','=',0],
                ['mobile','<>','']
            ]))->chunk(100,function ($users) use (&$sendTotal){
                foreach ($users as $user){
                    try{
                        $ret=AliSms::sendSms($user['mobile'],$this->getAttr('sms_template_code'));
                        if ($ret){
                            $sendTotal++;
                        }
                    }catch (\Exception $e){
                        continue;
                    }
                }
            });
        $this->setAttr('send_total',$sendTotal);
        $this->setAttr('status',SmsSendTaskCommonModel::STATUS['done_send']);
        return $this->save();
    }
}
