<?php
class Ajax_Business_center extends Controller_AjaxGameNPC {

	protected $myType = 'business_center';

	public function show_Interact() {
		$this->output('maintext', 'Willkommen im Business-Center! Von hier aus
		kannst du deine Firma und deren Fabrikgebäude verwalten und steuern. Such
		dir einfach einen Arbeitsplatz aus und klicke auf den PC. Dank moderner Netzwerk-
		Technik musst du nicht jedesmal zum gleichen PC zurückkehren, sondern kannst alle
		hier verfügbaren Rechner benutzen. <br /> <br />
		Um Rohstoffe für deine Firma zu kaufen oder zu verkaufen, oder um fertige Produkte auf
		den Markt zu bringen musst du ins Trading-Center nebenan gehen.<br /><br />
		Dort kannst du auch Aufträge annehmen, die dir und deinem Unternehmen eine sichere
		Einnahme-Quelle versprechen.');
	}
}
?>
