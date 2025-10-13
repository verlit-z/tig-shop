<?php

namespace app\service\admin\user;

use app\model\setting\Region;
use app\model\user\User;
use app\model\user\UserCompany;
use app\service\admin\common\sms\SmsService;
use app\service\common\BaseService;
use exceptions\ApiException;
use think\facade\Db;
use utils\Config;
use utils\Time;

class UserCompanyService extends BaseService
{
	/**
	 * 筛选查询
	 * @param array $filter
	 * @return object|\think\db\BaseQuery
	 */
	public function filterQuery(array $filter): object
	{
		$query = UserCompany::query()->withJoin([
			'user' => function ($query) {
				$query->field("username,is_company_auth");
			}
		]);

        if (isset($filter['type']) && !empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }

		if (isset($filter['username']) && !empty($filter['username'])) {
			$query->whereLike('username', '%' . $filter['username'] . '%');
		}

		if (isset($filter['status']) && !empty($filter['status'])) {
			$query->where('status', $filter['status']);
		}

		return $query;
	}

	/**
	 * 企业认证审核
	 * @param int $id
	 * @param array $data
	 * @return bool
	 * @throws ApiException
	 */
	public function audit(int $id, array $data):bool
	{
		$userCompany = $this->getDetail($id);
		if ($userCompany['status'] != UserCompany::STATUS_WAIT) {
			throw new ApiException('状态参数错误');
		}

		if ($data['status'] == UserCompany::STATUS_REFUSE && empty($data['audit_remark'])) {
			throw new ApiException('请填写审核备注');
		}

		if ($data['status'] == UserCompany::STATUS_PASS) {
			$data['audit_time'] = Time::now();
		}

		try {
			Db::startTrans();
			$result = $userCompany->save($data);
			if ($result && $data['status'] == UserCompany::STATUS_PASS) {
				if(!$this->auditRelated($userCompany->user_id)) {
					Db::rollback();
					throw new ApiException('操作失败');
				}
			}
			Db::commit();
		} catch (\Exception $e) {
			Db::rollback();
			throw new ApiException($e->getMessage() . $e->getFile() . $e->getLine() . $e->getTraceAsString());
		}
		return $result;
	}

	/**
	 * 删除
	 * @param int $id
	 * @return bool
	 * @throws ApiException
	 */
	public function del(int $id):bool
	{
		$userCompany = $this->getDetail($id);
		if ($userCompany->status != UserCompany::STATUS_REFUSE) {
			throw new ApiException('审核未通过的才可删除');
		}
		$result = $userCompany->delete();
		return $result !== false;
	}

	/**
	 * 企业认证申请
	 * @param array $data
	 * @return UserCompany
	 * @throws ApiException
	 */
	public function companyApply(array $data): UserCompany
	{
		try {
			Db::startTrans();

			if (isset($data['status']) && $data['status'] == UserCompany::STATUS_PASS) {
				$data['audit_time'] = Time::now();
			}

			$result = UserCompany::create($data);

			if ($result && isset($data['status']) && $data['status'] == UserCompany::STATUS_PASS) {
				// 审核通过 -- 执行相关操作
				$this->auditRelated($data['user_id']);
			}

            // 发送短信通知
            if (Config::get('smsNote')) {
                $type = $data['type'] == 1 ? '个人' : '企业';
                $day = Config::get('tips');
                $content = [$type, $day];
                app(SmsService::class)->sendSms($data['contact_mobile'],"user_certification", $content);
            }

			Db::commit();
		}catch (\Exception $e) {
			Db::rollback();
			throw new ApiException($e->getMessage() . $e->getFile() . $e->getLine() . $e->getTraceAsString());
		}

		return $result;
	}

	/**
	 * 企业认证审核通过之后的操作
	 * @param int $id
	 * @param int $user_id
	 * @return bool
	 * @throws ApiException
	 */
	public function auditRelated(int $user_id): bool
	{
		// 审核通过 - 修改会员状态
		$user = User::find($user_id);
		if (empty($user)) {
			throw new ApiException('会员不存在');
		}
		$result = $user->save(['is_company_auth' => 1]);
		return $result;
	}

	/**
	 * 企业认证详情
	 * @param int $id
	 * @return UserCompany
	 * @throws ApiException
	 */
	public function getDetail(int $id): UserCompany
	{
		$userCompany = UserCompany::with(['user'])->append(['status_text','type_text'])->find($id);
		if (empty($userCompany)) {
			throw new ApiException('企业认证信息不存在');
		}
        // 数据中地址处理
        if (!empty($userCompany['company_data']['license_addr_province'])) {
            $regionList = Region::whereIn('region_id',
                $userCompany['company_data']['license_addr_province'])->column('region_name', 'region_id');

            $license_addr_province_name = "";
            foreach ($userCompany['company_data']['license_addr_province'] as $regionId) {
                $license_addr_province_name .= $regionList[$regionId] ?? '';
            }
            $company_data = $userCompany['company_data'];
            $company_data['license_addr_province_name'] = $license_addr_province_name;
            $userCompany->company_data = $company_data;
        }

		return $userCompany;
	}

	/**
	 * 当前用户的企业认证申请
	 * @param int $user_id
	 * @return UserCompany|null
	 */
	public function getApplyByUserId(int $user_id): UserCompany|null
	{
		$userCompany = UserCompany::where('user_id', $user_id)
			->order('id','desc')
			->field(['id,user_id,status','type'])
			->append(['status_text','type_text'])
			->find();
		return $userCompany;
	}
}