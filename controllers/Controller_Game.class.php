<?php
abstract class Controller_Game extends Controller_GameAuth {

	protected $_use_tpl = '';

	protected $_use_scripts = array();

	public function __destruct() {
		$this->_use_scripts[] = "game";

		$this->_use_scripts = Framework::bundleJavaScripts($this->_use_scripts);

		$this->regenerateSecureHash();

		Framework::TPL()->assign("fSecureHash", $this->_secureHash);
		Framework::TPL()->assign("User", $this->user);
		Framework::TPL()->assign("js_scripts", $this->_use_scripts);
		Framework::TPL()->assign("template", $this->_use_tpl);
		Framework::TPL()->display("game.html");
	}

}
?>