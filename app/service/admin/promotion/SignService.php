<?php

namespace app\service\admin\promotion;

use app\model\promotion\Sign;
use app\model\promotion\SignInSetting;
use app\service\admin\user\UserService;
use app\service\common\BaseService;
use utils\Config as UtilsConfig;
use utils\Time;

class SignService extends BaseService
{
    public function __construct(Sign $sign)
    {
        $this->model = $sign;
    }

    /**
     * 获取签到设置项
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSignSettingList(): array
    {
        return app(SignInSetting::class)->order('day_num', 'asc')->select()->toArray();
    }

    /**
     * 获取上次签到记录行数
     * @param int $user_id
     * @return int
     */
    public function getSignCount(int $user_id): int
    {
        $sign_num = $this->model->where('user_id', $user_id)->order('id', 'desc')->value('sign_num');

        return $sign_num ?? 0;
    }

    /**
     * 检测用户今日是否签到
     * @param int $user_id
     * @return int
     */
    public function checkUserSignIn(int $user_id): int
    {
        if (empty($user_id)) return false;
        $today = Time::today();
        $is_sign = $this->model->where([['user_id', '=', $user_id], ['add_time', '>', $today]])->value('id');

        return $is_sign ? 1 : 0;
    }

    /**
     * 获取今日能获取的积分
     * @param array $list
     * @param int $count
     * @param int $user_sign
     * @return int
     */
    public function getSignPoints(array $list, int $count, int $user_sign): int
    {
        if (!$user_sign) {
            $count = $count + 1;
        }
        $sign_points = 0;
        foreach ($list as $v) {
            if ($v['day_num'] == $count) $sign_points = $v['points'];
        }

        return $sign_points;
    }

    /**
     * 签到
     * @param int $user_id
     * @param int $sign_num
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addSignIn(int $user_id, int $sign_num): bool
    {
        $data = [
            'user_id' => $user_id,
            'sign_num' => $sign_num,
            'add_time' => Time::now(),
        ];
        $insert_id = $this->model->insert($data);
        if (!$insert_id) return false;
        //赠送积分
        $record = self::getSignCount($user_id);
        $list = self::getSignSettingList();
        $sign_point = self::getSignPoints($list, $record, 1);
        if ($sign_point > 0) {
            $integralName = UtilsConfig::get('integralName');
            app(UserService::class)->incPoints($sign_point, $user_id, '签到赠送'. $integralName);
        }

        return true;
    }
}