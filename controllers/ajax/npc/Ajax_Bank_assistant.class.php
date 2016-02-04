<?php
class Ajax_Bank_assistant extends Controller_AjaxGameNPC {

	private $account;

	protected $myType = 'bank_assistant';

	public function init() {
		parent::init();

		$this->account = R::findOne('bank_account', ' user_id = ?', array($this->user->getID()));

		if (!$this->account) {
			// create account
			$this->account = R::dispense('bank_account');
			$this->account->user = $this->user;
			$this->account->balance = 0;
			$this->account->lastCalc = time();
			R::store($this->account);
		}
	}

	public function show_Interact() {
		$this->output('maintext', 'Hallo <b>'.htmlspecialchars($this->user->username).'</b>! Hier
		bei der Bank kannst du deinen Kontostand prüfen, Geld einzahlen oder Geld abheben.
		Du bekommst auf dein eingezahltes Geld pro Tag 1% Zinsen!');

		$this->output('speak', 'Was kann ich tun?');

		$this->output('options', array('account' => 'Kontostand prüfen',
		'add' => 'Geld einzahlen', 'draw' => 'Geld abheben'));
	}

	public function show_Account() {
		$this->output('maintext', 'Dein Aktueller Kontostand beträgt <b>'.$this->account->balance.'
		</b> <img src="'.APP_DIR.'static/images/icons/money.png" alt="Geld" />');

		$this->output('options', array('interact' => 'Zurück'));
	}

	public function show_Draw() {
		$this->output('maintext', 'Wie viel Geld möchtest du abheben?');

		$this->output('form', array('target' => 'transaction',
				'elements' => array(
		array('desc' => 'Menge: ', 'name' => 'amount', 'type' => 'text', 'css' => ''),
		array('desc' => '', 'name' => 'action', 'type' => 'hidden', 'value' => 'draw', 'css' => '')
		)));

		$this->output('options', array('interact' => 'Zurück'));
	}

	public function show_Add() {
		$this->output('maintext', 'Wie viel Geld möchtest du einzahlen?');

		$this->output('form', array('target' => 'transaction',
		'elements' => array(
			array('desc' => 'Menge: ', 'name' => 'amount', 'type' => 'text', 'css' => ''),
		array('desc' => '', 'name' => 'action', 'type' => 'hidden', 'value' => 'add', 'css' => '')
		)));

		$this->output('options', array('interact' => 'Zurück'));
	}

	public function show_Transaction() {
		$type = @$_POST['action'];

		if (!in_array($type, array('add', 'draw'))) {
			$this->error('Invalid Transaction type');
		}

		if (!is_numeric($_POST['amount']) || $_POST['amount'] < 0) {
			$this->output('maintext', 'Du hast einen ungültigen Betrag angegeben!');
			$this->output('options', array($type => 'Zurück'));
			return;
		}

		$check = false;

		if ($type == "draw") {
			$check = ($_POST['amount'] <= $this->account->balance);
		} else {
			$check = ($_POST['amount'] <= $this->user->cash);
		}

		if (!$check) {
			$this->output('maintext', 'Du hast nicht genügend Geld '.($type == "draw" ? "auf dem Konto" : "dabei").'!');
			$this->output('options', array($type => 'Zurück'));
			return;
		}

		R::$adapter->startTransaction();

		if ($type == "draw") {
			$this->account->balance -= $_POST["amount"];
			$this->user->cash += $_POST["amount"];
		} else {
			$this->account->balance += $_POST["amount"];
			$this->user->cash -= $_POST["amount"];
		}

		R::store($this->account);
		R::store($this->user);

		R::$adapter->commit();

		$this->output('maintext', 'Du hast '.$_POST['amount'].' <img src="'.APP_DIR.'static/images/icons/money.png" alt="Geld" />
		'.($type == "draw" ? "abgehoben." : "eingezahlt."));

		$this->output('options', array('interact' => 'Zurück'));
	}

}