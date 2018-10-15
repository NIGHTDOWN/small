<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Comment extends Backend
{
    
    /**
     * VideoComment模型对象
     * @var \app\admin\model\VideoComment
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\VideoComment;

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
            $total = Db::name('VideoComment')
                ->alias('video_comment')
                ->join([
                    ['video', 'video_comment.video_id = video.id', 'LEFT'],
                    ['user', 'video_comment.user_id = user.id', 'LEFT']
                ])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = Db::name('VideoComment')
                ->alias('video_comment')
                ->join([
                    ['video', 'video_comment.video_id = video.id', 'LEFT'],
                    ['user', 'video_comment.user_id = user.id', 'LEFT']
                ])
                ->where($where)
                ->order($sort, $order)
                ->field([
                    'video_comment.id', 
                    'video_comment.video_comment', 
                    'video_comment.replace_comment', 
                    'video_comment.create_time', 
                    'video_comment.update_time', 
                    'video_comment.status', 
                    'video.title', 
                    'user.nickname',
                ])
                ->select();
            
            $data = [];
            foreach ($list as $key => $value) {
                $data[$key]['video_comment']['id'] = $value['id'];
                $data[$key]['video_comment']['video_comment'] = $value['video_comment'];
                $data[$key]['video_comment']['replace_comment'] = $value['replace_comment'];
                $data[$key]['video_comment']['create_time'] = $value['create_time'];
                $data[$key]['video_comment']['update_time'] = $value['update_time'];
                $data[$key]['video_comment']['status'] = $value['status'];
                $data[$key]['video']['title'] = $value['title'];
                $data[$key]['user']['nickname'] = $value['nickname'];
            }

            $result = array("total" => 1, "rows" => $data);
            return json($result);
        }
        return $this->view->fetch();
    }

    public function show()
    {
        if ($this->request->isPost()) {
            $id = input('id');
            $result = $this->model->show($id);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

    public function hide()
    {
        if ($this->request->isPost()) {
            $param = $this->request->only(['id', 'replace_comment']);
            $result = $this->model->hide($param);
            if ($result) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
    }

}
