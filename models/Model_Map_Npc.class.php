<?php
class Model_Map_Npc extends RedBean_SimpleModel {
	public function update() {

		if ($this->x < 0 || $this->y < 0 || $this->x > 22 || $this->y > 16) {
			throw new Exception("Ungültige Koordinaten!");
		}

	}
}
?>