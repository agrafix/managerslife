<?php
abstract class Controller_GameAuth extends Controller {

	protected $session;

	protected $user;

	protected $_rights = array();

	public function init() {

		// check if logged in session is valid, if not redir to main page
		if (!isset($_SESSION['loginHash'])) {
			Framework::Redir("site/index");
			die();
		}

		$activeSession = R::findOne('session', ' hash = ? AND ip = ? AND expires > ?', array(
			$_SESSION['loginHash'],
			$_SERVER['REMOTE_ADDR'],
			time()
		));

		if (!$activeSession) {
			unset($_SESSION['loginHash']);
			Framework::Redir("site/index/main/session_expired");
			die();
		}

		$activeSession->expires = time() + (SESSION_MAX_AGE*2);
		R::store($activeSession);

		$this->session = $activeSession;

		$this->user = R::load('user', $this->session->user->getId());

		Framework::TPL()->assign('user_premium', $this->user->hasPremium());

		// check needed rights if any
		foreach ($this->_rights as $r) {
			if (!$this->user->hasRight($r)) {
				Framework::Redir("game/index");
				die();
			}
		}

	}

}
?>