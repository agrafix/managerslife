<?php
class Game_Index extends Controller_Game {

	protected $_use_scripts = array('poker_engine', 'game/game_poker');

	protected $_use_tpl = 'game/game_poker.html';

	public function init() {
		parent::init();

		// only works if you are in casino
		$ct = R::getCell('SELECT map FROM map_position WHERE user_id = ?',
		array($this->user->getID()));

		if ($ct != "casino") {
			Framework::redir("game/index");
			exit;
		}
	}

	public function show_Main() {
		$poker_player = R::relatedOne($this->user, 'poker_player');

		if ($poker_player == null) {
			$poker_player = R::dispense('poker_player');
			$poker_player->status = 'view';
			$poker_player->cards = '';
			$poker_player->bid = 0;
			$poker_player->all_in = false;
			$poker_player->all_in_amount = 0;

			R::store($poker_player);
			R::associate($poker_player, $this->user);
		}

		if ($poker_player->status == 'view') {
			Framework::TPL()->assign('can_join', true);
		}
	}

}
?>