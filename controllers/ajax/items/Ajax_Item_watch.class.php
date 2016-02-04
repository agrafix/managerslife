<?php
class Ajax_Item_watch extends Controller_AjaxGameItem {

	protected $myType = "watch";

	public function show_Use() {
		$this->output('maintext', 'Heute ist der '.date('d.m.Y').' <br />
		Es ist '.date('H:i:s').' Uhr');
	}

}
?>