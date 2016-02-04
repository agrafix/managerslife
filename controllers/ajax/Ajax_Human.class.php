<?php
/**
 * handle click on player
 */

class Ajax_Human extends Controller_AjaxGame {

	protected $selectedUser;

	protected $selectedUserPos;

	public function init() {
		parent::init();

		$id = $_POST["id"];

		// check if id is valid
		if (!is_numeric($id) || empty($id) || $id < 0) {
			$this->error('Ungültige Player-ID');
		}

		// load player
		$this->selectedUser = R::findOne('user', ' id = ?', array($id));

		if (!$this->selectedUser) {
			$this->error('Der Spieler konnte nicht gefunden werden');
		}

		// check if player is online
		if (!$this->selectedUser->isOnline()) {
			$this->error('Der Spieler ist nicht online');
		}


		// load pos
		$userPos = R::findOne('map_position', ' user_id = ?', array($this->user->getID()));
		$this->selectedUserPos = R::findOne('map_position', ' user_id = ?', array($this->selectedUser->getID()));

		// is to far away?
		$dist = sqrt(pow($this->selectedUserPos->x - $userPos->x, 2) + pow($this->selectedUserPos->y - $userPos->y, 2));

		if ($dist > 5) {
			$this->error('Du bist zu weit entfehrt um mit '.$this->selectedUser->username.' zu interagieren');
		}
	}

	public function show_Main() {
		$this->error('Ungültige Aktion');
	}

	public function show_Interact() {
		$this->output('maintext', 'Was möchtest du tun?');

		$this->output('options', array(
			'give' => "Diesem Spieler ein Item geben",
			'giveCash' => "Diesem Spieler Geld geben",
			'privateMsg' => "Diesem Spieler etwas privat sagen"
		));
	}

	public function show_Give() {
		if ($this->get(1) != "" && is_numeric($this->get(1))) {
			$id = $this->get(1);

			$inv = R::findOne('inventory', 'id = ? AND user_id = ?',
			array($id, $this->user->getID()));

			if (!$inv) {
				$this->error('Item nicht gefunden');
			}

			$amount = (isset($_POST["amount"]) && is_numeric($_POST["amount"]) ? $_POST["amount"] : 0);

			if ($amount == 0 || $amount > $inv->amount) {

				$this->output('maintext', 'Wie oft möchtest du '.htmlspecialchars($inv->item->name).' an
				'.htmlspecialchars($this->selectedUser->username).' geben?');

				$this->output('form', array(
					'target' => 'give/'.$id,

					'elements' => array(
						array(
							'desc' => "Menge",
							'name' => "amount",
							'value' => $inv->amount,
							'type' => 'text'
						)
					)
				));

				$this->output('options', array(
											'give' => "Zurück"
				));
				return;

			} else {

				$newInv = R::dispense('inventory');
				$newInv->amount = $amount;
				$newInv->param = $inv->param;
				$newInv->item = $inv->item;
				$newInv->user = $this->selectedUser;

				$inv->amount -= $amount;

				R::$adapter->startTransaction();

				R::store($newInv);
				R::store($inv);

				R::$adapter->commit();

				$this->output('maintext', 'Du hast '.$amount.'x '.htmlspecialchars($inv->item->name).' an
								'.htmlspecialchars($this->selectedUser->username).' gegeben.');

				$this->systemChat($this->user->username.' hat dir '.$amount.'x '.htmlspecialchars($inv->item->name).' gegeben',
				$this->selectedUserPos->map,
				$this->selectedUser);

				$this->output('options', array(
											'interact' => "Zurück"
				));
				return;
			}
		}

		$this->output('maintext', 'Was möchtest du '.htmlspecialchars($this->selectedUser->username).' geben?');

		$o = array();

		$itms = R::find('inventory', 'user_id = ?', array($this->user->getID()));
		foreach ($itms as $inventory) {
			$o['give/'.$inventory->getID()] = $inventory->amount.'x '.$inventory->item->name;
		}

		$o['interact'] = "Zurück";
		$this->output('options', $o);

	}

	public function show_PrivateMsg() {
		$this->output('maintext', 'Um mit einem Spieler privat zu schreiben, musst du das
		<b>/private</b>-Kommando im Chat-Fenster benutzen. Angenommen du möchtest
		'.htmlspecialchars($this->selectedUser->username).' <i>Hallo</i> schreiben, ist folgender
		Befehl nötig: <br />
		<br /><i>/private "'.htmlspecialchars($this->selectedUser->username).'" Hallo</i> <br />
		<br />
		Das tippst Du wie gewohnt in das Chat-Feld ein und bestätigst das dann mit [ENTER].
		Natürlich bleibt der <i>/private "'.htmlspecialchars($this->selectedUser->username).'"</i>
		Teil nach dem Abschicken der Nachricht stehen, sodass du ihn nicht jedesmal erneut eingeben musst.');

		$this->output('options', array(
					'interact' => "Zurück"
		));
	}

	public function show_GiveCash() {
		if (isset($_POST["amount"]) && is_numeric($_POST["amount"]) && $_POST["amount"] > 0) {
			$a = floor($_POST["amount"]);

			if ($a <= $this->user->cash) {

				R::$adapter->startTransaction();

				$this->user->cash -= $a;
				$this->selectedUser->cash += $a;

				R::store($this->user);
				R::store($this->selectedUser);

				R::$adapter->commit();

				$this->systemChat($this->user->username." hat dir ".$a." {money} gegeben",
								  $this->selectedUserPos->map,
								  $this->selectedUser);

				$this->output('maintext', 'Du hast '.htmlspecialchars($this->selectedUser->username).'
				'.htmlspecialchars($a).' {money} gegeben.');
				$this->output('options', array(
											'interact' => "Zurück"
				));
				return;
			} else {
				$this->output('maintext', 'Du hast nicht '.htmlspecialchars($a).' {money}.');
				$this->output('options', array(
							'giveCash' => "Zurück"
				));
				return;
			}
		}

		$this->output('maintext', 'Wie viel Geld möchtest du '.$this->selectedUser->username.' geben?');

		$this->output('form', array(
			'target' => 'giveCash',

			'elements' => array(
				array(
					'name' => 'amount',
					'desc' => 'Menge',
					'type' => 'text',
					'value' => 0
				)
			)
		));

		$this->output('options', array(
			'interact' => "Zurück"
		));
	}

}
?>