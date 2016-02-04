<?php
class Ajax_Object_casino_slotmachine extends Controller_AjaxGameObject {

	protected $myType = 'casino_slotmachine';

	protected static $slot = array(
		'bomb',
		'book',
		'box',
		'brick',
		'eye'/*,
		'hourglass',
		'key',
		'medal_gold_1',
		'pencil',
		'star',
		'user_suit'*/
	);

	public function init() {
		parent::init();

		mt_srand ((double) microtime(true) * 654321);
	}

	public function show_Interact() {
		$this->output('maintext', 'Willkommen an der Slotmachine. Wie viel möchtest
		du einsetzen?');

		$this->output('options', array(
			'play/1' => '10 {money} Einsatz: Auf eine Reihe setzen',
			'play/2' => '30 {money} Einsatz: Auf alle drei Reihen setzen',
			'play/3' => '50 {money} Einsatz: Auf alle drei Reihen und Diagonalen setzen'
		));
	}

	public function show_Play() {

		if ($this->get(1) == '1') {
			$this->user->cash -= 10;
		}
		elseif ($this->get(2) == '2') {
			$this->user->cash -= 30;
		}
		else {
			$this->user->cash -= 50;
		}

		if ($this->user->cash < 0) {
			$this->error('Du hast leider nicht genügend Geld!');
		}
		R::store($this->user);

		$slotField = array(0, 0, 0,
						   0, 0, 0,
						   0, 0, 0);

		$mark = array();

		$slotRows = array();

		$keys = array_keys(self::$slot);

		$slotRows[0] = $keys;
		$slotRows[1] = $keys;
		$slotRows[2] = $keys;

		shuffle($slotRows[0]);
		shuffle($slotRows[1]);
		shuffle($slotRows[2]);

		foreach ($slotField as $k => $v) {
			$slotField[$k] = array_pop($slotRows[($k+1)%3]);
		}

		$win = array();

		// check rows
		if ($this->get(1) >= 2
		&& ($slotField[0] == $slotField[1] && $slotField[1] == $slotField[2])) {
			array_push($mark, 0, 1, 2);
			array_push($win, array('type' => $slotField[0], 'text' => 'Reihe 1'));
		}

		if ($this->get(1) >= 1
		&& ($slotField[3] == $slotField[4] && $slotField[4] == $slotField[5])) {
			array_push($mark, 3, 4, 5);
			array_push($win, array('type' => $slotField[3], 'text' => 'Reihe 2'));
		}

		if ($this->get(1) >= 2
		&& ($slotField[6] == $slotField[7] && $slotField[7] == $slotField[8])) {
			array_push($mark, 6, 7, 8);
			array_push($win, array('type' => $slotField[6], 'text' => 'Reihe 3'));
		}

		// check diagonals
		if ($this->get(1) >= 3
		&& ($slotField[0] == $slotField[4] && $slotField[4] == $slotField[8])) {
			array_push($mark, 0, 4, 8);
			array_push($win, array('type' => $slotField[0], 'text' => 'Diagonal: Oben-Links nach Unten-Rechts'));
		}

		if ($this->get(1) >= 3
		&& ($slotField[6] == $slotField[4] && $slotField[4] == $slotField[2])) {
			array_push($mark, 6, 4, 2);
			array_push($win, array('type' => $slotField[6], 'text' => 'Diagonal: Unten-Links nach Oben-Rechts'));
		}

		// display
		$o = "<table class='ordered'><tr>";

		$i = 0;
		foreach ($slotField as $k => $f) {

			if ($i%3 == 0) {
				$o.= "</tr><tr>";
			}

			$o.= "<td ".(in_array($k, $mark) ? "style='background:#FFFB3A;'" : "")."><img src='".APP_DIR."static/images/icons/".self::$slot[$f].".png' alt='$f' /></td>";

			$i++;
		}

		$o .= "</tr></table>";

		// outcome
		if (count($win) == 0) {
			$o .= "<h3>Du verlierst deinen Einsatz!</h3>";
		} else {
			$o .= "<h3>Gewonnen!</h3><ul>";
			$total = 0;

			foreach ($win as $w) {
				$winMoney = floor(pow(1.25, ($w['type']+1)) * 250);
				$total += $winMoney;

				$o .= "<li><b>".$w['text'].":</b> <br />
				3x <img src='".APP_DIR."static/images/icons/".self::$slot[$w['type']].".png' alt='".$w['type']."' /> = ".formatCash($winMoney)." {money}</li>";
			}

			$o .= "</ul>";

			$o .= "<h3>Du hast ".formatCash($total)." {money} gewonnen!</h3>";

			$this->user->cash += $total;
			R::store($this->user);
		}

		$this->output('maintext', $o);

		$this->output('options', array('interact' => 'Zurück'));
	}

}
?>