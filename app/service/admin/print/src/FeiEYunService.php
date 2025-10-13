<?php
namespace app\service\admin\print\src;
use app\service\admin\merchant\ShopService;
use app\service\admin\order\OrderService;
use app\service\admin\pay\PayLogService;
use app\service\admin\print\AbstractPrintService;
use app\service\admin\setting\ConfigService;
use app\service\admin\user\UserService;
use utils\Util;

class FeiEYunService extends AbstractPrintService
{


    private $url = 'api.feieyun.cn';
    private $port = 80;
    private $path='/Api/Open/';
    public function add(array $data): array
    {

        $client = new FeiEYunHttpClientService($this->url, $this->port);
        if (!$client->post($this->path, $data)) {
            $response = ['msg' => 'HTTP请求错误', 'ret' => 500];
        } else {
            $content = $client->getContent();
            if ($content === null || $content === '') {
                $response = ['msg' => 'Empty response content', 'ret' => 500];
            } elseif (!is_string($content)) {
                $response = ['msg' => '返回内容格式错误', 'ret' => 500];
            } elseif (json_validate($content)) {
                $response = json_decode($content, true);
            } else {
                $response = ['msg' => 'json格式错误', 'ret' => 500];
            }
        }
        return $response;
    }

    public function delete(array $order): array
    {

        $client = new FeiEYunHttpClientService($this->url, $this->port);
        if (!$client->post($this->path, $order)) {
            $response = ['msg' => 'HTTP请求错误', 'ret' => 500];
        } else {
            $content = $client->getContent();
            if ($content === null || $content === '') {
                $response = ['msg' => 'Empty response content', 'ret' => 500];
            } elseif (!is_string($content)) {
                $response = ['msg' => '返回内容格式错误', 'ret' => 500];
            } elseif (json_validate($content)) {
                $response = json_decode($content, true);
            } else {
                $response = ['msg' => 'json格式错误', 'ret' => 500];
            }
        }
        return $response;
    }
    public function print(array $data): array
    {

        $client = new FeiEYunHttpClientService($this->url, $this->port);
        if (!$client->post($this->path, $data)) {
            $response = ['msg' => 'HTTP请求错误', 'ret' => 500];
        } else {
            $content = $client->getContent();
            if ($content === null || $content === '') {
                $response = ['msg' => 'Empty response content', 'ret' => 500];
            } elseif (!is_string($content)) {
                $response = ['msg' => '返回内容格式错误', 'ret' => 500];
            } elseif (json_validate($content)) {
                $response = json_decode($content, true);
            } else {
                $response = ['msg' => 'json格式错误', 'ret' => 500];
            }
        }
        return $response;
    }

    /**
     * [signature 生成签名]
     * @param  [string] $time [当前UNIX时间戳，10位，精确到秒]
     * @return [string]       [接口返回值]
     */
    public function signature($user,$key,$time){
        return sha1($user.$key.$time);//公共参数，请求公钥
    }


    /**
     * 根据订单信息和打印模板生成打印内容
     */
    public function generatePrintContent($orderId, $template) {
        $content = "";

        $order =app(OrderService::class)->getOrder($orderId);
        $orderVO = app(OrderService::class)->getDetail($orderId, $order['userId']);

        // 遍历打印模板，根据配置生成内容
        foreach ($template as $item) {
            $title = $item['title'];
            $options = $item['options'];

            // 根据模板项标题生成对应内容
            switch ($title) {
                case "小票头部":
                    $content .= $this->generateReceiptHeader($orderVO, $options);
                    break;
                case "配送信息":
                    $content .= $this->generateDeliveryInfo($orderVO, $options);
                    break;
                case "买家备注":
                    $content .= $this->generateBuyerNote($orderVO, $options);
                    break;
                case "商品信息":
                    $content .= $this->generateProductInfo($orderVO, $options);
                    break;
                case "运费信息":
                    $content .= $this->generateShippingInfo($orderVO, $options);
                    break;
                case "优惠信息":
                    $content .= $this->generateDiscountInfo($orderVO, $options);
                    break;
                case "支付信息":
                    $content .= $this->generatePaymentInfo($orderVO, $options);
                    break;
                case "客户信息":
                    $content .= $this->generateCustomerInfo($orderVO, $options);
                    break;
                case "其他订单信息":
                    $content .= $this->generateOtherOrderInfo($orderVO, $options);
                    break;
                case "二维码":
                    $content .= $this->generateCodeInfo($orderVO, $options);
                    break;
                case "底部公告":
                    $content .= $this->generateFooter($orderVO, $options);
                    break;
            }
        }
        return $content;
    }


