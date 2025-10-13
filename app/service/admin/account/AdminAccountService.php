<?php

namespace app\service\admin\account;

use app\model\authority\AdminUser;
use app\model\authority\AdminUserVendor;
use app\model\merchant\AdminUserShop;
use app\model\merchant\Merchant;
use app\model\user\User;
use app\service\admin\merchant\ShopService;
use app\service\admin\vendor\VendorService;
use app\service\common\BaseService;
use exceptions\ApiException;
use think\facade\Db;

class AdminAccountService extends BaseService
{

    protected AdminUserVendor $adminUserVendorModel;

    protected AdminUser $adminUserModel;

    protected AdminUserShop $adminUserShopModel;

    public function __construct()
    {
        $this->adminUserVendorModel = new AdminUserVendor();
        $this->adminUserModel = new AdminUser();
        $this->adminUserShopModel = new AdminUserShop();

    }

    /**
     * 根据店铺或供应商ID查询主账号信息
     * @param int $id
     * @param string $type
     * @return array
     * @throws ApiException
     */
    public function getMainAccountByShopOrVendorId(int $admin_id, int $id, string $type): array
    {
        $mainAdminUser = null;


        if ($admin_id > 0) {

            $mainAdminUser = $this->adminUserModel->where('admin_id', $admin_id)->find();
            return [
                'admin_id' => $mainAdminUser['admin_id'],
                'username' => $mainAdminUser['username'],
                'mobile' => $mainAdminUser['mobile'],
                'email' => $mainAdminUser['email'],
            ];
        }

        if ($type == 'shop') {
            $adminUserShop = $this->adminUserShopModel->where('shop_id', $id)->where('is_admin',1)->find();
            if (!is_null($adminUserShop)) {
                $mainAdminUser = $this->adminUserModel->where('admin_id', $adminUserShop['admin_id'])->find();
            }

        } elseif ($type == 'vendor') {
            $adminUserVendor = $this->adminUserVendorModel->where('vendor_id', $id)->where('is_admin',1)->find();
            if (!is_null($adminUserVendor)) {
                $mainAdminUser = $this->adminUserModel->where('admin_id', $adminUserVendor['admin_id'])->find();
            }
        }

        if (is_null($mainAdminUser)) {
            throw new ApiException('未找到对应的主账号信息');
        }

        return [
            'admin_id' => $mainAdminUser['admin_id'],
            'username' => $mainAdminUser['username'],
            'mobile' => $mainAdminUser['mobile'],
            'email' => $mainAdminUser['email'],
        ];
    }


    public function pageShopOrVendor(array $filter): array
    {

        if ($filter['type'] == 'vendor') {
            $vendorIdArr = $this->adminUserVendorModel->where('admin_id', $filter['admin_id'])->column('vendor_id');
            if (!empty($vendorIdArr)) {
                $filter['vendor_ids'] = $vendorIdArr;
            }
            $list = app(VendorService::class)->getFilterResult($filter, ['vendor_id as id', 'vendor_name as name', 'vendor_logo as logo', 'status']);
            if (empty($list)) {
                $total = 0;
            } else {
                $adminUsername = $this->adminUserModel->where('admin_id', $filter['admin_id'])->value('username');
                foreach ($list as &$item) {
                    $item['type'] = 'vendor';
                    $item['merchant_name'] = '';
                    $item['admin_username'] = $adminUsername;
                }
                $total = app(VendorService::class)->getFilterCount($filter);
            }
        } else {

            $shopIdArr = $this->adminUserShopModel->where('admin_id', $filter['admin_id'])->column('shop_id');
            if (!empty($shopIdArr)) {
                $filter['shop_ids'] = $shopIdArr;
            }
            $list = app(ShopService::class)->filterQuery($filter)->field(['shop_id as id', 'shop_title as name', 'shop_logo as logo', 'status', 'merchant_id'])->select()->toArray();
            if (empty($list)) {
                $total = 0;
            } else {

                $merchantIdArr = array_column($list, 'merchant_id');
                $merchantIdArr = array_unique($merchantIdArr);
                $merchantNameArr = Merchant::whereIn('merchant_id', $merchantIdArr)->field(['merchant_id','corporate_name','type'])->select();
                $merchantNameArr = $merchantNameArr->toArray();
                $merchantNameArr = array_column($merchantNameArr, 'corporate_name', 'merchant_id');
                $adminUsername = $this->adminUserModel->where('admin_id', $filter['admin_id'])->value('username');
                foreach ($list as &$item) {
                    $item['type'] = 'shop';
                    $item['merchant_name'] = $merchantNameArr[$item['merchant_id']];
                    $item['admin_username'] = $adminUsername;
                }
                $total = app(ShopService::class)->getFilterCount($filter);
            }

        }

        return [
            'records' => $list,
            'total' => $total,
        ];
    }


