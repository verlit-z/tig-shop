<?php

namespace app\service\admin\decorate\modules;

use app\model\promotion\WechatLive;
use app\service\common\BaseService;

/**
 * 直播模块
 */
class WechatLiveService extends BaseService
{
	public function formatData(array $module, array|null $params = null, array $decorate = []): array
	{
		$wechatLive = [];
		if (!empty($module)) {
			$page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
			$size = isset($params['size']) && $params['size'] > 0 ? $params['size'] : 10;

			// 正常状态下的直播
			$wechatLive = app(\app\service\admin\promotion\WechatLiveService::class)->getFilterList([
				'live_status' => WechatLive::LIVE_STATUS_NORMAL,
				'shop_id' => $decorate['shop_id'],
				'page' => $page,
				'size' => $size
			]);

		}
		$module['wechatLive_list'] = $wechatLive;
		return $module;
	}
}