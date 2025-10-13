<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 店铺
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\promotion;

use app\model\promotion\Promotion;
use app\service\admin\product\ProductService as ProductProductService;
use app\service\common\BaseService;

/**
 * 活动管理服务类
 */
class PromotionService extends BaseService
{

    public function __construct(Promotion $promotion)
    {
        $this->model = $promotion;
    }


    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    protected function filterQuery(array $filter): object
    {
        $query = $this->model->query();
        // 处理筛选条件
        if (isset($filter['type']) && !empty($filter['type'])) {
            $type = is_array($filter['type']) ? $filter['type'] : explode(',', $filter['type']);
            $query->whereIn('type', $type);
        }
        if (isset($filter['shop_id']) && $filter['shop_id'] > -1) {
            $query->where('shop_id', $filter['shop_id']);
        }
        if (isset($filter['time_type']) && $filter['time_type'] == 1) {
            $query->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<=', time())->where('end_time', '>=', time());
                })->whereOr(function ($query) {
                    $query->where("start_time", 0)->where("end_time", 0);
                });
            });
        } elseif (isset($filter['time_type']) && $filter['time_type'] == 2) {
            $query->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<=', time())->where("end_time", "<=",
                        time() + 7 * 24 * 3600)->where('end_time', '>=', time())->where('end_time', '<>', 0);
                });
            });

        } elseif (isset($filter['time_type']) && $filter['time_type'] == 3) {
            $query->where('start_time', '>', time());
        } elseif (isset($filter['time_type']) && $filter['time_type'] == 4) {
			$query->where('end_time', '>', time());
		}


        if (isset($filter['is_delete'])){
            $query->where('is_delete', $filter['is_delete']);
        }

        if (isset($filter['is_available'])) {
            $query->where('is_available', $filter['is_available']);
        }
        return $query;
    }

	/**
	 * 满足营销的商品列表
	 * @param array $type
	 * @param int $page
	 * @param int $size
	 * @param int $product_number
	 * @param int $shop_id
	 * @return array
	 */
	public function getPromotionByProduct(array $type, int $page, int $size, int $product_number, int $shop_id):array
	{
		$product = [];
		// 进行中的营销活动
		$promotion = $this->getFilterList([
			"is_available" => 1,
			'is_delete' => 0,
			'shop_id' => $shop_id,
			'size' => -1,
			'time_type' => 1,
			'type' => $type
		]);

		if (!$promotion->isEmpty()) {
			$filter = [
				'intro_type' => 'hot',
				'product_status' => 1,
				'is_delete' => 0,
				'page' => $page,
				'size' => $size,
				'shop_id' => $shop_id
			];
			// 指定商品id
			$product_ids = [];
			// 不含商品id
			$extra_product_ids = [];

			if (!in_array(0,array_column($promotion->toArray(), 'range'))) {
				foreach ($promotion as $item) {
					if ($item['range'] == Promotion::PROMOTION_RANGE_PRODUCT) {
						// 指定商品
						$product_ids = array_merge($product_ids, $item['range_data']);
					} elseif ($item['range'] == Promotion::PROMOTION_RANGE_EXCLUDE_PRODUCT) {
						// 不含商品id
						$extra_product_ids = array_merge($extra_product_ids, $item['range_data']);
					}
				}

				if (!empty($product_ids)) {
					$filter['product_ids'] = array_unique($product_ids);
				} else {
					$filter['extra_product_ids'] = array_unique($extra_product_ids);
				}
				unset($filter['intro_type']);
			}

			if ($page > 1 && $page * $size > $product_number){
				$page_limit = ceil($product_number / $size);
				if ($page > $page_limit) {
					$product = [];
				} else {
					$final_size = min($size, $product_number - ($page - 1) * $size);
					$product = app(ProductProductService::class)->getProductList($filter);
					$product =  array_slice($product, 0, $final_size);
				}
			} else {
				$product = app(ProductProductService::class)->getProductList($filter);
			}
		}
		return $product;
	}

}
