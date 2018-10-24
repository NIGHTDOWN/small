<?php

namespace app\admin\model;

use think\Model;
use app\common\model\Video as VideoCommonModel;
use think\Validate;
use wsj\WQiniu;
use app\common\model\HotVideo as HotVideoCommonModel;
use app\common\model\ActivityTop as ActivityTopCommonModel;
use app\common\model\Robot as RobotCommonModel;

class Video extends Model
{
    // 表名
    protected $name = 'video';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 追加属性
    protected $append = [
        'play_url'
    ];

    public function getStatusList()
    {
        $statusList = VideoCommonModel::STATUS_TEXT;
        unset($statusList[-1]);
        return $statusList;
    }

    public function getPlayUrl($id, $status)
    {
        VideoCommonModel::getPublicUrl($id, $status);
    }

    public function getProcessDoneTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['process_done_time']) ? $data['process_done_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
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

    public function getPlayUrlAttr($value, $data)
    {
        return $this->getPlayUrl($data['id'], $data['status']);
    }

    protected function setProcessDoneTimeAttr($value)
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

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'left')->setEagerlyType(1);
    }

    public function extend()
    {
        return $this->hasOne('VideoExtend', 'video_id', 'id', [], 'left')->setEagerlyType(0);
    }

    public function log()
    {
        return $this->hasMany('VideoAdminLog', 'video_id', 'id');
    }

    public function subjects()
    {
        return $this->belongsToMany('Subject', 'SubjectVideo', 'subject_id', 'video_id');
    }

    public function hotvideo()
    {
        return $this->hasOne('HotVideo','video_id','id',[],'left')->setEagerlyType(0);
    }

    /**
     * 编辑标题
     * @param array $data
     * @return bool
     */
    public function editTitle($data)
    {
        $ret = $this->allowField(['title'])->save($data);
        if ($ret) {
            VideoCommonModel::updateEs($this->getAttr('id'), ['title' => $data['title']]);
        }
        return $ret;
    }

    /**
     * 编辑分类
     * @param array $data
     * @return false|int
     */
    public function editCategory($data)
    {
        $oldCategoryId = $this->getAttr('category_id');
        $ret = $this->allowField(['category_id'])->save($data);
        if ($ret) {
            if ($this->getAttr('recommend')) {
                if ($oldCategoryId != $data['category_id']) {
                    VideoCommonModel::delTopVideoFromCache($this->getAttr('id'), $oldCategoryId);
                }
                VideoCommonModel::addTopVideoToCache($this->getAttr('id'), $data['category_id']);
            }
        }
        return $ret;
    }

    /**
     * 编辑封面
     * @param string $cover_imgs
     * @return false|int
     */
    public function editCoverImg($cover_imgs)
    {
        if (!Validate::is($cover_imgs, 'url')) {
            $this->error = '封面不是合法的url地址';
            return false;
        }
        try{
            $img_info=getimagesize($cover_imgs);
        }catch (\Exception $e){
            $this->error='无效的图片';
            return false;
        }
        if ($img_info[0]!=$this->getAttr('width')||$img_info[1]!=$this->getAttr('height')){
            $this->error='图片尺寸与视频不一致';
            return false;
        }
        $oldCoverImg = $this->getAttr('extend')->getAttr('cover_imgs');
        $ret = $this->getAttr('extend')->save(['cover_imgs' => $cover_imgs]);
        if ($ret) {
            if ($oldCoverImg) {
                VideoCommonModel::deleteCoverImgFile($oldCoverImg);
            }
        }
        return $ret;
    }

    /**
     * 增加点赞
     * @param int $number
     * @return bool
     */
    public function addLike($number)
    {
        $param_key='user_action_event_param';
        $event_param=RobotCommonModel::getRobotParam()[$param_key];
        RobotCommonModel::likeTask($this->getAttr('id'),$number,$event_param['finish_time']);
        return true;
    }

    /**
     * 审核通过
     */
    public function checkPass()
    {
        if ($this->getAttr('process_status') != 0) {
            $this->error = '七牛转码处理中';
            return false;
        }
        if (!in_array($this->getAttr('status'), [VideoCommonModel::STATUS['robot_fail'], VideoCommonModel::STATUS['robot_success']])) {
            $this->error = '重复操作';
            return false;
        }
        try {
            $this->startTrans();
            $this->setAttr('status', VideoCommonModel::STATUS['display']);
            if (!$this->save()) {
                exception('失败');
            }
            //记录log
            $logData = [
                'type' => VideoAdminLog::TYPE['check_pass'],
                'admin_id' => session('admin.id'),
            ];
            $this->log()->save($logData);
            //进行后续的视频转码(原机器检查后,在这一歩停止)
            WQiniu::transcodeVideo($this->getAttr('id'));
            //排行榜生效
            ActivityTopCommonModel::doTopData($this->getAttr('id'));
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rollback();
            return false;
        }
    }

    /**
     * 审核不通过
     * @param string $remark 备注
     * @return bool
     */
    public function checkNoPass($remark)
    {
        if ($this->getAttr('process_status') != 0) {
            $this->error = '七牛转码处理中';
            return false;
        }
        if (!in_array($this->getAttr('status'), [VideoCommonModel::STATUS['robot_fail'], VideoCommonModel::STATUS['robot_success']])) {
            $this->error = '重复操作';
            return false;
        }
        try {
            $this->startTrans();
            $this->setAttr('status', VideoCommonModel::STATUS['check_no_pass']);
            if (!$this->save()) {
                exception('失败');
            }
            //记录log
            $logData = [
                'type' => VideoAdminLog::TYPE['check_no_pass'],
                'admin_id' => session('admin.id'),
                'remark' => $remark,
            ];
            $this->log()->save($logData);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rollback();
            return false;
        }
    }

    /**
     * 上架
     */
    public function show()
    {
        if ($this->getAttr('status') != VideoCommonModel::STATUS['hide']) {
            $this->error = '只有下架视频可以设置上架';
            return false;
        }
        try {
            $this->startTrans();
            $this->setAttr('status', VideoCommonModel::STATUS['display']);
            if (!$this->save()) {
                exception('失败');
            }
            //log
            $logData = [
                'type' => VideoAdminLog::TYPE['display'],
                'admin_id' => session('admin.id'),
            ];
            $this->log()->save($logData);
            //es
            VideoCommonModel::addEs($this->getAttr('id'), $this->getAttr('title'));
            //恢复排行数据
            ActivityTopCommonModel::doTopData($this->getAttr('id'));
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 下架
     */
    public function hide()
    {
        if ($this->getAttr('status') != VideoCommonModel::STATUS['display']) {
            $this->error = '只有上架视频可以设置下架';
            return false;
        }
        try {
            $this->startTrans();
            $this->setAttr('status', VideoCommonModel::STATUS['hide']);
            if (!$this->save()) {
                exception('失败');
            }
            //log
            $logData = [
                'type' => VideoAdminLog::TYPE['hide'],
                'admin_id' => session('admin.id'),
            ];
            $this->log()->save($logData);
            //es
            VideoCommonModel::delEs($this->getAttr('id'));
            //冻结排行数据
            ActivityTopCommonModel::hideTopData($this->getAttr('id'));
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rollback();
            return false;
        }
    }

    /**
     * 删除
     * @param $remark
     * @return bool
     */
    public function del($remark)
    {
        $originalStatus = $this->getAttr('status');
        if ($originalStatus == VideoCommonModel::STATUS['delete']) {
            $this->error = '视频已经删除';
            return false;
        }
        try {
            $this->startTrans();
            $this->setAttr('status', VideoCommonModel::STATUS['delete']);
            if (!$this->save()) {
                exception('失败');
            }
            //log
            $logData = [
                'type' => VideoAdminLog::TYPE['delete'],
                'admin_id' => session('admin.id'),
                'remark' => $remark,
            ];
            $this->log()->save($logData);
            //es
            VideoCommonModel::delEs($this->getAttr('id'));
            //删除排行数据
            ActivityTopCommonModel::delTopData($this->getAttr('id'));
            //推送这个删除到队列进行后续处理
            $data = [
                'action' => 'deleteVideo',
                'params' => [
                    'video_id' => $this->getAttr('id'),
                    'original_status' => $originalStatus,
                ],
            ];
            publish_message($data);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rollback();
            return false;
        }
    }

    /**
     * 置顶
     * @param $action
     * @return bool|int
     */
    public function top($action)
    {
        if (!$this->getAttr('category_id')) {
            $this->error = '未设置分类,无法置顶';
            return false;
        }
        $this->setAttr('recommend', $action ? 1 : 0);
        $ret = $this->save();
        if ($ret) {
            if ($action) {
                VideoCommonModel::addTopVideoToCache($this->getAttr('id'), $this->getAttr('category_id'));
            } else {
                VideoCommonModel::delTopVideoFromCache($this->getAttr('id'), $this->getAttr('category_id'));
            }
        }
        return $ret;
    }

    /**
     * 热门
     * @param $action
     * @return bool|int
     */
    public function hot($action)
    {
        if ($this->getAttr('status')!=VideoCommonModel::STATUS['display']){
            $this->error='只有上架的视频可以设置热门';
            return false;
        }
        if ($action){
            //设置热门
            $hotVideo=$this->getAttr('hotVideo');
            if ($hotVideo){
                //原来加过
                $hotVideo->setAttr('status',HotVideoCommonModel::STATUS['normal']);
                $ret=$hotVideo->save();
            }else{
                //原来没有加过
                $hotData=[
                    'admin_id'=>session('admin.id'),
                    'status'=>HotVideoCommonModel::STATUS['normal'],
                ];
                $ret=$this->hotvideo()->save($hotData);
                if ($ret){
                    //加热门任务
                    \app\common\model\Mission::runMission('public_video_hot',$this->getAttr('user_id'));
                }
            }
        }else{
            //取消热门
            $ret=$this->getAttr('hotVideo')->save(['status'=>HotVideoCommonModel::STATUS['cancel']]);
        }
        return $ret;
    }

    /**
     * 获取今日评论总数
     */
    public function getTodayUploadTotal()
    {
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $start = mktime(0,0,0,$month,$day,$year);
        $end= mktime(23,59,59,$month,$day,$year);
        $count=$this
            ->where([
                'create_time'=>['between',[$start,$end]],
            ])
            ->count();
        return $count;
    }

    /**
     * 获取今日播放总数
     * @return bool|string
     */
    public function getTodayViewTotal()
    {
        return VideoCommonModel::getTodayViewTotal();
    }
}
