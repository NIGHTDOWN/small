<?php

namespace app\admin\model;

use app\common\model\Robot AS RobotCommonModel;
use think\Db;

class Robot extends RobotCommonModel
{
	/**
     * 机器人参数验证
     * @param $params
     * @return bool
     */
    public function paramValidate($params)
    {
        if (!is_array($params)){
            $this->error='参数格式不正确';
            return false;
        }
        if (count($params) !== 3){
            $this->error='参数格式不正确';
            return false;
        }

        // 时限转换Start
        if (!isset($params['user_put_video_event_param']['hour']) || !isset($params['user_put_video_event_param']['minute'])) {
        	$this->error='缺少用户发布视频时间';
        	return false;
        }
        if (!isset($params['user_action_event_param']['hour']) || !isset($params['user_action_event_param']['minute'])) {
        	$this->error='缺少用户行为事件时间';
        	return false;
        }
        if (!isset($params['user_long_time_inactivity_event_param']['hour']) || !isset($params['user_long_time_inactivity_event_param']['minute'])) {
        	$this->error='缺少用户长期不活跃事件事件时间';
        	return false;
        }
        $params['user_put_video_event_param']['finish_time'] = $this->secToTime($params['user_put_video_event_param']['hour'], $params['user_put_video_event_param']['minute']);
        $params['user_action_event_param']['finish_time'] = $this->secToTime($params['user_action_event_param']['hour'], $params['user_action_event_param']['minute']);
        $params['user_long_time_inactivity_event_param']['finish_time'] = $this->secToTime($params['user_long_time_inactivity_event_param']['hour'], $params['user_long_time_inactivity_event_param']['minute']);
        unset($params['user_put_video_event_param']['hour']);
        unset($params['user_put_video_event_param']['minute']);
        unset($params['user_action_event_param']['hour']);
        unset($params['user_action_event_param']['minute']);
        unset($params['user_long_time_inactivity_event_param']['hour']);
        unset($params['user_long_time_inactivity_event_param']['minute']);
        // 时限转换End

        $data = $params;
        $keys=array_keys($data);
        $default_keys=array_keys(self::DEFAULT_ROBOT_PARAM);
        if ($keys!==$default_keys){
            $this->error='参数格式不正确';
            return false;
        }
        if (!is_array($data['user_put_video_event_param'])){
            $this->error='用户发布视频事件参数格式不正确';
            return false;
        }
        if (array_keys(self::DEFAULT_ROBOT_PARAM['user_put_video_event_param'])!==array_keys($data['user_put_video_event_param'])){
            $this->error='用户发布视频事件参数格式不正确';
            return false;
        }
        if (!is_array($data['user_action_event_param'])){
            $this->error='用户行为事件参数格式不正确';
            return false;
        }
        if (array_keys(self::DEFAULT_ROBOT_PARAM['user_action_event_param'])!==array_keys($data['user_action_event_param'])){
            $this->error='用户行为事件参数格式不正确';
            return false;
        }
        if (!is_array($data['user_long_time_inactivity_event_param'])){
            $this->error='用户长期不活跃事件参数格式不正确';
            return false;
        }
        if (array_keys(self::DEFAULT_ROBOT_PARAM['user_long_time_inactivity_event_param'])!==array_keys($data['user_long_time_inactivity_event_param'])){
            $this->error='用户长期不活跃事件参数格式不正确';
            return false;
        }
        foreach ($data as $k1=>$v1){
            foreach ($v1 as $k2=>$v2){
                $int_v2=(int)$v2;
                $data[$k1][$k2]=$int_v2;
                if (($int_v2!=$v2)||$int_v2<0){
                    $this->error='值必须是大于等于0的整数';
                    return false;
                }
            }
        }
        if ($data['user_put_video_event_param']['like_min']>$data['user_put_video_event_param']['like_max']){
            $this->error='范围第二个值不可小于第一个值';
            return false;
        }
        if ($data['user_put_video_event_param']['comment_min']>$data['user_put_video_event_param']['comment_max']){
            $this->error='范围第二个值不可小于第一个值';
            return false;
        }
        if ($data['user_action_event_param']['like_min']>$data['user_action_event_param']['like_max']){
            $this->error='范围第二个值不可小于第一个值';
            return false;
        }
        if ($data['user_action_event_param']['comment_min']>$data['user_action_event_param']['comment_max']){
            $this->error='范围第二个值不可小于第一个值';
            return false;
        }
        if ($data['user_action_event_param']['forward_min']>$data['user_action_event_param']['forward_max']){
            $this->error='范围第二个值不可小于第一个值';
            return false;
        }
        if ($data['user_long_time_inactivity_event_param']['like_min']>$data['user_long_time_inactivity_event_param']['like_max']){
            $this->error='范围第二个值不可小于第一个值';
            return false;
        }
        if ($data['user_long_time_inactivity_event_param']['comment_min']>$data['user_long_time_inactivity_event_param']['comment_max']){
            $this->error='范围第二个值不可小于第一个值';
            return false;
        }
        if ($data['user_long_time_inactivity_event_param']['forward_min']>$data['user_long_time_inactivity_event_param']['forward_max']){
            $this->error='范围第二个值不可小于第一个值';
            return false;
        }
        if ($data['user_put_video_event_param']['finish_time']>self::MAX_FINISH_TIME){
            $this->error='完成时限不可大于七天';
            return false;
        }
        if ($data['user_action_event_param']['finish_time']>self::MAX_FINISH_TIME){
            $this->error='完成时限不可大于七天';
            return false;
        }
        if ($data['user_long_time_inactivity_event_param']['finish_time']>self::MAX_FINISH_TIME){
            $this->error='完成时限不可大于七天';
            return false;
        }
        if ($data['user_put_video_event_param']['finish_time']<self::MIN_FINISH_TIME){
            $this->error='完成时限不可小于一分钟';
            return false;
        }
        if ($data['user_action_event_param']['finish_time']<self::MIN_FINISH_TIME){
            $this->error='完成时限不可小于一分钟';
            return false;
        }
        if ($data['user_long_time_inactivity_event_param']['finish_time']<self::MIN_FINISH_TIME){
            $this->error='完成时限不可小于一分钟';
            return false;
        }
        return $data;
    }

