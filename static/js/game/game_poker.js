$(function() {
	var context = document.getElementById('mainPoker').getContext('2d');

	PE.init(context, 704, 512);

	var offset = $("#mainPoker").offset();


	var controlsDiv = $("#controls");

	controlsDiv.css('position', 'absolute')
			   .css('right', offset.left+120)
			   .css('top', offset.top+400);

	$("#controls button").button().css('width', 100);

	if ($("#joinDialog").hasClass('poker_canJoin')) {
		// show join dialog
		$("#joinDialog").text("Möchtest dich an diesen Tisch setzen und Poker spielen?");

		$("#joinDialog").dialog({
			autoOpen: true,
			closeOnEscape: false,
			modal:true,
			title: 'Poker-Tisch',
			draggable: true,
			resizable: false,

			buttons: {
				"mitspielen": function() {
					MF.apiGet('poker', 'join', {}, function(json) {
						if (json.state == 'join_ok') {
							// u joined
							$("#joinDialog").dialog("close");
						} else {
							$("#joinDialog").text("Sorry, aber der Tisch ist zurzeit voll.");
						}
					});
				 },
				"zuschauen": function() {
					$(this).dialog("close");
				}
			},

			beforeClose: function(event, ui) {
				PE.requestLoop();
			}
		});
	} else {
		PE.requestLoop();
	}
});