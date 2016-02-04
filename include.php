<?php
define('PATH', str_replace(PATH_SEPARATOR, '/', dirname(__FILE__)));

require_once PATH."/config.php";

if (file_exists(PATH.'/mlock')) {
	if (isset($_GET['q']) && strpos($_GET['q'], 'ajax') !== false) {
		echo '{"error":"not_loggedin","success":false}';
		exit;
	}
	else {
		header("Location: ".APP_DIR."static/html/wartungsarbeiten.html");
		exit;
	}
}

require_once PATH."/lib/autoload.php";
require_once PATH."/lib/smarty/Smarty.class.php";

session_name('managersession');
session_set_cookie_params(SESSION_MAX_AGE*4, APP_DIR);
session_start();
setcookie(session_name(),session_id(),time()+(SESSION_MAX_AGE*4), APP_DIR);

if (get_magic_quotes_gpc()) {
	function stripslashes_gpc(&$value) {
		$value = stripslashes($value);
	}

	array_walk_recursive($_GET, 'stripslashes_gpc');
	array_walk_recursive($_POST, 'stripslashes_gpc');
	array_walk_recursive($_COOKIE, 'stripslashes_gpc');
	array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}

if (!DEBUG) {
	error_reporting(0);
}

// connect to database
R::setup('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);

if (!DEBUG) {
	R::freeze(true);
}

//R::debug(true);

// configure framework
Framework::addControllerType('site');
Framework::addControllerType('game');
Framework::addControllerType('ajax');

Framework::setDefaultController('global', 'site/index');
Framework::setDefaultController('ajax', 'index');
Framework::setDefaultController('site', 'index');
Framework::setDefaultController('game', 'index');
?>