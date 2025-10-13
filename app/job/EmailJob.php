<?php

namespace app\job;

use utils\Util;

class EmailJob extends BaseJob
{
	/**
	 * 发送邮件队列
	 * @param $data
	 * @return bool
	 */
	public function doJob($data): bool
	{
		try {
			if (empty($data['email'])) return false;
			Util::sendEmail($data);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
}