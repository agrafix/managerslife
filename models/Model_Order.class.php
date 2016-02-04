<?php
class Model_Order extends RedBean_SimpleModel {
	public function cancel_Order() {

		// when deleting an order, cash and ress must be returned to company
		$company = R::relatedOne($this->bean, 'company');

		if ($this->type == 'buy') {
			// buy order needs cash updated

			$company->balance += $this->r_amount * $this->price;

			R::store($company);
			R::trash($this->bean);

		} else {
			// sell order needs ress updated
			if ($this->r_type == 'resource') {
				$holder = R::findOne('company_ress', ' company_id = ?', array($company->id));
			} else {
				$holder = R::findOne('company_products', ' company_id = ?', array($company->id));
			}

			$holder->{$this->r_name} += $this->r_amount;

			R::store($holder);
			R::trash($this->bean);
		}

	}
}
?>