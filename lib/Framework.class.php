<?php
class Framework {

	/**
	 * save default controllers
	 * @var array
	 */
	private static $defaultControllers = array();

	/**
	 * save controller types
	 * @var array
	 */
	private static $controllerTypes = array();

	/**
	 * Template Engine
	 *
	 * @var Smarty
	 */
	private static $tpl = null;

	/**
	 * add a controller type
	 * @param string $name
	 */
	public static function addControllerType($name) {
		self::$controllerTypes[] = $name;
	}

	/**
	 * set default controller for a specified controller type. 'global' is default for everything
	 * @param string $type
	 * @param string $name
	 */
	public static function setDefaultController($type, $name) {
		self::$defaultControllers[$type] = $name;
	}

	public static function bundleJavaScripts($javaScripts) {

		return $javaScripts;

		if (DEBUG) {
			return $javaScripts; // no bundeling
		}

		$h = "";

		foreach($javaScripts as $script) {
			$h .= filectime(PATH."/static/js/".$script.".js");
		}

		$hash = sha1(implode(",", $javaScripts).$h);
		$bundleFile = PATH."/static/js/bundle/".$hash.".js";
		$extPath = "bundle/".$hash;

		if (!file_exists($bundleFile)) {
			$fp = fopen($bundleFile, "w");

			foreach ($javaScripts as $script) {
				$ct = file_get_contents(PATH."/static/js/".$script.".js");

				$ct = trim($ct);
				$ct = preg_replace("#//(.*)#", "", $ct);
				$ct = preg_replace("#/\*([^\*]*?)\*/#s", "", $ct);
				$ct = preg_replace("#/\*\*([^\*]*?)\*/#s", "", $ct);
				//$ct = preg_replace("#\s{2}#", "", $ct);

				fwrite($fp, $ct);
			}

			fclose($fp);
		}

		return array($extPath);
	}

	/**
	 * Get template engine
	 *
	 * @return Smarty
	 */
	public static function TPL() {
		if (self::$tpl == null) {
			self::$tpl = new Smarty();

			self::$tpl->template_dir = PATH."/templates";
			self::$tpl->compile_dir = PATH."/templates_c";
			self::$tpl->cache_dir = PATH."/cache";


			self::$tpl->assign("app_dir", APP_DIR);
			self::$tpl->assign("img_dir", APP_DIR."static/images/");
			self::$tpl->assign("css_dir", APP_DIR."static/css/");
			self::$tpl->assign("js_dir", APP_DIR."static/js/");
		}

		return self::$tpl;
	}

	/**
	 *
	 * redirect to url
	 * @param string $url
	 */
	public static function Redir($url) {
		$url = APP_DIR.$url;

		header("Location: $url");
		die();
	}

	/**
	 *
	 * generate random string
	 * @param int $length
	 * @return string
	 */
	public static function randomString($length) {
		$string = "";

		for ($i=0; $i<=$length; $i++) {
			$d=rand(1,30)%2;
			$string .= $d ? chr(rand(65,90)) : chr(rand(48,57));
		}

		return $string;
	}

	/**
	 *
	 * send plaintext email
	 * @param string $to
	 * @param string $title
	 * @param string $content
	 */
	public static function sendMail($to, $title, $content) {
		@mail($to, $title,
		$content,
				'From: Manager\'s Life Team <'.ADMIN_EMAIL.'>'."\r\n"
		.'MIME-Version: 1.0'."\r\n"
		.'Content-type: text/plain; charset=utf-8'."\r\n");
	}

	/**
	 * Return hash of given string
	 * @param string $text
	 */
	public static function hash($text) {
		return sha1(md5(md5($text).sha1(HASH_SALT)).$text);
	}

	/**
	 * loads the controller specified by the url
	 */
	public static function loadController($url) {

		if (!isset(self::$defaultControllers['global'])) {
			die("No global init controller defined!");
		}

		if (strpos($url, "/") === 0) {
			$url = substr($url, 1); // fix slashes
		}

		$c = preg_match('#(?P<name>[a-z0-9_]*)?/?'
		.'(?P<controller>[a-z0-9_]*)?/?'
		.'(?P<function>[a-z0-9_]*)?/?'
		.'(?P<param1>[a-z0-9_]*)?/?'
		.'(?P<param2>[a-z0-9_]*)?/?'
		.'(?P<param3>[a-z0-9_]*)?/?'
		.'(?P<param4>[a-z0-9_]*)?/?'
		.'#si', $url, $m);

		if (!$c) {
			die('Invalid URL given.');
		}

		// if empty controller type, or not existant, launch global one
		if (empty($m["name"]) || !in_array($m["name"], self::$controllerTypes)) {

			self::launchController('global',
							 	   self::$defaultControllers['global']);
			return;
		}

		$controllerType = ucfirst($m["name"]);
		$controllerName = ucfirst($m["controller"]);
		$controllerFunction = ucfirst($m["function"]);

		// if empty controller, or not existant, launch type-global one
		if (empty($controllerName) || !class_exists($controllerType.'_'.$controllerName, true)) {

			self::launchController($controllerType,
								   self::$defaultControllers[strtolower($controllerType)]);
			return;
		}

		// the controller exists lets launch it!
		self::launchController($controllerType, $controllerName, $controllerFunction,
							   $m["param1"], $m["param2"], $m["param3"], $m["param4"]);

	}

	/**
	 * start a controller
	 * @param string $name
	 */
	private static function launchController($type, $name, $func='main', $p1='', $p2='', $p3='', $p4='') {

		if (strpos($name, "/") !== false) {
			$p = explode("/", $name);
			$type = $p[0];
			$name = $p[1];
		}

		$className = ucfirst($type)."_".ucfirst($name);
		$functionName = "show_".ucfirst($func);

		$c = new $className();
		$c->readSecureHash();
		$c->_controllerName = $name;
		$c->_controllerFunc = $func;

		$c->_getDefine(1, $p1);
		$c->_getDefine(2, $p2);
		$c->_getDefine(3, $p3);
		$c->_getDefine(4, $p4);

		$c->init();

		if (!method_exists($c, $functionName)) {
			$functionName = 'show_Main';
		}
		$c->$functionName();
	}

}
?>