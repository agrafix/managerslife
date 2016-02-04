<?php
/**
 * Handle the crontabs
 * to be run every 15 minutes, to balance load
 *
 * @author alexander
 *
 */
class Site_Cron extends Controller {

	/**
	 * calculates a companys production & running costs
	 *
	 * can sadly not be done in a single sql-stmt...
	 * @TODO fix this(?)
	 */
	protected function cronProduction() {
		$includeFrom = time() - 30 * 60; // every 30mins

		// load production data
		$_mress = Config::getConfig('resources');
		$_mprod =  Config::getConfig('products');
		$machineData = array_merge($_mress, $_mprod);

		// get companys
		$companys = R::find('company', ' lastCalc <= ?', array($includeFrom));

		// for statistic stuff
		$stat = array("ress" => 0, "prod" => 0, "comp" => 0);

		foreach ($companys as $company) {
			// dont run bankrupt companies
			if ($company->balance < 0) {
				$company->lastCalc = time();
				R::store($company);
				continue;
			}

			// get the machine data of selected company
			$machines = R::findOne('company_machines', ' company_id = ?', array($company->id));

			// get resource and product data of selected company
			$resources =  R::findOne('company_ress', ' company_id = ?', array($company->id));
			$products =  R::findOne('company_products', ' company_id = ?', array($company->id));

			// store the costs for this company
			$costs = 0;

			// make sure ress/prod not stored without being changed
			$ress_changed = false;
			$prod_changed = false;

			foreach ($machines as $name => $amount) {
				if ($name == 'id' || $name == 'company_id' || $amount == 0) {
					continue;
				}

				$rp = $machineData[$name];

				// calculate the maximum amount of products produced in given hour
				$maxAmount = round(($rp["machine"]["prod_per_hour"]*$amount) / 2);

				// now check how many can be produced
				$prod = (isset($_mprod[$name]));

				foreach ($rp["needs"] as $n) {
					if (is_array($n)) {
						$pAmount = $n["amount"];
						$mName = $n["ress"];
					} else {
						// result is a resource
						$pAmount = 1;
						$mName = $n;
					}

					$avail = $resources->$mName;
					$productsPossible = $avail / $pAmount;

					$maxAmount = min($productsPossible, $maxAmount);
				}

				// substract needed products
				foreach ($rp["needs"] as $n) {
					$total = 0;

					if (is_array($n)) {
						$total = $maxAmount * $n["amount"];
						if (isset($_mprod[$n["ress"]])) {
							$products->$n["ress"] -= $total;
						} else {
							$resources->$n["ress"] -= $total;
						}
						$prod_changed = true;
					} else {
						$total = $maxAmount;
						if (isset($_mprod[$n])) {
							$products->$n -= $total;
						}
						else {
							$resources->$n -= $total;
						}
						$ress_changed = true;
					}


				}

				// give the results to company
				if ($maxAmount > 0) {
					if ($prod) {
						$products->$name += $maxAmount;
						$prod_changed = true;
					} else {
						$resources->$name += $maxAmount;
						$ress_changed = true;
					}
				}

				// calculate costs
				if ($maxAmount > 0) {
					$costs += $rp["machine"]["running_cost"] * $amount;
				} else {
					//$costs += $rp["machine"]["running_cost"] * $amount * 0.1;
					// idle only costs 10%
					$costs += 0;
				}
			}

			// save
			if ($ress_changed) {
				R::store($resources);
				$stat["ress"]++;
			}

			if ($prod_changed) {
				R::store($products);
				$stat["prod"]++;
			}

			// update last calculation
			$company->balance -= $costs;
			$company->lastCalc = time();
			R::store($company);
			$stat["comp"]++;
		}

		$this->log("production", "Updated ".$stat["ress"]."x Ress, ".$stat["prod"]."x Prod, ".$stat["comp"]."x Companies");
	}