    /**
     * 生成小票头部
     */
    private function generateReceiptHeader($orderVO, $options) {
        $content = "";
        $shopName = app(ConfigService::class)->getConfigByCode('shopName');
        $orderVO['shop_name'] =null;
        if (self::isChoose($options, "商家名称")) {
            $shop = app(ShopService::class)->getDetail($orderVO['shop_id']);
            if (!empty($shop)) {
                $orderVO['shop_name'] = $shop['shop_title'];
            }
            $content .= "<C>" . ($orderVO['shop_name'] != null ? $orderVO['shop_name'] : $shopName) . "</C><BR>";
        }

        if (self::isChoose($options, "门店名称")) {
            $content .= "<CB>" . ($orderVO['shop_name'] != null ? $orderVO['shop_name'] : $shopName) . "</CB><BR>";
        }

        return $content;
    }

    /**
     * 生成配送信息
     */
    private function generateDeliveryInfo($orderVO, $options) {
        $content = "";

        if (self::isChoose($options, "配送方式")) {
            $content .= "配送方式：" . ($orderVO['shipping_type_name'] != null ? $orderVO['shipping_type_name'] : "") . "<BR>";
        }

        if (self::isChoose($options, "商配&同城-收货信息")) {

            if (self::isChoose($options, "手机号模糊")) {
                $mobile = Util::maskMiddleHalf($orderVO['mobile']);
            } else {
                $mobile = $orderVO['mobile'];
            }

            $content .= self::formatMultpartLine(
                    "收货信息：",
                    $orderVO['consignee'] . "，" .$mobile . "，" . $orderVO['user_address']
                ) . "<BR>";
        }

        if (self::isChooseAny($options, "同城配送-收货信息", "配送方式")) {
            $content .= "--------------------------------<BR>";
        }

        return $content;
    }

    /**
     * 生成买家备注
     */
    private function generateBuyerNote($orderVO, $options)
    {
        $content = "";

        if (self::isChoose($options, "买家备注")) {
            $buyerNote = !empty($orderVO['buyer_note']) ? $orderVO['buyer_note'] : "无";
            $content .= "<BOLD>" . self::formatMultpartLine("买家备注：", $buyerNote) . "</BOLD><BR>";
            $content .= "--------------------------------<BR>";
        }
        return $content;
    }

    /**
     * 生成商品信息
     */
    private function generateProductInfo($orderVO, $options) {
        $content = "";
        $configVal = str_replace("¥", "￥", app(ConfigService::class)->getConfigByCode('dollarSign'));
        if (self::isChooseAny($options, "商品基础信息", "规格编码")) {
            $content .= "商品<BR>";
            $content .= "单价          数量          小计<BR>";
            $content .= "--------------------------------<BR>";
        }

        if (!empty($orderVO['items'])) {
            foreach ($orderVO['items'] as $item) {
                if (self::isChoose($options, "商品基础信息")) {
                    $content .= $item['product_name'];
                    if (!empty($item['sku_value'])) {
                        $content .= "（" . $item['sku_value'] . "）";
                    }
                    $content .= "<BR>";
                }

                if (self::isChoose($options, "规格编码")) {
                    $content .= "商品编码：" . $item['product_sn'] . "<BR>";
                }

                if (self::isChoose($options, "商品基础信息")) {
                    $subtotal = $item['price'] * $item['quantity'];
                    $content .= self::formatLineProduct(
                            $configVal . $item['price'],
                            $item['quantity'],
                            $configVal . $subtotal
                        ) . "<BR>";
                }
            }
        }

        $sum = array_sum(array_column($orderVO['items'], 'quantity'));
        if (self::isChooseAny($options, "商品基础信息", "规格编码")) {
            $content .= "--------------------------------<BR>";
            $content .= self::formatLine(
                    "共" . $sum . "件",
                    "<BOLD>合计：" . $configVal . $orderVO['product_amount']
                ) . "</BOLD><BR>";
            $content .= "--------------------------------<BR>";
        }
        return $content;
    }

