<?php

namespace app\service\admin\logistics\src;

use app\model\setting\LogisticsCompany;
use app\service\admin\logistics\LogisticsApiLogService;
use app\service\admin\logistics\LogisticsService;
use exceptions\ApiException;
use tig\Http;
use utils\Config;
use utils\Time;

class KDNiaoService extends LogisticsService
{
    /**
     * 快递鸟申请的api_key
     * @var string
     */
    protected string $apiKey = '';
    /**
     * 快递鸟用户ID
     * @var string
     */
    protected string $eBusinessId = '';
    /**
     * 查询接口地址
     * @var string
     */
    protected string $traceUrl = 'https://api.kdniao.com/api/dist';
    /**
     * 电子面单接口地址
     * @var string
     */
    protected string $electronicUrl = 'https://api.kdniao.com/api/EOrderService';

    protected string $traceRequestType = '8001';
    protected string $electronicRequestType = '1007';

    protected string $cancelElectronicRequestType = '1147';

    public function __construct()
    {
        $this->apiKey = Config::get('kdniaoApiKey');
        $this->eBusinessId = Config::get('kdniaoBusinessId');

    }

    /**
     * 物流轨迹查询
     * @param array $params ['shipperCode' => '快递公司编码','LogisticCode' => '快递单号','CustomerName' => '寄件人or收件人 手机号后四位/顺丰必填 跨越必填']
     * @return array
     */
    public function track(array $params): array
    {
        $requestData = [
            'ShipperCode' => $params['shipperCode'],
            'LogisticCode' => $params['logisticCode'],
            'CustomerName' => $params['customerName'] ?? '',
            'Sort' => 1
        ];
        $requestDataJson = json_encode($requestData);
        $result = $this->sendPost($this->traceUrl, $this->traceRequestType, $requestDataJson);
        return $result;
    }

    /**
     * 获取电子面单
     * @param array $order
     * @param string $remark
     * @return array
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getElectronicWaybill(array $order, string $remark = ''): array
    {
        //JDVF02836148634--20240531943669--6特惠送
        $logistics_id = $order['logistics_id'];
        $logistics = LogisticsCompany::where('logistics_id', $logistics_id)->find();
        if (empty($logistics)) throw new ApiException('未存在物流公司信息');
        // 收件人信息
        $receiver_data = [
            'Name' => $order['consignee'],
            'Mobile' => $order['mobile'],
            'ProvinceName' => $order['region_ids'][0] > 1 ? $order['region_names'][0] : $order['region_names'][1],
            'CityName' => $order['region_ids'][0] > 1 ? $order['region_names'][1] : $order['region_names'][2],
            'ExpAreaName' => $order['region_ids'][0] > 1 ? $order['region_names'][2] : $order['region_names'][3],
            'Address' => $order['address'],
        ];
        // 寄件人信息
        $sender_data = [
            'ProvinceName' => Config::get('provinceName'),
            'CityName' => Config::get('cityName'),
            'ExpAreaName' => Config::get('areaName'),
            'Address' => Config::get('address'),
            'Name' => Config::get('sender'),
            'Mobile' => Config::get('mobile'),
            'PostCode' => '000000'
        ];
        //计算商品总重量
        $items = $order['items'];
        $weight = 0;
        foreach ($items as $value) {
            $weight += $value['product_weight'];
        }
        //预约取件时间--打印面单时间往后推一个小时
        $startDate = Time::format(Time::now() + 3600);
        $endDate = Time::format(Time::now() + 3 * 3600);
        //基础数据
        $requestData = [
            'OrderCode' => $this->createOrderCode($order['order_sn'], $order['order_id']),
            'ShipperCode' => $logistics['logistics_code'],
            'PayType' => 3,
            'ExpType' => $logistics['exp_type'],
            'Receiver' => $receiver_data,
            'Sender' => $sender_data,
            'Quantity' => 1,
            'Weight' => max($weight, 1),
            'Volume' => 1,
            'Remark' => $remark,
            'Commodity' => [
                [
                    'GoodsName' => '电商产品'
                ]
            ],
            'CustomerName' => $logistics['customer_name'],
            'CustomerPwd' => $logistics['customer_pwd'],
            'SendSite' => $logistics['month_code'],
            'SendStaff' => $logistics['send_site'],
            'MonthCode' => $logistics['send_staff'],
            'IsNotice' => 0,
            'StartDate' => $startDate,
            'EndDate' => $endDate,
            'CurrencyCode' => 'CNY',
            'Dutiable' => [
                'DeclaredValue' => $order['product_amount']
            ],
            'IsReturnPrintTemplate' => 1
        ];
        $requestDataJson = json_encode($requestData);
        $result = $this->sendPost($this->electronicUrl, $this->electronicRequestType, $requestDataJson);
        if (!$result['Success']) throw new ApiException($result['Reason']);
        //插入日志信息
        app(LogisticsApiLogService::class)->addLog($order['order_id'], $result['Order']['OrderCode'], $result['Order']['LogisticCode'], $result['PrintTemplate'] ?? '');

        return $result;
    }

    /**
     * 获取订单编号
     * @param string $order_sn
     * @param int $order_id
     * @return string
     */
    private function createOrderCode(string $order_sn, int $order_id): string
    {
        return $order_sn . $order_id . rand(100000, 999999);
    }

    /**
     * 发送请求
     * @param string $url
     * @param string $RequestType
     * @param string $requestDataJson
     * @return array
     */
    private function sendPost(string $url, string $RequestType, string $requestDataJson): array
    {
        // 组装系统级参数
        $data = array(
            'EBusinessID' => $this->eBusinessId,
            'RequestType' => $RequestType,
            'RequestData' => urlencode($requestDataJson),
            'DataType' => '2',
        );
        $data['DataSign'] = $this->encrypt($requestDataJson);
        $res = Http::post($url, $data);

        return json_decode($res, true);
    }

    /**
     * 取消电子面单
     * @param $order
     * @return bool
     * @throws ApiException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelElectronicWaybill($order): bool
    {
        $logistics_id = $order['logistics_id'];
        $logistics = LogisticsCompany::where('logistics_id', $logistics_id)->find();
        if (empty($logistics)) throw new ApiException('未存在物流公司信息');
        $logistics_info = app(LogisticsApiLogService::class)->getDetail($order['tracking_no']);
        if (!$logistics_info) throw new ApiException('未获取到发货信息');
        $requestData = [
            'ShipperCode' => $logistics['logistics_code'],
            'OrderCode' => $logistics_info['order_code'],
            'ExpNo' => $logistics_info['logistic_code'],
            'CustomerName' => $logistics['customer_name'],
            'CustomerPwd' => $logistics['customer_pwd'],
            'MonthCode' => $logistics['month_code']
        ];
        $requestDataJson = json_encode($requestData);
        $result = $this->sendPost($this->electronicUrl, $this->cancelElectronicRequestType, $requestDataJson);
        if (!$result['Success']) throw new ApiException($result['Reason']);

        return true;
    }

    /**
     * 电商Sign签名生成
     * @param string $data
     * @return string
     */
    private function encrypt(string $data): string
    {
        return urlencode(base64_encode(md5($data . $this->apiKey)));
    }

}