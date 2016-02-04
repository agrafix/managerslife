<?php
/**
 * PokerParser Class
 *
 * Class for parsing my poker-abstraction language
 *
 * @link http://blog.agrafix.net/2011/12/poker-abstraktion/
 *
 * @author Alexander Thiemann <mail@agrafix.net>
 * @copyright 2011 by Alexander Thiemann www.agrafix.net
 *
 * @version 0.1 prototype
 * @license CC BY-SA 2.0
 *
 */
class PokerParser {

	private $input;
	private $tokenized;

	private $pos = 0;

	private $cards;

	private $highest_card = '2';

	const TOKEN_CARD = 1;
	const TOKEN_COLOR = 2;
	const TOKEN_RANDOM = 4;
	const TOKEN_FOLLOW = 8;

	const TOKEN_EXPLICIT = 16;

	const TOKEN_AMOUNT = 32;

	private static $playingCards = array(
		'all', 'a', 'k', 'q', 'j', '10', '9', '8', '7', '6', '5', '4', '3', '2'
	);

	public static $playingCardsOrder = array(
		'a' => 13,
		'k' => 12,
		'q' => 11,
		'j' => 10,
		'10' => 9,
		'9' => 8,
		'8' => 7,
		'7' => 6,
		'6' => 5,
		'5' => 4,
		'4' => 3,
		'3' => 2,
		'2' => 1
	);

	private function setHighestCard($c) {
		if (self::$playingCardsOrder[$c] > self::$playingCardsOrder[$this->highest_card]) {
			$this->highest_card = $c;
		}
	}

	public function getHighestCardValue() {
		return self::$playingCardsOrder[$this->highest_card];
	}

	public function __construct($input) {
		$this->input = $input;
		$this->tokenize();
	}


	public function check($cards) {
		$this->cards = $cards;
		$this->highest_card = '2';

		uasort($this->cards, function($a, $b) {
			$m1 = PokerParser::$playingCardsOrder[$a['card']];
			$m2 = PokerParser::$playingCardsOrder[$b['card']];

			if ($m1 == $m2) { return 0; }

			return ($m1 < $m2 ? 1 : -1);
		});

		reset($this->tokenized);

		$t = current($this->tokenized);

		$round_safe = false;
		foreach ($this->cards as $c) {
			if ($c['card'] == '5' || $c['card'] == '10') {
				$round_safe = true;
			}
		}
		$had_follower = false;
		$follow = false;
		$last_card = array();

		while ($t !== false) {

			if ($t['type'] == self::TOKEN_EXPLICIT) {
				// check if explicit card is existing
				$in = false;

				foreach ($this->cards as $k => $c) {
					if ($c['card'] == $t['value']) {
						$in = true;
						$last_card = $c;

						$this->setHighestCard($this->cards[$k]['card']);
						unset($this->cards[$k]);
						break;
					}
				}

				if (!$in) {
					return false;
				}

			}
			elseif ($t['type'] == self::TOKEN_FOLLOW) {
				$follow = true;
				$had_follower = true;
			}
			else {
				if ($t['amount'] != 1) {
					switch($t['type']) {
						case self::TOKEN_CARD:
							if (!$this->amountCheck('card', $t['amount'])) {
								return false;
							}
							break;

						case self::TOKEN_COLOR:
							if (!$this->amountCheck('color', $t['amount'])) {
								return false;
							}
							break;
					}
				}
				else {
					if ($follow) {
						$in = false;

						foreach ($this->cards as $k => $c) {
							if ($t['type'] == self::TOKEN_COLOR) {
								if ($c['color'] != $last_card['color']) {
									continue;
								}
							}

							if ($this->isFollower($last_card['card'], $c['card'])) {
								$in = true;
								$this->setHighestCard($this->cards[$k]['card']);
								unset($this->cards[$k]);
								$last_card = $c;
								break;
							}
						}

						if (!$in) {
							return false;
						}
					}
					else {

						if ($t['type'] == self::TOKEN_RANDOM) {
							$last_card = $this->firstStraightCard();

							if (!$last_card) {
								$last_card = $this->highestCard();
							}
						}

						elseif ($t['type'] == self::TOKEN_COLOR) {
							$last_card = $this->firstStraightCard('color');
						}

					}
				}

				$follow = false;
			}

			$t = next($this->tokenized);
		}


		if ($had_follower && !$round_safe) {
			// check against round-the-corner stuff
			return false;
		}

		return true;
	}

