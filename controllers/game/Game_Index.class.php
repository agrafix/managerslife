<?php
class Game_Index extends Controller_Game {

	protected $_use_scripts = array('game_maps', 'game_engine', 'game/game_main');

	protected $_use_tpl = 'game/game_map.html';

	public function show_Main() {

		// company
		/*$company = R::dispense('company');
		$company->name = 'symbol GmBH';
		$company->user = $this->user;
		$company->size = 200;
		$company->balance = 10000;
		R::store($company);*/

		// rights
		/*$right = R::dispense('user_right');
		$right->type = 'npc_editor';
		R::store($right);

		$right = R::dispense('user_right');
		$right->type = 'right_editor';
		R::store($right);*/

		// npcs
		/*
		$npc = R::dispense('map_npc');
		$npc->type = 'bank_assistant';
		$npc->name = 'Rosa';
		$npc->x = 3;
		$npc->y = 10;
		$npc->map = 'bank';
		$npc->can_walk = false;
		$npc->lookDirection = 1;
		$npc->characterImage = 8;
		R::store($npc);*/

		/*
		allow npc to sell stuff
		$item = R::load('item', 2);
		$item->sharedSold_by = R::find('map_npc', ' type = ?', array('supermarket_cashier'));
		R::store($item);*/

		// items
		/*
		$item = R::dispense('item');
		$item->type = 'teleporter';
		$item->usable = true;
		$item->can_carry = true;
		$item->name = 'Teleporter';
		$item->desc = 'Mit diesem Item kannst du dich quer durch die Welt teleportieren';
		$item->value = 1000000000;
		R::store($item);

		$item = R::dispense('item');
		$item->type = 'trash';
		$item->usable = false;
		$item->can_carry = false;
		$item->name = 'Schrott';
		$item->desc = 'Ein wertloser Schrotthaufen.';
		$item->value = 10;
		R::store($item);
		*/

		// inventory
		/*
		$inventory = R::dispense('inventory');
		$inventory->user = $this->user;
		$inventory->item = R::findOne('item', ' type = ?', array('trash'));
		$inventory->amount = 2;
		$inventory->param = "no_param";
		R::store($inventory);
		*/

		// do this at registration >.<
		if ($this->user->characterImage == null) {
			$this->user->characterImage = 1;
			R::store($this->user);
		}

		// if the player logs in for the first time, position him on the map & give him some cash
		$playerPos = R::findOne('map_position', ' user_id = ?', array($this->user->getId()));

		if (!$playerPos) {
			$playerPos = R::dispense('map_position');
			$playerPos->x = GAME_START_POSX;
			$playerPos->y = GAME_START_POSY;
			$playerPos->map = GAME_START_MAP;
			$playerPos->user = $this->session->user;
			R::store($playerPos);

			$this->user->cash = START_CASH;
			R::store($this->user);
		}

		Framework::TPL()->assign('x', $playerPos->x);
		Framework::TPL()->assign('y', $playerPos->y);
		Framework::TPL()->assign('map', $playerPos->map);
		Framework::TPL()->assign('characterImage', ($this->user->characterImage < 10 ? "0".$this->user->characterImage : $this->user->characterImage));

		// if player logs in for the first time, give him some premium feeling
		if (!R::findOne('user_premium', ' user_id = ?', array($this->user->getId()))) {
			$premium = R::dispense('user_premium');
			$premium->points = GAME_START_PREMIUM_PTS;
			$premium->until = GAME_START_PREMIUM_UNTIL;
			$premium->user = $this->session->user;
			$premium->auto = 0;
			R::store($premium);
		}

		// if player doesnt have a quest, give him one
		$quest = R::relatedOne($this->user, 'quests_npc', ' accepted = 0 AND complete_time = 0');

		if ($quest == null) {
			$allQuests = Config::getConfig('npc_quests');
			$rnd = array_rand($allQuests, 1);

			$quest = R::dispense('quests_npc');
			$quest->giveNewQuest($this->user);
		}
	}

}
?>