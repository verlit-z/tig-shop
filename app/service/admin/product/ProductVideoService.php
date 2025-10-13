<?php

namespace app\service\admin\product;

use app\model\product\ProductVideo;
use app\service\common\BaseService;

class ProductVideoService extends BaseService
{
    public function __construct()
    {
    }

    /**
     * 获取商品相册
     *
     * @param array $filter
     * @return array
     */
    public function getProductVideoList(int $product_id): array
    {
        $result = ProductVideo::where('product_id', $product_id)->order('video_id ASC')->select();
        return $result->toArray();
    }

    /**
     * 更新商品视频
     * @param int $product_id
     * @param array $video_list
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateProductVideo(int $product_id, array $video_list = [], $is_update = true): bool
    {
        //删除记录
        if (empty($product_id)) {
            return false;
        }

        if($is_update && empty($video_list)) {
            ProductVideo::where('product_id', $product_id)->delete();
            return false;
        }

        if(!$is_update && empty($video_list)) {
            return false;
        }

        $video_ids = [];
        foreach ($video_list as $value) {
            if (isset($value['product_id']) && $value['product_id'] > 0 && $value['id'] > 0) {
                $update_data = [
                    'video_id' => $value['id'],
                    'video_url' => $value['video_url'] ?? '',
                    'product_id' => $product_id,
                    'video_cover' => $value['video_cover'],
                    'format' => $value['format'],
                ];
                ProductVideo::where('video_id', $value['video_id'])
                    ->where('product_id', $product_id)
                    ->save($update_data);
                //array_push($video_ids, $value['video_id']);
            } else {
                $update_data = [
                    'video_id' => $value['id'],
                    'video_url' => $value['video_url'],
                    'product_id' => $product_id,
                    'video_cover' => $value['video_cover'],
                    'format' => $value['format'],
                ];
                $video_id = ProductVideo::insertGetId($update_data);
                //array_push($video_ids, $video_id);
            }
        }
        //更新商品所属视频
        //ProductVideo::where([['product_id', '=', $product_id], ['id', 'NOT IN', $video_ids]])->delete();
        return true;
    }

    /**
     * 复制视频
     * @param int $product_id
     * @param array $video_list
     * @return bool
     */
    public function copyProductVideo(int $product_id, array $video_list = []): bool
    {
        //删除记录
        if (empty($product_id) || empty($video_list)) {
            return false;
        }

        foreach ($video_list as $key => $value) {
            $update_data = [
                'video_id' => $value['id'],
                'video_url' => $value['video_url'],
                'product_id' => $product_id,
                'video_cover' => $value['video_cover'],
                'format' => $value['format'],
            ];
            ProductVideo::insertGetId($update_data);
        }

        return true;
    }

}