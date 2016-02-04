<?php
class Ajax_Item_npc_creator extends Controller_AjaxGameItem {

	protected $myType = "npc_creator";

	protected $_rights = array('npc_editor');

	public function show_Make() {
		$npc = R::dispense('map_npc');

		$npc->import($_POST, 'type,name,y,x,map,characterImage,lookDirection');

		if (isset($_POST['can_walk']) && $_POST['can_walk'] == 1) {
			$npc->can_walk = 1;
		} else {
			$npc->can_walk = 0;
		}

		try {
			R::store($npc);
		} catch (Exception $e) {
			$this->output('maintext', 'Fehler: '.$e->getMessage());
			return;
		}

		$this->output('maintext', 'NPC gespeichert');

		$this->output('options', array('use' => 'Zurück'));
	}

	public function show_Use() {
		$this->output('maintext', "Hier kannst du ein NPC erstellen.");

		$userPos = R::findOne('map_position', ' user_id = ?', array($this->user->getID()));

		$charImg = array();
		for ($i=1;$i<=HIGHEST_CHAR_IMG;$i++) {
			$charImg[$i] = $i;
		}

		$this->output('form', array(
			'target' => 'make',
			'elements' => array(
				array('name' => 'name', 'desc' => 'Name', 'type' => 'text'),
				array('name' => 'type', 'desc' => 'Typ', 'type' => 'text'),
				array('name' => 'x', 'desc' => 'X', 'type' => 'text', 'value' => $userPos->x),
				array('name' => 'y', 'desc' => 'Y', 'type' => 'text', 'value' => $userPos->y),
				array('name' => 'map', 'desc' => 'Karte', 'type' => 'select', 'options' => array(
					'main' => 'Main',
					'main2' => 'Main2',
					'main2' => 'Main3',
					'supermarket' => 'Supermarkt',
					'livinghouse' => 'Wohnhaus',
					'adminhouse' => 'Adminhaus',
					'businesscenter' => 'BusinessCenter',
					'tradingcenter' => 'TradingCenter',
					'casino' => 'Casino'
				),
				'value' => $userPos->map),
				array('name' => 'can_walk', 'desc' => 'Kann rumlaufen?', 'type' => 'checkbox', 'value' => 1),
				array('name' => 'characterImage', 'desc' => 'Bild', 'type' => 'select', 'options' => $charImg),
				array('name' => 'lookDirection', 'desc' => 'Blickrichtung', 'type' => 'select', 'options' => array(
					'0' => 'Hoch',
					'1' => 'Rechts',
					'2' => 'Runter',
					'3' => 'Links'
				))
			)
		));
	}

}
?>