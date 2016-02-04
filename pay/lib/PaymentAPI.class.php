<?php
/**
 *
 * PaymentAPI for Micropayments
 * @author alexander
 *
 */
class PaymentAPI {
	private $amount;
	private $title;
	private $auth;
	private $country;
	private $currency;
	private $function;

	private $prices;

	private $userID;
	private $premiumPoints;

	private static $paymentLog;

	public function __construct() {

		self::$paymentLog = R::dispense('payment_log');

		if (!isset($_GET['amount'])
		|| !isset($_GET['title'])
		|| !isset($_GET['auth'])
		|| !isset($_GET['country'])
		|| !isset($_GET['currency'])
		|| !isset($_GET['function'])) {
			self::error(99);
		}

		$this->amount = $_GET['amount'];
		$this->title = $_GET['title'];
		$this->auth = $_GET['auth'];
		$this->country = $_GET['country'];
		$this->currency = $_GET['currency'];
		$this->function = $_GET['function'];

		$call = 'action_'.ucfirst($this->function);

		self::$paymentLog->amount = $this->amount;
		self::$paymentLog->title = $this->title;
		self::$paymentLog->auth = $this->auth;
		self::$paymentLog->country = $this->country;
		self::$paymentLog->currency = $this->currency;
		self::$paymentLog->function = $this->function;

		// check if valid ip
		$this->checkIP();

		// loadup prices
		$this->prices = Config::getConfig('premium_price');

		// valid callback function?
		if (!method_exists($this, $call)) {
			self::error(1);
		}

		// valid item?
		$parts = explode("_", $this->title);
		if (count($parts) != 3) {
			self::error(2);
		}

		if ($parts[0] != "pa" || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
			self::error(3);
		}

		$this->premiumPoints = $parts[1];
		$this->userID = $parts[2];

		if (!isset($this->prices[$this->premiumPoints])) {
			self::error(4);
		}

		$this->$call();
	}

	private function checkIP() {
		/*Order deny,allow
Deny from all
Allow from service.micropayment.de
Allow from proxy.micropayment.de
Allow from access.micropayment.de*/

		$allowed_hosts = array('service.micropayment.de',
							   'proxy.micropayment.de',
							   'access.micropayment.de');

		$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		self::$paymentLog->host = $host;

		if (!in_array($host, $allowed_hosts)) {
			self::error(100);
		}
	}

	/**
	 * process billing
	 */
	private function action_Billing() {
		// check if currency is in EUR
		if ($this->currency != 'EUR') {
			self::error(7);
		}

		// check if user paid valid amount
		if (($this->amount/100) < $this->prices[$this->premiumPoints]) {
			self::error(5);
		}

		// does user exist?
		$user = R::findOne('user', 'id = ?', array($this->userID));
		if (!$user) {
			self::error(6);
		}

		// everything okay. update premium points
		$premium = R::findOne('user_premium', 'user_id = ?', array($user->id));

		if (!$premium) {
			$premium = R::dispense('user_premium');
			$premium->user = $user;
			$premium->points = 0;
			$premium->until = 0;
		}

		$premium->points += $this->premiumPoints;

		R::store($premium);

		self::ok();
	}

	/**
	 * process cancelation
	 */
	private function action_Storno() {
		Framework::sendMail('mail@managerslife.de', 'Storno - '.$this->title,
		'Title: '.$this->title."\n".'Amount: '.$this->amount);

		self::ok();
	}

	/**
	 * process ok
	 */
	private static function ok() {
		self::$paymentLog->errorID = 0;
		self::response( APP_WEBSITE.APP_DIR.'site/pay/ok', 'ok');
	}

	/**
	 * die with error
	 * @param int $id
	 */
	private static function error($id) {
		self::$paymentLog->errorID = $id;
		self::response( APP_WEBSITE.APP_DIR.'site/pay/error/'.$id, 'error');
	}

	/**
	 * send response
	 * @param string $url
	 * @param string $status ok|error
	 * @param string $target
	 * @param int $forward
	 */
	private static function response($url, $status="ok", $target="_top", $forward=1) {
		self::$paymentLog->status = $status;
		R::store(self::$paymentLog);

		header("Content-Type: text/plain");
		echo "status=".$status."\nurl=".$url."\ntarget=".$target."\nforward=".$forward;
		exit;
	}

}
?>