<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use think\Db;

/**
 * 热门视频管理
 *
 * @icon fa fa-circle-o
 */
class Hotvideo extends Backend
{
    
    /**
     * Video模型对象
     * @var \app\admin\model\HotVideo
     */
    protected $model = null;
    // 关联搜索
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\HotVideo;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['admin', 'video'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['admin', 'video'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $key => $value) {
                $list[$key]['hot_video'] = 232;
                $value->visible(['id', 'video_id', 'admin_id', 'create_time', 'status'], true);
                $value->visible(['admin']);
                $value->getRelation('admin')->visible(['nickname']);
                $value->visible(['video']);
                $value->getRelation('video')->visible(['title']);
            }

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            // $pk = $this->model->getPk();
            // $adminIds = $this->getDataLimitAdminIds();
            // if (is_array($adminIds)) {
            //     $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            // }
            // $list = $this->model->where($pk, 'in', $ids)->select();
            // $count = 0;
            // foreach ($list as $k => $v) {
            //     $count += $v->delete();
            // }
            // if ($count) {
            //     $this->success();
            // } else {
            //     $this->error(__('No rows were deleted'));
            // }
        }
        // $this->error(__('Parameter %s can not be empty', 'ids'));
        $this->error(__('滚犊子吧，删什么删'));
    }

    /**
     * 播放视频
     * 
     * @param  string $ids [description]
     * @return [type]      [description]
     */
    public function play($ids = "")
    {
        if ($ids) {
            $row = Db::name('HotVideo')->where(['id' => $ids])->find();
            if (!$row) {
                $this->error('视频Id错误');
            }
            $videoInfo = Db::name('video')->where(['id' => $row['video_id']])->find();

            // $play_url=$qiniuConfig['public_video_bkt_protocol'].'://'.$qiniuConfig['public_video_bkt_domain'].'/'.$key;
            
            $play_url = 'https://videopub.actuive.com/' . $videoInfo['key'];
            $this->view->assign("play_url", $play_url);
            return $this->view->fetch();
        }
    }

}