	/**
	 * company questing
	 */
	protected function cronCompanyQuest() {
		$completed = 0;
		$canceled = 0;

		// find quests that expired
		$quests = R::find('company_quest', ' valid_until < ? AND completed = 0', array(time()));

		foreach ($quests as $q) {
			$user = R::relatedOne($q, 'user');

			if ($user == null) {
				$this->log('cronCompanyQuery', "Quest ".$q->id." has no owner?!");
			}

			if ($user != null && $user->hasPremium()) {
				// try to complete quest
				try {
					$q->complete();
					$completed++;
				} catch (Exception $e) {
					// didn't work, so cancel
					$q->cancel();
					$canceled++;
				}
			} else {
				$q->cancel();
				$canceled++;
			}
		}

		// log
		$this->log("company_quest", "Completed: $completed, Canceled: $canceled");
	}

	/**
	 * calculates the market price of products and resources
	 */
	protected function cronMarketPrice() {
		$rp = array_merge(Config::getConfig('resources'), Config::getConfig('products'));

		$types = array('buy', 'sell');

		foreach ($rp as $name => $r) {
			if (!isset($r['needs'])) {
				continue;
			}

			foreach ($types as $t) {
				$avg = R::getCell('SELECT AVG(price) FROM `order` WHERE `type` = ? AND `r_name` = ?', array($t, $name));

				if ($avg == 'NULL' || $avg == null) {
					$last_avg = R::getCell('SELECT `value` FROM market_price WHERE `type` = ? AND `name` = ? ORDER BY `time` DESC LIMIT 1', array($t, $name));
					if ($last_avg == 'NULL' || $last_avg == null) {
						$avg = $r['base_price'];
					} else {
						$avg = $last_avg;
					}
				}

				$market_price = R::dispense('market_price');
				$market_price->type = $t;
				$market_price->name = $name;
				$market_price->time = time();
				$market_price->value = $avg;
				R::store($market_price);
			}
		}

		$this->log("market_price", "New prices calced");
	}

	/**
	 * cleanup market
	 */
	protected function cronMarket() {
		// refresh auto orders
		$c_ct = R::exec("UPDATE `order` SET `date` = ? WHERE `automatic` = 1 AND `a_expires` > ? AND date < ?",
		array(time(), time(), time() - (3 * 24 * 3600)));

		// cancel old orders
		$c_cancel = 0;

		$orders = R::find('order', ' date < ?',
		array(time() - (3 * 24 * 3600)));

		foreach ($orders as $order) {
			$order->cancel_Order();
			$c_cancel++;
		}

		$this->log('market', 'Refreshed: '.$c_ct.', Canceled: '.$c_cancel);
	}

