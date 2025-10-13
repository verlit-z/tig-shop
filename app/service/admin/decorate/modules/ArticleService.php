<?php

namespace app\service\admin\decorate\modules;

use app\service\common\BaseService;

/**
 * 文章模块
 */
class ArticleService extends BaseService
{
	public function formatData(array $module, array | null $params = null): array
	{
		$article = [];
		if (!empty($module)) {
			$page = isset($params['page']) && $params['page'] > 0 ? $params['page'] : 1;
			$size = isset($params['size']) && $params['size'] > 0 ? $params['size'] : 10;
			if (isset($module['article_num']) && $size > $module['article_num']) {
				$size = $module['article_num'];
			}

			$filter = [
				'page' => $page,
				'size' => $size,
				'is_show' => 1
			];

			if (!empty($module['article_category_id'])) {
				$filter['article_category_id'] = $module['article_category_id'];
				$filter['sort_field'] = "article_id";
				$filter['sort_order'] = "desc";
				if ($page > 1 && $page * $size > $module['article_num']){
					$page_limit = ceil($module['article_num'] / $size);
					if ($page > $page_limit) {
						$article = [];
					} else {
						$final_size = min($size, $module['article_num'] - ($page - 1) * $size);
						$article = app(\app\service\admin\content\ArticleService::class)->getFilterResult($filter);
						$article =  array_slice($article, 0, $final_size);
					}
				} else {
					$article = app(\app\service\admin\content\ArticleService::class)->getFilterResult($filter);
				}
			}
		}
		$module['article_list'] = $article;
		return $module;
	}
}