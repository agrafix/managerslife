<?php
abstract class Controller_AjaxGameNPC extends Controller_AjaxGame {

	protected $npc;

	protected $_type = 'map_npc';

	protected $myType = '';

	protected $myNPCQuest;

	protected $myNPCQuestRole = 'none';

	protected $myNPCQuestData = array();

	public function init() {
		parent::init();

		$id = $_POST["id"];

		// check if npc is valid
		if (!is_numeric($id) || $id <= 0) {
			$this->error('Invalid '.$this->_type.'-ID');
		}

		$this->npc = R::load($this->_type, $id);

		if ($this->npc->getID() != $id) {
			$this->error($this->_type.' doesnt exist!');
		}

		// check if npc has right type
		if ($this->npc->type != $this->myType) {
			$this->error("This ".$this->_type." is not of ".$this->myType);
		}

		// check if user is nearby
		$userPos = R::findOne('map_position', ' user_id = ?', array($this->user->getID()));

		$dist = sqrt(pow($this->npc->x - $userPos->x, 2) + pow($this->npc->y - $userPos->y, 2));
		if ($dist > 3 || $this->npc->map != $userPos->map) {
			$this->error('Du bist zu weit entfehrt!');
		}

		// check if quest availible
		$this->myNPCQuest = R::relatedOne($this->user, 'quests_npc', ' complete_time = 0 AND (startnpc_id = ? OR stopnpc_id = ?)',
		array($this->npc->getID(), $this->npc->getID()));

		if ($this->myNPCQuest != null) {
			if ($this->myNPCQuest->startnpc_id == $this->npc->getID()) {
				if ($this->myNPCQuest->accepted == 0) {
					$this->myNPCQuestRole = 'startnpc';
					$startnpc = $this->npc->name;
					$stopnpc = R::getCell('SELECT `name` FROM map_npc WHERE id = ?',
										   array($this->myNPCQuest->stopnpc_id));
				}
			} else {
				if ($this->myNPCQuest->accepted == 1) {
					$this->myNPCQuestRole = 'stopnpc';
					$stopnpc = $this->npc->name;
					$startnpc = R::getCell('SELECT `name` FROM map_npc WHERE id = ?',
					array($this->myNPCQuest->startnpc_id));
				}
			}

			if ($this->myNPCQuestRole != 'none') {
				$all = Config::getConfig('npc_quests');
				$this->myNPCQuestData = $all[$this->myNPCQuest->quest_id][$this->myNPCQuestRole];

				$this->myNPCQuestData["text"] = str_replace(array('{startnpc}', '{stopnpc}'),
															array($startnpc, $stopnpc),
															$this->myNPCQuestData["text"]);

				foreach ($this->myNPCQuestData["items"] as $k => $v) {
					$this->myNPCQuestData["items"][$k]["param"] = str_replace(array('{startnpc}', '{stopnpc}'),
															array($startnpc, $stopnpc),
															$v["param"]);
				}

				if ($this->_controllerFunc == 'Interact') {
					$this->output('quest', '<br /> <br />
					<b>Quest:</b> '.htmlspecialchars($this->myNPCQuestData["text"]).' <br />
					<a href="#questing">'.($this->myNPCQuestRole == 'startnpc' ? 'annehmen' : 'abschließen').'</a>');
				}
			}
		}
	}

	public function show_Questing() {
		if ($this->myNPCQuestRole == 'none') {
			$this->error('Dieses NPC hat keine Quest für dich!');
		}

		if ($this->myNPCQuestRole == 'startnpc' && $this->myNPCQuest->accepted == 0) {
			$this->output('maintext', $this->myNPCQuestData["text2"]. ' Denk daran:
			Je schneller du dieses Quest erledigst, desto mehr Erfahrungspunkte bekommst
			du als Belohnung!');

			R::begin();
			foreach ($this->myNPCQuestData["items"] as $k => $v) {
				$inv = R::dispense('inventory');
				$inv->amount = $v["amount"];
				$inv->param = $v["param"];
				$inv->item_id = $v["id"];
				$inv->user = $this->user;
				R::store($inv);
			}

			$this->myNPCQuest->accepted = 1;
			$this->myNPCQuest->accept_time = time();
			R::store($this->myNPCQuest);

			R::commit();
		}
		elseif ($this->myNPCQuestRole == 'stopnpc' && $this->myNPCQuest->accepted == 1) {
			// check if user has needed items in inventory
			$items = array();

			foreach ($this->myNPCQuestData["items"] as $k => $v) {
				$inv = R::findOne('inventory', ' item_id = ? AND amount >= ? AND param = ? AND user_id = ?',
				array($v["id"], $v["amount"], $v["param"], $this->user->getID()));

				if ($inv == null) {
					$this->output('maintext', 'Leider hast du nicht alle nötigen Items dabei!');
					$this->output('options', array('interact' => 'Zurück'));
					return;
				}

				$items[$v["id"]]["data"] = $inv;
				$items[$v["id"]]["amount"] = $v["amount"];
			}

			// calculate bonus
			$this->myNPCQuest->complete_time = time();

			$took = $this->myNPCQuest->complete_time - $this->myNPCQuest->accept_time;

			$xp = $this->myNPCQuestData["base_xp"];
			$cash = $this->myNPCQuestData["base_cash"];

			// randomize xp/cash
			$xp += 2 - mt_rand(0, 4);
			$cash += 2 - mt_rand(0, 4);

			if ($took > $this->myNPCQuestData["base_time"]) {

				$diff = $took - $this->myNPCQuestData["base_time"];

				// subtract of the bonus
				$xp -= floor($diff / 10); // every ten seconds late subtract 1xp
				if ($xp < 1) { $xp = 1; }

				$cash -= floor($diff / 5); // every five seconds late substract 1 cash
				if ($cash < 0) { $cash = 0; }
			}


			R::begin();

			$this->user->cash += $cash;
			$this->user->changeXP($this->user->xp + $xp);

			// take items from inventory
			foreach ($items as $i) {
				$i["data"]->amount -= $i["amount"];
				R::store($i["data"]);
			}

			R::store($this->myNPCQuest);

			R::commit();

			$quest = R::dispense('quests_npc');
			$quest->giveNewQuest($this->user);

			$this->output('maintext', $this->myNPCQuestData["text2"]. ' <br />
			<b>Du erhälst als Belohnung: '.formatCash($cash).' {money} und '.formatCash($xp).' {eye}</b>');
		}
		else {
			$this->output('maintext', 'Ich habe leider im Moment nichts zu tun für dich!');
		}

		$this->output('options', array('interact' => 'Zurück'));
	}

	/**
	 * enables buy/selling for an npc
	 * @param string $type buy/sell From the view of the NPC
	 * @param string $intoTable into which table should be sold/bought from?
	 * @param string $funcName name of the method this function is called
	 */
	protected function dealerNPC($type, $intoTable, $funcName) {

		if ($this->get(1) == "handle" && is_numeric($this->get(2))) {
			$itemID = $this->get(2);

			$maxAmount = 0;
			$val = 0;

			if ($type == "sell") {
				$item = R::findOne('item', ' id = ?', array($itemID));
				$val = $item->value;

				$maxAmount = floor($this->user->cash / $val);
			} else {
				$_i = R::findOne('inventory', ' id = ? AND user_id = ?', array($itemID, $this->user->getID()));
				$item = $_i->item;
				$val = floor($item->value*NPC_BUY_PRICE);

				$maxAmount = $_i->amount;
			}

			if ($maxAmount == 0) {
				$this->output("maintext", "Du kannst ".htmlspecialchars($item->name)." leider nicht ".($type == "sell" ? "kaufen" : "verkaufen"));
				$this->output('options', array($funcName => 'Zurück'));
				return;
			}

			if (isset($_POST["amount"]) && is_numeric($_POST["amount"]) && $_POST["amount"] > 0) {
				$amount = $_POST["amount"];

				if ($amount > $maxAmount) {
					$this->output("maintext", "Du kannst ".htmlspecialchars($item->name)." nicht ".htmlspecialchars($amount)."x ".($type == "sell" ? "kaufen" : "verkaufen"));
					$this->output('options', array($funcName => 'Zurück'));
					return;
				}

				R::$adapter->startTransaction();

				if ($type == "sell") { // sell items to user
					$inv = R::dispense('inventory');
					$inv->amount = $amount;
					$inv->param = "";
					$inv->user = $this->user;
					$inv->item = $item;

					R::store($inv);

					$this->user->cash -= $amount*$val;
					R::store($this->user);

				} else { // buy from user
					$_i->amount -= $amount;

					if ($_i->amount <= 0) {
						R::trash($_i);
					} else {
						R::store($_i);
					}

					$this->user->cash += $amount*$val;
					R::store($this->user);
				}

				R::$adapter->commit();

				$this->output("maintext", "Du hast ".htmlspecialchars($item->name)." erfolgreich ".htmlspecialchars($amount)."x ".($type == "sell" ? "gekauft" : "verkauft"));
				$this->output('options', array('interact' => 'Zurück'));

				$this->output('speak', 'Danke dir!');
				return;
			}

			$this->output("maintext", "Wie oft möchtest du ".htmlspecialchars($item->name)." zu ".$val." {money} pro Stück
			".($type == "sell" ? "kaufen" : "verkaufen")."? (maximal ".$maxAmount."x)");

			$this->output("form", array(
				"target" => $funcName."/handle/".$itemID,

				"elements" => array(
					array("desc" => "Anzahl", "name" => "amount", "type" => "text", "value" => 1)
				)
			));

			$this->output('options', array($funcName => 'Zurück'));

			return;
		}

		$this->output("maintext", "Was möchtest du ".($type == "sell" ? "kaufen" : "verkaufen")."?");

		$o = array();

		$listing = array();

		if ($type == "sell") {
			$items = R::related($this->npc, 'item');
			foreach ($items as $i) {
				$listing[] = array(
									'id' => $i->id,
									'name' => $i->name,
									'amount' => 1,
									'value' => $i->value
				);
			}
		} else {
			$inv = R::find('inventory', ' user_id = ?', array($this->user->getID()));
			foreach ($inv as $i) {
				$listing[] = array(
					'id' => $i->id,
					'name' => $i->item->name,
					'amount' => $i->amount,
					'value' => floor(NPC_BUY_PRICE*$i->item->value)
				);
			}
		}

		foreach ($listing as $l) {
			$o[$funcName."/handle/".$l["id"]] = $l["name"]. " (".$l["value"]." {money} pro Item)";
		}

		$o["interact"] = "Zurück";
		$this->output('options', $o);
	}

	public function show_Main() {
		$this->error('invalid Ajax-Call');
	}

	public abstract function show_Interact();
}
?>