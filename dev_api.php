<?php
/**
 * Dev API
 *
 * Access via dev_api.php?key=DEV_API_KEY
 * POST: action=[action]& [data]
 *
 * Hande SVN-Commits
 *
 */

include "include.php";

if ($_GET['key'] != DEV_API_KEY) {
	die('invalid API-Key');
}

class DevAPI {
	public function __construct($a)
	{
		$method = "api_".$a;

		if (!method_exists($this, $method)) {
			die('invalid API-call: '.$method);
		}

		$this->$method();
	}

	public function api_postcommit()
	{
		$revision = $_POST["rev"];
		$message = $_POST["msg"];

		$post = R::dispense('homepage_posts');
		$post->title = "Update R".$revision;
		$post->content = "Ein Update auf R".$revision." wurde aufgespielt. \nÄnderungen: \n\n".$message;
		$post->link = "";
		$post->time = time();

		R::store($post);

		$usr = R::load('user', 10);
		R::associate($post, $usr);

		echo "ok";
	}
}

new DevAPI($_POST['action']);
?>