    public function bindMainAccount(array $data): bool
    {

        $admin = $this->getAdmin($data);
        if (!$admin) {
            return false;
        }
        // 提取公共逻辑
        if ($data['type'] == 'vendor') {
            $model = $this->adminUserVendorModel;
            $field = 'vendor_id';
        } else {
            $model = $this->adminUserShopModel;
            $field = 'shop_id';
        }

        $record = $model->where($field, $data['id'])->where('is_admin', 1)->find();
        if ($record) {
            $record->admin_id = $admin['admin_id'];
            return $record->save();
        }
        return false;
    }

    public function updateMainAccount(array $data): bool
    {

        $admin = $this->adminUserModel->find($data['admin_id']);
        if (!$admin) {
            throw new ApiException('用户不存在');
        }

        if (!empty($data['username'])) {
            // 判断用户名是否已存在，但除去自己
            $count = $this->adminUserModel->where('username', $data['username'])->where('admin_id', '<>', $admin['admin_id'])->count();
            if ($count > 0) {
                throw new ApiException('用户名已存在');
            }
            $admin->username = $data['username'];
        }

        if (!empty($data['mobile'])) {
            // 判断手机号是否已存在，但除去自己
            $count = $this->adminUserModel->where('mobile', $data['mobile'])->where('admin_id', '<>', $admin['admin_id'])->count();
            if ($count > 0) {
                throw new ApiException('手机号已经使用');
            }
            $admin->mobile = $data['mobile'];
        }

        if (!empty($data['email'])) {
            // 判断油箱是否已存在，但除去自己
            $count = $this->adminUserModel->where('email', $data['email'])->where('admin_id', '<>', $admin['admin_id'])->count();
            if ($count > 0) {
                throw new ApiException('邮箱已经使用');
            }
            $admin->email = $data['email'];
        }

        return $admin->save();

    }

    public function updateMainAccountPwd(array $data): bool
    {

        $admin = $this->adminUserModel->find($data['admin_id']);
        if (!$admin) {
            throw new ApiException('用户不存在');
        }

        $admin->password = password_hash($data['password'], PASSWORD_DEFAULT);
        return $admin->save();

    }


    /**
     * @param array $data
     * @return array|mixed
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAdmin(array $data)
    {
        if ($data['admin_data']['type'] == 1) {
            //会员类型
            $user = app(User::class)->find($data['admin_data']['user_id']);
            if (is_null($user)) {
                throw new ApiException('会员不存在');
            }

            if (empty($user['mobile'])) {
                throw new ApiException('会员手机号码不能为空');
            }
            //通过手机查询是否有对应管理员
            $admin = $this->adminUserModel->where('mobile', $user['mobile'])->find();
            if (is_null($admin)) {
                //创建管理员
                $admin_user_data = [
                    'username' => $user['username'],
                    'user_id' => $user['user_id'],
                    'mobile' => $user['mobile'],
                    'email' => $user['email'],
                    'password' => password_hash('admin123321', PASSWORD_DEFAULT),
                    'add_time' => time(),
                    'admin_type' => $data['type'],
                    'auth_list' => '[]',
                    'is_using' => 0,
                    'role_id' => 1,
                ];
                $randomNumber = rand(1, 34);
                $admin_user_data['avatar'] = "../assets/avatar/" . $randomNumber . ".jpeg";
                $adminId = $this->adminUserModel->insertGetId($admin_user_data);
                $admin = $this->adminUserModel->find($adminId);
            }
        } else {
            //管理员类型
            $admin = $this->adminUserModel->find($data['admin_data']['admin_id']);
            if (is_null($admin)) {
                throw new ApiException('管理员不存在');
            }
        }
        return $admin;
    }

}