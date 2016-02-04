<?php
class Model_Company_quest extends RedBean_SimpleModel {

	public function complete() {
		$cs = Config::getConfig("company_quests");

		$user = R::relatedOne($this->bean, 'user');
		$company = R::findOne('company', 'user_id = ?', array($user->id));

		$company_ress = R::findOne('company_ress', 'company_id = ?', array($company->id));
		$company_products = R::findOne('company_products', 'company_id = ?', array($company->id));

		$details = $cs[$this->name];

		foreach ($details["needs"] as $n) {
			if ($n["type"] == "resource") {
				if ($company_ress->$n["name"] < $n["amount"]) {
					throw new Exception("Es ist nicht genügend {r_".$n["name"]."} vorhanden!");
					return;
				}
				else {
					$company_ress->$n["name"] -= $n["amount"];
				}
			}
			elseif ($n["type"] == "product") {
				if ($company_products->$n["name"] < $n["amount"]) {
					throw new Exception("Es ist nicht genügend {p_".$n["name"]."} vorhanden!");
					return;
				}
				else {
					$company_products->$n["name"] -= $n["amount"];
				}
			}
		}

		$this->completed = true;
		$company->balance += $details["oncomplete"]["cash"];

		R::begin();
		$user->changeXP($user->xp + $details["oncomplete"]["xp"]);
		R::store($this->bean);
		R::store($company);
		R::store($company_products);
		R::store($company_ress);
		R::commit();

	}

	public function cancel() {
		$cs = Config::getConfig("company_quests");

		$details = $cs[$this->name];

		//$company = R::relatedOne($this->bean, 'company');
		$user = R::relatedOne($this->bean, 'user');

		if ($user == null) {
			R::trash($this->bean);
			return;
		}

		$company = R::findOne('company', 'user_id = ?', array($user->id));

		$company->balance -= floor($details["oncomplete"]["cash"]*0.1);

		R::begin();
		R::trash($this->bean);
		R::store($company);
		R::commit();
	}

}
?>