    /**
     * 生成运费信息
     */
    private function generateShippingInfo($orderVO, $options) {
        $content = "";
        $configVal = str_replace("¥", "￥", app(ConfigService::class)->getConfigByCode('dollarSign'));

        if (self::isChoose($options, "运费")) {
            $content .= self::formatLine(
                    "运费",
                    $configVal . $orderVO['shipping_fee']
                ) . "<BR>";
            $content .= "--------------------------------<BR>";
        }
        return $content;
    }

    /**
     * 生成优惠信息
     */
    private function generateDiscountInfo($orderVO, $options) {
        $content = "";
        $configVal = str_replace("¥", "￥",  app(ConfigService::class)->getConfigByCode('dollarSign'));

        if (self::isChoose($options, "优惠明细")) {
            $content .= self::formatLine(
                    "优惠",
                    "优惠券：-" . $configVal . $orderVO['coupon_amount']
                ) . "<BR>";

            $content .= self::formatLine(
                    "",
                    "积分抵扣：-" . $configVal . $orderVO['points_amount']
                ) . "<BR>";

            $content .= self::formatLine(
                    "",
                    "活动优惠：-" . $configVal . $orderVO['discount_amount']
                ) . "<BR>";
        }

        $totalDiscount = $orderVO['coupon_amount'] + $orderVO['points_amount'] + $orderVO['discount_amount'];

        if (self::isChoose($options, "优惠总计") && !self::isChoose($options, "优惠明细")) {
            $content .= self::formatLine(
                    "优惠",
                    "<BOLD>总计：-" . $configVal . $totalDiscount
                ) . "</BOLD><BR>";
        }

        if (self::isChoose($options, "优惠总计") && self::isChoose($options, "优惠明细")) {
            $content .= self::formatLine(
                    "",
                    "<BOLD>总计：-" . $configVal . $totalDiscount
                ) . "</BOLD><BR>";
        }

        if (self::isChooseAny($options, "优惠明细", "优惠总计")) {
            $content .= "--------------------------------<BR>";
        }
        return $content;
    }

    /**
     * 生成支付信息
     */
    private function generatePaymentInfo($orderVO, $options) {
        $content = "";
        $configVal = str_replace("¥", "￥", app(ConfigService::class)->getConfigByCode('dollarSign'));
        $paylog=app(PayLogService::class)->getPayLogByOrderId($orderVO['order_id']);
        if (self::isChoose($options, "支付方式")) {

            if ($paylog['pay_sn'] != null) {
                $payMethodMap = [
                    'wechat' => '微信',
                    'alipay' => '支付宝',
                    'paypal' => 'paypal',
                    'offline' => '线下支付'
                ];
                $payMethodName = isset($payMethodMap[$paylog['pay_code']]) ? $payMethodMap[$paylog['pay_code']] : $paylog['pay_code'];
                $content .= "支付方式：" . $payMethodName . "<BR>";

            } else {
                if ($orderVO['balance'] > 0) {
                    $content .= "支付方式：" . "余额支付" . "<BR>";
                }
            }
        }

        if (self::isChoose($options, "实收金额")) {
            $content .= "实收金额：" . $configVal . $orderVO['total_amount'] . "<BR>";
        }

        if (self::isChoose($options, "第三方支付单号")) {
            if ($paylog['pay_sn'] != null) {
                $content .= "第三方支付单号：" . $paylog['transaction_id'] . "<BR>";
            } else {
                if ($orderVO['balance'] > 0) {
                    $content .= "第三方支付单号：" . "无" . "<BR>";
                }
            }
        }

        if (self::isChooseAny($options, "支付方式", "实收金额", "第三方支付单号")) {
            $content .= "--------------------------------<BR>";
        }
        return $content;
    }

