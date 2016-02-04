<?php
class Ajax_Trading_premium extends Controller_AjaxGameNPC {

	protected $myType = 'trading_premium';

	protected $myCompany;

	public function init() {
		parent::init();

		$this->myCompany = R::findOne('company', 'user_id = ?', array($this->user->getID()));

		if (!$this->myCompany) {
			$this->error('Du besitzt keine Firma!');
		}
	}

	public function show_Interact() {
		$this->output('maintext', 'Hallo! Ich verwalte für verschiedene Firmen die Lagerbestände.
		Das bedeutet sobald eine Firma weniger Rohstoffe/Produkte als vereinbart lagert, erstelle ich
		eine Kauforder für sie. Lagert eine Firma mehr Rohstoffe/Produkte als vereinbart, erstelle
		ich eine Verkauforder.<br /> <br /><i>'.($this->user->hasPremium() ? 'Wie ich sehe hast du einen
		Premiumaccount und kannst meine Dienste nutzen!' : 'Du hast leider keinen Premiumaccount.
		Ich kann nur für Spieler mit Premiumaccount arbeiten. Für mehr Informationen über einen
		Premiumaccount klicke bitte im Menü auf "Premium".').'</i>');

		if ($this->user->hasPremium()) {
			$this->output('options', array(
				'show' => 'Lagerverwaltung bearbeiten'
			));
		}
	}

	public function show_Show() {
		if (!$this->user->hasPremium()) {
			$this->error('Nur für Premiumaccounts.');
		}

		$o = '<h3>Aktuelle Lagerverwaltungs-Regeln</h3>';

		$o .= '<table class="ordered">
		<tr>
			<th>Aktion</th>
			<th>Ressource/Produkt</th>
			<th>Lager-Limit</th>
			<th>Preis pro VE</th>
			<th>Gültig bis</th>
			<th></th>
		</tr>';

		$show = R::related($this->myCompany, 'crule');

		foreach ($show as $s) {
			$o .= '<tr>
				<td>'.($s->action == 'buy' ? 'Kaufen' : 'Verkaufen').'</td>
				<td>{'.($s->r_type == 'resource' ? 'r' : 'p').'_'.$s->r_name.'}</td>
				<td>'.($s->action == 'buy' ? '<' : '>').' '.formatCash($s->r_limit).'</td>
				<td>'.formatCash($s->r_price).' {money}</td>
				<td>'.date('d.m.Y - H:i:s', $s->until).'</td>
				<td><a href="#cancel/'.$s->id.'">{cross title="Stornieren"}</a></td>
			</tr>';
		}

		$o .= '</table>';

		$this->output('maintext', $o);

		$this->output('options', array(
			'add' => 'Neue Regel hinzufügen'
		));
	}

	public function show_Cancel() {
		if (!$this->user->hasPremium()) {
			$this->error('Nur für Premiumaccounts.');
		}

		if (!is_numeric($this->get(1)) || $this->get(1) < 1) {
			$this->error('Ungültige ID');
		}

		$crule = R::relatedOne($this->myCompany, 'crule', 'id = ?', array($this->get(1)));

		if (!$crule) {
			$this->error('Die ID wurde nicht gefunden.');
		}

		R::trash($crule);

		$this->output('load', 'show');
	}

	public function show_Add() {
		if (!$this->user->hasPremium()) {
			$this->error('Nur für Premiumaccounts.');
		}

		if (isset($_POST['action'])) {

			$crule = R::dispense('crule');

			if (!in_array($_POST['action'], array('buy', 'sell'))) {
				$this->output('maintext', 'Ungültige Aktion.');

				$this->output('options', array(
					'add' => 'Zurück'
				));

				return;
			}

			$crule->action = $_POST['action'];


			$ress = Config::getConfig('resources');
			$prods = Config::getConfig('products');

			if (!in_array($_POST['r_name'], array_keys($ress))
			 && !in_array($_POST['r_name'], array_keys($prods))) {
				$this->output('maintext', 'Ungültiger Rohstoff/Produkt.');

				$this->output('options', array(
									'add' => 'Zurück'
				));

				return;
			}

			$crule->r_name = $_POST['r_name'];
			$crule->r_type = (isset($ress[$_POST['r_name']]) ? 'resource' : 'product');

			if (!is_numeric($_POST['r_limit']) || $_POST['r_limit'] < 0) {
				$this->output('maintext', 'Ungültiges Limit.');

				$this->output('options', array(
					'add' => 'Zurück'
				));

				return;
			}

			$crule->r_limit = $_POST['r_limit'];

			if (!is_numeric($_POST['r_price']) || $_POST['r_price'] < 0) {
				$this->output('maintext', 'Ungültiger Preis');

				$this->output('options', array(
					'add' => 'Zurück'
				));

				return;
			}

			$crule->r_price = $_POST['r_price'];

			if (!preg_match('#([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})#i', $_POST['until'], $m)) {
				$this->output('maintext', 'Das angegebene Ablaufdatum ist ungültig!.');

				$this->output('options', array(
									'add' => 'Zurück'
				));
				return;
			}

			$exp = mktime(date("H"), date("i"), date("s"), $m[2], $m[1], $m[3]);

			if ($exp < time() || ($exp-time()) > 60 * 24 * 3600) {
				$this->output('maintext', 'Das angegebene Ablaufdatum ist nicht in einem
										Zeitraum von 60 Tagen!');
				$this->output('options', array(
									'add' => 'Zurück'
				));

				return;
			}

			$premium = R::findOne('user_premium', ' user_id = ?', array($this->user->id));

			if ($exp > $premium->until) {
				$this->output('maintext', 'Das angegebene Ablaufdatum ist nach dem Ablaufen
				deines Premiumaccounts! Dieser läuft ab am: '.date('d.m.Y - H:i:s', $premium->until));
				$this->output('options', array(
													'add' => 'Zurück'
				));

				return;
			}

			$crule->until = $exp;

			R::store($crule);
			R::associate($crule, $this->myCompany);

			$this->output('maintext', 'Die Regel wurde gespeichert.');
			$this->output('options', array(
				'show' => 'Zurück'
			));

			return;
		}

		$r = array();

		$p = array_merge(Config::getConfig('resources'), Config::getConfig('products'));

		foreach ($p as $k => $px) {
			if (!isset($px["needs"])) {
				continue;
			}
			$r[$k] = $k;
		}

		$this->output('form', array(
			'target' => 'add',

			'elements' => array(
				array(
					'desc' => 'Aktion',
					'name' => 'action',
					'type' => 'select',
					'options' => array(
						'buy' => 'Kaufen',
						'sell' => 'Verkaufen'
					)
				),

				array(
					'desc' => 'Rohstoff/Produkt',
					'name' => 'r_name',
					'type' => 'select',
					'options' => $r
				),

				array(
					'desc' => 'Limit (Beim Verkaufen wird verkauft, sobald das Lager dieses Limit übersteigt.
					Beim Kaufen wird gekauft sobald das Lager unter dieses Limit sinkt.)',
					'name' => 'r_limit',
					'type' => 'text',
					'value' => '0'
				),

				array(
					'desc' => 'Preis pro VE',
					'name' => 'r_price',
					'type' => 'text',
					'value' => '0'
				),

				array(
					'desc' => 'Diese Regel ist gültig bis',
					'name' => 'until',
					'type' => 'date'
				)
			)
		));

		$this->output('options', array(
			'show' => 'Zurück'
		));
	}
}
?>