<?php
//**---------------------------------------------------------------------+
//** 服务层文件 -- 会员等级
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\service\admin\user;

use app\model\finance\RefundApply;
use app\model\order\AftersalesItem;
use app\model\order\Order;
use app\model\order\OrderItem;
use app\model\user\RankGrowthLog;
use app\model\user\User;
use app\model\user\UserRank;
use app\model\user\UserRankConfig;
use app\model\user\UserRankLog;
use app\service\admin\order\OrderService;
use app\service\common\BaseService;
use exceptions\ApiException;
use utils\Time;

/**
 * 会员等级服务类
 */
class UserRankService extends BaseService
{
    protected UserRank $userRankModel;

    public function __construct(UserRank $userRankModel)
    {
        $this->userRankModel = $userRankModel;
    }

    /**
     * 筛选查询
     *
     * @param array $filter
     * @return object
     */
    protected function filterQuery(array $filter): object
    {
        $query = $this->userRankModel->query();
        // 处理筛选条件

        if (isset($filter['rank_name']) && !empty($filter['rank_name'])) {
            $query->where('rank_name', 'like', '%' . $filter['rank_name'] . '%');
        }

        if (isset($filter['rank_type']) && !empty($filter['rank_type'])) {
            $query->where('rank_type', $filter['rank_type']);
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
    public function getDetail(int $rank_type): array
    {
        $user_rank = UserRank::select()->toArray();
        $grow_config = "";
        $rank_config = [];
        $type = $rank_type;
        if(config('app.IS_PRO') > 0){
            if(empty($user_rank)) {
                $res = $this->defaultRankData();
                $type = $res['rank_type'];
                $user_rank = $res['user_rank_list'];
                $grow_config = $res['grow_up_setting'];
                $rank_config = $res['rank_config'];
            } else{
                if ($rank_type == 1) {
                    $grow_config = $this->getGrowConfig();
                }
                $rank_config = $this->getRankConfigArray();
            }
        } else {
            if(empty($user_rank)){
                $res = $this->defaultRankData();
                $user_rank = $res['user_rank_list_not_pro'];
            }
        }
        return [
            'rank_type' => $type,
            'user_rank_list' => $user_rank,
            'user_rank_config' => $rank_config,
            'grow_up_setting' => $grow_config
        ];
    }

    /**
     * 默认会员等级数据
     * @return array
     */
    public function defaultRankData()
    {
        return [
            'rank_type' => 1,
            'rank_config' => [
                'type' => 1,
                'rank_type' => 1,
                'rank_after_month' => 12,
                'use_month' => 12,
                'code' => 'rank_config',
                'data' => [
                    'type'=> 1,
                    'rank_after_month' => 12,
                    'use_month' => 12,
                ]
            ],
            'grow_up_setting' => [
                'buy_order' => 1,
                'buy_order_number' => 1,
                'buy_order_growth' => 1,
                'evpi' => 1,
                'evpi_growth' => 1,
                'bind_phone' => 1,
                'bind_phone_growth' => 1
            ],
            'user_rank_list' => [
                [
                    'rank_level' => 1,
                    'rank_name' => '黄金会员',
                    'rank_logo' => 'https://oss.tigshop.com/img/gallery/202501/1737524324ro1DJxNm3aQowZKnPU.png',
                    'rank_card_type' => 1,
                    'rank_ico' => 'card1',
                    'rank_bg' => '',
                    'min_growth_points' => 0,
                    'discount' => 0,
                    'rank_point' => 0,
                    'free_shipping' => 0,
                    'rights' => []
                ]
            ],
            'user_rank_list_not_pro' => [
                [
                    'rank_level' => 1,
                    'rank_name' => '黄金会员',
                    'rank_logo' => 'https://oss.tigshop.com/img/gallery/202501/1735803176Yh9mCaE2r9ebXK3bGm.png',
                ]
            ]
        ];
    }

    public function getUserRankList()
    {
        $result = $this->userRankModel->field('rank_id,rank_name,rank_type,discount,free_shipping,rank_point')->order('min_growth_points')->select();
        return $result->toArray();
    }

    /**
     * 更新会员等级
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function updateUserRank(array $data):bool
    {
        if(config('app.IS_PRO')){
            if (empty($data['rank_type'])) {
                throw new ApiException('请选择会员等级类型');
            }
            if (count($data['data']) == 0) {
                throw new ApiException('等级配置至少保存一个');
            }

            if ($data['rank_type'] == 1 && empty($data['grow_up_setting'])) {
                throw new ApiException('请填写成长值设置');
            }
        }

        $user_rank = [];
        foreach ($data['data'] as $k => $item) {
            $tmp =  [
                "rank_name" => $item['rank_name'],
                "min_growth_points" =>config('app.IS_PRO') ? $item['min_growth_points'] : 0,
                "discount" => config('app.IS_PRO') ? $item['discount'] : 0.0,
                "rank_type" => config('app.IS_PRO')? $data['rank_type'] : 0,
                "rank_ico" => config('app.IS_PRO')?$item['rank_ico'] : '',
                "rank_bg" => config('app.IS_PRO')? $item['rank_bg'] : '',
                "rank_point" =>config('app.IS_PRO') ? $item['rank_point'] : 0,
                "free_shipping" => config('app.IS_PRO') ? $item['free_shipping'] : 0,
                'rank_card_type' => config('app.IS_PRO') ? $item['rank_card_type'] : 1,
                'rank_level' => $item['rank_level'],
                "rights" => config('app.IS_PRO') ? $item['rights'] : [],
                'rank_logo' => $item['rank_logo'],
            ];
            if(isset($item['rank_id']) && !empty($item['rank_id'])) {
                $tmp['rank_id'] = $item['rank_id'];
            }
            $user_rank[$k] = $tmp;

        }

        try {
            // 删除旧数据
            (new UserRank)->saveAll($user_rank);
            if(config('app.IS_PRO')){
                // 更新配置
                $rank_config = $this->getRankConfig();
                if (!empty($rank_config)) {
                    $rank_config->save(["rank_type" => $data['rank_type'], 'data' => $data['user_rank_config']]);
                } else {
                    UserRankConfig::create([
                        "code" => "rank_config",
                        "rank_type" => $data['rank_type'],
                        'data' => $data['user_rank_config']
                    ]);
                }
                // 更新成长值配置
                if ($data['rank_type'] == 1) {
                    $grow_config = $this->getRankConfig("grow_config");
                    if (!empty($grow_config)) {
                        $grow_config->save(["data" => $data['grow_up_setting'],'rank_type' => 1]);
                    } else {
                        UserRankConfig::create(["code" => "grow_config","data" => $data['grow_up_setting'],'rank_type' => 1]);
                    }
                }
            }
            return  true;
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage());
        }
    }

    /**
     * 兼容新修改的数据
     * @param string $code
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRankConfigArray(string $code = 'rank_config'): array
    {
        $config = UserRankConfig::where('code',$code)->find();
        if(empty($config)){
            $res = $this->defaultRankData();
            return $res['rank_config'];
        } else {
            if(empty($config->data)){
                $res = $this->defaultRankData();
                return $res['rank_config'];
            }
            return $config->toArray();
        }
    }

    /**
     * 获取会员配置
     * @return UserRankConfig|null
     */
    public function getRankConfig(string $code = 'rank_config'): ?UserRankConfig
    {
        $config = UserRankConfig::where('code',$code)->find();
        return !empty($config) ? $config : null;
    }

    /**
     * 获取成长值配置
     * @return array
     */
    public function getGrowConfig():array
    {
        $config = UserRankConfig::where('code','grow_config')->find();
        $data = !empty($config) && !empty($config->data) ? $config->data : [];
        return $data;
    }

    /**
     * 获取会员成长值
     * @param int $user_id
     * @return bool
     */
    public function getRankGrowth(int $user_id):bool
    {
        $config = $this->getGrowConfig();
        if (!empty($config) && $config['buyOrder']) {
            $growth_points = 0;
            // 获取最新插入的新增的成长日志
            $growth_log = RankGrowthLog::where(['user_id' => $user_id,'type' => 1,"change_type" => 1])->order('id', 'desc')->find();
            // 获取时间范围内用户已支付的订单数
            $order_num_query = Order::where(['pay_status' => Order::PAYMENT_PAID,'user_id' => $user_id,'is_del' => 0]);

            if (!empty($growth_log)) {
                $start_time = Time::toTime($growth_log->create_time);
                $order_num_query = $order_num_query->where('add_time','>',$start_time)
                    ->where('add_time',"<=",Time::now());
            } else {
                $start_time = Time::now();
                $order_num_query = $order_num_query->where('add_time','>=',$start_time);
            }
            // 获取时间范围内用户已支付的订单数
            $order_num = $order_num_query->count();

            if ($order_num >= $config['buyOrderNumber']) {
                $growth_points = intval(($order_num / $config['buyOrderNumber']) * $config['buyOrderGrowth']);
            }
            if (!empty($growth_points)) {
                $record_log = [
                    'user_id' => $user_id,
                    'type' => RankGrowthLog::GROWTH_TYPE_ORDER,
                    'growth_points' => $growth_points,
                    'change_type' => 1
                ];
                RankGrowthLog::create($record_log);

                //原来只增加了记录 没有去增加用户表的成长值
                User::where('user_id',$user_id)->inc('growth_points',$growth_points)->save();
                $user_growth_points = User::where('user_id',$user_id)->value('growth_points');
                $this->changeUserRank($user_growth_points,$user_id);
            }
        }
        return true;
    }

    /**
     * 根据成长值或消费额修改会员等级
     * @param int $user_id
     * @return bool
     * @throws ApiException
     */
    public function modifyUserRank(int $user_id):bool
    {
        if(!config('app.IS_PRO')){
            return true;
        }
        $user = User::find($user_id);
        if (empty($user)) {
            return true;
        }
        // 根据配置判断会员是按哪种类型提升等级
        $rank_config = $this->getRankConfig();
        if (!empty($rank_config)) {
            if ($rank_config['rank_type'] == 1) {
                // 成长值 -- 等级有效期内共获得的成长值
                $this->getGrowthByRule($user_id);

            } elseif ($rank_config['rank_type'] == 2) {
                // 消费行为
                $order_amount = app(OrderService::class)->getFilterSum([
                    'user_id' => $user_id,
                    'pay_status' => Order::PAYMENT_PAID
                ],'total_amount');
                $min_growth_points = $order_amount;
                $this->changeUserRank($min_growth_points,$user_id,$rank_config['rank_type']);
            }
        }
        return true;
    }

    /**
     * 修改会员等级
     * @param $growth_points
     * @param int $user_id
     * @param int $rank_type
     * @return bool
     */
    public function changeUserRank($growth_points,int $user_id, int $rank_type = 1):bool
    {
        if(!config('app.IS_PRO')){
            return true;
        }
        $user = User::find($user_id);
        $user_rank_current = UserRank::where('min_growth_points', '<=', $growth_points)
            ->where("rank_type", $rank_type)
            ->order("min_growth_points", "desc")
            ->find();
        if (!empty($user_rank_current) && $user_rank_current->rank_id != $user->rank_id) {
            $hasExpire = false;
            $rank_config = app(UserRankService::class)->getRankConfig();
            $user_rank_log = UserRankLog::where('user_id', $user_id)->order("id", "DESC")->find();
            if (!empty($user_rank_log)) {
                // 有时效
                $rank_expire_time = Time::format(strtotime('+' . $rank_config['data']['rankAfterMonth'] . ' months',
                    Time::toTime($user_rank_log['change_time'])));
                // 等级过期之后重新定义等级
                if (Time::now() >= Time::toTime($rank_expire_time)) {
                    $hasExpire = true;
                }
            }
            if (!$hasExpire && $user_rank_current->rank_id < $user->rank_id) {
                return true;
            }
            $user->rank_id = $user_rank_current->rank_id;
            // 记录会员等级变更记录
            if ($user->save()) {
                $rank_log = [
                    'user_id' => $user_id,
                    'rank_id' => $user_rank_current->rank_id,
                    'rank_type' => $rank_type,
                    'rank_name' => $user_rank_current->rank_name,
                ];
                UserRankLog::create($rank_log);
            }
        }
        return true;
    }



    /**
     * 扣减成长值
     * @param int $refund_id
     * @return bool
     * @throws ApiException
     */
    public function reduceGrowth(int $refund_id):bool
    {
        if(!config('app.IS_PRO')){
            return true;
        }
        // 判断是否为订单整单退款，部分退款不扣减
        $refund_apply = RefundApply::find($refund_id);
        if (empty($refund_apply)) {
            throw new ApiException(/** LANG */'退款信息不存在');
        }
        $afterSales_item_count = AftersalesItem::where('aftersale_id',$refund_apply->aftersale_id)->group("order_item_id")->count();
        $order_item_count = OrderItem::where('order_id',$refund_apply->order_id)->count();
        if ($afterSales_item_count != $order_item_count) {
            // 部分商品退款不扣减
            return true;
        }

        // 以下单时间判断该笔订单是否已计算入成长值表
        $config = $this->getGrowConfig();
        if (!empty($config) && $config['buyOrder']) {
            $order_time = Order::find($refund_apply->order_id)->add_time;
            $growth_user = RankGrowthLog::where(['type' => RankGrowthLog::GROWTH_TYPE_ORDER,'user_id' => $refund_apply->user_id,'change_type' => 1])->order('id', 'desc')->find();
            $growth_time = 0;
            if (!empty($growth_user)) {
                $growth_time = $growth_user->create_time;
            }
            if ($order_time <= $growth_time) {
                // 订单已计入成长值计算 -- 按配置比例扣减成长值
                $change_growth = bcdiv($config['buyOrderGrowth'], $config['buyOrderNumber'], 2);

                $growth_log = [
                    'user_id' => $refund_apply->user_id,
                    'type' => RankGrowthLog::GROWTH_TYPE_REFUND,
                    'growth_points' => $change_growth,
                    'change_type' => 2
                ];
                RankGrowthLog::create($growth_log);

                //原来只增加了记录 没有去减少用户表的成长值
                User::where('user_id',$refund_apply->user_id)->dec('growth_points',$change_growth)->save();
       //         $this->changeUserRank($change_growth,$refund_apply->user_id);
            }
        }
        return true;
    }

    /**
     * 根据保级规则计算用户的成长值并重新定义等级
     * @param int $user_id
     * @return bool
     */
    public function getGrowthByRule(int $user_id):bool
    {
        $rank_config = $this->getRankConfig();
        $rank_data = !empty($rank_config) && !empty($rank_config['data']) ? $rank_config['data'] : [];
        if (!empty($rank_data)) {
            // 按等级规则的成长值重新计算等级
            if ($rank_data['type'] == 1) {
                // 获取用户保级时间范围的成长值
                $growth = $this->getExpireRangeGrowth($user_id);
                // 根据成长值重新计算等级  -- 在保级范围内等级不变
                if ($rank_config['rank_type'] == 1) {
                    $this->changeUserRank($growth,$user_id,$rank_config['rank_type']);
                }
            }
        }
        return true;
    }

    /**
     * 获取保级范围内的成长值
     * @param int $user_id
     * @return int
     */
    public function getExpireRangeGrowth(int $user_id):int
    {
        $growth = 0;
        $rank_config = $this->getRankConfig();
        $rank_data = !empty($rank_config) && !empty($rank_config['data']) ? $rank_config['data'] : [];
        // 等级变更日志
        $current_rank = UserRankLog::where('user_id',$user_id)->order('id', 'desc')->find();
        if (isset($rank_data['type']) && $rank_data['type'] == 1) {
            // 获取用户保级时间范围
            $growth_query = RankGrowthLog::where('user_id',$user_id);
            if (!empty($current_rank)) {
                // $rank_end_time = strtotime('+' . $rank_data['rank_after_month'] . ' months',Time::toTime());
                $growth_query = $growth_query
                    ->where('create_time', '>=',Time::toTime($current_rank->change_time));
            } else {
                $growth_query = $growth_query;
            }
            $inc_growth_query = clone $growth_query;
            $dec_growth_query = clone $growth_query;

            $inc_growth = $inc_growth_query->where("change_type",1)->sum('growth_points') ?? 0;
            $dec_growth = $dec_growth_query->where("change_type",2)->sum('growth_points') ?? 0;

            $growth = bcsub($inc_growth,$dec_growth,2);

            $growth = $growth < 0 ? 0 : $growth;
        }
        return $growth;
    }

    /**
     * 返回会员权益信息
     * @param int $rank_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRankInfo(int $rank_id, int $rank_type = 1)
    {
        $rank_info =  $this->userRankModel::find($rank_id);
        $grow_config = [];
        if (empty($rank_info)) {
            $res = $this->defaultRankData();
            $user_rank = $res['user_rank_list'][0];
            $grow_config = $res['grow_up_setting'];
            $rank_config = $res['rank_config'];
        } else {
            $user_rank = $rank_info->toArray();
            if ($rank_type == 1) {
                $grow_config = $this->getGrowConfig();
            }
            $rank_config = $this->getRankConfigArray();
        }

        return [
            'rank_type' => $rank_type,
            'user_rank' => $user_rank,
            'rank_config' => $rank_config,
            'grow_up_setting' => $grow_config
        ];
    }
}
