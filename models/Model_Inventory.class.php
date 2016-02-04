<?php
class Model_Inventory extends RedBean_SimpleModel {

	public function after_update() {
		// check if amount == 0
		if ($this->amount <= 0) {
			R::trash($this->bean);
			return;
		}

		// check if merging two entries is possible
		$mergeTo = R::findOne('inventory', " param = ? AND user_id = ? AND item_id = ? AND id != ?",
							  array($this->param, $this->user->id, $this->item->id, $this->id));

		if ($mergeTo != false) {
			R::exec("UPDATE inventory SET amount = ? WHERE id = ?",
			array($this->amount + $mergeTo->amount, $this->id));

			R::trash($mergeTo);
		}
	}

}
?>