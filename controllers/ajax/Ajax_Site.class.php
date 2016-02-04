<?php
class Ajax_Site extends Controller_Ajax {

	public function show_Main() {
		$this->error("invalid ajax-api call.");
	}

	public function show_News() {
		$id = $this->get(1);

		if (!is_numeric($id)) {
			$this->error("Invalid ID");
		}

		$post = R::load('homepage_posts', $id);

		if (!$post->id) {
			$this->error("Post not found");
		}

		$author = R::relatedOne($post, 'user');

		$this->output('date', date("d.m.Y", $post->time));
		$this->output('title', $post->title);
		$this->output('text', nl2br(htmlspecialchars($post->content)));
		$this->output('link', $post->link);
		$this->output('author', $author->username);
	}

	public function show_Account() {
		switch(@$_POST['action']) {
			case "reactivate":
				if (isset($_POST['resetparam']) && !empty($_POST['resetparam'])) {
					$resetUser = R::findOne('user', '(username = ? OR email = ?)',
					array($_POST['resetparam'],
					$_POST['resetparam']));

					if (!$resetUser) {
						$this->error('Der Benutzername oder die Emailadresse wurde
										nicht gefunden.');
					}

					if ($resetUser->is_active == 1) {
						$this->error('Der Benutzername wurde bereits aktiviert. Du kannst dich
						nun einloggen!');
					}

					$resetUser->sendActivationMail();

					$this->output('t', 'Der Aktivierungscode wurde an '.$resetUser->email.'
									geschickt!');
				}
				break;

			case "forgot":
				if (isset($_POST['resetparam']) && !empty($_POST['resetparam'])) {
					$resetUser = R::findOne('user', '(username = ? OR email = ?)
					 AND last_pass_reset < ?', array($_POST['resetparam'],
													 $_POST['resetparam'],
													 time() - 24 * 3600));

					if (!$resetUser) {
						$this->error('Der Benutzername oder die Emailadresse wurde
						nicht gefunden. Alternativ hast du in den letzten 24 Stunden
						dein Passwort schonmal zurückgesetzt.');
					}

					$resetUser->resetPassword();

					$this->output('t', 'Dir wird ein neues Passwort an '.$resetUser->email.'
					zugeschickt!');
				}
				break;

			default:
				$this->error('invalid action');
				break;
		}
	}

	public function show_Login() {
		$loginUser = R::findOne('user', ' username = ? AND password = ?', array(
			$_POST['username'],
			Framework::hash($_POST['password'])
		));

		if (!$loginUser) {
			$this->error("Login fehlgeschlagen. Das Passwort und/oder der Benutzername ist inkorrekt!");
		} else {

			if ($loginUser->is_active == 0) {
				$this->error("Der Account wurde noch nicht aktiviert. Falls du keine Aktivierungsmail
				bekommen hast, so klick bitte auf 'Aktivierungsmail nicht bekommen?'.");
			}

			// check if user still has opened session
			if (isset($_SESSION['loginHash'])) {
				$oldSession = R::findOne('session', ' hash = ? AND ip = ? AND expires > ?', array(
					$_SESSION['loginHash'],
					$_SERVER['REMOTE_ADDR'],
					time()
				));

				if ($oldSession != false) { // kill old session
					$oldSession->expires = time();
					R::store($oldSession);
				}

				// unset cookie
				unset($_SESSION['loginHash']);
			}

			session_regenerate_id(true);

			// open new session
			$newSession = R::dispense('session');
			$newSession->user = $loginUser;
			$newSession->start_time = time();
			$newSession->expires = time() + SESSION_MAX_AGE;
			$newSession->ip = $_SERVER['REMOTE_ADDR'];
			$newSession->browser = $_SERVER['HTTP_USER_AGENT'];
			$newSession->hash = Framework::hash(microtime(true).mt_rand(22222, 33333));

			$_SESSION['loginHash'] = $newSession->hash;

			R::store($newSession);

		}
	}

	public function show_Register() {

		if (!isset($_POST["rules"]) || $_POST["rules"] != "yes") {
			$this->error("Die AGBs und Regeln wurden nicht akzeptiert.");
		}

		if (!isset($_POST["password"]) || empty($_POST["password"]) || strlen($_POST["password"]) < 6) {
			$this->error("Die mindestlänge für Passwörter beträgt 6 Zeichen!");
		}

		if ($_POST["password"] != $_POST["password2"]) {
			$this->error("Die Passwörter stimmen nicht überein.");
		}

		if (R::findOne('user', ' LOWER(email) = LOWER(?)', array($_POST["email"]))) {
			$this->error("Die angegebene Email-Adresse ist bereits registriert!");
		}

		if (R::findOne('user', ' LOWER(username) = LOWER(?)', array($_POST["username"]))) {
			$this->error("Der angegebene Benutzername ist bereits registriert!");
		}

		$newUser = R::dispense('user');


		// reflink support
		if ($_POST["by"] != "-1" && is_numeric($_POST["by"])) {
			$refUser = R::findOne('user', ' id = ?', array((int)$_POST["by"]));

			if ($refUser != false) {
				$newUser->referee = $refUser;
			}
		}


		$newUser->import($_POST, 'username,email');
		$newUser->password = Framework::hash($_POST["password"]);

		$newUser->is_active = false;
		$newUser->activation_code = Framework::hash(microtime(true).mt_rand(1000, 2000));
		$newUser->register_date = time();

		$newUser->characterImage = 1;

		try {
			R::store($newUser);
		} catch(Exception $e) {
			$this->error($e->getMessage());
		}

		$newUser->sendActivationMail();

		$this->output("message", "Die Anmeldung war erfolgreich. Du bekommst jetzt einen Link per Email
		um deine Anmeldung zu bestätigen!");
	}

}
?>