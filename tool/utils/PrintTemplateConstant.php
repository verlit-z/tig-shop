<?php

namespace utils;

use JsonException;

class PrintTemplateConstant
{
    /**
     * 默认购物小票模板
     * @var string
     */
    public static string $DEFAULT_RECEIPT_TEMPLATE;

    /**
     * 静态初始化块
     */
    public static function init()
    {
        try {
            return self::$DEFAULT_RECEIPT_TEMPLATE = json_encode(self::createDefaultReceiptTemplate(), JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new \RuntimeException("初始化默认打印模板失败", 0, $e);
        }
    }

    /**
     * 创建默认购物小票模板
     *
     * @return array
     */
    public static function createDefaultReceiptTemplate(): array
    {
        $template = [];

        $template[] = self::createTemplateItem("小票头部",
            self::createOption("商家名称", true, "演示商家"),
       //     self::createOption("门店名称", true, "门店名称")
        );

        $template[] = self::createTemplateItem("配送信息",
            self::createOption("配送方式", true, "配送方式"),
            self::createOption("商配&同城-收货信息", true, "商配&同城-收货信息"),
            self::createOption("手机号模糊", true, "手机号模糊")
        );

        $template[] = self::createTemplateItem("买家备注",
            self::createOption("买家备注", true, "买家备注")
        );

        $template[] = self::createTemplateItem("商品信息",
            self::createOption("商品基础信息", true, "商品基础信息"),
            self::createOption("规格编码", true, "规格编码")
        );

        $template[] = self::createTemplateItem("运费信息",
            self::createOption("运费", true, "运费")
        );

        $template[] = self::createTemplateItem("优惠信息",
            self::createOption("优惠明细", true, "优惠明细"),
            self::createOption("优惠总计", true, "优惠总计")
        );

        $template[] = self::createTemplateItem("支付信息",
            self::createOption("实收金额", true, "实收金额"),
            self::createOption("支付方式", true, "支付方式"),
            self::createOption("第三方支付单号", true, "第三方支付单号")
        );

        $template[] = self::createTemplateItem("客户信息",
            self::createOption("客户信息", true, "客户信息")
        );

        $template[] = self::createTemplateItem("其他订单信息",
            self::createOption("下单时间", true, "下单时间"),
            self::createOption("支付时间", true, "支付时间"),
            self::createOption("渠道类型", true, "渠道类型")
        // self::createOption("门店店址", true, "门店店址")
        );

        $template[] = self::createTemplateItem("二维码",
            self::createOption("订单二维码", true)
        );

        $template[] = self::createTemplateItem("底部公告",
            self::createOption("底部公告", true, "感谢您的惠顾，欢迎再次光临！")
        );

        return $template;
    }

    /**
     * 创建模板项
     *
     * @param string $title 模板项标题
     * @param array ...$options 模板项选项数组
     * @return array
     */
    private static function createTemplateItem(string $title, ...$options): array
    {
        return [
            'title' => $title,
            'options' => $options
        ];
    }

    /**
     * 创建模板选项
     *
     * @param string $chooseTitle 选项标题
     * @param bool $choose 是否选中
     * @param string $templateValue 模板值
     * @param string $value 实际值
     * @return array
     */
    private static function createOption(string $chooseTitle, bool $choose, string $value = ""): array
    {
        return [
            'chooseTitle' => $chooseTitle,
            'choose' => $choose,
     //       'templateValue' => $templateValue,
            'value' => $value
        ];
    }
}

