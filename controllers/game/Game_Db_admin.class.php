<?php
class Game_Db_admin extends Controller_Game {

	protected $_rights = array('db_admin');

	protected $_use_scripts = array('game/game_dba');

	protected $_use_tpl = 'game/admin_db_admin.html';

	private $dbTables = array(
		'homepage_posts' => array('title', 'content', 'link'),
		'map_object' => array('type', 'x', 'y', 'map'),
		'map_npc' => array('type', 'name', 'x', 'y', 'map', 'can_walk', 'characterImage', 'lookDirection')
	);

	private $currentTable;

	private function prehookHomepage_posts(&$bean) {
		$bean->time = time();
	}

	private function posthookHomepage_posts(&$bean) {
		if (!R::areRelated($bean, $this->user)) {
			R::associate($bean, $this->user);
		}
	}

	public function show_Main() {
		$this->switchTable();
		$this->handleChanges();
		$this->loadTable();
	}

	private function handleChanges() {
		if (!isset($_POST['entryAction'])) {
			return;
		}

		if ($_POST['entryAction'] == 'add') {
			$bean = R::dispense($this->currentTable);

		} elseif ($_POST['entryAction'] == 'edit' && is_numeric($_POST['entryId'])) {
			$bean = R::load($this->currentTable, $_POST['entryId']);

			if (!$bean->id) {
				die('Invalid ID');
			}
		} elseif ($_POST['entryAction'] == 'delete' && is_numeric($_POST['entryId'])) {
			$bean = R::load($this->currentTable, $_POST['entryId']);

			if (!$bean->id) {
				die('Invalid ID');
			}

			R::trash($bean);
			Framework::Redir("game/db_admin");
			return;
		} elseif ($_POST['entryAction'] == 'duplicate' && is_numeric($_POST['entryId'])) {
			$bean = R::load($this->currentTable, $_POST['entryId']);

			if (!$bean->id) {
				die('Invalid ID');
			}

			$newBean = R::dispense($this->currentTable);

			foreach ($this->dbTables[$this->currentTable] as $f) {
				$newBean->$f = $bean->$f;
			}

			R::store($newBean);

			Framework::Redir("game/db_admin");
			return;

		} else {
			Framework::Redir("game/db_admin");
			return;
		}

		// load stuff into bean
		$bean->import($_POST['entry'], implode(",", $this->dbTables[$this->currentTable]));

		// check if any special prehooks defined for current table
		$beanHook = 'prehook'.ucfirst($this->currentTable);

		if (method_exists($this, $beanHook)) {
			$this->{$beanHook}($bean);
		}

		// store bean
		R::store($bean);

		// post hook
		$beanHook = 'posthook'.ucfirst($this->currentTable);

		if (method_exists($this, $beanHook)) {
			$this->{$beanHook}($bean);
		}

		Framework::Redir("game/db_admin");
	}

	private function loadTable() {
		$rows = array();

		$r = R::find($this->currentTable, ' 1 ORDER BY id ASC LIMIT 0,30');

		foreach ($r as $row) {
			foreach ($this->dbTables[$this->currentTable] as $field) {
				$rows[$row->getID()][$field] = $row->$field;
			}
		}

		Framework::TPL()->assign('tableRows', $rows);
	}

	private function switchTable() {
		if (isset($_POST['switchTable'])) {
			$_SESSION['currentTable'] = $_POST['switchTable'];
			Framework::Redir("game/db_admin");
		}

		if (!isset($_SESSION['currentTable']) || !in_array($_SESSION['currentTable'], array_keys($this->dbTables))) {
			$_SESSION['currentTable'] = 'map_object';
		}

		$this->currentTable = $_SESSION['currentTable'];

		Framework::TPL()->assign('currentTable', $this->currentTable);
		Framework::TPL()->assign('dbTables', $this->dbTables);
	}
}
?>