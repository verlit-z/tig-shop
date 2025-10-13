<?php

namespace app\model\setting;

use think\Model;

class GalleryVideo extends Model
{
    protected $pk = 'id';
    protected $table = 'gallery_video';

    protected $createTime = "add_time";
    protected $autoWriteTimestamp = true;
}