<?php
class Site_Index extends Controller_Site {

	protected $_use_tpl = 'site/site_home.html';

	protected $_use_scripts = array('site/site_login');

	public function show_Main() {

		if ($this->get(1) == "login") {
			Framework::TPL()->assign('first_login', true);
		} else {
			Framework::TPL()->assign('first_login', false);
		}

		$session_expired = false;

		if ($this->get(1) == "session_expired") {
			$session_expired = true;
		}

		Framework::TPL()->assign('session_expired', $session_expired);

		$count = R::getCell('select count(id) from user');

		Framework::TPL()->assign("playercount", $count);

		// assign news
		$news = array();

		$dbNews = R::find('homepage_posts', ' 1=1 ORDER BY id DESC LIMIT 3');

		foreach ($dbNews as $n) {
			$author = R::relatedOne($n, 'user');

			$news[] = array('id' => $n->id,
							'title' => $n->title,
							'author' => $author->username,
							'time' => $n->time);
		}

		Framework::TPL()->assign("news", $news);
	}

}
?>