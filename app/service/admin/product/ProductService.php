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

use app\model\common\Locales;
use app\model\merchant\Shop;
use app\model\msg\AdminMsg;
use app\model\product\Brand;
use app\model\product\Product;
use app\model\product\ProductArticle;
use app\model\product\ProductAttributes;
use app\model\product\ProductGallery;
use app\model\product\ProductGroup;
use app\model\product\ProductSku;
use app\model\product\ProductVideo;
use app\model\promotion\Coupon;
use app\model\vendor\VendorProduct;
use app\model\vendor\VendorProductSku;
use app\model\vendor\VendorShopBind;
use app\service\admin\msg\AdminMsgService;
use app\service\admin\participle\ParticipleService;
use app\service\admin\setting\ShippingTplService;
use app\service\common\BaseService;
use app\validate\product\ProductValidate;
use exceptions\ApiException;
use log\AdminLog;
use think\facade\Db;
use utils\Config;
use utils\Time;
use utils\Util;

/**
 * 商品服务类
 */
class ProductService extends BaseService
{
    protected ProductValidate $productValidate;

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
        $filter['page'] = !empty($filter['page']) ? intval($filter['page']) : 1;
        $filter['size'] = !empty($filter['size'] && $filter['size'] < 100) ? intval($filter['size']) : 100;
        $query = $this->filterQuery($filter)
            ->field('category_id,brand_id,product_tsn,market_price,virtual_sales,shipping_tpl_id,free_shipping,product_id,pic_url,pic_thumb,product_name,check_status,check_reason,shop_id,suppliers_id,product_type,product_sn,product_price,product_status,is_best,is_new,is_hot,product_stock,sort_order');
        if (isset($filter['sort_field_raw']) && !empty($filter['sort_field_raw'])) {
            $query->orderRaw($filter['sort_field_raw']);
        } elseif (isset($filter['sort_field']) && !empty($filter['sort_field'])) {
            $query->order($filter['sort_field'], $filter['sort_order'] ?? 'desc');
        } else {
            $query->order('sort_order', 'asc')->order('product_id', 'desc');
        }
        $result = $query->page($filter['page'], $filter['size'])->select();
        $result->load([
            'productSku',
            'shop' => function ($query) {
                $query->field(['shop_id', 'shop_title']);
            }
        ]);

        foreach ($result as &$item) {
            $productDetailService = new ProductDetailService($item['product_id']);
            $productAvailability = $productDetailService->getProductSkuDetail($item['product_sku']['0']['sku_id'] ?? 0,
                0,
                '');
            $item['price'] = $productAvailability['price'];
            $item['is_seckill'] = $productAvailability['is_seckill'];
            $item['seckill_end_time'] = $productAvailability['seckill_end_time'];
        }

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
     * 获取商品列表
     * @param array $filter
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductList(array $filter): array
    {
        $filter['page'] = !empty($filter['page']) ? intval($filter['page']) : 1;
        $filter['size'] = !empty($filter['size']) && $filter['size'] < 999 ? intval($filter['size']) : 999;
        $filter['product_status'] = 1;
        $filter['is_delete'] = 0;
        $query = $this->filterQuery($filter)->with([
            'skuMinPrice',
            'seckillMinPrice',
            "product_sku",
            "shop" => function ($query) {
                $query->field(['shop_id', 'shop_title']);
            }
        ])
            ->field('product_id,pic_thumb,pic_url,product_name,virtual_sales,check_status,shop_id,suppliers_id,product_type,product_sn,product_price,market_price,product_status,is_best,is_new,is_hot,product_stock,sort_order,product_brief');
        if (isset($filter['sort_field']) && !empty($filter['sort_field'])) {
            $query->order($filter['sort_field'], $filter['sort_order'] ?? 'desc');
        } elseif (isset($filter['sort_order']) && $filter['sort_order'] == 'rand') {
            $query->orderRaw('RAND()');
        } else {
            $query->order('sort_order', 'asc')->order('product_id', 'desc');
        }
        $result = $query->page($filter['page'], $filter['size'])->select();
        foreach ($result as $value) {
            //秒杀价优先级大于属性价格
            if ($value->seckill_price > 0) {
                $value->org_product_price = $value->product_price;
                $value->product_price = $value->seckill_price;
                $value->market_price = $value->org_product_price;
            }
            if ($value->sku_price > 0 && empty($value->seckill_price)) {
                $value->product_price = $value->sku_price;
            }
        }
        return $result->toArray();
    }

