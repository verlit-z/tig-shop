<?php

namespace app\service\admin\promotion;

use app\model\promotion\WechatLive;
use app\service\admin\image\Image;
use app\service\common\BaseService;
use exceptions\ApiException;
use log\AdminLog;
use tig\Http;
use utils\Config;
use utils\Time;

class WechatLiveService extends BaseService
{
	/**
	 * 过滤查询
	 * @param array $filter
	 * @return object|\think\db\BaseQuery
	 */
	protected function filterQuery(array $filter): object
	{
		$query = WechatLive::query();

		if (isset($filter['wechat_live_title']) && !empty($filter['wechat_live_title'])) {
			$query->whereLike('wechat_live_title', '%' . $filter['wechat_live_title'] . '%');
		}

		if (isset($filter['shop_id']) && $filter['shop_id'] > -1) {
			$query->where('shop_id', $filter['shop_id']);
		}

		if (isset($filter['live_status']) && !empty($filter['live_status'])) {
			$filter['live_status'] = is_array($filter['live_status']) ? $filter['live_status'] : explode(',', $filter['live_status']);
			$query->whereIn('live_status', $filter['live_status']);
		}

		return $query;
	}

	/**
	 * 直播详情
	 * @param $id
	 * @return WechatLive
	 */
	public function getDetail($id):WechatLive
	{
		$wechatLive = WechatLive::find($id);
		if (empty($wechatLive)) {
			throw new ApiException(/** LANG */'直播不存在');
		}
		return $wechatLive;
	}

	/**
	 * 更新直播
	 * @param int $id
	 * @param array $data
	 * @return bool
	 * @throws \Exception
	 */
	public function updateWechatLive(int $id,array $data): bool
	{
		$wechatLive = $this->getDetail($id);
		return $wechatLive->save($data);
	}

	/**
	 * api 更新直播间
	 * @return bool
	 * @throws ApiException
	 * @throws \think\Exception
	 */
	public function refreshByApi(int $shop_id):bool
	{
        $appid = Config::get('wechatMiniProgramAppId');
        $secret = Config::get('wechatMiniProgramSecret');

		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret;

		$res = Http::post($url);
		$res = json_decode($res,true);

		if (!isset($res['access_token'])) {
			throw new ApiException($res['errmsg']);
		}

		$access_token = $res['access_token'];

		$url = 'https://api.weixin.qq.com/wxa/business/getliveinfo?access_token=' . $access_token;

		$data = [
			'start' => 0,
			'limit' => 10
		];

		$data = json_encode($data);
		$res = Http::post($url,$data);

		ini_set('memory_limit','-1');
		$res = json_decode($res,true);

		if (empty($res['room_info'])) {
			throw new ApiException('获取直播间信息失败');
		}

		$wechat_live_arr = [];
		foreach ($res['room_info'] as $key => $row) {
			$wechat_live_arr = [
				'wechat_live_title' => $row['name'],
				'cover_img' => $row['cover_img'],
				'start_time' => $row['start_time'],
				'end_time' => $row['end_time'],
				'anchor_name' => $row['anchor_name'],
				'anchor_img' => $row['anchor_img'],
				'room_id' => $row['roomid'],
				'live_status' => $row['live_status'],
				'share_img' => $row['share_img'],
				'last_update_time' => Time::now(),
				'product_data' => json_encode($row['goods'])
			];
			if($row['cover_img']){
				$path = public_path() . 'img/live/' . md5($row['cover_img']) . '.png';
				$image = new Image($row['cover_img'],$path);
				$image->save();
				//缩略图
				$thumb_img = $image->makeThumb(500,500);
				$wechat_live_arr['thumb_img'] = $thumb_img;
			}

			$wechat_live = WechatLive::where("room_id",$wechat_live_arr["room_id"])->find();
			if (!empty($wechat_live)) {
				$result = $wechat_live->save($wechat_live_arr);
			} else {
				$wechat_live_arr['shop_id'] = $shop_id;
				$wechat_live_arr['live_sn'] = md5($row['roomid'] . Time::now());
				$result =  WechatLive::create($wechat_live_arr);
			}
		}
		AdminLog::add('更新直播间:' . $wechat_live_arr['wechat_live_title']);
		return $result != false;
	}
}