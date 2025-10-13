<?php

namespace app\adminapi\controller\product;

use app\adminapi\AdminBaseController;
use app\service\admin\product\BrandService;
use app\service\admin\product\PriceInquiryService;
use exceptions\ApiException;
use think\App;
use think\facade\Db;
use think\Response;

class PriceInquiry extends AdminBaseController
{
    protected PriceInquiryService $priceInquiryService;

    /**
     * 构造函数
     *
     * @param App $app
     * @param BrandService $brandService
     */
    public function __construct(App $app, PriceInquiryService $priceInquiryService)
    {
        parent::__construct($app);
        $this->priceInquiryService = $priceInquiryService;
    }

    /**
     * 列表
     * @return Response
     */
    public function list():Response
    {
        $filter = $this->request->only([
            'mobile' => '',
            'status/d' => -1,
            'product_id/d' => 0,
            'page/d' => 1,
            'size/d' => 15,
            'sort_field' => 'id',
            'sort_order' => 'desc',
        ], 'get');

        if ($this->shopId > 0) {
            $filter['shop_id'] = $this->shopId;
        }
        $filterResult = $this->priceInquiryService->getFilterList($filter,['product','shop_info']);
        $total = $this->priceInquiryService->getFilterCount($filter);

        return $this->success([
            'records' => $filterResult,
            'total' => $total,
        ]);
    }

    /**
     * 详情
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function detail():Response
    {
        $id =$this->request->all('id/d', 0);
        $item = $this->priceInquiryService->getDetail($id);
        return $this->success(
            $item
        );
    }

    /**
     * 回复
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function reply():Response
    {
        $id = $this->request->all('id/d', 0);
        $data = $this->request->only([
            'id/d' => $id,
            'remark' => ''
        ], 'post');
        $result = $this->priceInquiryService->reply($id,$data);
        return $result ? $this->success() : $this->error('回复失败');
    }

    /**
     * 删除
     * @return Response
     * @throws \exceptions\ApiException
     */
    public function del(): Response
    {
        $id =$this->request->all('id/d', 0);
        $this->priceInquiryService->del($id);
        return $this->success();
    }

    /**
     * 批量操作
     * @return Response
     * @throws ApiException
     */
    public function batch(): Response
    {
        if (empty($this->request->all('ids')) || !is_array($this->request->all('ids'))) {
            return $this->error(/** LANG */'未选择项目');
        }

        if ($this->request->all('type') == 'del') {
            try {
                //批量操作一定要事务
                Db::startTrans();
                foreach ($this->request->all('ids') as $key => $id) {
                    $id = intval($id);
                    $this->priceInquiryService->del($id);
                }
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                throw new ApiException($exception->getMessage());
            }

            return $this->success(/** LANG */);
        } else {
            return $this->error(/** LANG */'#type 错误');
        }
    }

}