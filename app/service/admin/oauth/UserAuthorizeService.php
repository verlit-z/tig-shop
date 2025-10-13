<?php

namespace app\service\admin\oauth;

use app\model\user\UserAuthorize;
use app\service\common\BaseService;
use utils\Time;
use utils\Util;

class UserAuthorizeService extends BaseService
{
    const AUTHORIZE_TYPE = [
        'wechat' => 1,
        'miniProgram' => 2,
        'pc' => 1,
        'qq' => 4,
    ];

    /**
     * 获取用户授权信息
     * @param string $open_id
     * @param string $union_id
     * @return int
     * @throws \exceptions\ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserOAuthInfo(string $open_id, string $union_id = ''): int
    {
        if (!empty($union_id)) {
            $user_id = UserAuthorize::field('user_id')->where(['unionid' => $union_id])->find();
        } else {
            $user_id = UserAuthorize::field('user_id')->where(['open_id' => $open_id])->find();
        }
        if (!$user_id) return 0;

        return $user_id->user_id;
    }

    /**
     * 添加第三方授权记录
     * @param int $user_id
     * @param string $open_id
     * @param array $open_data
     * @param string $union_id
     * @return true
     */
    public function addUserAuthorizeInfo(int $user_id, string $open_id = '', array $open_data = [], string $union_id = ''): bool
    {
        $open_data = $open_data ? json_encode($open_data) : '';
        $open_id = $open_id ? strip_tags(trim($open_id)) : $open_id;
        $union_id = $union_id ? strip_tags(trim($union_id)) : $union_id;
        $authorize_id = UserAuthorize::where(['open_id' => $open_id])->value('authorize_id');
        $client_type = Util::getClientType();
        $authorize_type = self::AUTHORIZE_TYPE[$client_type];
        if (empty($authorize_id) && $user_id && $open_id && $authorize_type) {
            $arr = [
                'authorize_type' => $authorize_type,
                'user_id' => $user_id,
                'open_id' => $open_id,
                'open_data' => $open_data,
                'unionid' => $union_id,
                'add_time' => Time::now()
            ];
            UserAuthorize::insert($arr);
        }

        return true;
    }

    /**
     * 更新用户openid
     * @param int $user_id
     * @param string $open_id
     * @param int $authorize_type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateUserAuthorizeInfo(int $user_id, string $open_id, int $authorize_type = 1): bool
    {
        $info = UserAuthorize::where('open_id', '=', $open_id)->find();
        if ($info) {
            $info->user_id = $user_id;
            $info->save();
        } else {
            $arr = [
                'authorize_type' => $authorize_type,
                'user_id' => $user_id,
                'open_id' => $open_id,
                'open_data' => [],
                'add_time' => Time::now()
            ];
            UserAuthorize::insert($arr);
        }

        return true;
    }

    /**
     * 获取用户授权的openid
     * @param int $user_id
     * @param int $authorize_type
     * @return string|null
     */
    public function getUserAuthorizeOpenId(int $user_id, int $authorize_type = 1): string|null
    {
        return UserAuthorize::where(['user_id' => $user_id, 'authorize_type' => $authorize_type])->value('open_id');
    }

    /**
     * 获取用户授权的openid
     * @param int $user_id
     * @param int $authorize_type
     */
    public function getUserAuthorizeByOpenId($openId, int $authorize_type = 1)
    {
        return UserAuthorize::where(['open_id' => $openId, 'authorize_type' => $authorize_type])->find();
    }

    /**
     * 删除授权
     * @param int $user_id
     * @param int $authorize_type
     * @return bool
     */
    public function delUSerAuthorizeInfo(int $user_id, int $authorize_type = 1): bool
    {
        return UserAuthorize::where(['user_id' => $user_id, 'authorize_type' => $authorize_type])->delete();
    }

    /**
     * 检测用户是否授权
     * @param int $user_id
     * @param int $authorize_type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkUserIsAuthorize(int $user_id, int $authorize_type = 1): bool
    {
        $res = UserAuthorize::where(['user_id' => $user_id, 'authorize_type' => $authorize_type])->find();

        return (bool)$res;
    }
}