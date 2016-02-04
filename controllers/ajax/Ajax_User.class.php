<?php
class Ajax_User extends Controller_AjaxGame {
	public function show_Main() {
		$this->error('invalid Ajax-Call');
	}

	public function show_Logoff() {
		$this->session->expires = time();
		R::store($this->session);

		unset($_SESSION['loginHash']);
		unset($_SESSION['frameworkHash']);
	}

	public function show_Data() {
		$this->output('cash', $this->user->cash);
		$this->output('xp', $this->user->xp);
		$this->output('level', $this->user->level);

		$this->output('players_online', R::getCell('SELECT COUNT(id) FROM session WHERE expires > ?', array(time())));
	}

	public function show_KeepAlive() {
		// just to keep the session alive
	}

	public function show_Inventory() {
		$items = R::find('inventory', ' user_id = ?', array($this->user->getID()));

		$i = array();

		foreach ($items as $itm) {
			$item = R::load('inventory', $itm->getID());

			$i[] = array(
				"id" => $item->getID(),
				"name" => $item->item->name,
				"desc" => str_replace('{param}', $item->param, $item->item->desc),
				"is_usable" => ($item->item->usable == 1 ? true : false),
				"value" => $item->item->value,
				"usable_link" => $item->item->usable_link_desc,
				"amount" => $item->amount,
				"type" => $item->item->type
			);
		}

		$this->output('items', $i);
	}

	public function show_UpdateProfile() {
		$this->user->characterImage = $_POST["characterImage"];

		try {
			R::store($this->user);
		} catch(Exception $e) {
			$this->error($e->getMessage());
		}

		$this->output("message", "Profil gespeichert");
	}

	public function show_deleteUser() {
		if ($this->user->password != Framework::hash($_POST['password'])) {
			$this->error('Das Passwort war falsch!');
		}

		R::trash($this->user);

		$this->show_Logoff();

		$this->output('deleted', true);
		$this->output('message', 'Der Account wurde gelöscht.');
	}
}
?>