<?php
class Ajax_Object_company_computer extends Controller_AjaxGameObject {

	protected $myType = 'company_computer';

	private $company;

	private $company_ress;

	private $company_products;

	private $company_machines;

	private $quests_running;

	private $quests_complete;

	public function init() {
		parent::init();

		$this->company = R::findOne('company', ' user_id = ?', array($this->user->id));

		if ($this->company) {
			$this->company_ress = R::findOne('company_ress', 'company_id = ?', array($this->company->id));
			$this->company_products =  R::findOne('company_products', 'company_id = ?', array($this->company->id));
			$this->company_machines = R::findOne('company_machines', 'company_id = ?', array($this->company->id));
			$this->initRess();

			$this->quests_running = R::related($this->user, 'company_quest', ' completed = 0');
			$this->quests_complete = R::related($this->user, 'company_quest', ' completed = 1');
		}
	}

	public function show_Interact() {
		// if person doesnt have company, create one for him!
		if ($this->foundCompany()) {
			return;
		}

		// resources
		$ress = array_keys(Config::getConfig("resources"));

		$titleRow = "<th>{r_".implode("}</th><th>{r_", $ress)."}</th>";
		$contentRow = "";

		foreach ($ress as $r) {
			$contentRow .= "<td>".formatCash($this->company_ress->$r)."</td>";
		}

		// products
		$prod = array_keys(Config::getConfig("products"));
		$ptitleRow = "<th>{p_".implode("}</th><th>{p_", $prod)."}</th>";
		$pcontentRow = "";

		foreach ($prod as $p) {
			$pcontentRow .= "<td>".formatCash($this->company_products->$p)."</td>";
		}

		$this->output('maintext','Willkommen im BusinessManager-System. Von hier kannst
			du deine Firma und ihre Fabrikation verwalten. <br /><br />
			Firma: <b>'.htmlspecialchars($this->company->name).'</b><br />
			Bankguthaben: <b>'.formatCash($this->company->balance).'</b> {money}<br />
			Laufende Aufgaben: <b>'.count($this->quests_running).'</b><br />
			Fertige Aufgaben: <b>'.count($this->quests_complete).'</b><br />
		<br />
		<i>Neu produzierte Ressourcen/Produkte werden alle 30 Minuten an dein Lager geliefert. <br />
		Letzte Lieferung: am '.date("d.m.Y \u\m H:i:s", $this->company->lastCalc).' Uhr</i><br /> <br />

		Aktuell gelagerte Resourcen: <br />
		<table class="ordered">
			<tr>
				'.$titleRow.'
			</tr>
			<tr>
				'.$contentRow.'
			</tr>
		</table>
		<br />
		Aktuell gelagerte Produkte: <br />
		<table class="ordered">
			<tr>
				'.$ptitleRow.'
			</tr>
			<tr>
				'.$pcontentRow.'
			</tr>
		</table>');

		$this->output('options', array(
			'machines' => 'Übersicht über die Produktionsmaschinen',
			'quests' => 'Übersicht über laufende Aufgaben',
			'transfer' => 'Geld auf das Firmen-Konto verschieben/vom Firmen-Konto abheben'
		));

	}

	public function show_Transfer() {
		$bankAccount = R::findOne('bank_account', ' user_id = ?', array($this->user->id));

		if (isset($_POST["amount"]) && is_numeric($_POST["amount"]) && $_POST["amount"] > 0) {
			$amount = $_POST["amount"];

			switch (@$_POST["type"]) {
				case "put":
					if ($bankAccount->balance < $amount) {
						$this->output('maintext', 'Du hast nicht genügend Geld auf deinem Bankkonto');
						$this->output('options', array('transfer' => 'Zurück'));
						return;
					}

					$bankAccount->balance -= $amount;
					$this->company->balance += $amount;

					R::begin();
					R::store($bankAccount);
					R::store($this->company);
					R::commit();
					break;

				case "take":
					if ($this->company->balance < $amount) {
						$this->output('maintext', 'Es ist nicht genügend Geld auf dem Firmenkonto');
						$this->output('options', array('transfer' => 'Zurück'));
						return;
					}

					$bankAccount->balance += $amount;
					$this->company->balance -= $amount;

					R::begin();
					R::store($bankAccount);
					R::store($this->company);
					R::commit();
					break;
			}
		}

		$this->output('maintext','Du kannst Geld von deinem Bankkonto auf das Firmen-Konto
		überweisen, oder Geld vom Firmen-Konto auf dein Konto überweisen. <br /><br />
					Firmen-Konto: <b>'.formatCash($this->company->balance).'</b> {money}<br />
					Dein Bankkonto: <b>'.formatCash($bankAccount->balance).' {money}</b>');

		$this->output('form', array(
			'target' => 'transfer',

			'elements' => array(
				array('desc' => "Betrag", 'name' => 'amount', 'type' => 'text'),
				array('desc' => "Aktion", 'name' => 'type', 'type' => 'select',
				'options' => array('put' => 'Von deinem Bankkonto auf das Firmen-Konto überweisen',
								   'take' => 'Vom Firmen-Konto auf dein Bankkonto buchen'))
			)
		));

		$this->output('options', array('interact' => 'Zurück'));
	}

	public function show_Quests() {
		if (count($this->quests_running) == 0) {
			$this->output('maintext', 'Du hast derzeit keine laufenden Aufgaben.
			Du kannst dir im Gebäude nebenan neue Aufgaben besorgen!');

			$this->output('options', array(
				'interact' => 'Zurück'
			));
			return;
		}

		$qDetails = Config::getConfig('company_quests');

		if ($this->get(1) == "complete" && is_numeric($this->get(2))) {
			$id = $this->get(2);

			foreach ($this->quests_running as $quest) {
				if ($quest->id == $id) {
					try {
						$quest->complete();
					} catch (Exception $e) {
						$this->output('maintext', 'Fehler: '.$e->getMessage());

						$this->output('options', array(
										'quests' => 'Zurück'
						));
						return;
					}

					$oc = $qDetails[$quest->name]["oncomplete"];

					$this->output('maintext', 'Du hast die Aufgabe erfolgreich erfüllt! Du erhälst
					'.formatCash($oc["cash"]).' {money} und
					'.formatCash($oc["xp"]).' {eye}.');

					$this->output('options', array(
						'quests' => 'Zurück'
					));
					return;
					break;
				}
			}

			$this->output('maintext', 'Fehler! Aufgabe nicht gefunden!');

			$this->output('options', array(
				'quests' => 'Zurück'
			));

			return;
		}

		$oOpt = array();
		$oDesc = array();

		$this->output('maintext', 'Dies ist eine Übersicht über deine aktuell laufenden Aufträge. Wenn
		du einen Premium-Acount aktiviert hast, dann werden bein einem Auftrag zum Fertigstellungstermin
		automatisch die entsprechenden Ressourcen/Produkte verschickt. Du kannst die Ressourcen/Produkte jederzeit
		selbst verschicken!');

		foreach ($this->quests_running as $quest) {

			if ($quest->valid_until < time()) {
				$quest->cancel();
				continue;
			}

			$q  = $qDetails[$quest->name];

			$rows = "";

			foreach ($q["needs"] as $n) {
				$rows .= "<tr class='tdright'>";
				$rows .= "<td>{".($n["type"] == "resource" ? 'r' : 'p')."_".$n["name"]."}</td>
				<td>".formatCash($n["amount"])."</td>";

				if ($n["type"] == "resource") {
					$Tamount = $this->company_ress->$n["name"];
				} else {
					$Tamount = $this->company_products->$n["name"];
				}

				$rows .= "<td>".formatCash($Tamount)."</td>";
				$rows .= "<td>".number_format(($Tamount/$n["amount"])*100, 2, ",", ".")."%</td>";
				$rows .= "</tr>";
			}

			$oDesc["quests/complete/".$quest->id] = "<hr /><h3>".htmlspecialchars($q["title"])."</h3>
			<p>Du hast noch bis zum <b>".date("d.m.Y \u\m H:i:s", $quest->valid_until)."</b> Uhr Zeit diesen
			Auftrag zu erledigen</p>

			<b>Benötigte Resourcen/Produkte:</b>
			<table class='ordered'>
			<tr>
				<th></th>
				<th>Menge</th>
				<th>Derzeit im Lager</th>
				<th>In Prozent</th>
			</tr>
			".$rows."

			</table>

			<br />
			<b>Belohung:</b><br />
			".formatCash($q["oncomplete"]["xp"])." {eye} <br />
			".formatCash($q["oncomplete"]["cash"])." {money} <br />";

			$oOpt["quests/complete/".$quest->id] = "Benötigte Ressourcen/Produkte abschicken und den Auftrag erledigen";
		}

		$oOpt["interact"] = "Zurück";
		$oDesc["interact"] = "<hr />";

		$this->output('options_desc', $oDesc);
		$this->output('options', $oOpt);
	}

	public function show_Machines() {

		// machines
		$machines = "";
		$mTotal = array("amount" => 0, "ph" => 0, "cph" => 0);

		$ms = array_merge(Config::getConfig("resources"), Config::getConfig("products"));

		foreach($ms as $name => $details) {
			if (!isset($details["needs"])) {
				continue;
			}

			$amount = $this->company_machines->$name;
			$mTotal["amount"] += $amount;

			if ($amount == 0) {
				continue;
			}

			$machines .= "<tr class='tdright tdvcenter'><th>";

			$prod = false;

			foreach ($details["needs"] as $v) {
				if (is_array($v)) {
					$machines .= $v["amount"]."x {r_".$v["ress"]."} ";
					$prod = true;
				} else {
					$machines .= "{r_".$v."} ";
				}
			}

			$machines .= " {arrow_right} ";

			$machines .= " {".($prod ? "p" : "r")."_".$name."}";

			$machines .= "</th><td>".$amount ."</td>";

			$ph = $details["machine"]["prod_per_hour"] * $amount;
			$mTotal["ph"] += $ph;

			$machines .= "<td>".formatCash($ph)." {".($prod ? "p" : "r")."_".$name."}</td>";

			$cph = $details["machine"]["running_cost"] * $amount;
			$mTotal["cph"] += $cph;

			$machines .= "<td>".formatCash($cph)." {money}</td>";

			$machines .= "</tr>";
		}

		$machines .= "<tr class='tdright'>
			<th>Gesamt:</th>
			<td>".formatCash($mTotal["amount"])."</td>
					<td>".formatCash($mTotal["ph"])."</td>
			<td>".formatCash($mTotal["cph"])." {money}</td>
			</tr>";

		$this->output('maintext','<table class="ordered">
		<tr>
		<th>Produktionsvorgang</th>
		<th>Vorhanden</th>
		<th>Produkte/Stunde</th>
		<th>Kosten/Stunde*</th>
		</tr>
					'.$machines.'
		</table>

		<br /> <br />
		<i>* Maschinen, für die die Resourcen für eine Produktion fehlen kosten keine weiteren
		laufende Kosten. Wenn du kein Geld mehr auf dem Firmenkonto hast um
		die laufenden Kosten zu decken wird deine Produktion stillgelegt bis wieder Geld
		verfügbar ist.</i>');

		$this->output('options', array(
					'change' => 'Maschinen kaufen/verkaufen',
					'interact' => 'Zurück'
		));
	}

	public function show_Change() {
		$ms = array_merge(Config::getConfig("resources"), Config::getConfig("products"));

		$menuItems = array(); // name, desc, buyprice, sellprice

		foreach($ms as $name => $details) {
			if (!isset($details["needs"])) {
				continue;
			}

			$mI = array("amount" => 0, "name" => $name, "icons" => "", "desc" => '<hr />', "buyprice" => $details["machine"]["cost"], "sellprice" => floor($details["machine"]["cost"]*0.9));

			$prod = false;
			foreach ($details["needs"] as $v) {
				if (is_array($v)) {
					$mI["icons"] .= $v["amount"]."x {r_".$v["ress"]."} ";
					$prod = true;
				} else {
					$mI["icons"] .= "{r_".$v."} ";
				}
			}

			$mI["icons"] .= " {arrow_right} ";

			$mI["icons"] .= " {".($prod ? "p" : "r")."_".$name."}";

			$mI["desc"] .= $mI["icons"];

			$mI["desc"] .= "<br />";

			$mI["desc"] .= "<b>Laufende Kosten pro Stunde:</b> ".formatCash($details["machine"]["running_cost"])." {money} <br />";

			$mI["desc"] .= "<b>Produkte pro Stunde:</b> ".formatCash($details["machine"]["prod_per_hour"])."<br />";

			$mI["desc"] .= "<b>Derzeit vorhandene Maschinen:</b> ".formatCash($this->company_machines->$name)."<br />";

			$mI["amount"] = $this->company_machines->$name;

			$menuItems[$name] = $mI;
		}

		if ($this->get(1) != '' && in_array($this->get(2), array("buy", "sell")) && ctype_alpha($this->get(3))) {
			$mode = $this->get(2);
			$machine = $this->get(3);

			if (!isset($menuItems[$machine])) {
				$this->output('maintext', 'Diese Maschine gibt es nicht!');
				$this->output('options', array(
								'change' => "Zurück"
				));
				return;
			}

			$m = $menuItems[$machine];

			if (isset($_POST["amount"]) && is_numeric($_POST["amount"]) && $_POST["amount"] > 0) {
				$amount = $_POST["amount"];
				$totalValue = $amount * $m[$mode."price"];

				if ($mode == "buy" && $totalValue > $this->company->balance) {
					$this->output('maintext', 'Deine Firma hat nicht genügend Geld!');
					$this->output('options', array(
						'change/do/'.$mode.'/'.$machine => "Zurück"
					));
					return;
				} elseif ($mode == "sell" && $amount > $m["amount"]) {
					$this->output('maintext', 'Deine Firma hat nicht so viele Maschinen wie du verkaufen möchtest!');
					$this->output('options', array(
						'change/do/'.$mode.'/'.$machine => "Zurück"
					));
					return;
				}


				if ($mode == "buy") {
					$this->company_machines->$m["name"] += $amount;
					$this->company->balance -= $totalValue;

					R::$adapter->startTransaction();
					R::store($this->company);
					R::store($this->company_machines);
					R::$adapter->commit();
				} elseif ($mode == "sell") {
					$this->company_machines->$m["name"] -= $amount;
					$this->company->balance += $totalValue;

					R::$adapter->startTransaction();
					R::store($this->company);
					R::store($this->company_machines);
					R::$adapter->commit();
				}

				$this->output('maintext', 'Der '.($mode == "buy" ? "Kauf" : "Verkauf").' war
				erfolgreich!'.($mode == "buy" ? ' Die neuen Maschinen werden sofort in Betrieb
				genommen.' : ''));

				$this->output('options', array(
					'change' => "Zurück zur Kauf/Verkauf Übersicht",
					'machines' => "Zurück zur Übersicht über vorhandene Maschinen"
				));

				return;
			}

			$this->output('maintext', "Wie oft möchtest du diese Maschine
			".($mode == "buy" ? "kaufen" : "verkaufen")."? <br /> ".$m["icons"]." <br />
			<b>Kosten pro Stück:</b> ".formatCash($m[$mode."price"])." {money}". "<br />
			<b>Derzeit vorhanden:</b> ".formatCash($m["amount"])." {money}". "<br />
			<b>Bankguthaben deiner Firma: </b>".formatCash($this->company->balance)." {money}");

			$max_machines = ($mode == "buy" ? floor($this->company->balance / $m[$mode."price"]) : $m["amount"]);
			$options = array();
			for ($i = 0; $i <= $max_machines; $i++)
			  $options[$i] = $i;

			$this->output('form', array(
				'target' => 'change/do/'.$mode.'/'.$machine,
				'elements' => array(
						    array('desc' => 'Menge', 'value' => 0, 'options' => $options,
							  'name' => 'amount', 'type' => 'select')
				)
			));

			$this->output('options', array(
				'change' => "Zurück"
			));

			return;
		}

		$this->output('maintext', 'Hier kannst du Maschinen kaufen und verkaufen. Bedenke, dass
		du bei einem Verkauf nur 90% des Kaufpreises bekommst. <br />
		Bankguthaben deiner Firma: <b>'.formatCash($this->company->balance).'</b> {money}');

		$oDesc = array();
		$oOpt = array();

		foreach ($menuItems as $i) {
			$oDesc["change/do/buy/".$i["name"]] = $i["desc"];

			$oOpt["change/do/buy/".$i["name"]] = "Diese Maschine für ".formatCash($i["buyprice"])." {money} kaufen";

			if ($i["amount"] > 0) {
				$oOpt["change/do/sell/".$i["name"]] = "Diese Maschine für ".formatCash($i["sellprice"])." {money} verkaufen";
			}
		}

		$oDesc["machines"] = "<hr />";
		$oOpt["machines"] = "Zurück";

		$this->output('options_desc', $oDesc);
		$this->output('options', $oOpt);
	}

	private function initRess() {
		if (!$this->company_ress) {
			$this->company_ress = R::dispense('company_ress');
			$this->company_ress->company = $this->company;

			$ress = Config::getConfig('resources');

			foreach ($ress as $k => $r) {
				$this->company_ress->$k = 0;
			}

			R::store($this->company_ress);
		}

		if (!$this->company_products) {
			$this->company_products = R::dispense('company_products');
			$this->company_products->company = $this->company;

			$ress = Config::getConfig('products');

			foreach ($ress as $k => $r) {
				$this->company_products->$k = 0;
			}

			R::store($this->company_products);
		}

		if (!$this->company_machines) {
			$this->company_machines = R::dispense('company_machines');
			$this->company_machines->company = $this->company;

			$merged = array_merge(
				Config::getConfig('products'),
				Config::getConfig('resources')
			);

			foreach ($merged as $k => $t) {
				if (isset($t["needs"])) {
					$this->company_machines->$k = 0;
				}
			}

			R::store($this->company_machines);
		}
	}

	private function foundCompany() {

		if (!$this->company) {
			$myBalance = R::getCell('SELECT balance FROM bank_account WHERE user_id = ?', array($this->user->id));

			if (isset($_POST['foundName']) && isset($_POST["foundCash"]) && is_numeric($_POST["foundCash"])) {
				$fName = $_POST['foundName'];
				$fCash = $_POST['foundCash'];

				if ($fCash < 0 || $fCash > $myBalance) {
					$this->output('maintext', 'Du hast ein ungültiges Startkapital angegeben.');

					$this->output('options', array('interact' => 'Zurück'));
					return true;
				}

				$isUnique = R::getCell('SELECT COUNT(id) FROM company WHERE LOWER(name) = LOWER(?)', array($fName));

				if ($isUnique != 0) {
					$this->output('maintext', 'Der angegebene Name wird bereits verwendet.');

					$this->output('options', array('interact' => 'Zurück'));
					return true;
				}

				$company = R::dispense('company');
				$company->name = $fName;
				$company->user = $this->user;
				$company->balance = $fCash;
				$company->lastCalc = time();

				R::$adapter->startTransaction();

				try {
					R::store($company);
				} catch(Exception $e) {
					R::$adapter->rollback();

					$this->output('maintext', $e->getMessage());

					$this->output('options', array('interact' => 'Zurück'));
					return true;
				}
				R::exec('UPDATE bank_account SET balance = balance - ? WHERE user_id = ?',
				array($fCash, $this->user->id));

				R::$adapter->commit();

				$this->output('maintext', 'Herzlichen Glückwunsch! Die Firma '.htmlspecialchars($company->name).' wurde
				soeben gegründet.');

				$this->output('options', array('interact' => 'Weiter'));
				return true;
			}

			$this->output('maintext', 'Willkommen im BusinessManager-System. Von hier kannst
			du deine Firma und ihre Fabrikation verwalten. <br /> <br />
			Du besitzt derzeit noch keine Firma. Um eine Firma zu gründen brauchen wir
			einen Namen, und wie viel Startkapital von deinem Konto auf das Firmenkonto
			überwiesen werden soll.<br />
			Dein derzeitiger Kontostand beträgt '.formatCash($myBalance).' {money}. <br /> <br />
			<i>Hinweis: Der Name kann nachträglich nicht mehr geändert werden!</i>');

			$this->output('form', array(
				'target' => 'interact',

				'elements' => array(
			array(
						'desc' => 'Name der Firma',
						'type' => 'text',
						'name' => 'foundName'
			),

			array(
						'desc' => 'Startkapital',
						'type' => 'text',
						'name' => 'foundCash'
			)
			)
			));

			return true;
		}

		return false;
	}

}
?>
