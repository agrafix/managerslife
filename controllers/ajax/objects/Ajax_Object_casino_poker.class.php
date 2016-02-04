<?php
class Ajax_Object_casino_poker extends Controller_AjaxGameObject {

	protected $myType = 'casino_poker';


	public function show_Interact() {
		$this->output('redir', 'poker');
	}
}
?>