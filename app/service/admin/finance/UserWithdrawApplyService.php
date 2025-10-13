<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 提现申请
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\finance;

use app\model\finance\UserWithdrawAccount;
use app\model\finance\UserWithdrawApply;
use app\model\msg\AdminMsg;
use app\model\user\User;
use app\service\admin\msg\AdminMsgService;
use app\service\admin\user\UserService;
use app\service\common\BaseService;
use exceptions\ApiException;
use think\facade\Db;
use utils\Time;
use utils\Util;

/**
 * 提现申请服务类
 */
class UserWithdrawApplyService extends BaseService
{
    public function __construct()
    {
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter)->with(["user"])->append(["status_type"]);
        $result = $query->page($filter['page'], $filter['size'])->select();
        return $result->toArray();
    }

    /**
     * 获取筛选结果数量
     *
     * @param array $filter
     * @return int
     */
    public function getFilterCount(array $filter): int
    {
        $query = $this->filterQuery($filter);
        $count = $query->count();
        return $count;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    public function filterQuery(array $filter): object
    {
        $query = UserWithdrawApply::query();
        // 处理筛选条件

        if (isset($filter["keyword"]) && !empty($filter['keyword'])) {
            $query->username($filter["keyword"]);
        }

        // 状态检索
        if (isset($filter["status"]) && $filter["status"] > -1) {
            $query->where('status', $filter["status"]);
        }

        if (isset($filter["user_id"]) && $filter["user_id"] > 0) {
            $query->where('user_id', $filter["user_id"]);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }
        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return array
     * @throws ApiException
     */
    public function getDetail(int $id): array
    {
        $result = UserWithdrawApply::with('user')->where('id', $id)->append(["status_type"])->find();

        if (!$result) {
            throw new ApiException(/** LANG */ '提现申请不存在');
        }

        return $result->toArray();
    }

    /**
     * 提现申请余额操作
     * @param array $data
     * @return void
     */
    public function balanceOperation(array $data): void
    {
        // 处理状态已完成
        if ($data["status"] == 1) {
            //减去用户冻结的余额
            app(UserService::class)->decFrozenBalance($data["amount"], $data["user_id"], '提现审核通过扣减冻结余额');
        }
        if ($data["status"] == 2) {
            //拒绝后返回余额
            app(UserService::class)->incBalance($data["amount"], $data["user_id"], '提现审核拒绝返回余额');
            //减去用户冻结的余额
            app(UserService::class)->decFrozenBalance($data["amount"], $data["user_id"], '提现审核拒绝扣减冻结余额');
        }
    }


    /**
     * 添加提现申请
     * @param array $data
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createUserWithdrawApply(array $data): bool
    {
        if ($data["status"] == 1) {
            $data["finished_time"] = Time::now();
        }
        // 判断提现金额
		$user = User::find($data['user_id']);
		$balance = !empty($user) ? $user->balance : 0;
        if ($data["amount"] > $balance) {
            throw new ApiException(/** LANG */ '提现金额大于账户的可用余额');
        }
        try {
            Db::startTrans();
			$result = UserWithdrawApply::create($data);
            if (in_array($data["status"], [0, 1])) {
                // 保存账号信息
                $data["account_data"]["user_id"] = $data["user_id"];
                UserWithdrawAccount::create($data["account_data"]);
            }
            switch ($data['status']){
                case 0:
                    //待处理--减少余额-增加冻结余额
                    app(UserService::class)->incFrozenBalance($data['amount'], $data["user_id"], '提现冻结余额');
                    app(UserService::class)->decBalance($data['amount'], $data["user_id"], '提现扣除余额');
                    break;
                case 1:
                    //处理成功--减少冻结余额
                    app(UserService::class)->decBalance($data['amount'], $data["user_id"], '提现扣除余额');
                    break;
            }

			// 发送后台消息  -- 提现申请
			app(AdminMsgService::class)->createMessage([
				'msg_type' => AdminMsg::MSG_TYPE_WITHDRAW_APPLY,
				'title' => "您有新的提现申请,申请用户：{$user->username}",
				'content' => "用户【{$user->username}】申请提现，申请金额：{$data['amount']}元",
				'related_data' => [
					"withdraw_apply_id" => $result->id
				]
			]);
            Db::commit();

            return true;
        }catch (\Exception $exception){
            Db::rollback();
            throw new ApiException($exception->getMessage());
        }
    }

    /**
     * 执行提现申请更新
     *
     * @param int $id
     * @param array $data
     * @param bool $isAdd
     * @return int|bool
     * @throws ApiException
     */
    public function updateUserWithdrawApply(int $id, array $data)
    {
        if ($data["status"] == 1) {
            $data["finished_time"] = Time::now();
        }
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $apply_info = UserWithdrawApply::find($id);
        if ($apply_info->status > 0) {
            throw new ApiException(/** LANG */ '该笔提现申请已完成，不能修改');
        }
        $result = UserWithdrawApply::where('id', $id)->save($data);
        if ($result !== false) {
            $balance_data = [
                'amount' => $apply_info->amount,
                'user_id' => $apply_info->user_id,
                'status' => $data['status']
            ];
            $this->balanceOperation($balance_data);
            return true;
        }
        return false;
    }

    /**
     * 删除提现申请
     *
     * @param int $id
     * @return bool
     */
    public function deleteUserWithdrawApply(int $id): bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */ '#id错误');
        }
        $result = UserWithdrawApply::destroy($id);
        return $result !== false;
    }

    /**
     * PC 提现账号列表
     * @param array $filter
     * @param int $user_id
     * @return array|\think\Collection|\think\db\BaseQuery[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAccountList(array $filter, int $user_id)
    {
        $query = UserWithdrawAccount::query();
        // 处理筛选条件
        if (isset($filter["account_type"]) && !empty($filter["account_type"])) {
            $query->where('account_type', $filter["account_type"]);
        }
        if (isset($filter["account_id"]) && !empty($filter['account_id'])) {
            $query->where('account_id', $filter["account_id"]);
        }
        if ($user_id > 0) {
            $query->where('user_id', $user_id);
        }
        return $query->append(["account_type_name"])->select();
    }

    /**
     * PC 添加提现账号
     * @param array $data
     * @param int $user_id
     * @return bool
     */
    public function addWithdrawAccount(array $data, int $user_id)
    {
        if (UserWithdrawAccount::where('user_id', $user_id)->count() > 15) {
            throw new ApiException(/** LANG */ Util::lang('最多添加15个卡'));
        }
        $data["user_id"] = $user_id;
        //检测是否存在相同的卡号
        $res = UserWithdrawAccount::where([['user_id', '=', $user_id], ['account_no', '=', $data['account_no']], ['account_type', '=', $data['account_type']]])->find();
        if ($res) {
            throw new ApiException(/** LANG */ Util::lang('当前账号已存在'));
        }
        $result = UserWithdrawAccount::create($data);
        return $result !== false;
    }

    /**
     * 删除提现账号
     * @param array $data
     * @param int $user_id
     * @return bool
     */
    public function delWithdrawAccount(int $account_id, int $user_id)
    {
        $result = UserWithdrawAccount::where('user_id', $user_id)->where('account_id', $account_id)->delete();
        return $result !== false;
    }

    /**
     * 编辑提现账号
     * @param array $data
     * @param int $user_id
     * @return bool
     */
    public function editWithdrawAccount(int $account_id, int $user_id, array $data)
    {
        //检测是否存在相同的卡号
        $res = UserWithdrawAccount::where([['account_id', '<>', $data['account_id']], ['user_id', '=', $user_id], ['account_no', '=', $data['account_no']], ['account_type', '=', $data['account_type']]])->find();
        if ($res){
            throw new ApiException(/** LANG */ Util::lang('当前账号已存在'));
        }
        $result = UserWithdrawAccount::where('user_id', $user_id)->where('account_id', $account_id)->update($data);
        return $result !== false;
    }

    /**
     * 提现账号详情
     * @param array $data
     * @param int $user_id
     * @return bool
     */
    public function withdrawAccountDetail(int $account_id, int $user_id)
    {
        $result = UserWithdrawAccount::where('user_id', $user_id)->where('account_id', $account_id)->find();
        return $result;
    }

    /**
     * 提现申请
     * @param array $data
     * @param int $user_id
     * @return true
     * @throws ApiException
     */
    public function updateUserWithdrawApplyPc(array $data, int $user_id): bool
    {
        if (empty($data["account_data"])) {
            throw new ApiException(/** LANG */ Util::lang('请填写提现账号信息'));
        }
        $user = User::findOrEmpty($user_id);
		$balance = !empty($user) ? $user->balance : 0;
        if ($data["amount"] > $balance) {
            throw new ApiException(/** LANG */ Util::lang('提现金额大于账户的可用余额'));
        }
        $data["user_id"] = $user_id;
        try {
            Db::startTrans();
            $result = UserWithdrawApply::create($data);
            app(UserService::class)->incFrozenBalance($data['amount'], $user_id, '提现冻结余额');
            app(UserService::class)->decBalance($data['amount'], $user_id, '提现扣除余额');
            Db::commit();

			// 发送后台消息  -- 提现申请
			app(AdminMsgService::class)->createMessage([
				'msg_type' => AdminMsg::MSG_TYPE_WITHDRAW_APPLY,
				'title' => "您有新的提现申请,申请用户：{$user->username}",
				'content' => "用户【{$user->username}】申请提现，申请金额：{$data['amount']}元",
				'related_data' => [
					"withdraw_apply_id" => $result->id
				]
			]);

            return true;
        } catch (\Exception $exception) {
            Db::rollback();
            throw new ApiException(Util::lang($exception->getMessage()));
        }
    }
}