    /**
     * 生成客户信息
     */
    private function generateCustomerInfo($orderVO, $options) {
        $content = "";
        $configVal = str_replace("¥", "￥", app(ConfigService::class)->getConfigByCode('dollarSign'));

        if (self::isChoose($options, "客户信息")) {
            $content .= "客户姓名：" . Util::maskMiddleHalf($orderVO['user']['username']) . "<BR>";
            $content .= "手机号：" . Util::maskMiddleHalf($orderVO['user']['mobile']) . "<BR>";

//            $user = app(UserService::class)->getDetail($orderVO['user_id']);
//            if ($user != null) {
//                $content .= "积分：" . $user['points'] . "<BR>";
//                $content .= "余额：" . $configVal . $user['balance'] . "<BR>";
//            }

            $content .= "--------------------------------<BR>";
        }
        return $content;
    }

    /**
     * 生成其他订单信息
     */
    private function generateOtherOrderInfo($orderVO, $options) {
        $content = "";

        if (self::isChoose($options, "下单时间")) {
            $content .= "下单时间：" . $orderVO['add_time'] . "<BR>";
        }

        if (self::isChoose($options, "支付时间")) {
            if (empty($orderVO['pay_time'])) {
                $content .= "支付时间：" . '-' . "<BR>";
            }else{
                $content .= "支付时间：" . $orderVO['pay_time'] . "<BR>";
            }
        }

        if (self::isChoose($options, "渠道类型")) {
            $content .= "渠道类型：" . $orderVO['order_source'] . "<BR>";
        }

        if (self::isChooseAny($options, "下单时间", "支付时间", "渠道类型")) {
            $content .= "--------------------------------<BR>";
        }
        return $content;
    }


    /**
     * 订单二维码信息
     */
    private function generateCodeInfo($orderVO, $options)
    {
        $content = "";
        if (self::isChoose($options, "订单二维码")) {
            $content .= "订单编号：" . $orderVO['order_sn'] . "<BR>";
            $content .= "<C><BC128_C>" . $orderVO['order_sn'] . "</BC128_C></C><BR>";
        } else {
            $content .= "<BR>";
            $content .= "<BR>";
        }
        return $content;
    }

    /**
     * 生成底部信息
     */
    private function generateFooter($orderVO, $options) {
        $content = "";

        if (self::isChoose($options, "底部公告")) {
//            $content .= "<BR>";
//            $content .= "<BR>";
            $content .= "<C>" . $options[0]['value'] . "<C><BR>";
        }

        $content .= "<BR>";
        $content .= "<BR>";
        $content .= "<CUT>";

        return $content;
    }


    /**
     * 格式化对齐商品价格信息
     *
     * @param string $price 单价
     * @param int $quantity 数量
     * @param string $subtotal 小计
     * @return string 格式化后的字符串，字段间通过空格对齐
     */
    public static function formatLineProduct($price, $quantity, $subtotal)
    {
        // 定义每个字段的宽度（以字符数为单位）
        $priceWidth = 11;
        $quantityWidth = 11;
        $subtotalWidth = 11;

        // 格式化为字符串，右对齐
        $priceStr = str_pad($price, $priceWidth, " ", STR_PAD_RIGHT);
        $quantityStr = self::centerString(strval($quantity), $quantityWidth);
        $subtotalStr = str_pad($subtotal, $subtotalWidth, " ", STR_PAD_LEFT);

        return $priceStr . $quantityStr . $subtotalStr;
    }

    /**
     * 格式化对齐（支持汉字宽度）
     *
     * @param string $left 左侧字段（左对齐）
     * @param string $right 右侧字段（右对齐）
     * @return string 对齐后的字符串
     */
    public static function formatLine($left, $right)
    {
        $contains = strpos($right, "<BOLD>") !== false;
        if ($contains) {
            $right = str_replace("<BOLD>", "", $right);
        }

        $leftDisplayWidth = 15;
        $rightDisplayWidth = 15;

        $leftStr = self::padRightConsideringChinese($left, $leftDisplayWidth);
        $rightStr = self::padLeftConsideringChinese($right, $rightDisplayWidth);

        return $leftStr . ($contains ? "<BOLD>" : "") . $rightStr;
    }

