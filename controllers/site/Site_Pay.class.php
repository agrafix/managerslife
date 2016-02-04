<?php
class Site_Pay extends Controller_Site {

	protected $_use_tpl = 'site/site_pay.html';


	public function show_Main() {

	}

	public function show_Ok() {
		Framework::TPL()->assign('status', 'ok');
	}

	public function show_Error() {
		Framework::TPL()->assign('status', 'error');

		if (is_numeric($this->get(1))) {
			$id = $this->get(1);
		} else {
			$id = -1;
		}

		Framework::TPL()->assign('error', $id);
	}
}
?>