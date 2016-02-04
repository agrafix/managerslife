<?php
abstract class Controller_AjaxGame extends Controller_Ajax {

	protected $session;
	protected $user;

	protected $_rights = array();

	/**
	 * this is stupid
	 * @see Controller_GameAuth::init()
	 */
	public function init() {
		// check if logged in session is valid, if not redir to main page
		if (!isset($_SESSION['loginHash'])) {
			$this->error('not_loggedin');
			die();
		}

		// check if secure hash is valid
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (!$this->checkSecureHash($_POST['fSecureHash'])) {
				$this->error('Ungültiger fSecureHash.');
				die();
			}
		}

		$activeSession = R::findOne('session', ' hash = ? AND ip = ? AND expires > ?', array(
		$_SESSION['loginHash'],
		$_SERVER['REMOTE_ADDR'],
		time()
		));

		if (!$activeSession) {
			unset($_SESSION['loginHash']);
			$this->error('not_loggedin');
			die();
		}

		$activeSession->expires = time() + SESSION_MAX_AGE;
		R::store($activeSession);

		$this->session = $activeSession;

		$this->user = $this->session->user;//R::load('user', $this->session->user->getId());

		// check needed rights if any
		foreach ($this->_rights as $r) {
			if (!$this->user->hasRight($r)) {
				$this->error('no_rights');
				die();
			}
		}
	}

	public function systemChat($msg, $map, $toUser=null) {

		$message = R::dispense('chat_message');

		if ($toUser == null) {
			$message->type = 'public';
		} else {
			$message->type = 'private';
			$message->visible_for = $toUser;
		}

		$message->map = $map;
		$message->time = time();
		$message->author = 'sys';
		$message->text = $msg;

		R::store($message);
	}

}
?>