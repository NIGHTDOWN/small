<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class ActiveTask extends Model
{
    /**
     * 设置参数
     * @param $data
     * @return bool
     */
    public function setActiveParam($name,$value)
    {
        $count = Db::name('core_value')->where('name','=',$name)->count();
        if ($count) {
            $ret = Db::name('core_value')
                ->where('name','=',$name)
                ->update(['value'=>$value]);
        } else {
            $data = ['name'=>$name,'value'=>$value];
            $ret = Db::name('core_value')->insert($data);
        }
        if (!$ret){
            $this->error = '设置失败';
            return false;
        }
        \app\common\model\ActiveTask::initActiveParamCache();
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
