<?php
class Ajax_Bank_item extends Controller_AjaxGameNPC {

	protected $myType = 'bank_item';

	public function show_Interact() {
		$this->output('maintext', 'Guten Tag <b>'.htmlspecialchars($this->user->username).'</b>! Hier
			kannst du Items zur Lagerung abgeben.!');

		$this->output('speak', 'Hallo!');

		$this->output('options', array('storage' => 'Gelagerte Items anzeigen/abholen',
			'store' => 'Item abgeben'));
	}

	private function MoveItem($id, $direction) {

		$take = ($direction == "take" ? "bank_locker" : "inventory");
		$put = ($direction == "take" ? "inventory" : "bank_locker");
		$action = ($direction == "take" ? "storage" : "store");

		$item = R::findOne($take, ' user_id = ? AND id = ?', array($this->user->getID(), $id));

		if (!$item) {
			$this->output('maintext', 'Ungültiges Item angegeben!');
			return;
		}

		$amount = (isset($_POST["amount"]) && is_numeric($_POST["amount"]) ? $_POST["amount"] : -1);

		if ($item->amount > 1 && ($amount == -1 || $amount > $item->amount)) {
			$this->output('maintext', 'Wie oft möchtest du '.htmlspecialchars($item->item->name).'
			'.($direction == "take" ? "mitnehmen" : "abgeben").'?');

			$this->output('form', array(
							'target' => $action.'/'.$item->id,

							'elements' => array(
			array('desc' => 'Menge', 'type' => 'text', 'name' => 'amount', 'value' => $item->amount)
			)
			));
			return;
		}

		if ($amount == -1 || $amount > $item->amount) {
			$amount = 1;
		}


		$locker = R::dispense($put);
		$locker->user = $this->user;
		$locker->item = $item->item;
		$locker->amount = $amount;
		$locker->param = $item->param;

		R::$adapter->startTransaction();

		R::store($locker);

		$item->amount -= $amount;

		if ($item->amount == 0) {
			R::trash($item);
		} else {
			R::store($item);
		}

		R::$adapter->commit();

		$this->output('maintext', 'Du hast das Item '.htmlspecialchars($item->item->name).'
		'.$amount.'x
		'.($direction == "take" ? "mitgenommen" : "abgegeben"));

		$this->output('options', array("interact" => "Zurück"));

		return;

	}

	public function show_Storage() {

		if ($this->get(1) != "" && is_numeric($this->get(1))) {
			$id = $this->get(1);

			$this->MoveItem($id, "take");
			return;
		}

		$this->output('maintext', 'Welches Item möchtest du mitnehmen?');

		$o = array();

		$items = R::find('bank_locker', ' user_id = ?', array($this->user->getID()));
		foreach ($items as $item) {
			$o["storage/".$item->id] = $item->amount."x ".$item->item->name;
		}

		$o["interact"] = "Zurück";

		$this->output('options', $o);
	}

	public function show_Store() {

		if ($this->get(1) != "" && is_numeric($this->get(1))) {
			$id = $this->get(1);

			$this->MoveItem($id, "put");
			return;
		}

		$this->output('maintext', 'Welches Item möchtest du abgeben?');

		$o = array();

		$items = R::find('inventory', ' user_id = ?', array($this->user->getID()));
		foreach ($items as $item) {
			$o["store/".$item->id] = $item->amount."x ".$item->item->name;
		}

		$o["interact"] = "Zurück";

		$this->output('options', $o);

	}
}
?>