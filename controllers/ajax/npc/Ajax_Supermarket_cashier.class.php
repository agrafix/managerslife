<?php
class Ajax_Supermarket_cashier extends Controller_AjaxGameNPC {

	protected $myType = 'supermarket_cashier';

	public function show_Buy() {
		$this->dealerNPC("sell", "inventory", "buy"); // user is buying, npc is selling
	}

	public function show_Sell() {
		$this->dealerNPC("buy", "inventory", "sell"); // user is selling, npc is buying
	}

	public function show_Interact() {
		$this->output('maintext', "Hallo! Was kann ich für dich tun?");

		$this->output('speak', 'Was kann ich für dich tun?');

		$this->output('options', array('buy' => 'Ich möchte etwas kaufen', 'sell' => 'Ich möchte etwas verkaufen'));
	}
}
?>