    /**
     * 获取商品列表
     * @param array $filter
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductIds(array $filter): array
    {
        $filter['page'] = !empty($filter['page']) ? intval($filter['page']) : 1;
        $filter['size'] = !empty($filter['size']) && $filter['size'] < 999 ? intval($filter['size']) : 999;
        $filter['product_status'] = 1;
        $filter['is_delete'] = 0;
        $query = $this->filterQuery($filter)
            ->field('product_id');
        if (isset($filter['sort_field']) && !empty($filter['sort_field'])) {
            $query->order($filter['sort_field'], $filter['sort_order'] ?? 'desc');
        } elseif (isset($filter['sort_order']) && $filter['sort_order'] == 'rand') {
            $query->orderRaw('RAND()');
        } else {
            $query->order('sort_order', 'asc')->order('product_id', 'desc');
        }
        $result = $query->page($filter['page'], $filter['size'])->select();
        return $result->toArray();
    }

    public function initNewShopData($shop_id)
    {
        for ($i = 0; $i < 4; $i++) {
            $item = [
                'product_name' => '示例商品' . $i,
                'product_stock' => 1000,
                'product_price' => 0.01,
                'product_desc_arr' => [],
                'product_tsn' => $i,
                'shop_id' => $shop_id,
                'product_status' => 0
            ];
            $this->updateProduct(0, $item, true);
        }
        return true;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    public function filterQuery(array $filter): object
    {
        $query = Product::query();
        // 处理筛选条件
        if (!empty($filter['with_cart_sum'])) {
            $query->withSum('cart', 'quantity');
        }
        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            // 根据关联语言的关键词检索
            if (config('app.IS_OVERSEAS') && !empty(request()->header('X-Locale-Code')) && request()->header('X-Locale-Code') != 'zh') {
                $locales = Locales::where('locale_code', request()->header('X-Locale-Code'))->find();
                if (empty($locales)) {
                    $locales = Locales::where('is_default', 1)->find();
                }
                $query->hasWhere('transactionData', function ($query) use ($filter, $locales) {
                    $query->where(function ($q) use ($filter, $locales) {
                        $q->where('translation_value', 'like', '%' . $filter['keyword'] . '%')->whereOr('product_sn',
                            'like', '%' . $filter['keyword'] . '%');
                    })->where('locale_id', $locales->id);
                });
            } else {
                $query->whereOr(function ($query) use ($filter) {
                    $query->where('product_name', 'like', '%' . $filter['keyword'] . '%')
                        ->whereOr('keywords', 'like', '%' . $filter['keyword'] . '%')
                        ->whereOr('product_sn', 'like', '%' . $filter['keyword'] . '%');
                });
            }
        }

        if (!empty($filter['coupon_id'])) {
            $coupon = Coupon::find($filter['coupon_id']);
            if (empty($coupon)) {
                $query->where('product_id', -1);
            } else {
                if ($coupon['send_range'] == 1) {
                    $query->where(function ($query) use ($filter, $coupon) {
                        foreach ($coupon['send_range_data'] as $rand_data) {
                            $query->whereIn('category_id', app(CategoryService::class)->catAllChildIds($rand_data),
                                'OR');
                        }
                    });

                } elseif ($coupon['send_range'] == 2) {
                    $query->whereIn('brand_id', $coupon['send_range_data']);
                } elseif ($coupon['send_range'] == 3) {
                    $query->whereIn('product_id', $coupon['send_range_data']);
                } elseif ($coupon['send_range'] == 4) {
                    $query->whereNotIn('product_id', $coupon['send_range_data']);
                }
                // 所属店铺商品
                $query->where('shop_id', $coupon["shop_id"]);
            }
        }

        if (isset($filter['brand_id']) && $filter['brand_id'] > 0) {
            $query->where('brand_id', $filter['brand_id']);
        }

        if (isset($filter['brand_ids']) && is_array($filter['brand_ids']) && !empty($filter['brand_ids'])) {
            $query->whereIn('brand_id', $filter['brand_ids']);
        }

        if (isset($filter['product_id']) && $filter['product_id'] > 0) {
            $query->where('product_id', $filter['product_id']);
        }
        if (isset($filter['product_ids']) && !empty($filter['product_ids'])) {
            $query->whereIn('product_id', $filter['product_ids']);
        }

        if (isset($filter['extra_product_ids']) && !empty($filter['extra_product_ids'])) {
            $query->whereNotIn('product_id', $filter['extra_product_ids']);
        }

        if (isset($filter['category_id']) && $filter['category_id'] > 0) {
            $query->whereIn('category_id', app(CategoryService::class)->catAllChildIds($filter['category_id']));
        }

        if (isset($filter['ids']) && $filter['ids'] !== null) {
            $query->whereIn('product_id', is_array($filter['ids']) ? $filter['ids'] : explode(',', $filter['ids']));
        }
        if (isset($filter['max_price']) && $filter['max_price'] > 0) {
            $query->where('product_price', '<=', $filter['max_price']);
        }
        if (isset($filter['min_price']) && $filter['min_price'] > 0) {
            $query->where('product_price', '>=', $filter['min_price']);
        }

        // 店铺id
        if (isset($filter["shop_id"]) && $filter["shop_id"] > -1) {
            $query->where('shop_id', $filter["shop_id"]);
        }

        // 检索店铺商品
        if (isset($filter['search_shop']) && !empty($filter['search_shop'])) {
            $query->where('shop_id', ">", 0);
        }

        // 店铺id
        if (isset($filter["shop_category_id"]) && $filter["shop_category_id"] > -1) {
            $query->where('shop_category_id', $filter["shop_category_id"]);
        }

        //分组id
        if (!empty($filter['product_group_id'])) {
            $product_group = ProductGroup::find($filter['product_group_id']);
            if (!empty($product_group)) {
                $query->whereIn('product_id', $product_group->product_ids);
            } else {
                $query->where('product_id', -1);
            }
        }
        // 商品类型
        if (isset($filter["intro_type"]) && !empty($filter["intro_type"])) {
            $query->introType($filter["intro_type"]);
        }

        // 商品上下架
        if (isset($filter["product_status"]) && $filter["product_status"] != -1) {
            $query->where('product_status', $filter["product_status"]);
        }
        //是否删除
        if (isset($filter["is_delete"]) && $filter["is_delete"] != -1) {
            $query->where('is_delete', $filter["is_delete"]);
        }
        if (isset($filter["is_new"])) {
            $query->where('is_new', $filter["is_new"]);
        }
        // 审核状态
        if (isset($filter["check_status"]) && $filter["check_status"] != -1) {
            $query->where('check_status', $filter["check_status"]);
        }

        if (isset($filter['suppliers_id']) && !empty($filter['suppliers_id'])) {
            $query->where('suppliers_id', $filter['suppliers_id']);
        }
        return $query;
    }

    /**
     * 获取筛选商品结果所包含的所有品牌
     * @param array $filter
     * @param int $size
     * @return array
     */
    public function getProductBrands(array $filter, int $size = 300): array
    {
        $query = $this->filterQuery($filter)
            ->with(['brand'])
            ->field('brand_id')
            ->where('brand_id', '>', 0);
        //->where('status', Brand::AUDIT_PASS);
        $result = $query->group('brand_id')->limit($size)->select();
        // 过滤掉 brand 为空的数据
        $filtered = array_filter($result->toArray(), function ($item) {
            return !empty($item['brand_name']);
        });

        return array_values($filtered); // 重新索引数组
    }


