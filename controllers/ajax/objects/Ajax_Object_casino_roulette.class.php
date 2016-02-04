<?php
class Ajax_Object_casino_roulette extends Controller_AjaxGameObject {

	protected $myType = 'casino_roulette';

	/**
	 * 0 -> 0, 1 -> red, 2 -> black
	 * @var array
	 */
	public static $table = array(
		0 => 0,
		1 => 1,
		2 => 2,
		3 => 1,
		4 => 2,
		5 => 1,
		6 => 2,
		7 => 1,
		8 => 2,
		9 => 1,
		10 => 2,
		11 => 2,
		12 => 1,
		13 => 2,
		14 => 1,
		15 => 2,
		16 => 1,
		17 => 2,
		18 => 1,
		19 => 1,
		20 => 2,
		21 => 1,
		22 => 2,
		23 => 1,
		24 => 2,
		25 => 1,
		26 => 2,
		27 => 1,
		28 => 2,
		29 => 2,
		30 => 1,
		31 => 2,
		32 => 1,
		33 => 2,
		34 => 1,
		35 => 2,
		36 => 1
	);

	public function init() {
		parent::init();

		mt_srand ((double) microtime(true) * 654321);
	}

	public function show_Interact() {
		$this->output('maintext', 'Der Roulette-Tisch ist derzeit in Arbeit.');

	}
}