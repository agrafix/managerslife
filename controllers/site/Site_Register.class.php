<?php
class Site_Register extends Controller_Site {

	protected $_use_tpl = 'site/site_register.html';

	protected $_use_scripts = array('site/site_register');

	public function show_Main() {
		if (!ALLOW_REGISTER) {
			Framework::TPL()->assign('no_register', true);
			return;
		}

		$refed_by = $this->get(1);

		if (is_numeric($refed_by)) {
			Framework::TPL()->assign('by', $refed_by);
		} else {
			Framework::TPL()->assign('by', -1);
		}
	}

	public function show_Activate() {
		$aHash = $this->get(1);

		$user = R::findOne('user', ' activation_code = ?', array($aHash));

		if (!$user) {
			echo "Ungültiger Aktivierungscode";
			exit;
		}

		$user->activation_code = '';
		$user->is_active = true;

		R::store($user);

		Framework::Redir("site/index/main/login");
	}
}
?>