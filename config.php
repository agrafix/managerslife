<?php
if (isset($_SERVER["SystemRoot"]) && $_SERVER["SystemRoot"] == "C:\Windows") {
	/**
	* Debugging enabled?
	*
	* If disabled, javascripts will be cached and combined,
	* database layout will freeze
	*
	* @var boolean
	*/
	define('DEBUG', true);

/**
 * DB Host
 *
 * @var string
 */
define('DB_HOST', 'localhost');

/**
* DB User
*
* @var string
*/
define('DB_USER', 'root');

/**
* DB Password
*
* @var string
*/
define('DB_PASS', '');

/**
* DB Name
*
* @var string
*/
define('DB_NAME', 'managerslife_v2');

/**
* URL to host of, eg. http://google.de
*
* @var string
*/
define('APP_WEBSITE', 'http://localhost');

/**
 * External path to site, eg /search/main/
 *
 * @var string
 */
define('APP_DIR', '/eclipse/managerslife_v2/');

define('ALLOW_REGISTER', true);

} else {
	define('ALLOW_REGISTER', true);

	define('DEBUG', false);
	define('DB_HOST', 'localhost');
	define('DB_USER', 'managerslife');
	define('DB_PASS', '***');
	define('DB_NAME', 'managerslife');

	define('APP_WEBSITE', 'http://managerslife.de');
	define('APP_DIR', '/');
}

/**
* Administrator email
*
* @var string
*/
define('ADMIN_EMAIL', 'mail@managerslife.de');

/**
 * key for cronjobs
 * @var string
 */
define('CRON_KEY', 'cronkey');

/**
* Maximum Session age in seconds
*
* @var int
*/
define('SESSION_MAX_AGE', 600);

/**
* Where do new players start? X-Coord
*
* @var int
*/
define('GAME_START_POSX', 1);

/**
* Where do new players start? Y-Coord
*
* @var int
*/
define('GAME_START_POSY', 6);

/**
* Where do new players start? Map-Name
*
* @var string
*/
define('GAME_START_MAP', 'main');

/**
* Free premium points for new players
*
* @var int
*/
define('GAME_START_PREMIUM_PTS', 0);

/**
* Free premium account for new players until given timestamp
*
* @var int
*/
define('GAME_START_PREMIUM_UNTIL', time() - 5);

/**
* Maximum Chat message age in seconds
*
* @var int
*/
define('CHAT_LIFETIME', 1200);

/**
 * How much do npcs pay for an item? Percentage of basic value
 *
 * @var double
 */
define('NPC_BUY_PRICE', 0.7);

/**
 * cash the user starts with
 * @var int
 */
define('START_CASH', 10000);

/**
 * Highest id of Character-Image
 * @var int
 */
define('HIGHEST_CHAR_IMG', 9);

/**
 * API Key for the dev api
 */
define('DEV_API_KEY', '');

/**
 * API Key for payments
 */
define('PAYMENT_ACCESS_KEY', '');

define('SECURE_HASH_SALT', '');
define('HASH_SALT', '');
?>
