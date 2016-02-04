<?php
class Site_Account extends Controller_Site {
	protected $_use_tpl = 'site/site_account.html';

	protected $_use_scripts = array();

	public function show_Main() {
		//die('no function');
	}

	public function show_Reactivate() {
		Framework::TPL()->assign('acc_type', 'reactivate');
		$this->_use_tpl = 'site/site_account_forgot.html';
		$this->_use_scripts[] = 'site/site_account_forgot';
	}

	public function show_Forgot() {
		Framework::TPL()->assign('acc_type', 'forgot');
		$this->_use_tpl = 'site/site_account_forgot.html';
		$this->_use_scripts[] = 'site/site_account_forgot';
	}
}
?>