<?php
//**---------------------------------------------------------------------+
//** 通用接口控制器文件 -- 收货地址
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\api\controller\user;

use app\api\IndexBaseController;
use app\service\admin\user\UserAddressService;
use app\validate\user\UserAddressValidate;
use think\App;
use think\exception\ValidateException;
use think\Response;
use utils\Util;

/**
 * 收货地址控制器
 */
class Address extends IndexBaseController
{
    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 收货地址列表
     * @return Response
     */
    public function list(): Response
    {
        $filter = $this->request->only([
            'page' => 1,
            'size' => 15,
        ], 'get');
        $result = app(UserAddressService::class)->userAddressList($filter);
        return $this->success([
            'records' => $result["list"],
            'total' => $result["count"],
        ]);
    }

    /**
     * 收货地址详情
     * @return Response
     */
    public function detail(): Response
    {
        $id = $this->request->all('id/d', 0);
        $item = app(UserAddressService::class)->getAddressDetail($id, request()->userId);
        return $this->success($item);
    }

    /**
     * 请求数据
     * @return array
     */
    public function requestData(): array
    {
        $data = request()->only([
            'consignee' => '',
            'region_ids/a' => [],
            'region_names/a' => [],
            'address' => '',
            'mobile' => '',
            'telephone' => '',
            'postcode' => '',
            'email' => '',
            'address_tag' => '',
            'is_default' => 0,
        ]);
        return $data;
    }

    /**
     * 收货地址添加
     * @return Response
     */
    public function create(): Response
    {
        $data = $this->requestData();

        try {
            validate(UserAddressValidate::class)
                ->scene('create')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error(Util::lang($e->getError()));
        }

        $result = app(UserAddressService::class)->createUserAddress($data,request()->userId);
        if ($result) {
            return $this->success([
                'message' => /** LANG */ Util::lang('收货地址添加成功'),
                'address_id' => $result,
            ]);
        } else {
            return $this->error(/** LANG */Util::lang('收货地址添加失败'));
        }
    }


    /**
     * 收货地址更新
     * @return Response
     */
    public function update(): Response
    {
        $id = $this->request->all('id/d', 0);
        $data = $this->requestData();

        try {
            validate(UserAddressValidate::class)
                ->scene('update')
                ->check($data);
        } catch (ValidateException $e) {
            return $this->error(Util::lang($e->getError()));
        }

        $result = app(UserAddressService::class)->updateUserAddress($id, request()->userId, $data);
        if ($result) {
            return $this->success([
                'message' => /** LANG */Util::lang("收货地址更新成功"),
                'address_id' => $result,
            ]);
        } else {
            return $this->error(/** LANG */Util::lang('收货地址更新失败'));
        }
    }

    /**
     * 删除收货地址
     * @return Response
     */
    public function del(): Response
    {
        $id = $this->request->all('id/d', 0);
        $result = app(UserAddressService::class)->deleteUserAddress($id);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang('删除失败'));
    }

    /**
     * 设为选中
     * @return Response
     */
    public function setSelected(): Response
    {
        $id = $this->request->all('id/d', 0);
        $result = app(UserAddressService::class)->addressSetSelected(request()->userId, $id);
        return $result ? $this->success() : $this->error(/** LANG */ Util::lang('设置失败'));
    }

}
