<?php
class PlayingCards {
	public static $cards = array(
			'a' => 11,
			'2' => 2,
			'3' => 3,
			'4' => 4,
			'5' => 5,
			'6' => 6,
			'7' => 7,
			'8' => 8,
			'9' => 9,
			'10' => 10,
			'j' => 10,
			'q' => 10,
			'k' => 10
	);

	public static $colors = array(
			'clubs','spades', 'hearts', 'diamonds'
	);

	public static function sortCards(array $cards) {
		return self::multiSort($cards, 'color', 'card');
	}

	private static function multiSort() {
		//get args of the function
		$args = func_get_args();
		$c = count($args);
		if ($c < 2) {
			return false;
		}
		//get the array to sort
		$array = array_splice($args, 0, 1);
		$array = $array[0];
		//sort with an anoymous function using args
		usort($array, function($a, $b) use($args) {

			$i = 0;
			$c = count($args);
			$cmp = 0;
			while($cmp == 0 && $i < $c)
			{
				$cmp = strcmp($a[ $args[ $i ] ], $b[ $args[ $i ] ]);
				$i++;
			}

			return $cmp;

		});

		return $array;

	}

	public static function getRandomCard() {
		// draw a random card
		$keys = array_keys(self::$cards);
		$card = $keys[mt_rand(0, count($keys)-1)];

		// choose color of card
		$color = self::$colors[mt_rand(0, 3)];


		return array('card' => $card, 'color' => $color, 'value' => self::$cards[$card]);
	}

	public static function displayCard($card, $color) {
		$i = -1;
		foreach (self::$cards as $k => $cDef) {
			$i++;

			if ($k == $card) {
				break;
			}
		}

		$j = -1;
		foreach (self::$colors as $col) {
			$j++;

			if ($col == $color) {
				break;
			}
		}

		$posX = -1 * (73 * $i);
		$posY = -1 * (98 * $j);

		return "<div style='width:72px;
			height:98px;
			background-image:url(".APP_DIR."static/images/cards.png);
			background-position:".$posX."px ".$posY."px;
			background-repeat:no-repeat;
			display:inline-block;'>
			&nbsp;
			</div>";
	}

	public static function displayCardBack() {
		return "<div style='width:72px;
					height:98px;
					background-image:url(".APP_DIR."static/images/card_back.png);
					background-position:0 0;
					background-repeat:no-repeat;
					display:inline-block;
					margin-left:1px;'>
					&nbsp;
					</div>";
	}
}
?>