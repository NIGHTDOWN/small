<?php

namespace app\admin\model;

use think\Model;

class VideoComment extends Model
{
    /** 状态 */
    const STATUS=[
        'hide'=>0,
        'show'=>1,
    ];

    /** 状态说明 */
    const STATUS_TEXT=[
        0 => '隐藏',
        1 => '正常',
    ];
    // 表名
    protected $name = 'video_comment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        // 'create_time_text',
        // 'update_time_text'
    ];




    /**
     * 显示
     * @param $param
     * @return bool
     */
    public function show($id)
    {
        // 参数验证
        // $validateVideoComment=new ValidateVideoComment();
        // $ret=$validateVideoComment->scene('show')->check($param);
        // if (!$ret){
        //     $this->error=$validateVideoComment->getError();
        //     return false;
        // }
        if (!$id || !is_numeric($id)) {
            $this->error = 'ID错误';
        }

        $data = [
            'update_time' => time(),
            'status' => self::STATUS['show']
        ];
        $ret = $this->where(['id' => $id])->update($data);
        if (!$ret) {
            $this->error = '更新失败';
            return false;
        }
        return true;
    }

    /**
     * 隐藏
     * @param $param
     * @return bool
     */
    public function hide($param)
    {
        // 参数验证
        // $validateVideoComment = new ValidateVideoComment();
        // $ret=$validateVideoComment->scene('hide')->check($param);
        // if (!$ret){
        //     $this->error = $validateVideoComment->getError();
        //     return false;
        // }
        if (!$param['id'] || !is_numeric($param['id'])) {
            $this->error = 'ID错误';
        }
        if (empty($param['replace_comment'])) {
            $this->error = '缺少备注信息';
        }

        //更新
        $where = [
            ['id', '=', $param['id']],
        ];
        $data = [
            'status' => self::STATUS['hide'],
            'update_time' => time(),
            'replace_comment' => $param['replace_comment']
        ];
        $ret = $this->where(['id' => $param['id']])->update($data);
        if (!$ret) {
            $this->error = '更新失败';
            return false;
        }
        return true;
    }


    // public function getCreateTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }


    // public function getUpdateTimeTextAttr($value, $data)
    // {
    //     $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
    //     return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    // }

    // protected function setCreateTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }

    // protected function setUpdateTimeAttr($value)
    // {
    //     return $value && !is_numeric($value) ? strtotime($value) : $value;
    // }


}