    /**
     * 获取筛选商品结果中的最高原价
     * @param array $filter
     * @param int $size
     * @return float
     */
    public function getProductMaxPrice(array $filter, int $size = 300): float
    {
        unset($filter['max_price']);
        unset($filter['min_price']);
        $query = $this->filterQuery($filter)
            ->field('product_price');
        //->where('status', Brand::AUDIT_PASS);
        return $query->max('product_price');
    }

    /**
     * 获取筛选商品结果所包含的所有分类
     * @param array $filter
     * @param int $size
     * @return array
     */
    public function getProductCategorys(array $filter, int $size = 20): array
    {
        $query = $this->filterQuery($filter)->with(['category'])->field('category_id')->where('category_id', '>', 0);
        $result = $query->group('category_id')->limit($size)->select();
        $result = $result->toArray();
        foreach ($result as $k => &$v) {
            $v['category_name'] = Util::lang($v['category_name'], '', [], 3);
        }
        return $result;
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
        $result = Product::where('product_id', $id)->find();
        if (!$result) {
            throw new ApiException(Util::lang('商品不存在'));
        }
        $item = $result->toArray();

        $item['product_service_ids'] = $item['product_service_ids'] ?? [];
        $item['product_desc_arr'] = $this->getProductDescArr($item['product_desc']);
        $item['img_list'] = app(ProductGalleryService::class)->getProductGalleryList($id);
        $item['product_video_info'] = app(ProductVideoService::class)->getProductVideoList($id);
        return $item;
    }

