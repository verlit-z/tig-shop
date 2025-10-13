<?php

namespace app\service\admin\setting;

use app\job\MessageJob;
use app\job\MiniProgramJob;
use app\job\SmsJob;
use app\job\WechatJob;
use app\model\msg\AdminMsg;
use app\service\admin\msg\AdminMsgService;
use app\service\admin\oauth\UserAuthorizeService;
use app\service\admin\order\OrderService;
use app\service\common\BaseService;
use utils\Config;
use utils\TigQueue;

class MessageCenterService extends BaseService
{

    const NEW_ORDER = 1; //会员下单
    const ORDER_PAY = 2; //下单支付
    const ORDER_SHIPPING = 3;//订单发货
    const ORDER_REFUND = 4;//订单退款
    const NEW_ORDER_SHOP = 5;//下单给商家发送信息
    const ORDER_PAY_SHOP = 6;//支付订单给商家发送信息
    const  ORDER_INVOICE = 7;//发票邮寄
    protected array $messageTypeList = [
        self::NEW_ORDER,
        self::ORDER_PAY,
        self::ORDER_SHIPPING,
        self::ORDER_REFUND,
        self::NEW_ORDER_SHOP,
        self::ORDER_PAY_SHOP,
        self::ORDER_INVOICE,
    ];

