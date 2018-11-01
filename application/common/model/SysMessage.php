<?php
namespace app\common\model;

use think\Model;
use wsj\WQiniu;

use app\common\model\SysMessage as CommonModelSysMessage;

class SysMessage extends Model
{
    /** 状态 */
    const STATUS=[
        'no_send'=>0,
        'done_send'=>1,
        'wait_send'=>2,
    ];

    const STATUS_TEXT=[
        0=>'未发送',
        1=>'已发送',
        2=>'等待发送',
    ];

    /** 用户范围 */
    const USER_RANGE=[
        'all'=>0,
        'portion'=>1,
    ];

    const USER_RANGE_TEXT=[
        0=>'全部用户',
        1=>'部分用户',
    ];

    /**
     * 删除图片文件
     * @param $cover_img
     * @return mixed
     */
    public static function deleteCoverImgFile($cover_img)
    {
        $url=config('site.other_url');
        $key=str_replace("$url/",'',$cover_img);
        $bucket=config('site.other_bucket');
        return WQiniu::delete($bucket,$key);
    }

    /**
     * 发送系统消息
     * @param $data
     * @return bool
     */
    public function sendSysMessage($data)
    {
        $now=time();
        $data['send_time']=$data['is_now']?$now:$data['send_time'];
        $data['create_time']=$now;
        $data['update_time']=$now;
        //增加系统消息
        //主表
        $id=Db::name('sys_message')->insertGetId([
            'message'=>$data['message'],
            'cover_img'=>$data['cover_img'],
            'app_action_info'=>$data['app_action_info'],
            'user_range'=>$data['user_range'],
            'create_time'=>$data['create_time'],
            'update_time'=>$data['update_time'],
            'is_now'=>$data['is_now'],
            'send_time'=>$data['send_time'],
            'admin_id'=>$data['admin_id'],
            'status'=>self::STATUS['no_send'],
        ]);
        $data['id']=$id;
        if ($data['user_range']==self::USER_RANGE['portion']){
            //扩展表
            Db::name('sys_message_extend')->insert([
                'message_id'=>$data['id'],
                'target_user_ids'=>$data['target_user_ids'],
            ]);
        }
        //发送系统消息
        $status=self::STATUS['wait_send'];
        $queue_id=publishMessage([
            'action'=>'sendSysMessageToUser',
            'params'=>[
                'sys_message_id'=>$id,
            ],
        ],$data['is_now']?0:$data['send_time']);
        if ($queue_id!==false){
            Db::name('sys_message')->where('id',$id)->update(['queue_id'=>$queue_id,'status'=>$status]);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 发送系统消息给用户
     * (由队列调起)
     * @param $sys_message_id
     */
    public function  sendSysMessageToUser($sys_message_id)
    {
        $sys_message=Db::name('sys_message')
            ->field(['id','message_show','user_range'])
            ->where([
                ['id','eq',$sys_message_id],
                ['status','=',self::STATUS['wait_send']],
            ])
            ->find();
        if (!$sys_message){
            return;
        }
        $now=time();
        if ($sys_message['user_range']===self::USER_RANGE['all']){
            //发送给全部用户
            Db::name('sys_message_to')
                ->insert([
                    'message_id'=>$sys_message['id'],
                    'message_show'=>$sys_message['message_show'],
                    'user_id'=>0,
                    'time'=>$now,
                ]);
            $send_total=Db::name('user')->where('status','neq',-1)->count();
            Db::name('sys_message')
                ->where([
                    ['id','eq',$sys_message['id']]
                ])
                ->update([
                    'send_total'=>$send_total,
                    'update_time'=>$now,
                    'status'=>self::STATUS['done_send'],
                ]);
        }elseif ($sys_message['user_range']===self::USER_RANGE['portion']){
            //发送给指定用户
            $target_user_ids=Db::name('sys_message_extend')->where('message_id',$sys_message['id'])->value('target_user_ids');
            if (!$target_user_ids){
                return;
            }
            $target_user_ids=array_filter(array_unique(explode(',',$target_user_ids)));
            $insert_data=[];
            $send_total=0;
            foreach ($target_user_ids as $target_user_id){
                $send_total++;
                $insert_data[]=[
                    'message_id'=>$sys_message['id'],
                    'user_id'=>$target_user_id,
                    'time'=>$now,
                ];
            }
            Db::name('sys_message_to')->insertAll($insert_data,false,1000);
            Db::name('sys_message')
                ->where([
                    ['id','eq',$sys_message['id']]
                ])
                ->update([
                    'send_total'=>$send_total,
                    'update_time'=>$now,
                    'status'=>self::STATUS['done_send'],
                ]);
        }
    }
}