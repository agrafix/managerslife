<?php
class Game_Character extends Controller_Game {

	protected $_use_scripts = array('game/game_character');

	protected $_use_tpl = 'game/game_character.html';

	public function show_Main() {

		$charImg = array();
		for ($i=1;$i<=HIGHEST_CHAR_IMG;$i++) {
			$charImg[] = array("id" => $i, "name" => ($i < 10 ? "0".$i : $i));
		}

		Framework::TPL()->assign('charImg', $charImg);
	}

}
?>