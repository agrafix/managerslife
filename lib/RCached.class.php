<?php
class RCached {

	public static function clear() {
		foreach (glob(PATH.'/cache/*.rcache') as $fn) {
			@unlink($fn);
		}
	}

	public static function findOne($type, $sql = "1", $values = array(), $cacheId="0") {
		$cacheName = PATH.'/cache/r_'.sha1('single'.$type.$sql.json_encode($values).$cacheId).'.rcache';

		if (!file_exists($cacheName)) {
			if (strpos($sql, 'LIMIT') === false) {
				// append limit query
				$sql .= ' LIMIT 1';
			}

			$data = R::findOne($type, $sql, $values);

			$fp = fopen($cacheName, 'w');
			fwrite($fp, serialize($data));
			fclose($fp);

			return $data;
		}

		return unserialize(file_get_contents($cacheName));
	}

	public static function find($type, $sql = "1", $values = array(), $cacheId="0") {
		$cacheName = PATH.'/cache/r_'.sha1('multiple'.$type.$sql.json_encode($values).$cacheId).'.rcache';

		if (!file_exists($cacheName)) {
			$data = R::find($type, $sql, $values);

			$fp = fopen($cacheName, 'w');
			fwrite($fp, serialize($data));
			fclose($fp);

			return $data;
		}

		return unserialize(file_get_contents($cacheName));
	}

}
?>