<?php
class PlayingCardHand {
	private $cards;

	private $cards_color;
	private $cards_card;

	private $card_count_color;
	private $card_count_card;

	public function __construct(array $cards) {
		$this->cards = PlayingCards::sortCards($cards);

		foreach ($this->cards as $c) {
			$this->cards_color[$c["color"]] = $c["card"];
			$this->cards_card[$c["card"]] = $c["color"];

			if (!isset($this->card_count_card[$c["card"]])) {
				$this->card_count_card[$c["card"]] = 1;
			} else {
				$this->card_count_card[$c["card"]]++;
			}

			if (!isset($this->card_count_color[$c["color"]])) {
				$this->card_count_color[$c["color"]] = 1;
			} else {
				$this->card_count_color[$c["color"]]++;
			}
		}
	}

	public function getCardsOfCard($card) {
		$cards = array();

		foreach($this->cards as $k => $v) {
			if ($v['card'] != $card) {
				continue;
			}

			$cards[] = $this->cards[$k];
		}

		return new PlayingCardHand($cards);
	}

	public function getCardsOfColor($color) {
		$cards = array();

		foreach($this->cards as $k => $v) {
			if ($v['color'] != $color) {
				continue;
			}

			$cards[] = $this->cards[$k];
		}

		return new PlayingCardHand($cards);
	}

	public function getCardArray() {
		return $this->cards;
	}

	public function countCards() {
		return count($this->cards);
	}

	public function has($color, $card) {
		if (isset($this->cards_color[$color]) && $this->cards_color[$color] == $card) {
			return true;
		}

		return false;
	}

	public function countColor($color) {
		return (isset($this->card_count_color[$color]) ? $this->card_count_color[$color] : 0);
	}

	public function countCard($card) {
		return (isset($this->card_count_card[$card]) ? $this->card_count_card[$card] : 0);
	}
}
?>