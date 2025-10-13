<?php

namespace app\service\admin\decorate\modules;

use app\model\product\ProductGroup;
use app\service\admin\product\ProductService as ProductProductService;
use app\service\common\BaseService;

/**
 * 商品分组模块
 */
class ProductGroupService extends BaseService
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

			$product_group = ProductGroup::where('shop_id', $decorate['shop_id'])->find($module['product_group_id']);
			if (!empty($product_group)) {
				$filter = [
					'product_status' => 1,
					'is_delete' => 0,
					'product_ids' => $product_group->product_ids,
					'page' => $page,
					'size' => $size,
					'shop_id' => $decorate['shop_id']
				];

				if ($page > 1 && $page * $size > $module['product_number']) {
					$page_limit = ceil($module['product_number'] / $size);
					if ($page > $page_limit) {
						$product = [];
					} else {
						$final_size = min($size, $module['product_number'] - ($page - 1) * $size);
						$product = app(ProductProductService::class)->getProductList($filter);
						$product =  array_slice($product, 0, $final_size);
					}
				} else {
					$product = app(ProductProductService::class)->getProductList($filter);
				}
			}
		}
		$module['product_list'] = $product;
		return $module;
	}
}