	private function hasFollowers(array $skip_keys, array $cardA, $amount=4, $color='any') {

		if ($amount == 0) {
			return true;
		}

		foreach ($this->cards as $k => $card) {
			if (in_array($k, $skip_keys)) { continue; }

			if ($color != 'any' && $card['color'] != $color) { continue; }

			if (self::isFollower($card['card'], $cardA['card'])) {
				$amount--;
				$skip_keys[] = $k;
				if ($this->hasFollowers($skip_keys, $card, $amount)) {
					return true;
				}
			}
		}

		return false;
	}

	private static function isFollower($cardA, $cardB) {
		$a = 0;
		$b = 0;

		foreach(self::$playingCards as $k => $c) {
			if ($cardA == $c) {
				$a = $k;
			}

			if ($cardB == $c) {
				$b = $k;
			}
		}

		$diff = abs($a-$b);

		if ($diff != 1) {
			// inverse a and b
			$tmp = $cardB;
			$cardB = $cardA;
			$cardA = $tmp;

			$diff = abs($a-$b);
		}

		if ($diff != 1) {
			if ($cardA == 'a' && $cardB == '2') {
				$diff = 1;
			}
			elseif ($cardA == '2' && $cardB == 'a') {
				$diff = 1;
			}
		}

		return ($diff == 1);
	}

	private function highestCard() {
		$hq = -1;
		$sel = array();
		$selKey = -1;

		foreach ($this->cards as $k => $v) {
			if (self::$playingCardsOrder[$v['card']] > $hq) {
				$hq = self::$playingCardsOrder[$v['card']];

				$sel = $v;
				$selKey = $k;
			}
		}

		$this->setHighestCard($this->cards[$selKey]['card']);
		unset($this->cards[$selKey]);
		return $sel;
	}

	private function firstStraightCard($check='any') {
		$color = 'any';

		foreach ($this->cards as $k => $v) {

			if ($check == "color") {
				$color = $v['color'];
			}

			$chk = $this->hasFollowers(array($k), $v, 4, $color);

			if ($chk) {
				$this->setHighestCard($this->cards[$k]['card']);
				unset($this->cards[$k]);
				return $v;
			}
		}

		return false;
	}

	private function amountCheck($type, $amount) {

		foreach ($this->cards as $scard) {
			$i = 0;
			$used_keys = array();

			foreach ($this->cards as $k => $c) {
				if ($c[$type] == $scard[$type]) {
					$used_keys[] = $k;
					$i++;
				}
			}

			if ($i >= $amount) {
				foreach ($used_keys as $uk) {
					$this->setHighestCard($this->cards[$uk]['card']);
					unset($this->cards[$uk]);
				}
				return true;
			}
		}

		return false;
	}

	private function tokenize() {
		$this->pos = 0;
		$this->tokenized = array();

		$t = $this->nextToken();

		while ($t != false) {
			$type = $this->tokenType($t);


			$a = array();

			if ($type == self::TOKEN_EXPLICIT) {
				$t = $this->nextToken(); // get explicit value

				$a = array('type' => self::TOKEN_EXPLICIT,
											   'value' => $t);
			}
			else {
				$a = array('type' => $type, 'value' => $t);
			}

			// peek for amount
			if ($this->tokenType($this->peekToken()) == self::TOKEN_AMOUNT) {
				$this->nextToken();
				$a["amount"] = $this->nextToken();
				$this->nextToken();
			}
			else {
				$a["amount"] = 1;
			}

			// store
			$this->tokenized[] = $a;

			$t = $this->nextToken();
		}
	}

	private function tokenType($t) {
		if (ctype_digit($t)) {
			return self::TOKEN_CARD;
		}

		if (ctype_alpha($t)) {
			return self::TOKEN_COLOR;
		}

		if ($t == "?") {
			return self::TOKEN_RANDOM;
		}

		if ($t == "[") {
			return self::TOKEN_EXPLICIT;
		}

		if ($t == ">") {
			return self::TOKEN_FOLLOW;
		}

		if ($t == "{" || $t == "}") {
			return self::TOKEN_AMOUNT;
		}
	}

	private function peekToken() {
		if ($this->pos+1 > strlen($this->input)) {
			return false;
		}

		$t = $this->input{$this->pos};
		return $t;
	}

	private function nextToken() {
		if ($this->pos+1 > strlen($this->input)) {
			return false;
		}

		$t = $this->input{$this->pos};
		$this->pos++;

		return $t;
	}

}
?>