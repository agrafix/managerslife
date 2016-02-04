<?php
class Ajax_Casino_blackjack extends Controller_AjaxGameNPC {

	protected $myType = 'casino_blackjack';

	private $_myGame;

	private $_myCards;

	private $_dealerCards;

	public function init() {
		parent::init();

		mt_srand ((double) microtime(true) * 123456);

		$this->_myGame = R::findOne('blackjack', ' user_id = ?', array($this->user->getID()));

		if ($this->_myGame != null) {
			$this->_myCards = json_decode($this->_myGame->user_cards, true);
			$this->_dealerCards = json_decode($this->_myGame->dealer_cards, true);
		}
	}

	public function show_Interact() {
		if ($this->_myGame == null) {
			$this->welcome();
			return;
		}

		$cards = "";

		$values = array();
		$totalValue = 0;

		foreach ($this->_myCards as $c) {
			$totalValue += ($c['card'] == "a" ? 1 : $c['value']);

			$values[] = ($c['card'] == "a" ? "1/11" : $c['value']);
			$cards .= PlayingCards::displayCard($c['card'], $c['color']);
		}

		if ($totalValue > 21) {
			$this->output('load', 'game/finish');
			return;
		}

		$d_cards = array();

		foreach ($this->_dealerCards as $c) {
			array_push($d_cards, PlayingCards::displayCard($c['card'], $c['color']));
		}

		// last card is hidden
		array_pop($d_cards);
		array_push($d_cards, PlayingCards::displayCardBack());

		$s_cards = implode("", $d_cards);


		$this->output('maintext', 'Deine Karten ('.implode(" + ", $values). ' Punkte): <br />'.$cards.' <br /> <br />
		Die Karten des Dealers: <br />'.$s_cards);

		$this->output('options', array(
			'game/card' => 'Noch eine Karte',
			'game/finish' => 'Keine Karte mehr, aufhören'
		));

	}

	public function show_Game() {
		if ($this->_myGame == null) {
			$this->error('Du spielst derzeit nicht!');
		}

		switch($this->get(1)) {
			case 'card':
				$this->_myCards[] = PlayingCards::getRandomCard();
				$this->_myGame->user_cards = json_encode($this->_myCards);
				R::store($this->_myGame);

				$this->output('load', 'interact');
				break;

			case 'finish':
				// check if dealer needs new card or not
				$dealerValue = 0;
				$dealerCards = array();

				foreach ($this->_dealerCards as $c) {
					$dealerCards[] = PlayingCards::displayCard($c['card'], $c['color']);

					$dealerValue += (($dealerValue+$c['value'] > 21 and $c['card'] == 'a') ? 1 : $c['value']);
				}

				// now see if dealer has to draw more cards
				while ($dealerValue < 17) {
					$c = PlayingCards::getRandomCard();
					$dealerValue += (($dealerValue+$c['value'] > 21 and $c['card'] == 'a') ? 1 : $c['value']);

					$this->_dealerCards[] = $c;
					$dealerCards[] = PlayingCards::displayCard($c['card'], $c['color']);
				}

				// calculate player value
				$playerValue = 0;
				$playerCards = array();
				$playerSevenCount = 0;

				foreach ($this->_myCards as $c) {
					$playerCards[] = PlayingCards::displayCard($c['card'], $c['color']);

					if ($c['card'] == '7') {
						$playerSevenCount++;
					}

					// first count all the cards but no A-cards
					if ($c['card'] == 'a') {
						continue;
					}

					$playerValue += $c['value'];
				}

				foreach ($this->_myCards as $c) {
					// now count A-cards
					if ($c['card'] != 'a') {
						continue;
					}

					if ($playerValue + $c['value'] > 21) {
						$playerValue += 1; // if we would top 21 with 11, count as 1
					}
					elseif ($playerValue + 1 < $dealerValue) {
						$playerValue += 11; // if counting as 1 would be smaller than
											// dealer count as 11
					}
					else {
						$playerValue += 1; // count as 1
					}
				}

				// now check who wins?
				$CardDisplay = '<h3>Deine Karten:</h3>
				'.implode("", $playerCards).'
				<h3>Karten des Dealers:</h3>
				'.implode("", $dealerCards);

				// player bust
				if ($playerValue > 21) {
					$this->output('maintext', '<b>BUST:</b> Deine Hand übersteigt
					21 Punkte ('.$playerValue.' P). <br />
					Du verlierst deinen Einsatz '.$CardDisplay);

					R::trash($this->_myGame);

					$this->output('options', array('interact' => 'Zurück'));

					return;
				}

				// triple seven
				if ($playerSevenCount == 3 && count($playerCards) == 3) {
					$winAmount = (1.5 * $this->_myGame->bid) + $this->_myGame->bid;

					$this->output('maintext', '<b>TRIPLE SEVEN:</b> Du hast genau 3 Siebener
					auf der Hand! Du erhälst '.formatCash($winAmount).' {money} '.$CardDisplay);

					$this->user->cash += $winAmount;
					R::store($this->user);
					R::trash($this->_myGame);

					$this->output('options', array('interact' => 'Zurück'));

					return;
				}

				// dealer busts
				if ($dealerValue > 21) {
					$this->output('maintext', '<b>DEALER BUST:</b> Der Dealer hat mehr als 21
					Punkte auf der Hand! <br />
					Du bekommst '.formatCash($this->_myGame->bid*2).' {money}
					'.$CardDisplay);

					$this->user->cash += $this->_myGame->bid * 2;
					R::store($this->user);
					R::trash($this->_myGame);

					$this->output('options', array('interact' => 'Zurück'));

					return;
				}

				// dealer has blackjack and player has blackjack too
				if ((($dealerValue == 21 && count($dealerCards) == 2)
				&&  ($playerValue == 21 && count($playerCards) == 2))
				|| $playerValue == $dealerValue) {
					$this->output('maintext', '<b>STAND OFF:</b> Der Dealer hat die gleichviele
					Punkte. Du bekommst deinen Einsatz zurück!'.$CardDisplay);

					$this->user->cash += $this->_myGame->bid;
					R::store($this->user);
					R::trash($this->_myGame);

					$this->output('options', array('interact' => 'Zurück'));

					return;
				}

				// player has blackjack
				if (($playerValue == 21 && count($playerCards) == 2)) {
					$winAmount = (1.5 * $this->_myGame->bid) + $this->_myGame->bid;


					$this->output('maintext', '<b>BLACK JACK:</b> Du hast einen Black Jack.
					Du erhälst '.formatCash($winAmount).' {money}'.$CardDisplay);

					$this->user->cash += $winAmount;

					R::store($this->user);
					R::trash($this->_myGame);

					$this->output('options', array('interact' => 'Zurück'));

					return;
				}

				// compare points
				if ($playerValue > $dealerValue) {
					$this->output('maintext', '<b>EVEN MONEY:</b> Du gewinnst mit '.$playerValue.'
					Punkten gegen den Dealer ('.$dealerValue.' Punkte)! <br />
					Du erhälst '.formatCash($this->_myGame->bid*2).' {money}'.$CardDisplay);

					$this->user->cash += $this->_myGame->bid * 2;
					R::store($this->user);
					R::trash($this->_myGame);

					$this->output('options', array('interact' => 'Zurück'));

					return;
				}


				// dealer wins
				$this->output('maintext', 'Du verlierst gegen den Dealer. Du hast '.$playerValue.'
				Punkte, der Dealer '.$dealerValue.' Punkte. Du verlierst deinen Einsatz. '.$CardDisplay);

				R::trash($this->_myGame);

				$this->output('options', array('interact' => 'Zurück'));

				break;

			default:
				$this->error('Invalid ACTION!');
				break;
		}
	}

	public function show_Play() {
		if ($this->_myGame != null) {
			$this->error('Du hast bereits ein laufendes Spiel!');
		}

		if (isset($_POST['bid']) && is_numeric($_POST['bid']) && $_POST['bid'] <= 5000
		&& $_POST['bid'] <= $this->user->cash && $_POST['bid'] > 0) {

			// create a new game of blackjack
			$blackjack = R::dispense('blackjack');
			$blackjack->bid = $_POST['bid'];
			$blackjack->user = $this->user;

			$this->user->cash -= $_POST['bid'];

			$player_cards = array();
			$player_cards[] = PlayingCards::getRandomCard();
			$player_cards[] = PlayingCards::getRandomCard();

			$blackjack->user_cards = json_encode($player_cards);

			$dealer_cards = array();
			$dealer_cards[] = PlayingCards::getRandomCard();
			$dealer_cards[] = PlayingCards::getRandomCard();

			$blackjack->dealer_cards = json_encode($dealer_cards);

			R::store($this->user);
			R::store($blackjack);

			$this->output('load', 'interact');
			return;
		}

		$this->output('maintext', 'Wie viel möchtest du setzen?');

		$this->output('form', array(
			'target' => 'play',

			'elements' => array(
				array('desc' => 'Einsatz', 'type' => 'text', 'value' => 0, 'name' => 'bid')
			)
		));
	}

	public function show_Rules() {
		$this->output('maintext', 'Eine ausführliche Anleitung zu Black Jack findest du
		<a href="http://de.wikipedia.org/wiki/Black_Jack#Die_Regeln" target="_blank">hier</a>');

		$this->output('options', array(
			'interact' => 'Zurück',
			 'play' => 'Eine Runde Black Jack spielen'
		));
	}

	private function welcome() {
		$this->output('maintext', 'Willkommen beim Black Jack! Das Tischlimit beträgt
		<b>5.000 {money}</b>');

		$this->output('options', array(
			'rules' => 'Regeln erklären',
			'play' => 'Eine Runde Black Jack spielen'
		));
	}

}
?>