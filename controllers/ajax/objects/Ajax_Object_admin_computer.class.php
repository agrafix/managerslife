<?php
class Ajax_Object_admin_computer extends Controller_AjaxGameObject {

	protected $myType = 'admin_computer';

	public function show_Interact() {
		$this->output('maintext', 'Das hier ist ein Admin-Computer. Er hat im Moment aber
		keine Funktion und mal schauen ob er noch eine bekommt... gibt erstmal wichtigere
		Sachen :)');
	}

}
?>