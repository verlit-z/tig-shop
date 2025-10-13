<?php

namespace app\service\admin\decorate\modules;

use app\model\promotion\Promotion;
use app\service\admin\promotion\PromotionService;
use app\service\common\BaseService;

/**
 * 满减模块
 */
class ProductPromotionService extends BaseService
{
	public function formatData(array $module, array|null $params = null, array $decorate = []): array
	{
		$product = [];
		if (!empty($module)) {
			$page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
			$size = isset($params['size']) && $params['size'] > 0 ? $params['size'] : 10;
			if (isset($module['product_number']) && $size > $module['product_number']) {
				$size = $module['product_number'];
			}

			$type = [Promotion::TYPE_PRODUCT_PROMOTION_1,Promotion::TYPE_PRODUCT_PROMOTION_2];
			$product = app(PromotionService::class)->getPromotionByProduct($type, $page, $size, $module['product_number'], $decorate['shop_id']);
		}
		$module['product_list'] = $product;
		return $module;
	}
}