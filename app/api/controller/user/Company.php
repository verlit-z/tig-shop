<?php

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\service\admin\user\UserCompanyService;
use think\App;
use think\Response;
use utils\Util;

/**
 * 会员企业认证
 */
class Company extends IndexBaseController
{
	protected userCompanyService $userCompanyService;
	public function __construct(App $app, UserCompanyService $userCompanyService)
	{
		$this->userCompanyService = $userCompanyService;
		parent::__construct($app);
	}

	/**
	 * 企业认证申请
	 * @return Response
	 * @throws \exceptions\ApiException
	 */
	public function apply():Response
	{
		$data = $this->request->only([
            'type/d' => 2,
			'company_data' => '',
		], 'post');
		$data['user_id'] = request()->userId;
		$data['contact_name'] = $data['company_data']['corporate_name'] ?? '';
		$data['contact_mobile'] = $data['company_data']['contact_phone'] ?? '';
		$data['company_name'] = $data['company_data']['company_name'] ?? '';

		$result = $this->userCompanyService->companyApply($data);

        return $this->success($result);
	}

	/**
	 * 企业认证详情
	 * @return Response
	 * @throws \exceptions\ApiException
	 */
	public function detail():Response
	{
		$id = $this->request->all('id/d',0);
		$item = $this->userCompanyService->getDetail($id);
		if ($item['user_id'] != request()->userId) {
			return $this->error(Util::lang('企业认证信息不存在'));
		}
        return $this->success($item);
	}

	/**
	 * 当前用户的申请
	 * @return Response
	 */
	public function myApply():Response
	{
		$item = $this->userCompanyService->getApplyByUserId(request()->userId);
        return $this->success($item);
	}
}