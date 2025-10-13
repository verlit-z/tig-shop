<?php

namespace app\service\admin\image;

use app\service\admin\image\src\Cos;
use app\service\admin\image\src\Local;
use app\service\admin\image\src\Oss;
use exceptions\ApiException;
use think\Exception;
use think\File\UploadedFile;
use utils\Config;
use utils\Time;

/**
 * Class Image
 */
class Image
{
    protected object $storageClass;
    protected string $orgPath;
    protected string $filePath;
    protected object|string $image;
    public string $orgName;
    //限制类型
    protected array $limit_ext;

    protected $image_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tif'];
    protected $video_ext = ['mp4'];

    public function __construct(UploadedFile|string $image = '', $nodePathName = 'upload', $rootPathName = 'img')
    {
        $storage_type = Config::get('storageType');
        //检测后台是否有配置对应域名
        $this->checkStorageUrlByType();
        switch ($storage_type) {
            case 0:
                //本地上传
                $this->storageClass = app(Local::class);
                break;
            case 1:
                //Oss上传
                $this->storageClass = app(Oss::class);
                break;
            case 2:
                //Cos上传
                $this->storageClass = app(Cos::class);
                break;
        }
        if($rootPathName == 'video') {
            $this->limit_ext = $this->video_ext;
        } elseif($rootPathName == 'img') {
            $this->limit_ext = $this->image_ext;
        }

        // 从请求中获取上传的图片或视频
        if ($image instanceof UploadedFile) {
            $extension = $image->getOriginalExtension();
            if (!empty($this->limit_ext) && !in_array($extension, $this->limit_ext)) {
                throw new ApiException('上传文件类型错误');
            }
            $this->orgName = pathinfo($image->getOriginalName(), PATHINFO_FILENAME);
            $this->storageClass->setOrgPath($image->getRealPath());
        } // 如果传入的是图片地址
        elseif (is_string($image)) {
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            if (!in_array($extension, $this->limit_ext) && !empty($image)) {
                throw new ApiException('上传文件类型错误');
            }
            $this->orgName = pathinfo($image, PATHINFO_FILENAME);
            $this->storageClass->setOrgPath($image);
        }
        // 过滤特殊字符
        $this->orgName = preg_replace('/[^\x{4E00}-\x{9FFF}a-zA-Z0-9_\.]/u', '', strip_tags($this->orgName));

        $filePath = $rootPathName . '/' . $nodePathName . '/' . date('Ym') . '/' . $this->randomFileName() . '.' . $extension;

        $this->storageClass->setImage($image);
        $this->storageClass->setFilePath($filePath);
    }

    /**
     * 获取一个不重复的名称
     * @return string
     */
    protected function randomFileName(): string
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $str = '';
        for ($i = 0; $i < 18; $i++) {
            $str .= $pattern[mt_rand(0, strlen($pattern) - 1)];
        }
        return Time::now() . $str;
    }

    /**
     * 保存图片
     * @return string
     * @throws Exception
     */
    public function save(): string
    {
        return $this->storageClass->save();
    }

    /**
     * 创建缩略图
     * @param int $width
     * @param int $height
     * @return string
     * @throws Exception
     */
    public function makeThumb(int $width = 0, int $height = 0): string
    {
        return $this->storageClass->makeThumb($width, $height);
    }

    /**
     * 获取缩略图
     * @param string $imageUrl
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getThumb(string $imageUrl, int $width = 0, int $height = 0): string
    {
        return $this->storageClass->getThumb($imageUrl, $width, $height);
    }

    /**
     * 获取url
     * @return string
     */
    public function getUrl(): string
    {
        return $this->storageClass->getUrl();
    }

    public function getStorageUrl(): string|null
    {
        $storage_type = Config::get('storageType');
        $url = '';
        switch ($storage_type) {
            case 0:
                $url = Config::get('storageLocalUrl');
                $url = $url ?? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . "/";
                break;
            case 1:
                $url = Config::get('storageOssUrl');
                break;
            case 2:
                $url = Config::get('storageCosUrl');
        }
        return $url;
    }

    /**
     * 检测后台是否配置存储地址
     * @return bool
     * @throws ApiException
     */
    public function checkStorageUrlByType(): bool
    {
        $storage = Config::getConfig();
        $msg = '请先在后台【设置>系统设置>商城设置>接口设置>存储设置】选择';
        switch ($storage['storageType']) {
            case 0:
                if (empty($storage['storageLocalUrl'])) {
                    throw new ApiException($msg.'【本地存储】，然后按照【参考格式】填写【图片访问域名】');
                }
            break;
        }
        return true;
    }
}
