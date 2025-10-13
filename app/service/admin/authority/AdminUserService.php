<?php

namespace app\service\admin\authority;

use app\model\authority\AdminRole;
use app\model\authority\AdminUser;
use app\model\authority\AdminUserVendor;
use app\model\merchant\AdminUserShop;
use app\model\merchant\MerchantUser;
use app\service\admin\common\sms\SmsService;
use app\service\common\BaseService;
use exceptions\ApiException;
use log\AdminLog;
use think\facade\Cache;

/**
 * 管理员服务类
 */
class AdminUserService extends BaseService
{
    protected AdminUser $adminUserModel;

    public function __construct(AdminUser $adminUserModel)
    {
        $this->adminUserModel = $adminUserModel;
    }

    /**
     * 获取筛选结果
     *
     * @param array $filter
     * @return array
     */
    public function getFilterResult(array $filter): array
    {
        $query = $this->filterQuery($filter)->with(["role",'user_shop']);
        $result = $query->field('c.*, COUNT(s.admin_id) AS has_children')
            ->leftJoin('admin_user s', 'c.admin_id = s.parent_id')
            ->group('c.admin_id')->page($filter['page'], $filter['size'])->select();

        foreach ($result as $item) {
            if (!empty($item->user_shop)) {
                $is_admin = array_column($item->user_shop->toArray(),'is_admin');
                $item->is_admin = 0;
                if (in_array(1,$is_admin)) {
                    $item->is_admin = 1;
                }
            }
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
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    protected function filterQuery(array $filter): object
    {
        $query = $this->adminUserModel->query()->alias('c');
        // 处理筛选条件

        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $query->where('c.username|c.mobile', 'like', '%' . $filter['keyword'] . '%');
        }
        if (!empty($filter['admin_type'])) {
            $query->where('c.admin_type', $filter['admin_type']);
        }

        if (isset($filter['admin_types']) && !empty($filter['admin_types'])) {
            $query->whereIn('c.admin_type', $filter['admin_types']);
        }
        // 管理员类型
        if (isset($filter['admin_user_type']) && !empty($filter['admin_user_type'])) {
            $admin_user_type = $filter['admin_user_type'] == 'shop' ? 'shop' : "admin,suppliers";;
            $admin_user_type = explode(",", $admin_user_type);
            $query->whereIn('c.admin_type', $admin_user_type);
        }

        // 供应商查询
        if (isset($filter['suppliers_id']) && !empty($filter['suppliers_id'])) {
            $query->where('c.suppliers_id', $filter['suppliers_id']);
        }

        if (isset($filter['parent_id']) && !empty($filter['parent_id'])) {
            $query->where('c.parent_id', $filter['parent_id']);
        }

        if (isset($filter['sort_field'], $filter['sort_order']) && !empty($filter['sort_field']) && !empty($filter['sort_order'])) {
            $query->order($filter['sort_field'], $filter['sort_order']);
        }

        if (isset($filter['merchant_id'])) {
            $query->where('c.merchant_id', $filter['merchant_id']);
        }
        //店铺后台筛选该店铺员工和该商户管理员
        if (isset($filter['shop_id']) && !empty($filter['shop_id'])) {
            $query->where(function ($query) use ($filter) {
                $query->where('c.shop_id', $filter['shop_id'])->whereOr('c.shop_id', 0);
            });
        }

        if (isset($filter['is_using'])) {
            $query->where('c.is_using', $filter['is_using']);
        }
        return $query;
    }

    /**
     * 获取详情
     *
     * @param int $id
     * @return AdminUser
     * @throws ApiException
     */
    public function getDetail(int $id): AdminUser
    {
        $result = $this->adminUserModel->with(['user','user_shop' => ['shop','user']])->withoutField('password')->where('admin_id', $id)->find();
        if (!$result) {
            throw new ApiException(/** LANG */'管理员不存在');
        }

        if ($result['role_id'] > 0) {
            // 获取权限组权限
            $auth_list = AdminRole::find($result['role_id'])->authority_list ?? null;
            if (!empty($auth_list)) {
                $result['auth_list'] = $auth_list;
            }
        }
        return $result;
    }


    /**
     * 获取管理员信息
     * @param int $id
     * @param array $column
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserColumn(int $id, array $column = ['*']): array
    {

        $info = $this->adminUserModel->field($column)->find($id);
        if (is_null($info)) {

            return [];
        }
        return $info->toArray();
    }


    /**
     * 获取名称
     *
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string
    {
        return $this->adminUserModel::where('admin_id', $id)->value('username');
    }

    /**
     * 获取添加/更新的通用数据
     * @param array $data
     * @return array
     * @throws ApiException
     */
    public function getCommunalData(array $data):array
    {
        $arr = [
            "username" => $data["username"],
            "mobile" => $data["mobile"],
            "email" => $data["email"],
            "role_id" => $data["role_id"],
            "merchant_id" => $data["merchant_id"] ?? 0,
            'shop_id' => $data['shop_id'] ?? 0,
            'avatar' => $data['avatar'],
            'user_id' => $data['user_id'] ?? 0,
            'admin_type' => !empty($data['admin_type']) ? $data['admin_type'] : 'admin',
            'is_using' => $data['is_using'] ?? 0,
            'suppliers_id' => $data['suppliers_id'] ?? 0
        ];

        if (empty($arr['avatar'])) {
            $rand = rand(1, 34);
            $arr['avatar'] = '../assets/avatar/' . $rand . '.jpeg';
        }

		if (isset($data['suppliers_id']) && !empty($data['suppliers_id'])) {
			$arr['admin_type'] = 'suppliers';
		}

        if (!empty($data["password"])) {
            $arr["password"] = password_hash($data["password"], PASSWORD_DEFAULT);
        }

        if (isset($data['password']) && isset($data['pwd_confirm']) && $data["password"] != $data["pwd_confirm"]) {
            throw new ApiException(/** LANG */'密码不一致');
        }

        if ($data["role_id"] > 0) {
            $arr["auth_list"] = AdminRole::find($data["role_id"])->authority_list;
        } else {
            $arr["auth_list"] = $data["auth_list"];
        }
        return $arr;
    }

    /**
     * 执行添加操作
     * @param array $data
     * @return int
     * @throws ApiException
     */
    public function createAdminUser(array $data): array|int
    {
        $arr = $this->getCommunalData($data);
        //添加初始密码
        $arr['initial_password'] = $data['initial_password']?? '';

        $result = $this->adminUserModel->create($arr);
        if (request()->adminUid) {
            AdminLog::add('新增管理员:' . $data['username']);
        }
        $admin_id = $result->admin_id;
        $this->updateMerchantUser($data, $admin_id);
        return $admin_id;
    }


    /**
     * 执行管理员更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateAdminUser(array $data, int $id):bool
    {
        $arr = $this->getCommunalData($data);
		$admin_user = AdminUser::findOrEmpty($id);
        if (!$id || empty($admin_user)) {
            throw new ApiException(/** LANG */'#id错误');
        }
        // 判断电话号码中是否存在*号
        if (strpos($arr["mobile"], '*') !== false) {
            // 存在
            $arr["mobile"] = $admin_user->mobile;
        }

		// 当前登录账号信息
		$current_admin = AdminUser::findOrEmpty($data['admin_uid']);
		if (!in_array('all',$current_admin->auth_list)) {
			// 非超管
			if ($data['admin_uid'] != $id) {
				throw new ApiException(/** LANG */'非超管,不能操作其他管理员');
			}
		}

        $result = $admin_user->save($arr);
        $this->updateMerchantUser($data, $id);
        return $result !== false;
    }

    /**
     * 更新商户用户
     * @param array $data
     * @param int $id
     * @return bool
     * @throws \think\db\exception\DbException
     */
    public function updateMerchantUser(array $data, int $id): bool
    {
        if (empty($data['merchant_id'])) {
            return true;
        }
        if (MerchantUser::where('merchant_id', $data['merchant_id'])->where('admin_user_id', $id)->count()) {
            MerchantUser::where('merchant_id', $data['merchant_id'])->where('admin_user_id', $id)->update([
                'user_id' => $data['user_id'] ?? 0,
                'is_admin' => $data['is_admin'] ?? 0,
            ]);
        } else {
            MerchantUser::create([
                'user_id' => $data['user_id'] ?? 0,
                'merchant_id' => $data['merchant_id'],
                'admin_user_id' => $id,
                'is_admin' => $data['is_admin'] ?? 0,
            ]);
        }
        return true;
    }

    /**
     * 删除管理员
     *
     * @param int $id
     * @return bool
     */
    public function deleteAdminUser(int $id): bool
    {
        if ($id == 1) {
            throw new ApiException(/** LANG */'超级管理员不能删除');
        }

        $get_name = $this->getName($id);
        $result = $this->adminUserModel::destroy($id);

        if ($result) {
            AdminLog::add('删除管理员:' . $get_name);
        }
        return $result !== false;
    }
    /**
     * 根据账号密码获取会员信息
     *
     * @param string $username
     * @param string $password
     * @return AdminUser
     */
    public function getAdminUserByPassword(string $username, string $password): AdminUser
    {
        if (!$username || !$password) {
            throw new ApiException(/** LANG */'用户名或密码不能为空');
        }
        $item = $this->adminUserModel->where('username|mobile', $username)->find();
        if (!$item || !$item['password'] || !password_verify($password, $item['password'])) {
            throw new ApiException(/** LANG */'管理员账号或密码错误，请重试');
        }
        return $this->getDetail($item['admin_id']);
    }

    /**
     * 根据手机短信获取会员信息
     *
     * @param string $mobile
     * @param string $mobile_code
     * @return AdminUser|null
     */
    public function getAdminByMobile(string $mobile): AdminUser|null
    {
		if (empty($mobile)) {
			return null;
		}
        $item = $this->adminUserModel->where('mobile', $mobile)->find();
        return !empty($item) ? $item : null ;
    }

    /**
     * 根据手机短信获取会员信息
     *
     * @param string $mobile
     * @param string $mobile_code
     * @return AdminUser
     */
    public function getAdminUserByMobile(string $mobile, string $mobile_code): AdminUser
    {
        if (empty($mobile)) {
            throw new ApiException(/** LANG */'手机号不能为空');
        }
        if (empty($mobile_code)) {
            throw new ApiException(/** LANG */'短信验证码不能为空');
        }
        if (app(SmsService::class)->checkCode($mobile, $mobile_code) == false) {
            throw new ApiException(/** LANG */'短信验证码错误或已过期，请重试');
        }
        $item = $this->adminUserModel->where('mobile', $mobile)->find();
        if (!$item) {
            throw new ApiException(/** LANG */'不存在此管理员账号，请重试');
        }
        return $this->getDetail($item['admin_id']);
    }
    /**
     * 会员登录操作
     *
     * @param int $admin_id
     * @param bool $token_login
     * @return array
     */
    public function setLogin(int $admin_id, bool $form_login = true): bool
    {
        if (empty($admin_id)) {
            throw new ApiException(/** LANG */'#adminId错误');
        }
        $user = $this->getDetail($admin_id);
        request()->adminUid = $user['admin_id'];
        request()->merchantId = $user['merchant_id'];
        request()->adminType = $user['admin_type'];
        request()->suppliersId = $user['suppliers_id'];
        request()->authList = $user['auth_list'] ?? [];
        request()->shopId = request()->header('X-Shop-Id', 0);
        request()->vendorId = request()->header('X-Vendor-Id', 0);
        if ($form_login) {
            Cache::delete('admin_user::auth::' . $admin_id);
            AdminLog::add('管理员登录:' . $user['username']);
        } else {
            $userCacheInfo = Cache::get('admin_user::auth::' . $admin_id);
            if (is_null($userCacheInfo)) {
                request()->shopId = request()->header('X-Shop-Id', 0);
            } else {

                $userCacheInfo = json_decode($userCacheInfo, true);
                switch ($userCacheInfo['admin_type']) {
                    case 'admin':
                        request()->shopId = request()->header('X-Shop-Id', 0);
                        request()->adminType = 'admin';
                        break;
                    case 'shop':
                        request()->shopIds = AdminUserShop::where('admin_id', $user['admin_id'])->column('shop_id');
                        request()->shopId = $userCacheInfo['id'];
                        request()->adminType = 'shop';
                        request()->authList =  AdminUserShop::where('admin_id', $user['admin_id'])->where('shop_id', $userCacheInfo['id'])->value('auth_list');
                        break;
                    case 'vendor':
                        request()->vendorId = $userCacheInfo['id'];
                        request()->adminType = 'vendor';
                        request()->authList =  AdminUserVendor::where('admin_id', $user['admin_id'])->where('vendor_id', $userCacheInfo['id'])->value('auth_list');
                        break;
                }
            }
        }
        return true;
    }

    /**
     * 更新单个字段
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateAdminUserField(int $id, array $data):bool
    {
        if (!$id) {
            throw new ApiException(/** LANG */'#id错误');
        }
        $result = AdminUser::where('admin_id', $id)->save($data);
        AdminLog::add('更新管理员:' . $this->getName($id));
        return $result !== false;
    }

    /**
     * 获取角色列表
     * @return array
     */
    public function getRoleList(int $shopId = 0,string $admin_type = "admin")
    {
        $list = AdminRole::where("role_id", "<>", 2)->where(['shop_id' => $shopId,'admin_type' => $admin_type])->field("role_id,role_name")->select();
        $list = empty($list) ? [] : $list->toArray();
        return $list;
    }

    /**
     * 个人中心管理员账号修改
     * @param array $data
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function modifyManageAccounts(array $data):bool
    {
        $admin_user_info = AdminUser::find($data["admin_uid"]);
        $admin_user_info = !empty($admin_user_info) ? $admin_user_info->toArray() : [];
        // 管理员账号修改
        $arr = [];
        switch ($data["modify_type"]) {
            case 1:
                // 修改个人信息
                $arr = [
                    "avatar" => $data["avatar"],
                    "email" => $data["email"],
                ];
                break;
            case 2:
                // 修改密码
                if (!empty($data["password"])) {
                    $arr["password"] = password_hash($data["password"], PASSWORD_DEFAULT);
                }
                $data['mobile'] = $admin_user_info['mobile'];
                if (app(SmsService::class)->checkCode($data["mobile"], $data["code"]) == false) {
                    throw new ApiException(/** LANG */ '短信验证码错误或已过期，请重试');
                }

                if ($data["password"] != $data["pwd_confirm"]) {
                    throw new ApiException(/** LANG */'密码不一致');
                }
                $arr = [
                    "password" => $arr["password"],
                ];
                Cache::delete('password_too_simple:' . $data["admin_uid"]);
                break;
            case 3:
                // 判断手机号是否被其他人使用
                $phoneCount=AdminUser::where("admin_id","<>",$data["admin_uid"])->where("mobile",$data["mobile"])->count();
                if ($phoneCount>0) {
                    throw new ApiException(/** LANG */'手机号已经被使用');
                }
                // 修改手机号
                if (!empty($data["mobile"])) {
                    $arr["mobile"] = $data["mobile"];
                }

                if (empty($data["code"])) {
                    throw new ApiException(/** LANG */'请输入验证码');
                }
                if (app(SmsService::class)->checkCode($data["mobile"], $data["code"]) == false) {
                    throw new ApiException(/** LANG */'短信验证码错误或已过期，请重试');
                }
                break;
        }
        $result = AdminUser::where("admin_id", $data["admin_uid"])->save($arr);
        return $result !== false;
    }


    /**
     * 根据ids获取用户列表
     * @param array $ids
     * @param array $column
     * @return array
     */
    public function getAdminListByIds(array $ids, array $column=['*']): array
    {

        return  AdminUser::whereIn('admin_id', $ids)->field($column)->select()->toArray();
    }
}
