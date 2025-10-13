<?php
//**---------------------------------------------------------------------+
//** 模型文件 -- 对账单
//**---------------------------------------------------------------------+
//** 版权所有：江西佰商科技有限公司. 官网：https://www.tigshop.com
//**---------------------------------------------------------------------+
//** 作者：Tigshop团队，yq@tigshop.com
//**---------------------------------------------------------------------+
//** 提示：Tigshop商城系统为非免费商用系统，未经授权，严禁使用、修改、发布
//**---------------------------------------------------------------------+

namespace app\model\finance;

use think\Model;

class StatementDownload extends Model
{
    protected $pk = 'statement_download_id';
    protected $table = 'statement_download';
    protected $createTime = 'gmt_create';
    protected $autoWriteTimestamp = true;

}
