<?php
class Model_Quests_npc extends RedBean_SimpleModel {

	public function giveNewQuest(RedBean_OODBBean $user) {
		$allQuests = Config::getConfig('npc_quests');
		$rnd = array_rand($allQuests, 1);

		$this->accepted = 0;
		$this->quest_id = $rnd;
		$this->startnpc = R::findOne('map_npc', ' map != ? ORDER BY RAND() LIMIT 1', array(
						'adminhouse'
		));

		$this->stopnpc = R::findOne('map_npc', ' map != ? AND map != ? AND id != ? ORDER BY RAND() LIMIT 1', array(
						$this->startnpc->map,
						'adminhouse',
		$this->startnpc->getID()
		));

		R::store($this->bean);
		R::associate($this->bean, $user);
	}

}
?>