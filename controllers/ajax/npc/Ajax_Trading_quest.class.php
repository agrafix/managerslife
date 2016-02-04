<?php
class Ajax_Trading_quest extends Controller_AjaxGameNPC {

	protected $myType = 'trading_quest';

	private $myQuests = array();

	public function init() {
		parent::init();

		$this->myQuests = R::related($this->user, 'company_quest');

		if (!R::findOne('company', ' user_id = ?', array($this->user->id))) {
			$this->error('Du besitzt keine Firma. Geh ins Nachbargebäude und gründe dort
			eine Firma bevor du Aufträge annehmen kannst.');
		}
	}

	public function show_Interact() {
		$this->output('maintext', '<p>Hallo! Ich bin selbst
		Unternehmer und hätte ein einige Aufgaben für dein Unternehmen. Solltest du
		die Aufgaben in vorgegebener Zeit und zu meiner Zufriedenheit erledigen, so
		bezahle ich dich gut und du erhälst einige Erfahrungspunkte.</p><p> Laufende und erledigte Aufträge
		kannst du an deinem Firmenverwaltungs-PC im Gebäude nebenan ansehen. Von dort aus kannst
		du deine Aufträge abarbeiten.');

		$this->output('options', array(
			'show_quests' => 'Klingt gut, was kann ich für dich tun?'
		));
	}

	public function show_Show_quests() {
		$quests = Config::getConfig('company_quests');

		if ($this->get(1) == 'check') {
			$name = $this->get(2);

			if (!isset($quests[$name]) || $quests[$name]["level"] > $this->user->level) {
				$this->output('maintext', 'Du hast eine ungültige Aufgabe gewählt');
				$this->output('options', array('show_quests' => 'Zurück'));
				return;
			}

			$quest = $quests[$name];

			if ($this->get(3) == 'accept' && $this->get(4) == $_SESSION['secHash']) {
				unset($_SESSION['secHash']); // security

				$company_quest = R::dispense('company_quest');
				$company_quest->name = $name;
				$company_quest->valid_until = time() + $quest["time"] * 24 * 3600;
				$company_quest->completed = false;
				R::store($company_quest);
				R::associate($this->user, $company_quest);

				$this->output('maintext', 'Du hast diese Aufgabe akzeptiert! Viel erfolg dabei!');
				$this->output('options', array('show_quests' => 'Zurück'));

				return;
			}

			$th = "";
			$td = "";

			foreach ($quest["needs"] as $n) {
				$th .= "<th>{".($n["type"] == "resource" ? 'r' : 'p')."_".$n["name"]."}</th>";
				$td .= "<td>".formatCash($n["amount"])."</td>";
			}

			$this->output('maintext', '<h3>'.htmlspecialchars($quest["title"]).'</h3>
			<p>'.htmlspecialchars($quest["text"]).'</p>

			<p>Du musst folgende Resourcen und Produkte abliefern:</p>
			<table class="ordered">
			<tr>
				<th></th>'.$th.'
			</tr>
			<tr>
				<td>Menge</td>'.$td.'
			</tr>
			</table>

			<i>Du hast <b>'.$quest["time"].' Tage</b> Zeit um diesen Auftrag zu erledigen. Solltest
			du den Auftrag nicht in dieser Zeit erledigen wird der Auftrag abgebrochen. Außerdem
			musst du '.formatCash(floor($quest["oncomplete"]["cash"]*0.1)).' {money} Strafe zahlen, was automatisch von deinem Firmenkonto abgebucht
			wird. Du kannst den Auftrag danach erneut annehmen.</i>

			<p>Wenn du den Auftrag erledigst, bekommst du:</p>
			<table class="ordered">
			<tr>
				<th>Erfahrungspunkte:</th>
				<td>'.formatCash($quest["oncomplete"]["xp"]).' {eye}</td>
			</tr>
			<tr>
				<th>Geld*:</th>
				<td>'.formatCash($quest["oncomplete"]["cash"]).' {money}</td>
			</tr>
			</table> <br />
			<i>* das verdiente Geld wird auf das Firmenkonto überwiesen</i>');

			$_SESSION['secHash'] = md5(time().mt_rand(1000, 10000));

			$this->output('options', array(
				'show_quests/check/'.$name.'/accept/'.$_SESSION['secHash'] => 'Aufgabe annehmen',
				'show_quests' => 'Zurück zur Übersicht'
			));
			return;
		}

		$this->output('maintext', 'Je nach deinem Level kannst du unterschiedliche
		Aufträge übernehmen. Sollten ich derzeit keine Aufträge mehr für dein Level haben,
		so sammel etwas Erfahrung und komm dann nochmal wieder! <br />
		Folgende Aufträge kannst du zur Zeit für mich übernehmen:');

		$oDesc = array();
		$oOpt = array();

		$todo = false;

		foreach ($quests as $name => $quest) {
			if ($quest["level"] > $this->user->level) {
				continue;
			}

			if (count($this->myQuests) > 0) {
				$isDone = false;
				foreach ($this->myQuests as $q) {
					if ($q->name == $name) {
						$isDone = true;
						break;
					}
				}

				if ($isDone) {
					continue;
				}
			}

			$todo = true;

			$oDesc["show_quests/check/".$name] = "<hr /><h3>".htmlspecialchars($quest["title"])."</h3>
			<p>".htmlspecialchars($quest["text"])."</p>";
			$oOpt["show_quests/check/".$name] = "mehr Informationen";
		}

		if (!$todo) {
			$oDesc["interact"] = "<i>Ich habe derzeit leider keine Aufträge für dich!</i>";
		} else {
			$oDesc["interact"] = "<hr />";
		}
		$oOpt["interact"] = "Zurück";

		$this->output('options_desc', $oDesc);
		$this->output('options', $oOpt);
	}
}
?>