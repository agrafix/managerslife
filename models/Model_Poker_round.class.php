<?php
class Model_Poker_round extends RedBean_SimpleModel {

	public function kick(RedBean_OODBBean $player) {
		$this->bean->global_pot += $player->bid;

		R::unassociate($this->bean, $player);

		$player->status = 'view';
		$player->cards = '';
		$player->bid = 0;
		$player->all_in = 0;
		$player->all_in_amount = 0;
		R::store($player);
	}

	public function player_count() {
		return R::getCell('SELECT
			count(poker_player.id)
		FROM
			poker_player, poker_round, poker_player_poker_round
		WHERE
			poker_round.id = ? AND
			poker_player_poker_round.poker_round_id = poker_round.id AND
			poker_player_poker_round.poker_player_id = poker_player.id', array($this->bean->getID()));
	}

	public function next_player() {
		$next = R::relatedOne($this->bean, 'poker_player', ' id > ? ORDER BY id',
							  array($this->bean->current_player_id));

		if ($next == null) {
			$next = R::relatedOne($this->bean, 'poker_player', ' id < ? ORDER BY id',
								  array($this->bean->current_player_id));
		}

		if ($next == null) {
			throw new Exception('Poker Error: No next player found.', 100);
			return;
		}

		$this->bean->current_player_id = $next->id;
		$this->bean->ttl = time() + 60;
		R::store($this->bean);

		return $next;
	}

	public function showdown() {
		$allPlayers = R::related($this->bean, 'poker_player');

		$pokerHands = array(
			10 => new PokerParser("[a>a>a>a>a"),
			9 => new PokerParser("a>a>a>a>a"),
			8 => new PokerParser("1{4}"),
			7 => new PokerParser("1{3}2{2}"),
			6 => new PokerParser("a{5}"),
			5 => new PokerParser("?>?>?>?>?"),
			4 => new PokerParser("1{3}"),
			3 => new PokerParser("1{2}2{2}"),
			2 => new PokerParser("1{2}"),
			1 => new PokerParser("?")
		);

		$bestValue = -1;
		$winners = array();

		$totalPot = $this->bean->global_pot;

		// new round to move players to
		$nextRound = R::findOne('poker_round', " state='pending'");
		if ($nextRound == null) {
			$nextRound = R::dispense('poker_round');
			$nextRound->state = 'pending';
			$nextRound->step = 0;
			$nextRound->global_pot = 0;
			R::store($nextRound);
		}

		foreach ($allPlayers as $p) {
			$cards = array_merge(json_decode($p->cards, true), json_decode($this->bean->cards, true));

			$totalPot += $p->bid;

			$val = 0;

			foreach ($pokerHands as $value => $parser) {
				if ($parser->check($cards)) {
					// player has this
					$val = ($value * 100) + $parser->getHighestCardValue();
					break;
				}
			}

			if ($val > $bestValue) {
				$bestValue = $val;
				$winners = array($p);
			}
			elseif ($val == $bestValue) {
				$winners[] = $p;
			}

			// kick from current round
			R::unassociate($this->bean, $p);

			// put into next round
			R::associate($nextRound, $p);
		}

		$winnerCount = count($winners);
		$winAmount = floor($totalPot / $winnerCount);

		$winnerNames = array();

		foreach ($winners as $win) {

			$usr = R::relatedOne($win, 'user');

			$usr->cash += $winAmount;
			$winnerNames[] = $usr->username;
			R::store($usr);
		}

		R::trash($this->bean);

		return array("winners" => $winnerNames, "amount" => $winAmount, "bestValue" => $bestValue);
	}

}
?>