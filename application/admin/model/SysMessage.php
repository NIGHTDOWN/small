<?php

namespace app\admin\model;

use think\Model;
use app\common\model\SysMessage as SysMessageCommonModel;
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

    /**
     * 添加
     * @param $data
     * @return bool
     */
    public function add($data)
    {
        //替换中文逗号为英文逗号,用户可能输入错误
        if (isset($data['target_user_ids'])&&$data['target_user_ids']){
            $data['target_user_ids']=str_replace('，',',',$data['target_user_ids']);
        }
        //处理参数
        $data['app_action_info']='';
        if (isset($data['link'])&&$data['link']){
            if(!Validate::is($data['link'],'url')) {
                $this->error='链接格式不合法';
                return false;
            }
            $data['app_action_info']=serialize([
                'appAction'=>'openWeb',
                'appActionParam'=>[
                    'url'=>$data['link'],
                ]
            ]);
        }
        unset($data['link']);

        $data['admin_id']=session('admin.id');
        $data['status']=SysMessageCommonModel::STATUS['no_send'];

        $extendData['target_user_ids']=$data['target_user_ids'];
        unset($data['target_user_ids']);

        $this->startTrans();
        try{
            $ret=$this->save($data);
            if (!$ret){
                $this->error='添加失败';
                return false;
            }
            if ($data['user_range']==SysMessageCommonModel::USER_RANGE['portion']){
                $extendData['message_id']=$this->getAttr('id');
                if (!model('SysMessageExtend')->insert($extendData)){
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
            if(!Validate::is($data['link'],'url')) {
                $this->error='链接格式不合法';
                return false;
            }
            $data['app_action_info']=serialize([
                'appAction'=>'openWeb',
                'appActionParam'=>[
                    'url'=>$data['link'],
                ]
            ]);
        }
        unset($data['link']);

        $data['status']=SysMessageCommonModel::STATUS['no_send'];

        $oldCoverImg=$this->getAttr('cover_img');

        $ret=$this->save($data);
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

        $queue_id=publish_message([
            'action'=>'sendSysMessageToUser',
            'params'=>[
                'sys_message_id'=>$this->getAttr('id'),
            ],
        ],$this->getAttr('is_now')?0:$this->getAttr('send_time'));

        if (!$queue_id){
            $this->error='发送失败';
            return false;
        }

        $this->setAttr('queue_id',$queue_id);
        $this->setAttr('status',SysMessageCommonModel::STATUS['wait_send']);

        return $this->save();
    }
}
