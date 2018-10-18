<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use think\Cache;
use app\common\model\Activity as CommonActivity;

class ActivityTop extends Model
{
    // 表名
    protected $name = 'activity_top';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'update_time_text'
    ];

    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    /**
     * 获取排行榜
     * @param $activity_id
     * @return array|false|mixed|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTop($activity_id)
    {
        $top_key = CommonActivity::ACTIVITY_TOP_DATA_PRE . $activity_id;
        $top = json_decode(Cache::get($top_key), 1);
        if (! $top) {
            // 排行榜信息
            $top = Db::name("activity_top")->where(['activity_id' => $activity_id])->find();
            unset($top['activity_id']);
            unset($top['update_time']);
            unset($top['top']);
            $top['video_play'] = isset($top['video_play']) ? json_decode($top['video_play'], 1) : [];
            $top['video_apply'] = isset($top['video_apply']) ? json_decode($top['video_apply'], 1) : [];
            $top['video_like'] = isset($top['video_like']) ? json_decode($top['video_like'], 1) : [];
        }
        foreach ($top as $key => $value) {
            if (!isset($top[$key]['data']) || empty($top[$key]['data'])) {
                $top[$key]['data'] = (object)[];
                $top[$key]['number'] = 0;
            } else {
                $video_ids = array_column($top[$key]['data'], 'video_id');
                $video_keys = Db::name('video')
                    ->where('id', 'in', $video_ids)
                    ->column('key,status', 'id');
                foreach ($value['data'] as $k => $val) {
                    $top[$key]['data'][$k]['video_play_url'] = isset($video_keys[$val['video_id']]) ?
                        Video::getPlayUrl($video_keys[$val['video_id']]['key'], $video_keys[$val['video_id']]['status']) : '';
                }
            }
            $top[$key]['text'] = CommonActivity::$matchText[$key];
            $top[$key]['active'] = $key == 'video_play' ? 1 : 0;
        }
        return $top;
    }

}
