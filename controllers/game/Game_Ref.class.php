<?php
class Game_Ref extends Controller_Game {

	protected $_use_scripts = array();

	protected $_use_tpl = 'game/game_ref.html';

	public function show_Main() {
		$link = APP_WEBSITE.APP_DIR."site/register/main/".$this->user->getID();

		Framework::TPL()->assign('ref_link', $link);
		Framework::TPL()->assign('refed_players', R::getAll('SELECT username, referee_awarded FROM user WHERE referee_id = ?', array($this->user->getID())));
	}

}
?>