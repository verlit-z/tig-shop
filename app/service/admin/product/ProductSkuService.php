<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 商品规格
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\product;

use app\model\product\Product;
use app\model\product\ProductSku;
use app\service\common\BaseService;
use app\validate\product\ProductSkuValidate;
use exceptions\ApiException;
use log\AdminLog;
use utils\Util;

/**
 * 商品规格服务类
 */
class ProductSkuService extends BaseService
{
    protected ProductSku $productSkuModel;
    protected ProductSkuValidate $productSkuValidate;

    public function __construct(ProductSku $productSkuModel)
    {
        $this->productSkuModel = $productSkuModel;
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter);
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
    protected function filterQuery(array $filter): object
    {
        $query = $this->productSkuModel->query();
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('sku_value', 'like', '%' . $filter['keyword'] . '%');
        }

        if (isset($filter['is_show']) && $filter['is_show'] > -1) {
            $query->where('is_show', $filter['is_show']);
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
        $result = $this->productSkuModel->where('sku_id', $id)->find();
        return !empty($result) ? $result->toArray() : [];
    }

    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return $this->productSkuModel::where('sku_id', $id)->value('sku_value');
    }

    /**
     * 执行商品规格添加或更新
     *
     * @param int $id
     * @param array $data
     * @param bool $isAdd
     * @return int|bool
     * @throws ApiException
     */
    public function updateProductSku(int $id, array $data, bool $isAdd = false): int|bool
    {
        validate(ProductSkuValidate::class)->only(array_keys($data))->check($data);
        if ($isAdd) {
            $result = $this->productSkuModel->save($data);
            AdminLog::add('新增商品规格:' . $data['sku_value']);
            return $this->productSkuModel->getKey();
        } else {
            if (!$id) {
                throw new ApiException('#id错误');
            }
            $result = $this->productSkuModel->where('sku_id', $id)->save($data);
            AdminLog::add('更新商品规格:' . $this->getName($id));

            return $result !== false;
        }
    }

    /**
     * 删除商品规格
     * @param int $id
     * @return bool
     * @throws ApiException
     */
    public function deleteProductSku(int $id): bool
    {
        if (!$id) {
            throw new ApiException('#id错误');
        }
        $result = $this->productSkuModel::destroy($id);

        if ($result) {
            AdminLog::add('删除商品规格:' . $this->getName($id));
        }

        return $result !== false;
    }

    /**
     * 获取规格
     * @param int $product_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSkuList(int $product_id): array
    {
        $result = $this->productSkuModel->where('product_id', $product_id)->select()->toArray();
        if (php_sapi_name() != 'cli' && !empty(request()->header('X-Locale-Code'))) {
            foreach ($result as $key => $value) {
                if (!empty($value['sku_data'])) {
                    $newSkuData = [];
                    foreach ($value['sku_data'] as $k => $eachSku) {
                        foreach ($eachSku as $skuName => $skuValue) {
                            $skuName = Util::lang($skuName, '', [], 8);
                            $skuValue = Util::lang($skuValue, '', [], 8);
                            $newSkuData[$k][$skuName] = $skuValue;
                        }
                    }
                    $result[$key]['sku_data'] = $newSkuData;
                }
                if (!empty($value['sku_value'])) {
                    $skuValue = explode(':', $value['sku_value']);
                    foreach ($skuValue as $k => $eachSkuValue) {
                        $skuValue[$k] = Util::lang($eachSkuValue, '', [], 8);
                    }
                    $result[$key]['sku_value'] = implode(':', $skuValue);
                }
            }
        }
        return $result;
    }

    /**
     * 处理规格数据
     * @param int $product_id
     * @param array $product_list
     * @return void
     * @throws \Exception
     */
    public function dealProductSpec(int $product_id, array $product_list = []): void
    {
        $data = [];
        $product_sn = app(ProductService::class)->getProductSn($product_id);
        foreach ($product_list as $key => $value) {
            $row = [];
            $row['sku_stock'] = intval($value['sku_stock']);
            $row['sku_sn'] = isset($value['sku_sn']) && !empty($value['sku_sn']) ? $value["sku_sn"] : $product_sn . "-" . $key + 1;
            $row['sku_tsn'] = isset($value['sku_tsn']) ? $value["sku_tsn"] : "";
            $row['sku_price'] = isset($value['sku_price']) ? floatval($value['sku_price']) : "0.00";
            if ($row['sku_price'] <= 0) {
                throw new ApiException('商品-' . $row['sku_sn'] . '价格不能小于0');
            }
            if (isset($value['attrs'])) {
                $arr = [];
                foreach ($value['attrs'] as $k => $val) {
                    $row['sku_data'][$k]['name'] = $val['attr_name'];
                    $row['sku_data'][$k]['value'] = $val['attr_value'];
                    $arr[] = implode(':', $val);
                }
                $row['sku_value'] = implode('|', $arr);
                $exist_sku_id = $this->productSkuModel->where('product_id', $product_id)->where('sku_value', $row['sku_value'])->value('sku_id');
                if ($exist_sku_id) {
                    $row['sku_id'] = $exist_sku_id;
                } else {
                    $row['product_id'] = $product_id;
                }
            } else {
                if (isset($value['sku_id'])) {
                    $row['sku_id'] = $value['sku_id'];
                }

                $row['product_id'] = $product_id;
            }

            $data[] = $row;
        }
        $result = $this->productSkuModel->saveAll($data);
        $sku_ids = $result->column('sku_id');
        // 删除不在数据里的sku
        $this->productSkuModel->where('product_id', $product_id)->whereNotIn('sku_id', $sku_ids)->delete();
    }

    /**
     * 复制商品属性
     * @param int $product_id
     * @param array $product_list
     * @return void
     * @throws ApiException
     */
    public function copyProductSpec(int $product_id, array $product_list = []): void
    {
        $data = [];
        $product_sn = app(ProductService::class)->getProductSn($product_id);
        foreach ($product_list as $key => $value) {
            $row = [];
            $row['sku_stock'] = intval($value['sku_stock']);
            $row['sku_sn'] = $product_sn . "-" . $key + 1;
            $row['sku_tsn'] = isset($value['sku_tsn']) ? $value["sku_tsn"] : "";
            $row['sku_price'] = isset($value['sku_price']) ? floatval($value['sku_price']) : "0.00";
            if ($row['sku_price'] <= 0) {
                throw new ApiException('商品-' . $row['sku_sn'] . '价格不能小于0');
            }
            $row['sku_value'] = $value['sku_value'];
            $row['sku_data'] = $value['sku_data'];
            $row['product_id'] = $product_id;
            $data[] = $row;
        }
        $this->productSkuModel->saveAll($data);
    }

    /**
     * 减库存
     * @param int $sku_id
     * @param int $quantity
     * @return bool
     */
    public function decStock(int $sku_id, int $quantity,int $shop_id=0): bool
    {
        //减商品库存
        $product_id = ProductSku::where('sku_id', $sku_id)->value('product_id');
        if($product_id) {
            $find = Product::where('product_id', $product_id)->find();
            if($find->product_stock > 0 ) {
                $find->product_stock = max(($find->product_stock - $quantity), 0);
                $find->save();
            }

        }
        // 3. 增加库存扣减日志
        $sku_stock = ProductSku::where('sku_id', $sku_id)->value('sku_stock');
        $diff = $sku_stock - $quantity;
        $productInventoryLog = [
            'product_id' => $product_id,
            'spec_id' => $sku_id,
            'number' => abs($diff),
            'add_time' => time(),
            'old_number' => $sku_stock,
            'type' => 2,
            'change_number' => $quantity,
            'desc' => "下单扣减库存",
            'shop_id' => $shop_id,
        ];
        app(ProductInventoryLogService::class)->updateProductInventoryLog(0, $productInventoryLog, true);

        return ProductSku::where('sku_id', $sku_id)->dec('sku_stock', $quantity)->update();
    }

    /**
     * 获取库存
     * @param int $sku_id
     * @return int
     */
    public function getStock(int $sku_id): int
    {
        return ProductSku::findOrEmpty($sku_id)->sku_stock ?? 0;
    }

    /**
     * 增加库存
     * @param int $sku_id
     * @param int $quantity
     * @return bool
     */
    public function incStock(int $sku_id, int $quantity,int $shop_id=0): bool
    {
        //增加商品库存
        $product_id = ProductSku::where('sku_id', $sku_id)->value('product_id');
        if($product_id) {
            $find = Product::where('product_id', $product_id)->find();
            $find->product_stock = $find->product_stock + $quantity;
            $find->save();
        }

        // 3. 增加库存扣减日志
        $sku_stock = ProductSku::where('sku_id', $sku_id)->value('sku_stock');
        $diff = $sku_stock + $quantity;
        $productInventoryLog = [
            'product_id' => $product_id,
            'spec_id' => $sku_id,
            'number' => $diff,
            'add_time' => time(),
            'old_number' => $sku_stock,
            'type' => 1,
            'change_number' => $quantity,
            'desc' => "取消订单返还",
            'shop_id' => $shop_id,
        ];
        app(ProductInventoryLogService::class)->updateProductInventoryLog(0, $productInventoryLog, true);
        return ProductSku::where('sku_id', $sku_id)->inc('sku_stock', $quantity)->update();
    }

    /**
     * 检测商品是否存在属性
     * @param int $product_id
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function checkProductHasSku(int $product_id): bool
    {
        $count = $this->productSkuModel->where('product_id', $product_id)->count();
        return $count > 0;
    }

    /**
     * 根据sku编码获取商品信息
     * @param string $goods_sn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductSkuBySn(string $goods_sn): array
    {
        $sku_info = $this->productSkuModel->where('sku_sn', $goods_sn)->field('sku_id,product_id')->find();
        if (empty($sku_info)) return [];

        return $sku_info->toArray();
    }
}