    /**
     * 格式化对齐（支持汉字宽度）多行
     *
     * @param string $left 左侧字段（左对齐）
     * @param string $right 右侧字段（右侧长文本）
     * @return string 对齐后的字符串（带<BR>换行）
     */
    public static function formatMultpartLine($left, $right)
    {
        $leftWidth = self::getDisplayWidth($left);
        $availableRightWidth = 28 - $leftWidth;

        $rightLines = self::splitByDisplayWidth($right, $availableRightWidth);

        $result = "";
        foreach ($rightLines as $i => $line) {
            if ($i == 0) {
                $result .= $left . $line;
            } else {
                $result .= "<BR>" . str_repeat(" ", $leftWidth) . $line;
            }
        }
        return $result;
    }

    /**
     * 将字符串按显示宽度拆分成多行
     */
    private static function splitByDisplayWidth($str, $maxWidth)
    {
        $lines = array();
        $line = "";
        $currentWidth = 0;

        for ($i = 0; $i < mb_strlen($str); $i++) {
            $c = mb_substr($str, $i, 1);
            $charWidth = self::isChinese($c) ? 2 : 1;
            if ($currentWidth + $charWidth > $maxWidth) {
                $lines[] = $line;
                $line = $c;
                $currentWidth = $charWidth;
            } else {
                $line .= $c;
                $currentWidth += $charWidth;
            }
        }
        if (strlen($line) > 0) {
            $lines[] = $line;
        }
        return $lines;
    }

    /**
     * 计算字符串显示宽度：英文=1，汉字=2
     */
    private static function getDisplayWidth($str)
    {
        $width = 0;
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $c = mb_substr($str, $i, 1);
            $width += self::isChinese($c) ? 2 : 1;
        }
        return $width;
    }

    /**
     * 判断字符是否为中文
     */
    private static function isChinese($c)
    {
        return preg_match('/[\x{4e00}-\x{9fa5}]/u', $c);
    }

    /**
     * 右填充空格（左对齐）
     */
    private static function padRightConsideringChinese($str, $targetDisplayWidth)
    {
        $currentWidth = self::getDisplayWidth($str);
        $paddingSpaces = $targetDisplayWidth - $currentWidth;
        return $str . str_repeat(" ", max(0, $paddingSpaces));
    }

    /**
     * 左填充空格（右对齐）
     */
    private static function padLeftConsideringChinese($str, $targetDisplayWidth)
    {
        $currentWidth = self::getDisplayWidth($str);
        $paddingSpaces = $targetDisplayWidth - $currentWidth;
        return str_repeat(" ", max(0, $paddingSpaces)) . $str;
    }

    public static function centerString($text, $width)
    {
        if ($text === null) $text = "";
        $textLength = strlen($text);
        if ($textLength >= $width) return $text;

        $padding = $width - $textLength;
        $paddingLeft = intval($padding / 2);
        $paddingRight = $padding - $paddingLeft;

        return str_repeat(" ", $paddingLeft) . $text . str_repeat(" ", $paddingRight);
    }


    /**
     * 检查选项是否被选中
     *
     * @param array|null $options 选项列表
     * @param string $chooseTitle 选项标题
     * @return bool
     */
    public static function isChoose(?array $options, string $chooseTitle): bool {
        if ($options === null) {
            return false;
        }

        foreach ($options as $option) {
            if ($option['choose'] ==1 && $chooseTitle == $option['chooseTitle']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断是否选中任意一个指定标题
     *
     * @param array $options 选项列表
     * @param array $chooseTitles 可选的标题
     * @return bool 是否选中任意一个
     */
    public static function isChooseAny(array $options = null, ...$chooseTitles): bool
    {
        if ($options === null || empty($chooseTitles)) {
            return false;
        }
        foreach ($options as $option) {
             if ($option['choose'] ==1 && in_array($option['chooseTitle'], $chooseTitles)) {
                 return true;
             }
        }
        return false;
    }

}