	/**
	* 设置机器人参数
	* @param $data
	* @return bool
	*/
    public function setRobotParam($data)
    {
        $data = $this->paramValidate($data);
        if ($data === false) {
            return false;
        }
        $count = Db::name('robot_param')->count();
        if ($count) {
        	$id = Db::name('robot_param')
                ->order('id', 'desc')
                ->limit(1)
                ->field('id')
                ->find();
            $ret = Db::name('robot_param')
                ->where(['id' => $id['id']])
                ->update([
                    'user_put_video_event_param' => serialize($data['user_put_video_event_param']),
                    'user_action_event_param' => serialize($data['user_action_event_param']),
                    'user_long_time_inactivity_event_param' => serialize($data['user_long_time_inactivity_event_param']),
                    'update_time' => time(),
                ]);
        } else {
            $ret = Db::name('robot_param')->insert([
                'user_put_video_event_param' => serialize($data['user_put_video_event_param']),
                'user_action_event_param' => serialize($data['user_action_event_param']),
                'user_long_time_inactivity_event_param' => serialize($data['user_long_time_inactivity_event_param']),
                'update_time' => time(),
            ]);
        }
        if (!$ret){
            $this->error = '设置失败';
            return false;
        }
        $this->initRobotParamCache();
        return true;
    }

    /**
     * 时、分转秒
     * @param  [type] $hour   [description]
     * @param  [type] $minute [description]
     * @return [type]         [description]
     */
    public function secToTime($hour, $minute)
    {
    	$secs = (($hour * 3600) + ($minute * 60));
    	return $secs;
    }
}
