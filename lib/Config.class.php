<?php
class Config {

	private static $cache = array();


	public static function getConfig($name) {
		if (!isset(self::$cache[$name])) {
			self::$cache[$name] = json_decode(file_get_contents(PATH.'/config/'.$name.'.json'), true);
		}

		return self::$cache[$name];
	}

}
?>