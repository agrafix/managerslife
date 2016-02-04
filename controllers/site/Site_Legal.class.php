<?php
class Site_Legal extends Controller_Site {

	protected $_use_tpl = 'site/site_legal.html';

	public function show_Main() {
		Framework::Redir("site/index");
	}

	private function displayLegal($title, $internal_type) {
		Framework::TPL()->assign('legal_title', $title);
		Framework::TPL()->assign('legal_tpl', $internal_type.".html");
	}

	public function show_Rules() {
		$this->displayLegal('Spielregeln', 'rules');
	}

	public function show_Data() {
		$this->displayLegal('Datenschutz', 'data');
	}

	public function show_Agb() {
		$this->displayLegal('AGBs', 'agb');
	}

	public function show_Disclaimer() {
		$this->displayLegal('Disclaimer', 'disclaimer');
	}

	public function show_Imprint() {
		$this->displayLegal('Impressum', 'imprint');
	}

	public function show_Credits() {
		$this->displayLegal('Credits', 'credits');
	}
}
?>