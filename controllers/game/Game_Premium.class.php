<?php
class Game_Premium extends Controller_Game {

	protected $_use_scripts = array("game/game_premium");

	protected $_use_tpl = 'game/game_premium.html';

	protected $myPremium;

	public function init() {
		parent::init();

		$this->myPremium = R::findOne('user_premium', ' user_id = ?',
		array($this->user->id));

		Framework::TPL()->assign('myPremium', $this->myPremium);
		if (!isset($_SESSION['secHash'])) {
			$_SESSION['secHash'] = md5(mt_rand(1000, 10000).microtime(true));
		}

		$deref = $_SESSION['secHash'];
		Framework::TPL()->assign('secHash', $deref);
	}

	public function show_Main() {

		$error = "";

		if ($this->get(1) == 'ok') {
			Framework::TPL()->assign('ok', true);
		}

		if (isset($_POST["change"])) {
			$auto = (isset($_POST["automatic"]) && $_POST["automatic"] == 1 ? 1 : 0);

			$this->myPremium->auto = $auto;
			R::store($this->myPremium);
			Framework::redir('game/premium/main');
		}

		if (isset($_POST["activate"])) {

			if ($_SESSION['secHash'] != $_POST['hash']) {
				Framework::TPL()->assign('pError','invalid hash');
				return;
			}

			$length = array(1 => array(3, 150),
							2 => array(7, 300),
							3 => array(14, 400),
							4 => array(30, 500));

			$l = @$_POST['length'];

			if (!isset($length[$l])) {
				Framework::TPL()->assign('pError','invalid length');
				return;
			}

			$details = $length[$l];

			if ($details[1] > $this->myPremium->points) {
				$error = "Du hast nicht genügend Premium-Punkte.";
			} else {
				$this->myPremium->points -= $details[1];

				if ($this->myPremium->until < time()) {
					$this->myPremium->until = time() + $details[0] * 24 * 3600;
				} else {
					$this->myPremium->until += $details[0] * 24 * 3600;
				}

				R::store($this->myPremium);
				$_SESSION['secHash'] = md5(mt_rand(1000, 10000).microtime(true));

				RCached::clear(); // clear cache files
				Framework::redir('game/premium/main/ok');
			}
		}

		Framework::TPL()->assign('pError', $error);
	}

	public function show_Buy() {
		$this->_use_tpl = 'game/game_premium_buy.html';

		$prices = Config::getConfig('premium_price');
		$rows = array();

		foreach ($prices as $points => $cost) {
			$row = array(
				'points' => $points,
				'cost' => number_format($cost, 2, ',', '.'),
				'ebank' => self::PaymentWindowUrl($cost*100, 'ebank2pay', $points, $this->user->id)
			);

			if ($cost < 15) {
				$row['call'] = self::PaymentWindowUrl($cost*100, 'call2pay', $points, $this->user->id);
			} else {
				$row['call'] = '';
			}

			if ($cost < 5) {
				$row['handy'] = self::PaymentWindowUrl($cost*100, 'handypay', $points, $this->user->id);
			} else {
				$row['handy'] = '';
			}
			$rows[] = $row;
		}

		Framework::TPL()->assign('pRows', $rows);
	}

	private static function PaymentWindowUrl($amount, $type, $points, $userID) {
		$params = "project=mngrsl&amount=".$amount."&currency=EUR&title=pa_".$points."_".$userID."&theme=d2";
		$accessKey = PAYMENT_ACCESS_KEY;
		$seal = md5($params . $accessKey);
		return 'https://billing.micropayment.de/'.$type.'/event/?' . $params . '&seal=' . $seal;
	}
}
?>