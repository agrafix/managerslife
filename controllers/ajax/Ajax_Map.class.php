<?php
class Ajax_Map extends Controller_AjaxGame {
	private $teleportMap;

	private $mapPosition;

	public function init() {
		parent::init();

		$this->teleportMap = Config::getConfig('teleportPoints');
		$this->mapPosition = R::findOne('map_position', ' user_id = ?', array($this->user->getID()));
	}

	public function show_Main() {
		$this->error('invalid Ajax-Call');
	}

	public function show_ChatMessage() {
		$msg = trim($_POST["message"]);

		if (empty($msg) || strlen($msg) > 255) {
			$this->error('Die Chat Nachricht darf nicht leer und nicht länger als 255 Zeichen sein!');
		}

		$message = R::dispense('chat_message');

		if (preg_match('#^/private "([^"]*)" (.*)$#i', $msg, $m)) {
			$msg = $m[2];

			$usr = R::findOne('user', ' username = ?', array($m[1]));

			if (!$usr) {
				$this->error('Der angegebene Benutzer konnte nicht gefunden werden.');
			}

			$targetPos = R::findOne('map_position', ' user_id = ?', array($usr->getID()));
			if ($targetPos[$usr->getID()]->map != $this->mapPosition->map) {
				$this->error('Der angegebene Benutzer ist nicht in deiner Nähe! ');
			}

			$message->type = 'private';
			$message->visible_for = $usr;
		} else {
			$message->type = 'public';
		}

		$message->map = $this->mapPosition->map;
		$message->time = time();
		$message->author = $this->user->username;
		$message->player = $this->user;
		$message->text = $msg;

		R::store($message);
	}

	public function show_Chat() {
		$lastMessage = $_POST["time"];

		if ($lastMessage < 0 || !is_numeric($lastMessage)) {
			$this->error('Invalid lastMessage-Timestamp');
		}

		if ($lastMessage < (time()-CHAT_LIFETIME)) {
			$lastMessage = time()-CHAT_LIFETIME;
		}

		$messages = R::find('chat_message', ' map = ? AND time > ?
		AND ((type = ? || (type = ? AND visible_for_id = ?)) || player_id = ?) ORDER BY time ASC',
		array($this->mapPosition->map, $lastMessage, 'public', 'private', $this->user->id, $this->user->id));

		$m = array();
		$latest = $lastMessage;

		foreach ($messages as $msg) {
			$to = "";

			$type = (($msg->player_id != null && $msg->player_id == $this->user->id) ? "own" : $msg->type);

			if ($type == "own" && $msg->visible_for_id != null) {
				$p = R::findOne('user', ' id = ?', array($msg->visible_for_id));

				if ($p != false) {
					$to = htmlspecialchars($p->username);
				}
			}

			$m[] = array('time' => date("H:i:s", $msg->time),
			'author' => htmlspecialchars($msg->author),
			'pid' => ($msg->player_id != null ? $msg->player_id : "-1"),
			'text' => $msg->text,
			'type' => $type,
			'to' => $to);

			$latest = $msg->time;
		}

		$this->output('messages', $m);
		$this->output('timestamp', $latest);
	}

	public function show_UpdateObjects() {
		$obj = array();

		$objts = RCached::find('map_object', 'map = ?', array($this->mapPosition->map), date("d.m.Y"));
		foreach ($objts as $o) {
			$obj[] = array('x' => $o->x, 'y' => $o->y, 'type' => $o->type, 'id' => $o->id);
		}

		$this->output('o', $obj);

	}

	public function show_Update() {
		$maxPX = 4;

		$userID = $this->user->getID();

		// check if new x/y pos is a valid position
		if (!is_numeric($_POST['x']) || !is_numeric($_POST['y'])) {
			$this->error('Invalid position');
		}

		// new pos
		$x = ($_POST['x'] < 0 ? 0 : $_POST['x']);
		$y = ($_POST['y'] < 0 ? 0 : $_POST['y']);

		// old pos
		$om = $this->mapPosition;

		// you cant move to much at once
		$deltaX = abs($om->x - $x);
		$deltaY = abs($om->y - $y);

		// skip if player did not move
		if ($deltaX != 0 || $deltaY != 0) {

			if ($deltaX > $maxPX || $deltaY > $maxPX) {
				$this->output('player_position', array($om->x, $om->y));
				$this->error('moved_to_far');
			}

			// update to new pos
			$om->x = $x;
			$om->y = $y;

			foreach ($this->teleportMap[$om->map] as $teleportPoint) {
				if ($om->x == $teleportPoint["x"] && $om->y == $teleportPoint["y"]) {
					// check if key is needed
					if (isset($teleportPoint["keys"])) {
						$array = $teleportPoint["keys"];

						$ct = R::getCell('SELECT
											COUNT(inventory.id)
									FROM
										inventory, item
									WHERE
										item.type IN ('.R::genSlots($teleportPoint["keys"]).')
									AND
										inventory.user_id = '.$this->user->getID(), $teleportPoint["keys"]);

						if ($ct == 0) {
							continue;
						}
					}

					$om->map = $teleportPoint["target"]["map"];
					$om->x = $teleportPoint["target"]["x"];
					$om->y = $teleportPoint["target"]["y"];
				}
			}

			//R::store($om);
			R::exec('UPDATE map_position SET x = ?, y = ?, map = ? WHERE user_id = ?',
			array($om->x, $om->y, $om->map, $this->user->getID()));
		}

		$this->output('player_position', array($om->x, $om->y));
		$this->output('map',$om->map);

		// get surrounding players
		/*$players = R::find('map_position', ' map = ? AND user_id != ?', array(
			$om->map,
			$this->user->getID()
		));*/

		// get surrounding npcs, cached
		$npcs = RCached::find('map_npc', ' map = ?', array(
			$om->map
		), date('d.m.Y'));

		$p = array();

		$players = R::getAll('SELECT
			map_position.x as x,
			map_position.y as y,

			user.id as userid,
			user.username as username,
			user.characterImage as ucharacter,

			COUNT(session.id) as isOnline

		FROM

			map_position, user, session

		WHERE

			map_position.map = ? AND
			map_position.user_id != ? AND
			user.id = map_position.user_id AND
			session.user_id = user.id AND
			session.expires > ?', array($om->map, $this->user->getId(), time()));

		foreach ($players as $playerPos) {
			if ($playerPos['x'] == null) {
				continue;
			}

			$p['p'.$playerPos['userid']] = array(
				'name' => $playerPos['username'],
				'id' => $playerPos['userid'],
				'x' => $playerPos['x'],
				'y' => $playerPos['y'],
				'character' => $playerPos['ucharacter'],
				'is_npc' => false,
				'look_direction' => 2,
				'npc_type' => ''
			);
		}

		/*
		foreach ($players as $playerPos) {
			if (!$playerPos->user->isOnline()) {
				continue;
			}

			$p['p'.$playerPos->user->id] = array('name' => $playerPos->user->username,
												 'x' => $playerPos->x,
												 'y' => $playerPos->y,
												 'id' => $playerPos->user->id,
												 'character' => $playerPos->user->characterImage,
												 'is_npc' => false,
											     'look_direction' => 2,
											     'npc_type' => "");
		}
		*/

		foreach ($npcs as $npc) {
			$p['n'.$npc->id] = array('name' => $npc->name,
															 'x' => $npc->x,
															 'y' => $npc->y,
															 'id' => $npc->id,
															 'character' => $npc->characterImage,
															 'is_npc' => true,
															'look_direction' => $npc->lookDirection,
															'npc_type' => $npc->type);

		}

		$this->output('players', $p);
	}
}
?>