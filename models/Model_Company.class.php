<?php
class Model_Company extends RedBean_SimpleModel {
	/**
	* ensure stuff in db is not messed up :)
	* @throws Exception
	*/
	public function update() {

		$forbidden = array('admin', 'root', 'system');

		if (in_array(strtolower($this->name), $forbidden)) {
			throw new Exception("Der Name ist verboten");
		}

		if (!preg_match('#^[a-z0-9 ]{4,20}$#si', $this->name)) {
			throw new Exception("Der Name $this->name muss zwischen 4 und 20 Zeichen lang sein
					und darf nur aus den Zeigen A-Z, a-z, 0-9 und Leerzeichen bestehen!");
		}

	}
}
?>