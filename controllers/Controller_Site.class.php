<?php
abstract class Controller_Site extends Controller {

	protected $_use_tpl = '';

	protected $_use_scripts = array();

	private $cacheID;


	public function init() {

		$this->cacheID = substr(md5("site".$this->_controllerFunc.$this->_controllerName
		.$this->get(1).$this->get(2).$this->get(3).$this->get(4)), 0, 15);

		Framework::TPL()->cache_lifetime = 24 * 3600; // 24 hours
		Framework::TPL()->caching = Smarty::CACHING_LIFETIME_CURRENT;

		if (Framework::TPL()->isCached("site.html", $this->cacheID)) {
			die();
		}

	}

	public function __destruct() {
		Framework::TPL()->assign("js_scripts", $this->_use_scripts);
		Framework::TPL()->assign("template", $this->_use_tpl);
		Framework::TPL()->display("site.html", $this->cacheID);
	}

}
?>