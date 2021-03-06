<?php

namespace app\admin\model;

use think\Model;
use app\common\model\SysMessage as SysMessageCommonModel;
use app\common\model\User as UserCommonModel;
use think\Validate;

class SysMessage extends Model
{
    // 表名
    protected $name = 'sys_message';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
        'link',
    ];

    public function getUserRangeList()
    {
        return SysMessageCommonModel::USER_RANGE_TEXT;
    }     

    public function getIsNowList()
    {
        return ['0' => __('Is_now 0'),'1' => __('Is_now 1')];
    }     

    public function getStatusList()
    {
        return SysMessageCommonModel::STATUS_TEXT;
    }

    public function getUserRangeTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['user_range']) ? $data['user_range'] : '');
        $list = $this->getUserRangeList();
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

    public function getLinkAttr($value, $data)
    {
        $value='';
        if (isset($data['app_action_info'])&&$data['app_action_info']){
            $data['app_action_info']=unserialize($data['app_action_info']);
            $value=$data['app_action_info']['appActionParam']['url'];
        }
        return $value;
    }

    protected function setSendTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function extend()
    {
        return $this->hasOne('SysMessageExtend','message_id','id',[],'left')->setEagerlyType(0);
    }

    public function toUser(){
        return $this->hasMany('SysMessageTo','message_id','id');
    }

    /**
     * 添加
     * @param $data
     * @return bool
     */
    public function add($data)
    {
        //处理参数
        $data['app_action_info']='';
        if (isset($data['link'])&&$data['link']){
            $data['app_action_info']=serialize([
                'appAction'=>'openWeb',
                'appActionParam'=>[
                    'url'=>$data['link'],
                ]
            ]);
        }
        if (isset($data['is_now'])&&$data['is_now']){
            $data['send_time']=date('Y-m-d H:i:s');
        }

        $data['admin_id']=session('admin.id');
        $data['status']=SysMessageCommonModel::STATUS['no_send'];

        $this->startTrans();
        try{
            $ret=$this->allowField(['message','cover_img','app_action_info','user_range','is_now','send_time','admin_id'])->save($data);
            if (!$ret){
                return false;
            }
            if ($data['user_range']==SysMessageCommonModel::USER_RANGE['portion']){
                $extendData['target_user_ids']=$data['target_user_ids'];
                if (!$this->extend()->save($extendData)){
                    exception('用户ID保存失败');
                }
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
     * @return false|int
     */
    public function edit($data)
    {
        if ($this->getAttr('status')==SysMessageCommonModel::STATUS['done_send']){
            $this->error='消息已发送,不可编辑';
            return false;
        }

        //处理参数
        $data['app_action_info']='';
        if (isset($data['link'])&&$data['link']){
            $data['app_action_info']=serialize([
                'appAction'=>'openWeb',
                'appActionParam'=>[
                    'url'=>$data['link'],
                ]
            ]);
        }

        $data['status']=SysMessageCommonModel::STATUS['no_send'];

        $oldCoverImg=$this->getAttr('cover_img');

        $ret=$this->allowField(['message','cover_img','app_action_info'])->save($data);
        if ($ret){
            //处理图片
            if (isset($data['cover_img'])){
                if ($oldCoverImg&&$oldCoverImg!=$data['cover_img']){
                    SysMessageCommonModel::deleteCoverImgFile($oldCoverImg);
                }
            }
        }
        return $ret;
    }

    public function send()
    {
        if ($this->getAttr('status')!=SysMessageCommonModel::STATUS['no_send']){
            $this->error='消息已设置发送';
            return false;
        }
        if (!$this->getAttr('is_now')){
            if ($this->getAttr('send_time')<time()){
                $this->error='超过定时时间,不可发送';
            }
        }

        $queueId=publish_message([
            'action'=>'sendSysMessageToUser',
            'params'=>[
                'sys_message_id'=>$this->getAttr('id'),
            ],
        ],$this->getAttr('is_now')?0:$this->getAttr('send_time'));
        if (!$queueId){
            $this->error='发送失败';
            return false;
        }

        $this->setAttr('queue_id',$queueId);
        $this->setAttr('status',SysMessageCommonModel::STATUS['wait_send']);

        return $this->save();
    }

    /**
     * 发送给用户
     */
    public function msgToUser()
    {
        if ($this->getAttr('status')!=SysMessageCommonModel::STATUS['wait_send']){
            $this->error='status error';
            return false;
        }
        if (!in_array($this->getAttr('user_range'),SysMessageCommonModel::USER_RANGE)){
            $this->error='undefined user_range';
            return false;
        }
        $now=time();
        try{
            $this->startTrans();
            if ($this->getAttr('user_range')==SysMessageCommonModel::USER_RANGE['all']){
                //全部用户
                $to_ret=$this->toUser()->save([
                    'user_id'=>0,
                    'time'=>$now,
                ]);
                if (!$to_ret){
                    exception('insert to_user fail');
                }
                $sendTotal=model('admin/User')->where('status','<>',UserCommonModel::STATUS['delete'])->count();
            }else{
                //部分用户
                $userIds=$this->getAttr('extend')->getAttr('target_user_ids');
                $userIds=array_filter(array_unique(explode(',',$userIds)));
                if (!$userIds){
                    $this->error='user ids require';
                    return false;
                }
                $sendTotal=0;
                $insertData=[];
                foreach ($userIds as $userId){
                    $insertData[]=[
                        'user_id'=>$userId,
                        'time'=>$now,
                    ];
                    $sendTotal+=1;
                }
                $to_ret=$this->toUser()->saveAll($insertData);
                if (!$to_ret){
                    exception('insert to_user fail');
                }
            }
            $this->setAttr('send_total',$sendTotal);
            $this->setAttr('status',SysMessageCommonModel::STATUS['done_send']);
            $ret=$this->save();
            if (!$ret){
                exception('update status fail');
            }
            $this->commit();
            return true;
        }catch (\Exception $e){
            $this->error=$e->getMessage();
            $this->rollback();
            return false;
        }
    }
}