    /**
     * 根据条码获取商品信息
     * @param string $goods_sn
     * @return int[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductKeyBySn(string $goods_sn): array
    {
        $product_id = 0;
        $sku_id = 0;
        $result = Product::where('product_sn', $goods_sn)->find();
        if ($result) {
            $product_id = $result->product_id;
        } else {
            $sku_info = app(ProductSkuService::class)->getProductSkuBySn($goods_sn);
            if (!empty($sku_info)) {
                $product_id = $sku_info['product_id'];
                $sku_id = $sku_info['sku_id'];
            }
        }

        return [$product_id, $sku_id];
    }

    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return Product::where('product_id', $id)->value('product_name');
    }


    /**
     * 执行商品添加或更新
     *
     * @param int $id
     * @param array $data
     * @param bool $isAdd
     * @return int|bool
     * @throws ApiException
     */
    public function updateProduct(int $id, array $data, bool $isAdd = false)
    {
        if (empty($data['keywords'])) {
            $data['keywords'] = app(ParticipleService::class)->cutForSearch($data['product_name']);
        }
        $data['product_desc'] = $this->getProductDesc($data['product_desc_arr']);
        $shop_id = $data['shop_id'] ?? 0;
        $is_new = $data['is_new'] ?? 0;
        $is_hot = $data['is_hot'] ?? 0;
        unset($data['product_desc_arr']);
        if ($isAdd) {
            if ($data['shop_id'] > 0) {
                if (Config::get('shopProductNeedCheck') == 1) {
                    $data['check_status'] = 0;
                    $data['product_status'] = 0;
                } else {
                    $data['check_status'] = 1;
                    $data['product_status'] = 1;
                }
            }
            //如果没有选择模板id，就选默认运费模板id
            if (empty($data['shipping_tpl_id']) && isset($data['no_shipping']) && $data['no_shipping'] == 0) {
                $data['shipping_tpl_id'] = app(ShippingTplService::class)->getDefaultShippingTplId($shop_id);
            }
            $data['add_time'] = Time::now();
            if (empty($data['product_sn'])) {
                $data['product_sn'] = $this->creatNewProductSn();
            }
            unset($data["product_id"]);

            $result = Product::create($data);

            // 关联文章
            if (!empty($data["product_article_list"])) {
                foreach ($data["product_article_list"] as $k => $v) {
                    $article_list[$k] = [
                        "goods_id" => $result->product_id,
                        "article_id" => $v,
                    ];
                }
                (new ProductArticle)->saveAll($article_list);
            }
            if (request()->adminUid) {
                AdminLog::add('新增商品:' . $data['product_name']);
            }
        } else {
            $item = $this->getDetail($id);

            if ($data['shop_id'] > 0) {
                if (Config::get('shopProductNeedCheck') == 1) {
                    $data['check_status'] = 0;
                    $data['product_status'] = 0;
                } else {
                    $data['check_status'] = 1;
                    $data['product_status'] = 1;
                }
                // 店铺
//                unset($data['is_best']);
//                unset($data['is_new']);
//                unset($data['is_hot']);
                unset($data['suppliers_id']);
                unset($data['give_integral']);
                unset($data['rank_integral']);
                unset($data['integral']);
                unset($data['virtual_sales']);
                unset($data['check_status']);
                if ($item['check_status'] != 1) {
                    $data['product_status'] = 0;
                }
            } elseif (request()->suppliersId > 0) {
                // 供应商
                unset($data['suppliers_id']);
                unset($data['store_cat_id']);
            } else {
                if (isset($data['check_status']) && $data['check_status'] != 1) {
                    $data['product_status'] = 0;
                }
                unset($data['store_cat_id']);
            }
            unset($data['product_type']);

            if (!$id) {
                throw new ApiException('#id错误');
            }
            $product_article_list = $data["product_article_list"];
            unset($data["product_article_list"]);

            // 运费模板判断
            if ($data['shop_id'] != $item['shop_id']) {
                if ($data['shipping_tpl_id'] != $item['shipping_tpl_id']) {
                    throw new ApiException('不能跨店铺修改运费模板');
                }
            }

            unset($data['shop_id']);
            //如果是多规格sku需要计算库存
            $has_sku = ProductSku::where('product_id', $id)->count();
            if ($has_sku) {
                $product_stock = ProductSku::where('product_id', $id)->sum('sku_stock');
                $data['product_stock'] = $product_stock;
            }

            $result = Product::where('product_id', $id)->save($data);
            ProductArticle::where('goods_id', $id)->delete();
            if (!empty($product_article_list)) {
                $article_list = [];
                foreach ($product_article_list as $k => $v) {
                    $article_list[$k] = [
                        "goods_id" => $id,
                        "article_id" => $v,
                    ];
                }
                (new ProductArticle)->saveAll($article_list);
            }
            if (request()->adminUid) {
                AdminLog::add('更新商品:' . $this->getName($id));
            }

            if ($data['product_status'] == 0) {
                // 发送后台消息  -- 商品下架
                app(AdminMsgService::class)->createMessage([
                    'msg_type' => AdminMsg::MSG_TYPE_PRODUCT_OFF_SHELF,
                    'shop_id' => $item['shop_id'],
                    'product_id' => $id,
                    'title' => "商品下架通知:{$item['product_name']}",
                    'content' => "商品下架通知：您的商品【{$item['product_name']}】已经下架，请及时处理！",
                    'related_data' => [
                        'product_id' => $id
                    ]
                ]);
            }

        }
        // 更新商品分组
        if ($result !== false) {
            $group_new = ProductGroup::where(["product_group_name" => "新品", "shop_id" => $shop_id])->find();
            $group_hot = ProductGroup::where(["product_group_name" => "热卖", "shop_id" => $shop_id])->find();
            $product_id = $isAdd ? $result->product_id : $id;

            if (!empty($group_hot) && !empty($group_new)) {
                // 取消标识
                if (!$isAdd && $data['product_status']) {
                    if ($item['is_new'] && !$is_new) {
                        $this->setProductGroup($product_id, $group_new, 1);
                    }
                    if ($item['is_hot'] && !$is_hot) {
                        $this->setProductGroup($product_id, $group_hot, 1);
                    }
                }

                // 赋予新品热卖类型标识
                if ($data['product_status']) {
                    if ($is_new) {
                        $this->setProductGroup($product_id, $group_new);
                    }
                    if ($is_hot) {
                        $this->setProductGroup($product_id, $group_hot);
                    }
                }
            }
        }
        return $isAdd ? $result->product_id : $result !== false;
    }

    /**
     * 设置商品分组
     * @param int $product_id
     * @param ProductGroup $productGroup
     * @return bool
     */
    public function setProductGroup(int $product_id, ProductGroup $productGroup, int $is_del = 0): bool
    {
        $product_ids = $productGroup->product_ids;

        if ($is_del) {
            if (in_array($product_id, $product_ids)) {
                $key = array_search($product_id, $product_ids);
                unset($product_ids[$key]);
            }
        } else {
            if (!in_array($product_id, $product_ids)) {
                array_unshift($product_ids, $product_id);
            }
        }
        $product_ids = array_values(array_unique($product_ids));
        $productGroup->product_ids = $product_ids;
        return $productGroup->save();
    }

