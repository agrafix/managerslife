<?php
class Ajax_Item_teleporter extends Controller_AjaxGameItem {

	protected $myType = "teleporter";

	public function show_Teleport() {
		$x = $_POST["x"];
		$y = $_POST["y"];
		$map = $_POST["map"];

		$userPos = R::findOne('map_position', ' user_id = ?', array($this->user->getID()));

		if (is_numeric($x) && is_numeric($y) && $x > 0 && $y > 0 && $x <= 22 && $y <= 16) {
			$userPos->x = $x;
			$userPos->y = $y;
			$userPos->map = $map;
			R::store($userPos);

			$this->output('maintext', 'Teleportation abgeschlossen.');
		} else {
			$this->output('maintext', 'Fehler. Ungültige Koordinaten');
		}
	}

	public function show_Use() {
		$userPos = R::findOne('map_position', ' user_id = ?', array($this->user->getID()));

		$this->output('maintext', 'Dieser Teleporter ist ein Admin-Item. Damit kannst du dich durch
		die Gegend teleportieren');

		$this->output('form', array(
			'target' => 'teleport',
			'elements' => array(
				array(
					'desc' => 'X',
					'name' => 'x',
					'type' => 'text'
				),
				array(
					'desc' => 'Y',
					'name' => 'y',
					'type' => 'text'
				),
				array('name' => 'map', 'desc' => 'Karte', 'type' => 'select', 'options' => array(
					'main' => 'Main',
					'main2' => 'Main2',
					'main3' => 'Main3',
					'supermarket' => 'Supermarkt',
					'livinghouse' => 'Wohnhaus'
				),
				'value' => $userPos->map),
			)
		));
		//$this->output('options', array('use' => 'Zurück'));
	}
}
?>