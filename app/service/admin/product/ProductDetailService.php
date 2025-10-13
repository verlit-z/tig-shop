<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 商品
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\product;

use app\model\order\Order;
use app\model\product\ECard;
use app\model\product\PriceInquiry;
use app\model\product\Product;
use app\model\product\ProductAttributes;
use app\model\product\ProductServices;
use app\model\promotion\Seckill;
use app\model\promotion\SeckillItem;
use app\model\promotion\TimeDiscountItem;
use app\model\user\CollectProduct;
use app\service\admin\user\FeedbackService;
use app\service\admin\user\UserInfoService;
use app\service\admin\user\UserRankService;
use app\service\admin\user\UserService;
use app\service\common\BaseService;
use app\service\front\cart\CartService;
use app\service\front\promotion\PromotionService;
use exceptions\ApiException;
use utils\Time;
use utils\Util;

/**
 * 商品服务类
 */
class ProductDetailService extends BaseService
{
    protected int|string $id;
    public $product = null;

    public function __construct(int|string $id)
    {
        $this->id = $id;
    }

    /**
     * 获取详情
     * @return array
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDetail(): array
    {
        if ($this->product === null) {
            $result = Product::with(['e_card_group'])->find($this->id);

            if (!$result) {
                throw new ApiException(Util::lang('商品不存在'));
            }

            // 判断商品是否参与秒杀
            $seckill = Seckill::where("product_id", $this->id)
                ->where("seckill_start_time", "<=", Time::now())
                ->where("seckill_end_time", ">=", Time::now())
                ->find();
            if (!is_null($seckill)) {
                $seckill = $seckill->toArray();
                $result->is_seckill = 1;
                $result->seckill_end_time = $seckill['seckill_end_time'];
            } else {
                $result->is_seckill = 0;
            }

            // 付费内容商品在用户购买过之后才可以显示
            $is_buy = 0;
            $user_id = request()->userId;
            if ($user_id) {
                $product_id = $this->id;
                $order_by_product_count = Order::hasWhere('items', function ($query) use ($product_id) {
                    $query->where('product_id', $product_id);
                })->where(['is_del' => 0, 'Order.user_id' => $user_id, "pay_status" => Order::PAYMENT_PAID])->count();

                if ($order_by_product_count) {
                    $is_buy = 1;
                }
            }

            if (!$is_buy) {
                $result->paid_content = "";
            }
            $result->is_buy = $is_buy;

            // 卡密商品根据卡券修改库存
            if ($result->product_type == Product::PRODUCT_TYPE_CARD) {
                $card_num = ECard::where(['group_id' => $result->card_group_id,'is_use' => 0])->count();
                $result->product_stock = $card_num;
            }

            if(!empty($result->paid_content)) {
                if(!is_array($result->paid_content)) {
                    $result->paid_content = [
                        [
                        'html' => $result->paid_content,
                        'type' => 'text'
                        ]
                    ];
                }
            }

            $this->product = $result;


        }
        $item = $this->product->toArray();
        return $item;
    }

    /**
     * 设置商品对象
     * @param object $product
     * @return void
     */
    public function setDetail(object $product): void
    {
        $this->product = $product;
    }

    /**
     * 获取商品秒杀信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSeckillInfo(): array
    {
        $seckill = Seckill::with(["seckill_item"])
            ->where("product_id", $this->id)
            ->where("seckill_start_time", "<=", Time::now())
            ->where("seckill_end_time", ">=", Time::now())
            ->select()->toArray();
        return $seckill;
    }

    /**
     * 获取默认选择的属性
     * @param int $sku_id
     * @return array
     * @throws ApiException
     */
    public function getSelectValue(int $sku_id): array
    {
        if (empty($sku_id)) {
            return [];
        }
        $sku_info = app(ProductSkuService::class)->getDetail($sku_id);
        if (empty($sku_info)) {
            return [];
        }
        if (empty($sku_info['sku_data'])) {
            return [];
        }
        $select_value = [];
        foreach ($sku_info['sku_data'] as $sku) {
            $select_value[] = $sku['name'] . ':' . $sku['value'];
        }
        return $select_value;
    }

    /**
     * 获取商品图文详情
     * @return array
     */
    public function getDescArr(): array
    {
        return app(ProductService::class)->getProductDescArr($this->product->product_desc);
    }

    /**
     * 获取相册列表
     * @return array
     */
    public function getPicList(): array
    {
        return app(ProductGalleryService::class)->getProductGalleryList($this->id);
    }

    public function getVideoList(): array
    {
        return app(ProductVideoService::class)->getProductVideoList($this->id);
    }

    /**
     * 获取属性列表
     * @return array
     */
    public function getAttrList(): array
    {
        return app(ProductAttributesService::class)->getAttrList($this->id);
    }

    /**
     * 获取sku列表
     * @return array
     */
    public function getSkuList(): array
    {
        return app(ProductSkuService::class)->getSkuList($this->id);
    }

    /**
     * 获取商品评论评分详情（随商品加载）
     * @return array
     */
    public function getProductCommentRankDetail(): array
    {
        return app(CommentService::class)->getProductCommentRankDetail($this->id);
    }

    /**
     * 获取商品评论详情（完整）
     * @return array
     */
    public function getProductCommentDetail(): array
    {
        return app(CommentService::class)->getProductCommentDetail($this->id);
    }

