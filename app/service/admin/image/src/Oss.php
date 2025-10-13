<?php

namespace app\service\admin\image\src;

use app\service\admin\setting\ConfigService;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Exception;
use think\File\UploadedFile;
use utils\Config as UtilsConfig;

class Oss
{
    protected object $ossClient;
    protected string $bucket;
    protected object|string $image;
    protected string $orgPath; //源文件
    protected string $filePath; //如 img/item/202301/example.jpg
    protected bool $watermark = false;
    protected string|null $url = null;

    public function __construct()
    {
        $accessKeyId = UtilsConfig::get('storageOssAccessKeyId');
        $accessKeySecret = UtilsConfig::get('storageOssAccessKeySecret');
        $bucket = UtilsConfig::get('storageOssBucket');
        $endpoint = UtilsConfig::get('storageOssRegion');
        if (empty($accessKeyId) || empty($accessKeySecret) || empty($endpoint) || empty($bucket)) {
            throw new Exception("OSS参数设置错误！");
        }
        $this->bucket = $bucket;
        $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    }

    /**
     * 设置文件
     * @param UploadedFile|string $image
     * @return void
     */
    public function setImage(UploadedFile|string $image): void
    {
        $this->image = $image;
    }

    /**
     * 设置源文件地址
     * @param $orgPath
     * @return void
     */
    public function setOrgPath($orgPath): void
    {
        $this->orgPath = $orgPath;
    }

    /**
     * 设置文件地址
     * @param $filePath
     * @return void
     */
    public function setFilePath($filePath): void
    {
        $this->filePath = $filePath;
    }

    /**
     * 保存图片
     * @return string
     * @throws Exception
     */
    public function save(): string
    {
        try {
            if (is_string($this->image)) {
                //替换oss链接地址
                $storage_url = UtilsConfig::get('storageOssUrl');
                $orgPath = str_replace($storage_url, '', $this->orgPath);
                $this->ossClient->copyObject($this->bucket, $orgPath, $this->bucket, $this->filePath);
            } else {
                $this->ossClient->uploadFile($this->bucket, $this->filePath, $this->orgPath);
            }
        } catch (OssException $e) {
            throw new Exception('上传图片失败:' . $e->getMessage());
        }

        $this->url = $this->filePath;
        $upload_save_full_domain = UtilsConfig::get('uploadSaveFullDomain');
        if ($upload_save_full_domain) {
            $storage_url = !empty(UtilsConfig::get('storageOssUrl')) ?
                UtilsConfig::get('storageOssUrl'): 'base_api_storage';
            return $storage_url . $this->filePath;
        }

        return $this->changeFilePath();
    }

    /**
     * 转换返回文件路径
     * @return string
     */
    public function changeFilePath(): string
    {
        $storage_url = !empty(UtilsConfig::get('storageOssUrl')) ?
            UtilsConfig::get('storageOssUrl') : 'base_api_storage';
        return $storage_url . $this->filePath;
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
        if (!$this->url) {
            $this->save();
        }
        $width = $width > 0 ? ',h_' . $width : '';
        $height = $height > 0 ? ',h_' . $height : '';
        $filePath = $this->changeFilePath();
        return $filePath . '?x-oss-process=image/resize,m_pad' . $width . $height;
    }

    /**
     * 获取缩略图地址
     * @param string $imageUrl
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getThumb(string $imageUrl, int $width = 0, int $height = 0): string
    {
        $width = $width > 0 ? ',h_' . $width : '';
        $height = $height > 0 ? ',h_' . $height : '';
        return $imageUrl . '?x-oss-process=image/resize,m_pad' . $width . $height;
    }

    /**
     * 获取url地址
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

}