	// buy and sell of the market
	protected function cronNPCOffers() {
		$data = array_merge(Config::getConfig('products'), Config::getConfig('resources'));

		$orders = R::find('order', ' date > ?',
		array(time() - (3 * 24 * 3600)));

		foreach ($orders as $order) {
			// chance, that the NPC cares about this trade is 50%
			if (mt_rand(0, 100) <= 50) {
				continue;
			}

			// amount the NPC would sell/buy
			$amount = floor($order->r_amount * (mt_rand(20, 80) / 100));

			if ($amount == 0) {
				continue;
			}

			// NPC only reacts to buy events if someone else sells
			if ($order->type == 'buy') {
				$search = R::getCell('SELECT
					COUNT(id)
				FROM
					`order`
				WHERE
					`type` = ? AND
					`r_type` = ? AND
					`r_name` = ?', array('sell', $order->r_type, $order->r_name));

				if ($search == 0) {
					continue;
				}
			}

			// now check the price
			$price = $data[$order->r_name]["base_price"];

			$dif = floor(abs($price - $order->price) / 3);

			$isOk = false;

			if ($dif == 0) {
				$isOk = true;
			}
			elseif (mt_rand(0, $dif) == ($dif-1)) {
				$isOk = true;
			}
			else {
				continue;
			}

			// now act
			$order->r_amount -= $amount;
			$cost = $amount * $order->price;

			$company = R::relatedOne($order, 'company');

			if ($order->type == 'sell') {
				$company->balance += $cost;
				R::store($company);
			} else {
				// company is buying
				if ($order->r_type == 'resource') {
					R::exec('UPDATE `company_ress`
					SET
					 `'.$order->r_name.'` = `'.$order->r_name.'` + ?
					WHERE
						company_id = ?', array($amount, $company->getID()));
				} else {
					R::exec('UPDATE `company_products`
							 SET
								`'.$order->r_name.'` = `'.$order->r_name.'` + ?
							 WHERE
								company_id = ?', array($amount, $company->getID()));
				}
			}

			if ($order->amount == 0) {
				R::trash($order);
			} else {
				R::store($order);
			}
		}
	}

	/**
	 * handle company rules
	 */
	protected function cronHandleCrule() {
		// delete old rules
		R::exec('DELETE FROM `crule` WHERE `until` < ?', array(time()));

		// get new rules
		$crules = R::find('crule');

		// counter
		$counter = array('buy' => 0, 'sell' => 0);

		foreach ($crules as $crule) {
			$company = R::relatedOne($crule, 'company');

			$amount = R::getCell('SELECT
				`'.$crule->r_name.'`
			FROM
				`company_'.($crule->r_type == 'product' ? 'products' : 'ress').'`
			WHERE
				company_id = ?', array($company->id));

			// fix amount with existant orders
			$existant = R::related($company, 'order', 'r_name = ? AND type = ?',
			array($crule->r_name, $crule->action));
			foreach ($existant as $e)  {
				if ($e->type == 'buy') {
					$amount += $e->r_amount;
				} else {
					$amount -= $e->r_amount;
				}
			}

			$order = R::dispense('order');
			$order->type = $crule->action;
			$order->r_type = $crule->r_type;
			$order->r_name = $crule->r_name;
			$order->price = $crule->r_price;
			$order->date = time();
			$order->automatic = false;
			$order->a_expires = 0;

			$sold = 0;

			if ($crule->action == 'buy' && $amount < $crule->r_limit) {
				// create buy order
				$maxBuy = $crule->r_limit - $amount;
				$costs = $maxBuy * $crule->r_price;

				if ($costs > $company->balance) {
					$maxBuy = floor($company->balance / $crule->r_price);
				}

				if ($maxBuy == 0) {
					continue;
				}

				$company->balance -= $maxBuy * $crule->r_price;

				$order->r_amount = $maxBuy;

				$counter['buy']++;

			} else if ($crule->action == 'sell' && $amount > $crule->r_limit) {
				// create sell order
				$order->r_amount = $amount - $crule->r_limit;
				$sold += $amount - $crule->r_limit;

				$counter['sell']++;

			} else {
				continue;
			}

			R::begin();
			if ($sold != 0) {
				R::exec('UPDATE
					`company_'.($crule->r_type == 'product' ? 'products' : 'ress').'`
				SET
					`'.$crule->r_name.'` = `'.$crule->r_name.'`-?
				WHERE
					company_id = ?', array($sold, $company->id));
			}
			R::store($order);
			R::store($company);
			R::associate($order, $company);
			R::commit();
		}

		$this->log('handleCrule', $counter['buy'].' new buy-orders, '.$counter['sell'].' new sell-orders');
	}

	/**
	 * calculates bank interest
	 */
	protected function cronBank() {
		$yesterday = time() - 24 * 3600; // every day

		$r = R::exec("UPDATE bank_account SET
			balance = CEIL(balance * POW(1.01, FLOOR((? - lastCalc) / (24 * 3600)))),
			lastCalc = ?
			WHERE
			lastCalc <= ?", array(time(), time(), $yesterday));

		$this->log("bank", "Updated balance for ".$r." accounts");
	}

	/**
	 * log what happend
	 * @param string $function
	 * @param string $msg
	 */
	protected function log($function, $msg) {
		$l = R::dispense('cron_log');
		$l->time = time();
		$l->function = $function;
		$l->msg = $msg;

		R::store($l);
	}

	/**
	 * run
	 *
	 * @see Controller::show_Main()
	 */
	public function show_Main() {

		if ($this->get(1) != CRON_KEY) {
			die('Invalid cronjob-KEY');
		}

		set_time_limit(0);

		$time = microtime(true);

		if ($this->get(2) == '') {
			$this->cronBank();
			$this->cronProduction();
			$this->cronCompanyQuest();
			$this->cronMarket();
			$this->cronHandleCrule();
			$this->cronMarketPrice();
		}
		elseif ($this->get(2) == 'hourly') {
			$this->cronNPCOffers();
		}

		$finish = floor((microtime(true) - $time) * 1000);

		$this->log('main', 'took '.$finish.'ms');

		echo 'ok';
	}
}
?>