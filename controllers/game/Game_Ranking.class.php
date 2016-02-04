<?php
class Game_Ranking extends Controller_Game {

	protected $_use_scripts = array("game/game_ranking");

	protected $_use_tpl = 'game/game_ranking.html';

	public function show_Main() {
		$usersPerPage = 20;
		$totalUsers = R::getCell('SELECT count(id) FROM user');
		$pages = ceil($totalUsers / $usersPerPage);

		$currentPage = (is_numeric($this->get(1)) && $this->get(1) > 0 && $this->get(1) <= $pages ? $this->get(1) : 1);

		$players = array();

		$dbP = R::find('user', ' 1=1 ORDER BY xp DESC LIMIT ?,?',
		array(($currentPage-1)*$usersPerPage, $usersPerPage));

		$i = ($usersPerPage * ($currentPage - 1)) + 1;

		foreach ($dbP as $p) {
			$players[] = array("rank" => $i, "username" => $p->username, "level" => $p->level,
			"xp" => formatCash($p->xp), "premium" => $p->hasPremium());
			$i++;
		}

		Framework::TPL()->assign('players', $players);
		Framework::TPL()->assign('currentPage', $currentPage);
		Framework::TPL()->assign('pages', $pages);
	}
}
?>