<?php

namespace app\service\admin\product;

use app\model\product\PriceInquiry;
use app\service\common\BaseService;
use exceptions\ApiException;

class PriceInquiryService extends BaseService
{
    /**
     * 查询条件
     * @param array $filter
     * @return object|\think\db\BaseQuery
     */
    protected function filterQuery(array $filter): object
    {
        $query = PriceInquiry::query();

        if (isset($filter['mobile']) && !empty($filter['mobile'])) {
            $query->where('mobile', 'like', '%' . $filter['mobile'] . '%');
        }

        if (isset($filter['product_id']) && !empty($product_id)) {
            $query->where('product_id', $filter['product_id']);
        }

        if (isset($filter['status']) && $filter['status'] != -1) {
            $query->where('status', $filter['status']);
        }

        if (isset($filter['shop_id']) && !empty($shop_id)) {
            $query->where('shop_id', $filter['shop_id']);
        }

        return $query;
    }

    /**
     * 详情
     * @param int $id
     * @return PriceInquiry
     * @throws ApiException
     */
    public function getDetail(int $id):PriceInquiry
    {
        $price_inquiry = PriceInquiry::find($id);
        if (empty($price_inquiry)) {
            throw new ApiException("该询价记录不存在");
        }
        return $price_inquiry;
    }

    /**
     * 回复
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function reply(int $id,array $data):bool
    {
        $price_inquiry = $this->getDetail($id);
        $reply = [
            'remark' => $data['remark'],
            'status' => 1
        ];
        return $price_inquiry->save($reply);
    }

    /**
     * 删除
     * @param int $id
     * @return bool
     * @throws ApiException
     */
    public function del(int $id): bool
    {
        $price_inquiry = $this->getDetail($id);
        return $price_inquiry->delete();
    }
}