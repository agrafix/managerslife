<?php
abstract class Controller_Ajax extends Controller {

	private $_json_output = array();

	protected function output($key, $val) {
		if ($key == "error" || $key == "success") {
			die("Key cant be error/success!");
		}

		$this->_json_output[$key] = $val;
	}

	protected function error($text) {
		$this->_json_output["error"] = $text;
		die();
	}

	private function quit() {
		if (!isset($this->_json_output["error"]) || empty($this->_json_output["error"])) {
			$this->_json_output["error"] = "";
			$this->_json_output["success"] = true;
		} else {
			$this->_json_output["success"] = false;
		}

		die(json_encode($this->_json_output));
	}

	public function __destruct() {
		$this->quit();
	}

}
?>