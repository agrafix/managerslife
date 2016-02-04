<?php
class Ajax_Poker extends Controller_AjaxGame {

	protected $_pokerPlayer;
	protected $_pokerRound;
	protected $_maxBid;
	protected $_minBid;
	protected $_pot;
	protected $_minGameState;

	public function init() {
		parent::init();

		// only works if you are in casino
		$ct = R::getCell('SELECT map FROM map_position WHERE user_id = ?',
		array($this->user->getID()));

		if ($ct != "casino") {
			$this->error('Not in casino.');
		}

		$this->_pokerPlayer = R::relatedOne($this->user, 'poker_player');

		if ($this->_pokerPlayer == null) {
			$this->error('Invalid Poker-ID');
		}
	}


	public function show_Main() {
		$this->error('Invalid ajax call');
	}

	public function show_Join() {

		// check if player is already playing some round
		$isPlaying = R::getCell("SELECT
			count(pp.id)
		FROM
			poker_player AS pp, poker_round AS pr,
			poker_player_poker_round AS conn
		WHERE
			(pr.state = 'running' OR pr.state = 'pending') AND
			conn.poker_player_id = pp.id AND
			conn.poker_round_id = pr.id AND
			pp.id = ?", array($this->_pokerPlayer->getID()));

		if ($isPlaying > 0) {
			$this->output('state', 'join_ok');
			return;
		}


		// count number of players in current&pending round
		$num = R::getCell("SELECT
			count(pp.id)
		FROM
			poker_player AS pp, poker_round AS pr,
			poker_player_poker_round As conn
		WHERE
			(pr.state = 'running' OR pr.state = 'pending') AND
			conn.poker_player_id = pp.id AND
			conn.poker_round_id = pr.id");

		if ($num >= 8) {
			$this->output('state', 'table_full');
		}
		else {
			// get next pending round
			$pending = R::findOne('poker_round', " state = 'pending'");

			if ($pending == null) {
				// open new pending round
				$pending = R::dispense('poker_round');
				$pending->state = 'pending';
				$pending->step = 0;
				$pending->cards = '';
				$pending->ttl = time();
				$pending->current_player = null;
				R::store($pending);
			}

			R::associate($this->_pokerPlayer, $pending);

			$this->output('state', 'join_ok');
		}
	}

	public function show_Play() {

		// return chat state
		if (isset($_POST['chatID']) && is_numeric($_POST['chatID'])) {
			$messages = R::getAll('SELECT id, time, message, MAX(id) as mid FROM
			poker_message WHERE time > ? AND id > ?', array(time() - 3600, $_POST['chatID']));

			if ($messages[0]['id'] == null) {
				$messages = array();
				$this->output('lastID', $_POST['chatID']);
			}
			else {
				$this->output('lastID', $messages[0]['mid']);

				foreach ($messages as $k => $v) {
					unset($messages[$k]['mid']);
					$messages[$k]['time'] = date('H:i:s', $v['time']);
				}
			}
			$this->output('msg', $messages);
		}

		// if no round is in running mode, set pending to running
		$count = R::getCell('SELECT
			count(id)
		FROM
			poker_round
		WHERE
			state = "running"');

		if ($count == 0) {
			// only start a round if enough players are there
			$pending = R::getCell("SELECT
			count(pp.id)
		FROM
			poker_player AS pp, poker_round AS pr,
			poker_player_poker_round As conn
		WHERE
			(pr.state = 'pending') AND
			conn.poker_player_id = pp.id AND
			conn.poker_round_id = pr.id");

			if ($pending >= 2) {
				R::exec("UPDATE poker_round SET state = 'running' WHERE state = 'pending'");
			}
			else {
				$this->output('waiting_for_players', true);
				return;
			}
		}

		// now get running round
		$this->_pokerRound = R::findOne('poker_round', " state = 'running'");

		// if no player is playing, reset round
		if ($this->_pokerRound->player_count() == 0) {
			R::trash($this->_pokerRound);
			return;
		}

		if ($this->_pokerRound->player_count() == 1) {
			$this->chatShowDown($this->_pokerRound->showdown());
			return;
		}

		// both
		$r = R::getRow('SELECT
			MAX(pp.bid) AS max_bid,
			MIN(pp.bid) AS min_bid,
			SUM(pp.bid) + pr.global_pot AS pot,
			MIN(pp.game_state) AS gstate
		FROM
			poker_player AS pp,
			poker_round AS pr,
			poker_player_poker_round AS conn
		WHERE
			pr.id = ? AND
			conn.poker_player_id = pp.id AND
			conn.poker_round_id = pr.id', array($this->_pokerRound->getID()));

		$this->_maxBid = $r['max_bid'];
		$this->_minBid = $r['min_bid'];
		$this->_pot = $r['pot'];
		$this->_minGameState = $r['gstate'];

		// game is handled locked
		if (!file_exists(PATH."/cache/pokerlock.txt") || true) {
			$fp = fopen(PATH."/cache/pokerlock.txt", "w");
			fwrite($fp, 0);
			fclose($fp);

			$this->handle();

			if (file_exists(PATH."/cache/pokerlock.txt")) {
				unlink(PATH."/cache/pokerlock.txt");
			}
			$this->output('locked', false);
		}
		else {
			$this->output('locked', true);
		}

		// time to live
		$ttl = ($this->_pokerRound->ttl - time());
		$this->output('ttl', $ttl);

		if ($ttl < 0) {
			$usr = R::findOne('user', ' id = ?', array($this->_pokerRound->current_player_id));

			// kick out specific player
			$this->_pokerRound->kick(R::load('poker_player', $this->_pokerRound->current_player_id));
			$this->_pokerRound->next_player();


			if ($usr != null) {
				$this->chatMessage(htmlspecialchars($usr->username).' verlässt das Spiel.');
			}
		}

		// output game state
		$nC = array();
		$cC = json_decode($this->_pokerRound->cards, true);
		foreach ($cC as $k => $v) {
			if ($v['turned']) {
				$nC[] = $v;
			}
			else {
				$nC[] = array('card' => '?', 'color' => '?', 'turned' => 'true');
			}
		}

		$this->output('maxbid', $this->_maxBid);
		$this->output('pot', $this->_pot);

		// my turn?
		if ($this->_pokerRound->current_player_id == $this->_pokerPlayer->getID()) {
			$this->output('myturn', true);

			$opts = array();

			if ($this->_pokerPlayer->bid < $this->_maxBid) {
				$opts = array('fold');

				$diff = $this->_maxBid - $this->_pokerPlayer->bid;
				if ($diff <= $this->user->cash) {
					$opts[] = 'call';
					$opts[] = 'raise';
				}
			}
			else {
				$opts = array('fold', 'check', 'raise');
			}

			$this->output('options', $opts);

			// handle player actions
			if ($this->get(1) != "" && in_array($this->get(1), $opts)) {
				switch($this->get(1)) {
					case "fold":
						$this->_pokerRound->kick($this->_pokerPlayer);
						$this->_pokerRound->next_player();
						$this->chatMessage('{me}: FOLD');
						break;

					case "call":
						$diff = $this->_maxBid - $this->_pokerPlayer->bid;

						$this->user->cash -= $diff;
						$this->_pokerPlayer->bid += $diff;

						R::store($this->user);
						R::store($this->_pokerPlayer);
						$this->_pokerRound->next_player();

						$this->chatMessage('{me}: CALL');
						break;

					case "raise":
						$diff =  $this->_maxBid - $this->_pokerPlayer->bid;
						if ($diff < 0) {
							$diff = 0;
						}


						if (is_numeric($this->get(2))) {

							if ($this->user->cash - $diff - $this->get(2) < 0) {
								return;
							}


							$this->user->cash -= ($diff + $this->get(2));
							$this->_pokerPlayer->bid += ($diff + $this->get(2));

							$this->_pokerRound->next_player();

							$this->chatMessage('{me}: RAISE '.formatCash($this->get(2)).' {money}');
						}
						else {
							return; // invalid input
						}
						break;

					case "check":
						$this->_pokerRound->next_player();

						$this->chatMessage('{me}: CHECK');
						break;
				}

				$this->_pokerPlayer->game_state = $this->_pokerRound->step;
				R::store($this->_pokerPlayer);

				return;
			}

		} else {
			$this->output('myturn', false);
		}

		// own cards?
		if (R::areRelated($this->_pokerPlayer, $this->_pokerRound)) {
			$this->output('my_bid', $this->_pokerPlayer->bid);
			$this->output('my_cards', json_decode($this->_pokerPlayer->cards, true));
		}

		$this->output('center_cards', $nC);

		// now output opp cards/status
		$opps = array();
		$q = R::getAll("SELECT
			pp.bid AS bid,
			pp.cards AS cards,
			pp.all_in AS all_in,
			pp.all_in_amount AS all_in_amount,
			u.username AS username
		FROM
			poker_player AS pp, poker_round AS pr,
			poker_player_poker_round As conn,
			poker_player_user AS uconn,
			user AS u
		WHERE
			pr.id = ? AND
			conn.poker_round_id = pr.id AND
			conn.poker_player_id = pp.id AND
			uconn.poker_player_id = pp.id AND
			uconn.user_id = u.id AND
			u.id != ?", array($this->_pokerRound->getID(),
							  $this->user->getID()));

		foreach ($q as $o) {
			$o['cards'] = json_decode($o['cards'], true);

			foreach ($o['cards'] as $k => $card) {
				$o['cards'][$k]['card'] = "?";
				$o['cards'][$k]['color'] = "?";
			}

			$opps[] = $o;
		}

		$this->output('players', $opps);
	}

	private function handle() {
		switch ($this->_pokerRound->step) {
			case 0:
				// toss out cards
				$this->giveCards();
				$this->_pokerRound->step++;
				break;

			case 1:
				if ($this->_minGameState == 1
				&& $this->_minBid == $this->_maxBid) {
					$cards = json_decode($this->_pokerRound->cards, true);

					$cards[2]["turned"] = true;

					$this->_pokerRound->cards = json_encode($cards);
					$this->_pokerRound->step++;
				}
				break;

			case 2:
				if ($this->_minGameState == 2
				&& $this->_minBid == $this->_maxBid) {
					$cards = json_decode($this->_pokerRound->cards, true);

					$cards[3]["turned"] = true;

					$this->_pokerRound->cards = json_encode($cards);
					$this->_pokerRound->step++;
				}
				break;

			case 3:
				if ($this->_minGameState == 3
				&& $this->_minBid == $this->_maxBid) {
					$cards = json_decode($this->_pokerRound->cards, true);

					$cards[4]["turned"] = true;

					$this->_pokerRound->cards = json_encode($cards);
					$this->_pokerRound->step++;
				}
				break;

			case 4:
				$this->chatShowDown($this->_pokerRound->showdown());
				break;
		}

		if ($this->_pokerRound->step != 4) {
			R::store($this->_pokerRound);
		}
	}

	private function giveCards() {
		$deck = new PlayingCardDeck();
		$deck->shuffle();

		// cards for center
		$center_cards = array();
		for($i=0;$i<5;$i++) {
			$center_cards[] = array_merge($deck->drawCard(), array('turned' => ($i <= 1 ? true : false)));
		}

		$this->_pokerRound->cards = json_encode($center_cards);

		// cards for players
		$first = null;
		$last = null;

		$players = R::related($this->_pokerRound, 'poker_player', ' 1=1 ORDER BY id');
		foreach ($players as $p) {

			// if any player has less than 10 cash, kick him out!
			$usr = R::relatedOne($this->_pokerPlayer, 'user');
			if ($usr->cash < 10) {
				$p->status = 'view';
				R::store($p);
				R::unassociate($this->_pokerRound, $p);
				continue;
			}

			$cards = array();
			$cards[] = $deck->drawCard();
			$cards[] = $deck->drawCard();

			$p->cards = json_encode($cards);
			$p->bid = 10;
			$p->status = 'play';
			$p->game_state = 0;

			$p->all_in = false;
			$p->all_in_amount = 0;

			R::store($p);

			// substract bid
			$usr->cash -= 10;
			R::store($usr);

			$last = $p;
		}

		$this->_pokerRound->current_player = $last;
		$this->_pokerRound->ttl = time() + 60;
		R::store($this->_pokerRound);
	}

	private function chatMessage($msg) {

		$msg = str_replace("{me}", htmlspecialchars($this->user->username), $msg);

		$d = R::dispense('poker_message');
		$d->time = time();
		$d->message = $msg;

		R::store($d);
	}

	private function chatShowDown(array $data) {
		/*10 => new PokerParser("[a>a>a>a>a"),
			9 => new PokerParser("a>a>a>a>a"),
			8 => new PokerParser("1{4}"),
			7 => new PokerParser("1{3}2{2}"),
			6 => new PokerParser("a{5}"),
			5 => new PokerParser("?>?>?>?>?"),
			4 => new PokerParser("1{3}"),
			3 => new PokerParser("1{2}2{2}"),
			2 => new PokerParser("1{2}"),
			1 => new PokerParser("?")*/

		$def = array(
			10 => "Royal Flush",
			9 => "Straight Flush",
			8 => "Four of a Kind",
			7 => "Full House",
			6 => "Flush",
			5 => "Straight",
			4 => "Three of a kind",
			3 => "Two pair",
			2 => "Pair",
			1 => "High Card"
		);

		foreach ($data['winners'] as $winner) {
			$chat = R::dispense('poker_message');
			$chat->time = time();

			$t = floor($data['bestValue'] / 100);
			$type = $def[$t];

			$trans = array_flip(PokerParser::$playingCardsOrder);
			$hc = $trans[$data['bestValue'] - ($t*100)];

			$chat->message = htmlspecialchars($winner).' gewinnt mit einem
			'.$type.' (Höchste Karte: '.$hc.') '.formatCash($data['amount']).' {money}';

			R::store($chat);
		}
	}
}
?>