    public function updateProductStockBySku($id, $stock)
    {
        $product = Product::find($id);
        $product->product_stock = $stock;
        return $product->save();
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return int|bool
     * @throws ApiException
     */
    public function updateProductField(int $id, array $data)
    {
        validate(ProductValidate::class)->only(array_keys($data))->check($data);
        if (!$id) {
            throw new ApiException('#id错误');
        }

        $product = Product::find($id);

        if (in_array('product_status', array_keys($data))) {
            if ($data['product_status'] == 0) {
                // 发送后台消息  -- 商品下架
                app(AdminMsgService::class)->createMessage([
                    'msg_type' => AdminMsg::MSG_TYPE_PRODUCT_OFF_SHELF,
                    'shop_id' => $product->shop_id,
                    'product_id' => $id,
                    'title' => "商品下架通知:{$product->product_name}",
                    'content' => "商品下架通知：您的商品【{$product->product_name}】已经下架，请及时处理！",
                    'related_data' => [
                        "product_id" => $id
                    ]
                ]);
            } else {
                if($product->vendor_product_id) {
                    $vendor_product = VendorProduct::where('id', $product->vendor_product_id)->find();
                    if($vendor_product && $vendor_product->product_state == VendorProduct::PRODUCT_STATUS_OFF) {
                        throw new ApiException('供应商商品未上架');
                    }
                }
            }
        }

        $result = $product->save($data);
        AdminLog::add('更新商品:' . $this->getName($id));
        return $result !== false;
    }

    /**
     * @param int $sku_id
     * @param int $stock
     * @return mixed
     */
    public function updateSkuStock(int $sku_id, int $stock): mixed
    {
        return ProductSku::where('sku_id', $sku_id)->update(['sku_stock' => $stock]);
    }


    /**
     * 移至回收站
     *
     * @param int $id
     * @return bool
     */
    public function recycleProduct(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $get_name = $this->getName($id);
        $result = Product::where('product_id', $id)->update(['is_delete' => 1]);
        if ($result) {
            AdminLog::add('移至回收站:' . $get_name);
        }

        return $result !== false;
    }

    /**
     * 还原商品
     *
     * @param int $id
     * @return bool
     */
    public function restoreProduct(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $get_name = $this->getName($id);
        $result = Product::where('product_id', $id)->update(['is_delete' => 0]);
        if ($result) {
            AdminLog::add('还原:' . $get_name);
        }

        return $result !== false;
    }

    /**
     * 删除商品
     *
     * @param int $id
     * @return bool
     */
    public function deleteProduct(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $get_name = $this->getName($id);
        $result = Product::destroy($id);
        if ($result) {
            AdminLog::add('删除商品:' . $get_name);
        }

        return $result !== false;
    }

    /**
     * 上架商品
     *
     * @param int $id
     * @return bool
     */
    public function upProduct(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $get_name = $this->getName($id);
        $result = Product::where('product_id', $id)->update(['product_status' => 1]);
        if ($result) {
            AdminLog::add('上架商品:' . $get_name);
        }

        return true;
    }

    /**
     * 下架商品
     *
     * @param int $id
     * @return bool
     */
    public function downProduct(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $get_name = $this->getName($id);
        $product = Product::find($id);
        $result = $product->save(['product_status' => 0]);
        if ($result) {
            AdminLog::add('下架商品:' . $get_name);
        }

        // 发送后台消息 --- 商品下架
        app(AdminMsgService::class)->createMessage([
            'msg_type' => AdminMsg::MSG_TYPE_PRODUCT_OFF_SHELF,
            'shop_id' => $product->shop_id,
            'product_id' => $id,
            'title' => "商品下架通知:{$product->product_name}",
            'content' => "商品下架通知：您的商品【{$product->product_name}】已经下架，请及时处理！",
            'related_data' => ["product_id" => $id]
        ]);
        return true;
    }

    /**
     * 获取商品详情
     * @param string $html
     * @return array
     */
    public static function getProductDescArr(string|null $html = ''): array
    {
        $res = [];
        if (empty($html)) {
            return $res;
        }
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            $html = Util::lang($html, '', [], 9);
        }
        $arr = explode('<div data-division=1></div>', $html);
        foreach ($arr as $key => $value) {
            if (strpos($value, 'desc-pic-item') !== false) {
                $res[$key]['type'] = 'pic';
                preg_match('~<img.*?src=["\']+(.*?)["\']+~', $value, $match);
                $res[$key]['pic'] = $match[1];
            } else {
                $res[$key]['type'] = 'text';
            }
            $res[$key]['html'] = $value;
        }
        return $res;
    }

    /**
     * 获取商品描述
     * @param array $product_desc_arr
     * @return string
     */
    public function getProductDesc(array $product_desc_arr): string
    {
        $html = '';
        if (empty($product_desc_arr)) {
            return '';
        }
        $res = [];
        foreach ($product_desc_arr as $key => $value) {
            if ($value['type'] == 'pic') {
                $res[$key] = "<div class=\"desc-pic-item\"><img src=\"" . $value['pic'] . "\"></div>";
            }
            if ($value['type'] == 'text') {
                $res[$key] = $value['html'];
            }
        }
        return implode('<div data-division=1></div>', $res);
    }

    /**
     * 获得商品的库存
     * @param int $product_id
     * @param int $sku_id
     * @return int
     */
    public function getProductStock(int $product_id, int $sku_id = 0): int
    {
        if ($sku_id == 0) {
            $result = Product::where('product_id', $product_id)->value('product_stock');
        } else {
            $result = ProductSku::where(['product_id' => $product_id, 'sku_id' => $sku_id])->value('sku_stock');
        }

        return $result ?: 0;
    }