    /**
     * 获取商品评论列表
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductCommentList(array $params): array
    {
        $params['product_id'] = $this->id;
        return app(CommentService::class)->getProductCommentList($params);
    }

    /**
     * 获取商品评论数量
     * @param array $params
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getProductCommentCount(array $params): int
    {
        $params['product_id'] = $this->id;
        return app(CommentService::class)->getProductCommentCount($params);
    }

    /**
     * 获取商品Sku详情
     * @param int $sku_id
     * @return array
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductSkuDetail(int $sku_id = 0, int $exclude_activity = 0, $extra_attr_ids = ''): array
    {
        $product = $this->getDetail();
        $stock = $product['product_stock'] ?: 0;
        $price = $product['product_price'] ?: 0;
        // 判断是否有sku
        if ($sku_id > 0) {
            $sku = app(ProductSkuService::class)->getDetail($sku_id);
            if ($sku) {
                $id = $sku['sku_id'];
                $stock = $sku['sku_stock'];
                $price = isset($sku['sku_price']) ? $sku['sku_price'] : $price;
                $data = $sku['sku_data'];
                if (isset($data['0']['name'])) {
                    $sku['pic_thumb'] = ProductAttributes::where('product_id', $sku['product_id'])->where('attr_name',
                        $data['0']['name'])->where('attr_value', $data['0']['value'])->value('attr_pic_thumb');
                }
            }
        }
        $ranks_list = app(UserRankService::class)->getUserRankList();
        if(request()->userId) { //兼容商品添加报错
            $user_rank_id = app(UserService::class)->getUserRankId(request()->userId);
        } else {
            $user_rank_id = -1;
        }

        foreach ($ranks_list as $key => $value) {
            if ($value['rank_id'] == $user_rank_id && $value['discount'] > 0) {
                $discount = floatval($value['discount']);
                $price = round($price * $discount / 10, 2);
            }
        }
        $attr_price = '0';
        if (!empty($extra_attr_ids)) {
            $attr_list = app(CartService::class)->getProductExtraDetail(explode(',', $extra_attr_ids));
            if (!empty($attr_list)) {
                foreach ($attr_list as $attr) {
                    $attr_price = bcadd($attr_price, (string)$attr['attr_price'], 2);
                }
            }
        }
        $promotionList = [];
        $newPrice = null;
        if (!$exclude_activity) {
            $promotions = app(PromotionService::class)->getProductsPromotion([
                [
                    'product_id' => $product['product_id'],
                    'sku_id' => $sku_id
                ]
            ], $product['shop_id'], 'detail');
            $promotionList = $promotions[$sku_id]['activity_info'] ?? [];
            foreach ($promotionList as $key => $promotion) {
                if ($promotion['type'] == 1) {
                    $promotionList[$key]['data']['item'] = SeckillItem::where([
                        'seckill_id' => $promotion['data']['seckill_id'],
                        'sku_id' => $sku_id
                    ])->find();
                    if (!empty($promotionList[$key]['data']['item'])) {
                        $newPrice = $promotionList[$key]['data']['item']['seckill_price'];
                        $stock = $promotionList[$key]['data']['item']['seckill_stock'];
                    }
                } elseif ($promotion['type'] == 6) {
                    $promotionList[$key]['data']['item'] = TimeDiscountItem::where([
                        'discount_id' => $promotion['data']['discount_id']
                    ])->where('product_id', $product['product_id'])->find();
                    $newPrice = app(\app\service\front\promotion\TimeDiscountService::class)->getTimeDiscountPrice([
                        'sku_id' => $sku_id,
                        'price' => $price
                    ], $promotionList[$key]['data']['item']);
                    $promotionList[$key]['data']['item']['discount_price'] = bcsub($price, $newPrice, 2);
                } elseif ($promotion['type'] == 2) {
                    if ($promotion['is_delete'] == 1) {
                        unset($promotionList[$key]);
                    }
                }

            }
        }

        $origin_price = Util::number_format_convert($newPrice) ? $price : null;
        $price = Util::number_format_convert($newPrice) ?: $price;
        if (!empty($origin_price)) {
            $origin_price = bcadd((string)$price, $attr_price, 2);
        }
        if (!empty($price)) {
            $price = bcadd((string)$price, $attr_price, 2);
        }

        return [
            'id' => $id ?? 0,
            'data' => $data ?? [],
            'sku' => $sku ?? [],
            'origin_price' => $origin_price, //没有活动价则不需要展示划线原价
            'price' => $price,
            'stock' => max($stock, 0),
            'promotion' => array_values($promotionList),
            'product_id' => $product['product_id'],
            'is_seckill' => $product['is_seckill'] ?? 0,
            'seckill_end_time' => $product['seckill_end_time'] ?? '',
        ];
    }

    /**
     * 获取商品服务信息
     * @return array
     * @throws ApiException
     */
    public function getServiceList(): array
    {
        $product = Product::where("product_id|product_sn", $this->id)->find();
        if (!$product) {
            throw new ApiException(Util::lang("商品不存在"));
        }
        $result = ProductServices::whereIn("product_service_id", $product->product_service_ids)
            ->where("default_on", 1)->order("sort_order", "asc")
            ->select()->toArray();
        return $result;
    }

    /**
     * 判断是否被收藏
     * @return bool
     * @throws ApiException
     */
    public function getIsCollect(): bool
    {
        $product = Product::where("product_id|product_sn", $this->id)->find();
        if (!$product) {
            return false;
        }
        if (CollectProduct::where(["product_id" => $product->product_id, "user_id" => request()->userId])->count()) {
            return true;
        }
        return false;
    }

    /**
     * 获取商品咨询数量
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getConsultationCount(): int
    {
        return app(FeedbackService::class)->getProductFeedbackCount($this->id);
    }

    /**
     * 获取相关商品
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRelatedList(): array
    {
        $product = Product::where("product_id", $this->id)->find();
        if (empty($product)) return [];
        $related = $product->product_related;
        if (!is_array($related)) return [];

        return Product::whereIn('product_id', $related)
            ->field("product_id,product_name,product_sn,market_price,pic_thumb")
            ->order("sort_order", "asc")
            ->select()
            ->toArray();
    }


}
