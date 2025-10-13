<?php

namespace app\model\setting;

use think\Model;

class GalleryVideoInfo extends Model
{
    protected $pk = 'id';
    protected $table = 'gallery_video_info';
    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;
}