<?php
class Ajax_Trading_manager extends Controller_AjaxGameNPC {

	protected $myType = 'trading_manager';

	private $myCompany;

	private $myRess;

	private $myProducts;

	public function init() {
		parent::init();

		$this->myCompany = R::findOne('company', ' user_id = ?', array($this->user->id));
		$this->myRess = R::findOne('company_ress', ' company_id = ?', array($this->myCompany->id));
		$this->myProducts = R::findOne('company_products', ' company_id = ?', array($this->myCompany->id));

		if (!$this->myCompany) {
			$this->error('Du besitzt keine Firma und kannst deshalb auch nicht handeln!
			Geh is Nachbargebäude und gründe dort eine Firma.');
		}
	}

	public function show_Interact() {
		$this->output('maintext', 'Willkommen im Trading-Center! Hier kannst du deine
		Rohstoffe und Produkte zum Kauf und Verkauf anbieten');

		$this->output('options', array(
			'basic_buy' => 'Basis-Rohstoffe kaufen',
			'action/buy' => 'Kaufen',
			'action/sell' => 'Verkaufen'
		));
	}

	public function show_Basic_buy() {
		$ress = Config::getConfig('resources');

		if ($this->get(1) != "" && isset($ress[$this->get(1)]) && !isset($ress[$this->get(1)]["needs"])) {
			$name = $this->get(1);

			if (isset($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
				$a = $_POST['amount'];

				$totalPrice = $a * $ress[$name]["base_price"];

				if ($totalPrice > $this->myCompany->balance) {
					$this->output('maintext', 'Deine Firma hat nicht genügend Geld um '.$a.'x {r_'.$name.'} zu kaufen!');
					$this->output('options', array(
						'basic_buy/'.$name => 'Zurück'
					));
					return;
				}

				$this->myCompany->balance -= $totalPrice;
				$this->myRess->$name += $a;

				R::begin();
				R::store($this->myCompany);
				R::store($this->myRess);
				R::commit();

				$this->output('load', 'basic_buy');

				return;
			}

			$this->output('maintext', '<h3>{r_'.$name.'} kaufen</h3>
			<p>Derzeit auf Lager in deiner Fabrik: <b>'.formatCash($this->myRess->$name).'</b> <br />
			Aktueller Kontostand deiner Firma: <b>'.formatCash($this->myCompany->balance).'</b> {money} <br />
			Preis pro Verkaufseinheit: <b>'.$ress[$name]["base_price"].' {money}</b></p>');

			$this->output('form', array(
				'target' => 'basic_buy/'.$name,

				'elements' => array(
					array('desc' => 'Menge', 'name' => 'amount', 'type' => 'text')
				)
			));

			$this->output('options', array(
						'basic_buy' => 'Zurück'
			));

			return;
		}

		$icons = '';
		$buttons = '';
		$costs = '';
		$stored = '';

		foreach ($ress as $name => $r) {
			if (isset($r["needs"])) {
				continue;
			}

			$icons .= '<th>{r_'.$name.'}</th>';
			$buttons .= '<td><a href="#basic_buy/'.$name.'">{cart_put title="'.$name.' Kaufen"}</a></td>';
			$costs .= '<td>'.$r["base_price"].' {money}</td>';
			$stored .= '<td>'.formatCash($this->myRess->$name).'</td>';
		}

		$this->output('maintext', '<h3>Basis-Rohstoffe kaufen</h3>

		<table class="ordered">
			<tr>
				<th></th>'.$icons.'
			</tr>
			<tr>
				<th>Preis pro VE*</th>
				'.$costs.'
			</tr>
			<tr>
				<th>Derzeit vorhanden**</th>
				'.$stored.'
			</tr>
			<tr>
				<td></td>'.$buttons.'
			</tr>
		</table>

		<p>* VE = Verkaufseinheit <br />
		** Anzahl der Rohstoffe, die zur Zeit in deiner Firma lagern</p>');

		$this->output('options', array(
			'interact' => 'Zurück'
		));
	}

	protected function actionCreate($action, $name, $details, $type, $id) {
		if (!isset($_POST['amount']) || !isset($_POST['price'])
		|| !is_numeric($_POST['amount']) || !is_numeric($_POST['price'])
		|| $_POST['amount'] <= 0 || $_POST['price'] <= 0) {
			$this->output('maintext', 'Ungültige Menge oder ungültier Preis!');
			return;
		}

		$amount = $_POST['amount'];
		$price = $_POST['price'];

		if ($action == "sell"
			 && $amount > ($type == 'r' ? $this->myRess->$name : $this->myProducts->$name)) {

			$this->output('maintext', 'Du hast nicht genügend {'.$type.'_'.$name.'} für eine
			Verkaufsorder.');
			return;
		}

		if ($action == "buy"
			&& ($price*$amount) > $this->myCompany->balance) {

			$this->output('maintext', 'Du hast nicht genügend {money} auf dem Firmenkonto für
			eine Kaufsorder.');
			return;
		}

		$order = R::dispense('order');
		$order->type = $action;
		$order->r_type = ($type == 'r' ? 'resource' : 'product');
		$order->r_name = $name;
		$order->r_amount = $amount;
		$order->price = $price;
		$order->date = time();

		if ($this->user->hasPremium()) {
			$order->automatic = (isset($_POST['automatic']));

			if (!preg_match('#([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})#i', $_POST['a_expires'], $m)) {
				$this->output('maintext', 'Das angegebene Ablaufdatum ist ungültig!.');
				return;
			}

			$exp = mktime(date("H"), date("i"), date("s"), $m[2], $m[1], $m[3]);

			if ($exp < time() || ($exp-time()) > 60 * 24 * 3600) {
				$this->output('maintext', 'Das angegebene Ablaufdatum ist nicht in einem
				Zeitraum von 60 Tagen!');
				return;
			}

			$order->a_expires = $exp;
		} else {
			$order->automatic = false;
			$order->a_expires = 0;
		}

		R::begin();

		if ($action == "buy") {
			$this->myCompany->balance -= ($price*$amount);
			R::store($this->myCompany);
		}
		elseif ($action == "sell") {
			if ($type == 'r') {
				$this->myRess->$name -= $amount;
				R::store($this->myRess);
			} else {
				$this->myProducts->$name -= $amount;
				R::store($this->myProducts);
			}
		}

		R::store($order);
		R::associate($this->myCompany, $order);

		R::commit();

		$this->output('maintext', 'Die Order wurde erstellt!');
	}

	protected function actionEdit($action, $name, $details, $type, $id) {
		if (!is_numeric($id) || $id < 0) {
			$this->output('maintext', 'Ungültige Order!');
			return;
		}

		$order = R::relatedOne($this->myCompany, 'order', ' id = ? AND type = ? AND r_name = ?', array($id, $action, $name));

		if (!$order) {
			$this->output('maintext', 'Die angegebene Order konnte nicht gefunden werden!');
			return;
		}

		if (isset($_POST['amount']) && isset($_POST['price'])
		&& is_numeric($_POST['amount']) && is_numeric($_POST['price'])
		&& $_POST['amount'] > 0 && $_POST['price'] > 0) {
			$price = $_POST['price'];
			$amount = $_POST['amount'];

			// figure out the changes
			$price_delta = $price - $order->price;
			$amount_delta = $amount - $order->r_amount;

			// check if they are possible
			if ($action == "sell") {
				if ($amount_delta > 0) {
					if ($amount_delta > ($type == 'r' ? $this->myRess->$name : $this->myProducts->$name)) {
						$this->output('maintext', 'Du hast nicht genügend Rohstoffe für diese Änderung');
						return;
					}
				}
			}
			elseif ($action == "buy") {
				if ($price_delta > 0 && ($price_delta*$amount) > $this->myCompany->balance) {
					$this->output('maintext', 'Deine Firma hat nicht genügend Geld für diese Änderung');
					return;
				}

				if ($price_delta == 0 && $amount_delta > 0 && ($price*$amount_delta) > $this->myCompany->balance) {
					$this->output('maintext', 'Deine Firma hat nicht genügend Geld für diese Änderung');
					return;
				}
			}

			// update cash
			if ($action == "buy") {
				if ($amount_delta > 0) {
					$this->myCompany->balance -= ($price*$amount_delta);
				} elseif ($amount_delta < 0) {
					$this->myCompany->balance -= ($order->price*$amount_delta);
				} else {
					$this->myCompany->balance -= ($price_delta*$amount);
				}
			} elseif ($action == "sell") {
				if ($type == 'r') {
					$this->myRess->$name -= $amount_delta;
				} else {
					$this->myProducts->$name -= $amount_delta;
				}
			}

			// update order
			$order->price = $price;
			$order->r_amount = $amount;

			if ($this->user->hasPremium()) {
				$order->automatic = (isset($_POST['automatic']));

				if (!preg_match('#([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})#i', $_POST['a_expires'], $m)) {
					$this->output('maintext', 'Das angegebene Ablaufdatum ist ungültig!.');
					return;
				}

				$exp = mktime(date("H"), date("i"), date("s"), $m[2], $m[1], $m[3]);

				if ($exp < time() || ($exp-time()) > 60 * 24 * 3600) {
					$this->output('maintext', 'Das angegebene Ablaufdatum ist nicht in einem
							Zeitraum von 60 Tagen!');
					return;
				}

				$order->a_expires = $exp;
			} else {
				$order->automatic = false;
				$order->a_expires = 0;
			}

			// save everything...
			R::begin();
			R::store($order);
			R::store($this->myCompany);
			R::store($this->myProducts);
			R::store($this->myRess);
			R::commit();

			$this->output('maintext', 'Die Änderungen wurden erfolgreich gespeichert!');

			return;
		}

		$this->output('maintext', '<h3>'.($action == "sell" ? "Verkauf" : "Kauf").'order bearbeiten</h3>

		<p>{'.$type.'_'.$name.'}</p>');

		$el = array();

		$el[] = array('desc' => 'Menge', 'name' => 'amount', 'type' => 'text', 'value' => $order->r_amount);
		$el[] = array('desc' => 'Preis pro VE', 'name' => 'price', 'type' => 'text', 'value' => $order->price);

		if ($this->user->hasPremium()) {
			$el[] = array('desc' => 'automatisch erneuern?', 'name' => 'automatic', 'type' => 'checkbox', 'checked' => ($order->automatic == 1 ? true : false));
			$el[] = array('desc' => 'Autmatisch erneuern bis:', 'name' => 'a_expires', 'type' => 'date', 'value' => date("d.m.Y", $order->a_expires));
		}

		$this->output('form', array(
					'target' => 'action/'.$action.'/'.$name.'/edit/'.$order->id,

					'elements' => $el
		));
	}

	protected function actionDelete($action, $name, $details, $type, $id) {
		if (!is_numeric($id) || $id < 0) {
			$this->output('maintext', 'Ungültige Order!');
			return;
		}

		$order = R::relatedOne($this->myCompany, 'order', ' id = ? AND type = ? AND r_name = ?', array($id, $action, $name));

		if (!$order) {
			$this->output('maintext', 'Die angegebene Order konnte nicht gefunden werden!');
			return;
		}

		$order->cancel_Order();
		$this->output('maintext', 'Die Order wurde gelöscht');
		return;
	}

	protected function actionM($action, $name, $details, $type, $id) {
		if (!is_numeric($id) || $id < 0) {
			$this->output('maintext', 'Ungültige Order!');
			return;
		}

		$order = R::findOne('order', ' id = ? AND type = ? AND r_name = ?', array($id, ($action == 'sell' ? 'buy' : 'sell'), $name));

		if (!$order) {
			$this->output('maintext', 'Die angegebene Order konnte nicht gefunden werden!');
			return;
		}

		if (R::areRelated($order, $this->myCompany)) {
			$this->output('maintext', 'Die angegebene Order konnte nicht gefunden werden!');
			return;
		}

		$orderCompany = R::relatedOne($order, 'company');

		if (isset($_POST['amount']) && is_numeric($_POST['amount']) && $_POST['amount'] > 0) {
			$amount = $_POST['amount'];

			if ($action == 'sell') {
				if ($amount > ($type == 'r' ? $this->myRess->$name : $this->myProducts->$name)) {
					$this->output('maintext', 'Deine Firma lagert nicht genügend Ressourcen für diesen Verkauf!');
					return;
				}

				if ($amount > $order->r_amount) {
					$this->output('maintext', 'Diese Firma Ordert {'.$type.'_'.$name.'} maximal '.formatCash($order->r_amount).' mal!');
					return;
				}

				// checks done
				$this->myCompany->balance += $amount*$order->price;

				if ($type == 'r') {
					$this->myRess->$name -= $amount;
					$order->r_amount -= $amount;

					$targetComp = R::findOne('company_ress', ' company_id = ?', array($orderCompany->id));
					$targetComp->$name += $amount;

					R::begin();
					R::store($this->myRess);
					if ($order->r_amount <= 0) {
						R::trash($order);
					}
					else {
						R::store($order);
					}
					R::store($targetComp);
					R::store($this->myCompany);
					R::commit();
				} else {
					$this->myProducts->$name -= $amount;
					$order->r_amount -= $amount;

					$targetComp = R::findOne('company_products', ' company_id = ?', array($orderCompany->id));
					$targetComp->$name += $amount;

					R::begin();
					R::store($this->myProducts);
					if ($order->r_amount <= 0) {
						R::trash($order);
					}
					else {
						R::store($order);
					}
					R::store($targetComp);
					R::store($this->myCompany);
					R::commit();
				}

				$this->output('maintext', 'Der Verkauf war erfolgreich!');
				return;
			} else {

				$totalPrice = $amount * $order->price;

				if ($totalPrice > $this->myCompany->balance) {
					$this->output('maintext', 'Deine Firma hat nicht genügend Geld für diesen Kauf!');
					return;
				}

				if ($amount > $order->r_amount) {
					$this->output('maintext', 'Es werden maximal '.formatCash($order->r_amount).' Verkaufseinheiten verkaufen!');
					return;
				}

				// buy
				$this->myCompany->balance -= $totalPrice;

				if ($type == 'r') {
					$this->myRess->$name += $amount;
				} else {
					$this->myProducts->$name += $amount;
				}

				$order->r_amount -= $amount;

				R::begin();
				R::store($this->myCompany);
				R::store($this->myRess);
				R::store($this->myProducts);
				if ($order->r_amount <= 0) {
					R::trash($order);
				}
				else {
					R::store($order);
				}
				R::commit();

				$this->output('maintext', 'Der Kauf war erfolgreich!');
				return;
			}
		}

		$this->output('maintext', '<h3>Fremde '.($order->type == 'sell' ? 'Verkauf': 'Kauf').'order</h3>
		<p>{'.$type.'_'.$name.'}</p>

		<p>Firma: <b>'.htmlspecialchars($orderCompany->name).'</b><br />
		Preis pro VE: <b>'.formatCash($order->price).' {money}</b> <br />
		Maximal Verfügbare VE\'s: <b>'.formatCash($order->r_amount).'</b>
		</p>

		<h4>'.($action == 'buy' ? 'Kaufen' : 'Verkaufen').'</h4>');

		$this->output('form', array(
			'target' => 'action/'.$action.'/'.$name.'/m/'.$order->id,
			'elements' => array(
				array('desc' => 'Menge', 'name' => 'amount', 'type' => 'text')
			)
		));
	}

	protected function actionDetails($action, $name, $rp) {
		if (!isset($rp[$name]) || !isset($rp[$name]["needs"])) {
			$this->output('maintext', 'Diese Seite ist ungültig!');

			return;
		}

		$details = $rp[$name];
		$type = (is_array($details["needs"][0]) ? 'p' : 'r');

		if ($this->get(3) != '') {
			$do = $this->get(3);
			$id = $this->get(4);

			if ($id != '') {
				if (!(is_numeric($this->get(4)) && $this->get(4) > 0)) {
					$this->error("Invalid ID!");
				}
			}

			$func = "action".ucfirst($do);

			$this->$func($action, $name, $details, $type, $id);

			return;
		}

		$own_orders = '';
		//$oOrders = R::find('order', ' type = ? AND r_name = ?', array($action, $name));
		$oOrders = R::related($this->myCompany, 'order', ' type = ? AND r_name = ?', array($action, $name));

		foreach ($oOrders as $o) {
			$own_orders .= '<tr>
			<td>'.formatCash($o->r_amount).'</td>
			<td>'.formatCash($o->price).' {money}</td>
			<td>'.date('d.m.Y - H:i:s', $o->date + (3 * 24 * 3600)).'</td>
			<td>'.($o->automatic == 1 ? '{tick} <i>'.($o->a_expires == 0 ? '(unbegrenzt)' : '(bis zum '.date('d.m.Y - H:i:s', $o->a_expires).' Uhr)').'</i>' : '{cross}').'</td>
			<td><a href="#action/'.$action.'/'.$name.'/edit/'.$o->id.'">{pencil title="Bearbeiten"}</a></td>
			<td><a href="#action/'.$action.'/'.$name.'/delete/'.$o->id.'">{bin title="Löschen"}</a></td>
			</tr>';
		}

		$forgein_orders = '';
		try {
			//$nOrders = @R::unrelated($this->myCompany, 'order', ' type = ? AND r_name = ?', array(($action == "buy" ? "sell" : "buy"), $name));

			$nOrders = R::getAll('SELECT
				`order`.`r_amount` as r_amount,
				`order`.`price` as price,
				`order`.`id` as id,
				`company`.name as name
			FROM
				`order`, company_order, company
			WHERE
				company_order.company_id != ?
			 AND `order`.id = company_order.order_id
			 AND company.id = company_order.company_id
			 AND `order`.type = ?
			 AND `order`.r_name = ?',
			array($this->myCompany->getID(), ($action == "buy" ? "sell" : "buy"), $name));

		} catch (Exception $e) {
			//$forgein_orders .= '<!-- Error: ' .$e->getMessage(). ' -->';
			$nOrders = array();
		}

		foreach ($nOrders as $o) {

			$forgein_orders .= '<tr>
					<td>'.formatCash($o["r_amount"]).'</td>
					<td>'.formatCash($o["price"]).' {money}</td>
					<td>'.htmlspecialchars($o["name"]).'</td>
					<td><a href="#action/'.$action.'/'.$name.'/m/'.$o["id"].'">{briefcase title="Details"}</a></td>
					</tr>';
		}

		$mp = R::getCell('SELECT `value` FROM market_price WHERE `type` = ? AND `name` = ?',
		array($action, $name));

		$this->output('maintext', '<h3>{'.$type.'_'.$name.'} '.($action == "buy" ? "kaufen" : "verkaufen").'</h3>

		<p>Aktueller Marktpreis: <b>'.formatCash($mp).' {money}</b><br />
		Aktueller Kontostand der Firma: <b>'.formatCash($this->myCompany->balance).' {money}</b> <br />
		Verfügbarkeit im Lager deiner Firma: <b>'.formatCash(($type == 'r' ? $this->myRess->$name : $this->myProducts->$name)).' VE</b></p>

		<h4>Fremde '.($action == "buy" ? "Verkauforder" : "Kauforder").'</h4>
		<table class="ordered">
			<tr>
				<th>Menge</th>
				<th>Preis pro VE</th>
				<th>Verkäufer</th>
				<th></th>
			</tr>
				'.$forgein_orders.'
		</table>
		<br />

		<h4>Eigene '.($action == "buy" ? "Kauforder" : "Verkauforder").'</h4>
		<table class="ordered">
			<tr>
				<th>Menge</th>
				<th>Preis pro VE</th>
				<th>Läuft bis</th>
				<th>automatisch erneuern?</th>
				<th></th>
				<th></th>
			</tr>
				'.$own_orders.'
		</table>
		<br />

		<h4>Neue '.($action == "buy" ? "Kauforder" : "Verkauforder").' erstellen</h4>');

		$el = array();

		$el[] = array('desc' => 'Menge', 'name' => 'amount', 'type' => 'text');
		$el[] = array('desc' => 'Preis pro VE', 'name' => 'price', 'type' => 'text');

		if ($this->user->hasPremium()) {
			$el[] = array('desc' => 'automatisch erneuern?', 'name' => 'automatic', 'type' => 'checkbox');
			$el[] = array('desc' => 'Autmatisch erneuern bis:', 'name' => 'a_expires', 'type' => 'date');
		}

		$this->output('form', array(
			'target' => 'action/'.$action.'/'.$name.'/create',

			'elements' => $el
		));
	}

	public function show_Action() {
		/*
		$order = R::dispense('order');
		$order->type = 'buy';
		$order->r_type = 'product';
		$order->r_name = 'smartphone';
		$order->r_amount = 100;
		$order->price = 577;
		$order->date = time();

		$order->automatic = true;
		// $order->a_limit = 5; useless!
		$order->a_expires = 0;


		R::store($order);
		R::associate($this->myCompany, $order);*/

		if (!in_array($this->get(1), array("sell", "buy"))) {
			$this->output('maintext', 'Ungültig.');
			return;
		}

		$action = $this->get(1);

		$rp = array_merge(Config::getConfig('resources'), Config::getConfig('products'));

		if ($this->get(2) != '') {
			$this->actionDetails($action, $this->get(2), $rp);

			$this->output('options', array(
				'action/'.$action.'/'.($this->get(3) == '' ? '' : $this->get(2)) => 'Zurück'
			));
			return;
		}

		$icons = array('', '', '', '');
		$market_price = array('', '', '', '');
		$availible = array('', '', '', '');
		$details = array('', '', '', '');

		$iTable = 0;
		$intCount = 0;

		foreach ($rp as $name => $r) {
			if (!isset($r['needs'])) {
				continue;
			}

			$type = (is_array($r['needs'][0]) ? 'p' : 'r');

			$icons[$iTable] .= '<th>{'.$type.'_'.$name.'}</th>';

			$mp = R::getCell('SELECT `value` FROM market_price WHERE `type` = ? AND `name` = ?',
			array($action, $name));

			$market_price[$iTable] .= '<td>'.formatCash($mp).' {money}</td>';

			$availible[$iTable] .= '<td>'.formatCash(($type == 'p' ? $this->myProducts->$name : $this->myRess->$name)).'</td>';

			$details[$iTable] .= '<td><a href="#action/'.$action.'/'.$name.'">{magnifier title="Detailansicht"}</a></td>';

			$intCount++;

			if ($intCount%4 == 0) {
				$iTable++;
			}
		}

		$tables = array();

		foreach($icons as $k => $v) {
			$tables[$k] = '<table class="ordered">
			<tr>
				<th></th>
				'.$icons[$k].'
			</tr>

			<tr>
				<th>Aktueller Kurs pro VE*</th>
				'.$market_price[$k].'
			</tr>

			<tr>
				<th>Derzeit vorhanden**</th>
				'.$availible[$k].'
			</tr>

			<tr>
				<td></td>
				'.$details[$k].'
			</tr>
		</table>';
		}

		$this->output('maintext', '<h3>Rohstoffe und Produkte '.($action == "buy" ? "kaufen" : "verkaufen").'</h3>

		'.implode('<br />', $tables).'

		<p>* VE = Verkaufseinheit <br />
		** Anzahl der Rohstoffe, die derzeit in deiner Firma lagern</p>
		');

		$this->output('options', array(
					'interact' => 'Zurück'
		));
	}
}
?>