<?php
abstract class Controller {

	private $_get = array();

	protected $_secureHash = "";

	public $_controllerName = "";

	public $_controllerFunc = "";

	public function _getDefine($id, $val) {
		$this->_get[$id] = $val;
	}

	protected function get($id) {
		return $this->_get[$id];
	}

	protected function checkSecureHash($hash) {
		return ($hash == $this->_secureHash);
	}

	public function readSecureHash() {
		$salt = SECURE_HASH_SALT;
		if (!isset($_SESSION['frameworkHash']) || $_SESSION['frameworkHash'] == '' || empty($_SESSION['frameworkHash'])) {
			$this->regenerateSecureHash();
		}
		else {
			$this->_secureHash = $_SESSION['frameworkHash'];
		}
	}

	public function regenerateSecureHash() {
		$salt = SECURE_HASH_SALT;
		$_SESSION['frameworkHash'] = sprintf('%x', crc32($salt.microtime(true).mt_rand(1000, 2000)));

		$this->_secureHash = $_SESSION['frameworkHash'];
	}

	public function init() {
		// nothing here
	}

	public abstract function show_Main();
}
?>