    /**
     * 检查商品的库存是否充足
     * @param int $quantity
     * @param int $product_id
     * @param int $sku_id
     * @return bool
     */
    public function checkProductStock(int $quantity, int $product_id, int $sku_id = 0): bool
    {
        $product_stock = $this->getProductStock($product_id, $sku_id);
        return $quantity <= $product_stock;
    }

    /**
     * 待审核商品数量
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getWaitingCheckedCount(): int
    {
        return Product::where('check_status', Product::CHECK_STATUS_PENDING)->where('is_delete', 0)->count();
    }

    /**
     * 创建商品编号
     * @param int $num
     * @return string
     */
    public function creatNewProductSn(int $num = 0): string
    {
        if (!$num) {
            $num = Product::max('product_id');
            $num = $num ? $num + 1 : 1;
        }
        $goods_sn = Config::get('snPrefix', '') . str_repeat('0', 7 - strlen($num)) . $num;
        return $goods_sn;
    }

    /**
     * 减库存
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function decStock(int $product_id, int $quantity, int $shop_id = 0): bool
    {

        // 3. 增加库存扣减日志
        $product_stock = Product::where('product_id', $product_id)->value('product_stock');
        $diff = $product_stock - $quantity;
        $productInventoryLog = [
            'product_id' => $product_id,
            'spec_id' => 0,
            'number' => $diff,
            'add_time' => time(),
            'old_number' => $product_stock,
            'type' => 2,
            'change_number' => $quantity,
            'desc' => "下单扣减库存",
            'shop_id' => $shop_id,
        ];
        app(ProductInventoryLogService::class)->updateProductInventoryLog(0, $productInventoryLog, true);


        return Product::where('product_id', $product_id)->dec('product_stock', $quantity)->update();
    }

    /**
     * 获取库存
     * @param int $product_id
     * @return int
     */
    public function getStock(int $product_id): int
    {
        return Product::findOrEmpty($product_id)->product_stock ?? 0;
    }


    /**
     * 增加库存
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function incStock(int $product_id, int $quantity, int $shop_id = 0): bool
    {

        // 3. 增加库存扣减日志
        $product_stock = Product::where('product_id', $product_id)->value('product_stock');
        $diff = $product_stock + $quantity;
        $productInventoryLog = [
            'product_id' => $product_id,
            'spec_id' => 0,
            'number' => $diff,
            'add_time' => time(),
            'old_number' => $product_stock,
            'type' => 1,
            'change_number' => $quantity,
            'desc' => "取消订单返还",
            'shop_id' => $shop_id,
        ];
        app(ProductInventoryLogService::class)->updateProductInventoryLog(0, $productInventoryLog, true);

        return Product::where('product_id', $product_id)->inc('product_stock', $quantity)->update();
    }

    /**
     * 增加销量
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function incSales(int $product_id, int $quantity): bool
    {
        return Product::where('product_id', $product_id)->inc('virtual_sales', $quantity)->update();
    }

    /**
     * 减少销量
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function decSales(int $product_id, int $quantity): bool
    {
        $virtual_sales = Product::where('product_id', $product_id)->value('virtual_sales');
        if ($virtual_sales < $quantity) {
            $quantity = $virtual_sales;
        }
        return Product::where('product_id', $product_id)->dec('virtual_sales', $quantity)->update();
    }

    /**
     * 获取商品编码
     * @param int $product_id
     * @return string
     */
    public function getProductSn(int $product_id): string
    {
        return Product::where('product_id', $product_id)->value('product_sn');
    }

    /**
     * 商品审核
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function auditProduct(int $id, array $data): bool
    {
        $product = Product::find($id);
        if (empty($product)) {
            throw new ApiException('商品不存在');
        }

        if ($product->check_status != 0) {
            throw new ApiException('商品审核状态错误');
        }

        if ($data['check_status'] == 2 && empty($data['check_reason'])) {
            throw new ApiException('请输入审核不通过的原因');
        }

        if ($data['check_status'] == 1) {
            $data['product_status'] = 1;
            $data['check_reason'] = "";
        }
        $result = $product->save($data);

        if ($result !== false) {
            $is_pass = $data['check_status'] == 1 ? '通过' : '未通过';
            $check_reason = $data['check_status'] == 1 ? '' : ",未通过的原因为：{$data['check_reason']}";
            // 发送审核通知消息
            app(AdminMsgService::class)->createMessage([
                'msg_type' => AdminMsg::MSG_TYPE_PRODUCT_AUDIT,
                'title' => "商品审核通知：{$product->product_name}",
                'shop_id' => $product->shop_id,
                'product_id' => $id,
                'content' => "商品 {$product->product_name} 审核结果为：{$is_pass} {$check_reason}",
                'related_data' => [
                    'product_id' => $id,
                ]
            ]);

            // 审核通过 -- 更新商品分组
            if ($data['check_status'] == 1) {
                $group_new = ProductGroup::where(["product_group_name" => "新品", "shop_id" => $product->shop_id])->find();
                $group_hot = ProductGroup::where(["product_group_name" => "热卖", "shop_id" => $product->shop_id])->find();

                if (!empty($group_hot) && !empty($group_new)) {
                    // 赋予新品热卖类型标识
                    if ($data['product_status']) {
                        if ($product->is_new) {
                            $this->setProductGroup($id, $group_new);
                        }
                        if ($product->is_hot) {
                            $this->setProductGroup($id, $group_hot);
                        }
                    }
                }
            }
        }
        return $result !== false;
    }

    /**
     * 再次审核
     * @param int $id
     * @param
     * @return bool
     * @throws ApiException
     */
    public function auditAgain(int $id): bool
    {
        $product = Product::find($id);
        if (empty($product)) {
            throw new ApiException('商品不存在');
        }
        if ($product->check_status != 2) {
            throw new ApiException('商品审核状态错误');
        }

        $data['check_status'] = 0;
        $data['check_reason'] = "";

        return $product->save($data);
    }

