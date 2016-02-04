<?php
class PlayingCardDeck {

	private $_cards = array();

	public function __construct() {
		$c = array_keys(PlayingCards::$cards);

		foreach (PlayingCards::$colors as $color) {

			shuffle($c);

			foreach ($c as $card) {
				$this->_cards[] = array('color' => $color, 'card' => $card);
			}
		}
	}

	public function shuffle() {
		for ($i=0;$i<5;$i++) {
			shuffle($this->_cards);
		}
	}

	public function drawCard() {
		return array_pop($this->_cards);
	}
}
?>