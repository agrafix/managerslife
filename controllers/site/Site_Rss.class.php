<?php
class Site_Rss extends Controller {

	public function show_Main() {

	}

	public function show_News() {
		header("Content-Type: application/rss+xml; charset=UTF-8");

		$tpl = new Smarty();
		$tpl->cache_lifetime = 3600 * 24;
		$tpl->setCaching(Smarty::CACHING_LIFETIME_CURRENT);

		$cacheID = 'rssFeed';

		if (!$tpl->isCached('rss.xml', $cacheID)) {

			$items = array();

			$dbItems = R::find('homepage_posts', ' 1=1 ORDER BY id DESC LIMIT 15');

			foreach ($dbItems as $item) {
				$author = R::relatedOne($item, 'user');

				$items[] = array(
					'title' => $item->title,
					'link' => ($item->link == '' ? APP_WEBSITE.APP_DIR : $item->link),
					'description' => $item->content,
					'author' => $author->username,
					'date' => date("D, d M Y H:i:s O", $item->time)
				);
			}

			$tpl->assign('items', $items);
			$tpl->assign('gentime', date("D, d M Y H:i:s O"));
			$tpl->assign('link', APP_WEBSITE.APP_DIR);

		}

		$tpl->display('rss.xml', $cacheID);
	}
}
?>