    /**
     * 批量下架
     * @param int $vendorId
     * @return Product|false
     */
    public function discontinueByVendorId(int $vendorId): Product|false
    {
        if (empty($vendorId)) {
            return false;
        }
        return Product::where('vendor_id', $vendorId)->update(['product_status' => 0]);
    }

    /**
     * 批量下架
     * @param int $vendorProductId
     * @return Product|false
     */
    public function discontinueByVendorProductId(int $vendorProductId): Product|false
    {
        if (empty($vendorProductId)) {
            return false;
        }
        return Product::where('vendor_product_id', $vendorProductId)->update(['product_status' => 0]);
    }

    /**
     * 批量下架
     * @param int $vendorProductId
     * @return Product|false
     */
    public function discontinueByVendorProductIds(array $vendorProductIds): Product|false
    {
        if (empty($vendorProductIds)) {
            return false;
        }
        return Product::whereIn('vendor_product_id', $vendorProductIds)->update(['product_status' => 0]);
    }

    /**
     * 供应商商品导入
     * @param $vendorProductIds
     * @param $shopId
     * @return true
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function vendorProductImport($vendorProductIds, $shopId)
    {
        try {
            Db::startTrans();
            //查询供应商商品信息
            $vendorProducts = VendorProduct::with(['skus', 'galleries', 'skuAttrs', 'video'])
                ->whereIn('id', $vendorProductIds)
                ->select()
                ->toArray();
            if (empty($vendorProducts)) {
                throw new ApiException('供应商商品不存在');
            }

            // 判断导入过的不可再次导入
            $products = Product::where('vendor_product_id', '>', 0)
                ->where('shop_id', $shopId)
                ->select()
                ->toArray();

            $importedVendorProductIds = array_column($products, 'vendor_product_id');

            $imported = false;
            foreach ($vendorProducts as $vendorProduct) {
                if (in_array($vendorProduct['id'], $importedVendorProductIds)) {
                    $imported = true;
                    break;
                }
            }

            if ($imported) {
                throw new ApiException('商品已导入过，请勿重复导入');
            }

            // 获取店铺价格设置
            $shop = Shop::find($shopId);
            $vendorSetPriceType = null;
            $vendorSetPriceAutoValue = null;

            if ($shopId != null && $shopId > 0 && $shop) {
                $vendorSetPriceType = $shop['vendor_set_price_type'];
                $vendorSetPriceAutoValue = $shop['vendor_set_price_auto_value'];
            } else {
                $vendorSetPriceType = Config::get('vendorSetPriceType');
                $vendorSetPriceAutoValue = Config::get('vendorSetPriceAutoValue');
            }
            $productStock = 0;
            $productPrice = 0;
            foreach ($vendorProducts as $vendorProduct) {
                // 1. 保存商品信息
                //获取商品价格和库存
                foreach ($vendorProduct['skus'] as $sku) {
                    if($productPrice == 0) {
                        $productPrice = $sku['supply_price'];
                    }
                    $productStock += $sku['sku_stock'];
                }
                $product = [
                    'product_name' => $vendorProduct['product_name'],
                    'product_sn' => $this->creatNewProductSn(),
                    'brand_id' => $vendorProduct['product_brand_id'],
                    'category_id' => $vendorProduct['product_category_id'],
                    'product_brief' => $vendorProduct['product_brief'],
                    'product_desc' => $vendorProduct['product_desc'],
                    'pic_thumb' => $vendorProduct['pic_thumb'],
                    'pic_original' => $vendorProduct['pic_original'],
                    //'product_volume' => $vendorProduct['product_volume'],
                    'product_stock' => $productStock,
                    'product_price' => $productPrice,
                    'product_status' => 0,
                    'is_best' => 0,
                    'is_hot' => 0,
                    'is_new' => 0,
                    'no_shipping' => 1,
                    'shop_id' => $shopId,
                    'vendor_id' => $vendorProduct['vendor_id'],
                    'vendor_product_id' => $vendorProduct['id'],
                    'create_time' => time(),
                    'update_time' => time(),
                ];
                $productModel = Product::create($product);
                $productId = $productModel->product_id;
                //2. 保存商品视频
                if (!empty($vendorProduct['video'])) {
                    $productVideo = [
                        'product_id' => $productId,
                        'video_url' => $vendorProduct['video']['video_url'],
                        'video_thumb' => $vendorProduct['video']['video_thumb'],
                        'sort_order' => $vendorProduct['video']['sort_order'],
                    ];
                    ProductVideo::create($productVideo);
                }

                // 3. 保存商品图片
                if (!empty($vendorProduct['galleries'])) {
                    foreach ($vendorProduct['galleries'] as $gallery) {
                        $productGallery = [
                            'product_id' => $productId,
                            'pic_thumb' => $gallery['pic_thumb'],
                            'pic_original' => $gallery['pic_original'],
                            'pic_large' => $gallery['pic_large'],
                            'sort_order' => $gallery['sort_order'],
                            'pic_url' => $gallery['pic_url'],
                            'pic_desc' => $gallery['pic_desc'],
                        ];
                        ProductGallery::create($productGallery);
                    }
                }
                //4. 保存商品属性
                if (!empty($vendorProduct['skuAttrs'])) {
                    foreach ($vendorProduct['skuAttrs'] as $skuAttr) {
                        $productSkuAttr = [
                            'product_id' => $productId,
                            'attr_type' => $skuAttr['attr_type'],
                            'attr_name' => $skuAttr['attr_name'],
                            'attr_value' => $skuAttr['attr_value'],
                            'attr_color' => $skuAttr['attr_color'],
                            'attr_pic' => $skuAttr['attr_pic'],
                            'attr_pic_thumb' => $skuAttr['attr_pic_thumb'],
                        ];
                        ProductAttributes::create($productSkuAttr);
                    }
                }
                //5. 保存商品规格
                $productSkus = [];
                if (!empty($vendorProduct['skus'])) {
                    // 创建vendor_product_sku_id到对象的映射
                    $vendorProductSkuMap = [];
                    foreach ($vendorProduct['skus'] as $vendorProductSku) {
                        $vendorProductSkuMap[$vendorProductSku['id']] = $vendorProductSku;
                    }

                    $productSkus = [];
                    foreach ($vendorProduct['skus'] as $index => $vendorProductSku) {
                        $skuPrice = $vendorProductSku['supply_price'];

                        if ($vendorSetPriceType !== null && $shop) {
                            if ($vendorSetPriceType == 2) {
                                $vendorSetPrice = (string)$vendorSetPriceAutoValue;
                                $skuPrice = bcadd($vendorProductSku['supply_price'], $vendorSetPrice, 2);
                            } elseif ($vendorSetPriceType == 1) {
                                $vendorSetPriceRate = (string)$vendorSetPriceAutoValue;
                                $addPrice = bcmul($vendorProductSku['supply_price'], $vendorSetPriceRate, 2);
                                $skuPrice = bcadd($vendorProductSku['supply_price'], $addPrice, 2);
                            } elseif ($vendorSetPriceType == 3) {
                                $skuPrice = $vendorProductSku['supply_price'];
                            }

                            // 确保价格不超过最高价
                            $maxPrice = $vendorProductSku['max_price'];
                            $skuPrice = bccomp($skuPrice, $maxPrice, 2) > 0 ? $maxPrice : $skuPrice;
                        }

                        $productSku = [
                            'product_id' => $productId,
                            'vendor_product_sku_id' => $vendorProductSku['id'],
                            'sku_value' => $vendorProductSku['sku_attr_val'],
                            'sku_data' => $vendorProductSku['sku_attr_json'],
                            'sku_stock' => $vendorProductSku['sku_stock'],
                            'sku_price' => $skuPrice,
                            'sku_sn' => $vendorProductSku['sku_sn'],
                            'sku_tsn' => $vendorProductSku['sku_tsn'],
                        ];

                        $productSkus[] = $productSku;
                    }

                    // 批量保存SKU
                    foreach ($productSkus as $productSku) {
                        ProductSku::create($productSku);
                    }
                }
                $vendorId = $vendorProduct['vendor_id'];
                // 查询是否已存在记录
                $exist = VendorShopBind::where('vendor_id', $vendorId)
                    ->where('shop_id', $shopId)
                    ->find();

                if (!$exist) {
                    $vendorShopBind = [
                        'vendor_id' => $vendorId,
                        'shop_id' => $shopId,
                        'add_time' => time(),
                    ];
                    VendorShopBind::create($vendorShopBind);
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new ApiException($e->getMessage());
        }
        return true;
    }

    public function getVendorMaxPrice($id)
    {
        // 获取VendorProductSku实例
        $vendorProductSku = VendorProductSku::find($id);

        // 检查是否存在
        if (!$vendorProductSku) {
            throw new ApiException('商品规格不存在');
        }

        // 返回最大价格
        return $this->getMaxPrice($vendorProductSku);
    }

    /**
     * 获取SKU的最大价格
     * @param VendorProductSku $vendorProductSku
     * @return mixed
     */
    protected function getMaxPrice($vendorProductSku)
    {
        // 假设模型中有这些字段，根据Java代码逻辑实现
        if (isset($vendorProductSku->supply_price_limit_type) && isset($vendorProductSku->supply_price_limit_val)) {
            if ($vendorProductSku->supply_price_limit_type == 1) {
                return bcadd((string)$vendorProductSku->supply_price ,(string)$vendorProductSku->supply_price_limit_val, 2);
            }

            if ($vendorProductSku->supply_price_limit_type == 2) {
                $limitPrice  = bcmul((string)$vendorProductSku->supply_price, (string)$vendorProductSku->supply_price_limit_val, 2);
                return bccomp($limitPrice, (string)$vendorProductSku->supply_price, 2);
            }
        }

        return $vendorProductSku->supply_price;
    }
}
