<?php
abstract class Controller_AjaxGameItem extends Controller_AjaxGame {

	protected $item;

	protected $myType = "";

	public function init() {
		parent::init();

		$id = $_POST["id"];

		// check if npc is valid
		if (!is_numeric($id) || $id <= 0) {
			$this->error('Invalid ITEM-ID');
		}

		$this->item = R::findOne('inventory', ' id = ? AND user_id = ?', array($id, $this->user->id));

		if (!$this->item) {
			$this->error('Du hast das Item nicht im Inventar!');
		}

		// check if usable
		if ($this->item->item->usable == 0) {
			$this->error('Item nicht benutzbar!');
		}

		// check if item has the right type
		if ($this->item->item->type != $this->myType) {
			$this->error('Das Item ist nicht vom Typ '.$this->myType);
		}
	}

	public function show_Main() {
		$this->error('invalid Ajax-Call');
	}

	public abstract function show_Use();
}
?>