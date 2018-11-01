<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/31
 * Time: 18:31
 */

namespace app\api\model;

use think\Model;

class FollowUser extends Model
{
    /**
     * 关注官方账号
     * @param int $user_id  用户id
     */
    public function followOfficialAccount($user_id)
    {
        $officialAccount = model('User')->getOfficialAccount();
        if ($officialAccount) {
            $this->follow($officialAccount, 1, $user_id);
        }
    }

    public function follow($follow_user_id = 0, $op = 1, $user_id = 0)
    {
        $code = 0;
        if (!model('user')->where('id', $follow_user_id)->count()) {
            $code = 121;
            goto end;
        }
        if (!$user_id) {
            $auth = \app\common\library\Auth::instance();
            $user_id = $auth->getUserinfo()['id'];
        }
        $data = [
            'user_id' => $user_id,
            'follow_user_id' => $follow_user_id,
        ];
        $row = $this->where($data)->find();
        if($op == 1){
            $blacklistWhere = [
                'user_id' => $user_id,
                'black_user_id' => $follow_user_id
            ];
            // 是否黑名单
            if (model('UserBlacklist')->where($blacklistWhere)->count() > 0) {
                $code = 164;
                goto end;
            }
            if ($row && $row['status'] === 1) {
                $code = 119;
                goto end;
            }
            if ($user_id == $follow_user_id) {
                $code = 119;
                goto end;
            }
            if ($row) {
                $this->where('id', $row['id'])->update(['status' => 1]);
            } else {
                $data['status'] = 1;
                $this->save($data);
                //关注任务
                $bonus_coin = \app\common\model\Mission::runMission('follow_user', $user_id);
            }
            /** @var \app\common\model\User $user_logic */
            $user_logic = model('User');
            $user_logic->incFansTotal($follow_user_id);
            $user_logic->incFollowTotal($user_id);
            publish_message([
                'action' => 'follow',
                'params' => [
                    'user_id' => $user_id,
                    'follow_user_id' => $follow_user_id,
                ],
            ]);
            //增加活跃值
            \app\common\model\ActiveTask::incrActiveValue('user_active_video_comment', $user_id);
        } else {
            if (!$row || $row['status'] === 0) {
                $code = 120;
                goto end;
            }
            $this->where('id', $row['id'])->update(['status' => 0]);
            /** @var \app\common\model\User $user_logic */
            $user_logic = model('User');
            $user_logic->decFansTotal($follow_user_id);
            $user_logic->decFollowTotal($user_id);
            publish_message([
                'action' => 'unfollow',
                'params' => [
                    'user_id' => $user_id,
                    'follow_user_id' => $follow_user_id,
                ],
            ]);
        }
        end:
        return ['code' => $code, 'bonus_coin' => $bonus_coin ?? 0];
    }
}