    /**
     * 发送消息
     * @param int $user_id
     * @param int $order_id
     * @param int $type
     * @return bool|array
     * @throws \exceptions\ApiException
     */
    public function sendUserMessage(int $user_id, int $order_id, int $type): bool|array
    {
        if (!in_array($type, $this->messageTypeList)) return false;
        $order = app(OrderService::class)->getOrderMessage($order_id, $user_id);
        if (!$order) return false;
        $template_info = app(MessageTemplateService::class)->getMessageTemplateList($type);
        if ($template_info['type_info']['is_message'] == 1 && $template_info['message']['disabled'] == 0) {
            //需要发送站内信
            $title = $template_info['message']['info']['template_name'];
            $content = $template_info['message']['info']['content'];
            $link = [
                'path' => 'order',
                'label' => '订单提醒',
                'id' => $order_id,
                'name' => $title
            ];
            //替换content内容
            $content = str_replace('{order_sn}', $order->order_sn, $content);
            if ($type == self::ORDER_SHIPPING) {
                $content = str_replace('{logistics_name}', $order->logistics_name, $content);
                $content = str_replace('{tracking_no}', $order->tracking_no, $content);
            }
            app(TigQueue::class)->push(MessageJob::class,
                ['user_id' => $user_id, 'title' => $title, 'content' => $content, 'link' => $link]);
        }
        if ($template_info['type_info']['is_msg'] == 1 && $template_info['msg']['disabled'] == 0) {
            //需要发送短信
            $template_code = $template_info['msg']['info']['template_id'];
            $content = [];
            $mobile = $order->mobile;
            if (in_array($type, [self::NEW_ORDER, self::ORDER_PAY, self::ORDER_REFUND, self::ORDER_INVOICE, self::ORDER_PAY_SHOP, self::NEW_ORDER_SHOP])) {
                $content['order'] = $order->order_sn;
            }
            if ($type == self::ORDER_SHIPPING) {
                $content['order'] = $order->order_sn;
                $content['shipping'] = $order->logistics_name;
                $content['code'] = $order->tracking_no;
            }
            if (in_array($type, [self::ORDER_PAY_SHOP, self::NEW_ORDER_SHOP])) {
                $mobile = Config::get('smsShopMobile');
            }
//            app(TigQueue::class)->push(SmsJob::class,
//                ['mobile' => $mobile, 'template_code' => $template_code, 'content' => $content]);
        }
        if ($template_info['type_info']['is_wechat'] == 1 && $template_info['wechat']['disabled'] == 0) {
            //需要发送公众号消息
            $openid = app(UserAuthorizeService::class)->getUserAuthorizeOpenId($user_id, 1);
            $template_id = $template_info['wechat']['info']['template_id'];
            if (!empty($openid) && !empty($template_id)) {
                $h5_domain = Config::get('h5Domain');
                if (empty($h5_domain)) {
                    $h5_domain = Config::get('pcDomain');
                }
                $url = $h5_domain . '/pages/user/order/info?id=' . $order_id;;
                $message = [
                    'touser' => $openid,
                    'template_id' => $template_id,
                    'url' => $url,
                ];
                $data = [];
                if ($type == self::ORDER_PAY) {
                    $data = [
                        'character_string3' => ['value' => $order->order_sn],
                        'time7' => ['value' => $order->add_time],
                        'amount4' => ['value' => round($order->total_amount,2)],
                    ];
                }
                if ($type == self::ORDER_SHIPPING) {
                    $data = [
                        'thing21' => ['value' => $order->logistics_name],
                        'character_string18' => ['value' => $order->tracking_no],
                        'time3' => ['value' => $order->shipping_time],
                        'thing17' => ['value' => $order->consignee . ' ' . $order->mobile],
                        'character_string2' => ['value' => $order->order_sn],
                    ];
                }
                if ($type == self::ORDER_REFUND) {
                    $data = [
                        'character_string5' => ['value' => $order->order_sn],
                        'amount2' => ['value' => $order->refund_money],
                    ];
                }
                $message['data'] = $data;
                app(TigQueue::class)->push(WechatJob::class, $message);
            }
        }
        if ($template_info['type_info']['is_mini_program'] == 1 && $template_info['mini_program']['disabled'] == 0) {
            //需要小程序消息
            $openid = app(UserAuthorizeService::class)->getUserAuthorizeOpenId($user_id, 2);
            $template_id = $template_info['mini_program']['info']['template_id'];
            if (!empty($openid) && !empty($template_id)){
                $page = '/pages/user/order/info?id=' . $order_id;
                $message = [
                    'touser' => $openid,
                    'template_id' => $template_id,
                    'page' => $page,
                ];
                $data = [];
                if ($type == self::ORDER_PAY) {
                    $data = [
                        'character_string2' => ['value' => $order->order_sn],
                        'time1' => ['value' => $order->add_time],
                        'amount4' => ['value' => round($order->total_amount,2)],
                    ];
                }
                if ($type == self::ORDER_SHIPPING) {
                    $data = [
                        'thing4' => ['value' => $order->logistics_name],
                        'character_string5' => ['value' => $order->tracking_no],
                        'date3' => ['value' => $order->shipping_time],
                        'thing8' => ['value' => $order->consignee . ' ' . $order->mobile],
                        'character_string2' => ['value' => $order->order_sn],
                    ];
                }
                $message['data'] = $data;
                app(TigQueue::class)->push(MiniProgramJob::class, $message);
            }
        }

		// 发送后台消息
		$admin_msg = [
			'shop_id' => $order->shop_id,
			'order_id' => $order_id,
		];
		if ($type == self::NEW_ORDER) {
			$admin_msg['msg_type'] = AdminMsg::MSG_TYPE_ORDER_NEW;
			$admin_msg['title'] = "您有新的订单：{$order->order_sn}，金额：{$order->total_amount}";
			$admin_msg['content'] = "您有新的订单【{$order->order_sn}】，请注意查看";
			$admin_msg['related_data'] = ["order_id" => $order_id];
			app(AdminMsgService::class)->createMessage($admin_msg);
            if ($admin_msg['shop_id'] > 0) {
                //如果是店铺订单则给平台订单也发一条
                $admin_msg['shop_id'] = 0;
                app(AdminMsgService::class)->createMessage($admin_msg);
            }
		} elseif ($type == self::ORDER_PAY) {
			$admin_msg['msg_type'] = AdminMsg::MSG_TYPE_ORDER_PAY;
			$admin_msg['title'] = "您的订单已支付完成：{$order->order_sn}，金额：{$order->total_amount}";
			$admin_msg['content'] = "您有订单【{$order->order_sn}】已支付完成，请注意查看";
			$admin_msg['related_data'] = ["order_id" => $order_id];
			app(AdminMsgService::class)->createMessage($admin_msg);
            if ($admin_msg['shop_id'] > 0) {
                //如果是店铺订单则给平台订单也发一条
                $admin_msg['shop_id'] = 0;
                app(AdminMsgService::class)->createMessage($admin_msg);
            }
		}

        return true;
    }
}