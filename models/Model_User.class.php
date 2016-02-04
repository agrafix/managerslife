<?php
class Model_User extends RedBean_SimpleModel {

	static $hasPremiumCache = array();

	/**
	 * check if user has given right
	 * @param string $type
	 * @return boolean
	 */
	public function hasRight($type) {
		$right = RCached::findOne('user_right', ' type = ?', array($type), date("d.m.Y - H"));

		if (!$right) {
			return false;
		}

		return R::areRelated($this->bean, $right);
	}

	/**
	 * add right to user
	 * @param string $type
	 * @return boolean
	 */
	public function addRight($type) {
		$right = RCached::findOne('user_right', ' type = ?', array($type), date("d.m.Y"));

		if (!$right) {
			return false;
		}

		R::associate($this->bean, $right);

		return true;
	}

	/**
	 * remove right from user
	 * @param string $type
	 * @return boolean
	 */
	public function removeRight($type) {
		$right = RCached::findOne('user_right', ' type = ?', array($type), date("d.m.Y"));

		if (!$right) {
			return false;
		}

		R::unassociate($this->bean, $right);

		return true;
	}

	/**
	 * check if given user has premium account
	 */
	public function hasPremium($nocache=false) {

		if (isset(self::$hasPremiumCache[$this->id]) && !$nocache) {
			return self::$hasPremiumCache[$this->id];
		}

		if ($nocache) {
			$premium = R::findOne('user_premium', ' user_id = ?', array($this->id), date("d.m.Y - H"));
		} else {
			$premium = RCached::findOne('user_premium', ' user_id = ?', array($this->id), date("d.m.Y - H"));
		}

		if (!$premium) {
			self::$hasPremiumCache[$this->id] = false;
			return false;
		}

		if ($premium->until > time()) {
			self::$hasPremiumCache[$this->id] = true;
		}
		else {
			self::$hasPremiumCache[$this->id] = false;
		}

		return self::$hasPremiumCache[$this->id];
	}

	/**
	 * check if given user is online
	 */
	public function isOnline() {

		$count = R::getCell('SELECT COUNT(id) FROM session WHERE user_id = ? AND expires > ? ', array($this->id, time()));

		return ($count != 0);
	}

	/**
	 * send new password to user
	 */
	public function resetPassword() {
		$pwdGen = Framework::randomString(12);

		$this->password = Framework::hash($pwdGen);
		$this->last_pass_reset = time();
		R::store($this->bean);

		$emailTPL = new Smarty();
		$emailTPL->assign("username", $this->username);
		$emailTPL->assign('homepage', APP_WEBSITE.APP_DIR);
		$emailTPL->assign('password', $pwdGen);

		Framework::sendMail($this->username.' <'.$this->email.'>',
									"Manager's Life: Neues Passwort",
		$emailTPL->fetch("mail/user_newpass.txt"));
	}

	/**
	 * send activation mail
	 */
	public function sendActivationMail() {

		$emailTPL = new Smarty();
		$emailTPL->assign("username", $this->username);
		$emailTPL->assign('homepage', APP_WEBSITE.APP_DIR);
		$emailTPL->assign("activation_url", APP_WEBSITE.APP_DIR."site/register/activate/".$this->activation_code);

		Framework::sendMail($this->username.' <'.$this->email.'>',
							"Manager's Life: Aktivierung",
							$emailTPL->fetch("mail/signup_confirm.txt"));


	}

	/**
	 * update users experience level. ONLY use this function!!
	 * @param int $newXP new Experience value, absolute value!
	 */
	public function changeXP($newXP) {
		$this->xp = $newXP;
		$this->level = self::XPtoLevel($newXP);

		if ($this->level >= 2 && $this->referee_id != null && $this->referee_awarded == 0) {
			// award referee
			R::exec('UPDATE user_premium SET `points` = `points` + 150 WHERE user_id = ?',
			array($this->referee_id));

			$this->referee_awarded = 1;
		}

		R::store($this->bean);
	}

	/**
	 * how much xp is needed until next level?
	 * @param int $currentLvl
	 */
	public static function XPuntilNextLevel($currentLvl) {
		return self::LeveltoXP($currentLvl+1) - self::LeveltoXP($currentLvl);
	}

	/**
	 * calculate level for given xp
	 * @param int $xp
	 */
	public static function XPtoLevel($xp) {
		// function is for level -> xp is
		// f(x) = 100 * 1.5 ^ (x) - 100
		//
		// function for xp -> level is
		// f(x) = - ( log(100 / (x + 100)) / log(3/2) )
		//
		return floor(- ( log(100 / ($xp + 100)) / log(3/2) ));
	}

	/**
	 * calculates lowest possible xp for given level
	 * @param int $level
	 * @see Model_User::XPtoLevel
	 */
	public static function LeveltoXP($level) {
		return floor(100 * pow(1.5, ($level)) - 100);
	}

	/**
	 * ensure stuff in db is not messed up
	 * @throws Exception
	 */
	public function update() {

		$forbidden = array('admin', 'root', 'system');

		if (in_array(strtolower($this->username), $forbidden)) {
			throw new Exception("Der Benutzername ist verboten");
		}

		if (!preg_match('#^[a-z0-9 ]{4,20}$#si', $this->username)) {
			throw new Exception("Der Benutzername $this->username muss zwischen 4 und 20 Zeichen lang sein
			und darf nur aus den Zeigen A-Z, a-z, 0-9 und Leerzeichen bestehen!");
		}

		if (!preg_match("#^[0-9a-f]{40}$#si", $this->password)) {
			throw new Exception("Interner Passwort-Fehler!");
		}

		if (!preg_match("#^[a-z0-9!$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[A-Z]{2}|com|org|net|edu|gov|mil|biz|info|mobi|name|aero|asia|jobs|museum)$#si", $this->email)) {
			throw new Exception("Die angegebene Emailadresse ist ungültig!");
		}

		if (!is_numeric($this->characterImage) || $this->characterImage < 1 || $this->characterImage > HIGHEST_CHAR_IMG) {
			throw new Exception("Ungültiges Charakter-Bild!");
		}
	}

}
?>