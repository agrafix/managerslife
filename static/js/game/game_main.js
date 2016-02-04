$(function() {
	// setup tabs
	$("#gameTabs").tabs();
	$("#gameTabs").bind('tabsselect', function(event, ui) {
		if (ui.index == 1) {
			$("#inventory").hide();
			$("#inventory_opener").hide();
		} else {
			$("#inventory_opener").show();
		}
	});

	// setup game engine
	var context = document.getElementById('mainMap').getContext('2d');
	GE.init(context);

	// launch chat
	$("#chatBtn").button();

	GE_chat.init("chatLog");

	var commandExpr = /^\/private "([^"]*)" (.*)$/i;

	$("#chatForm").submit(function(event)  {
		event.preventDefault();

		var data = {'message': $("#chattext").val()};

		$("#loadIcon").show();

		MF.apiPost('map', 'chatMessage', data, function(json) {
			if (!json.success) {
				MF.postMessage('red_box', 'Chat-Fehler', json.error);
				return;
			}

			$("#loadIcon").fadeOut();

			GE_chat.handle();

			var t = $("#chattext").val();

			if (!commandExpr.test(t)) {
				$("#chattext").val("");
			} else {
				var parts = commandExpr.exec(t);

				$("#chattext").val("/private \"" + parts[1] + "\" ");
			}
		});
	});

	// handle inventory
	GE_inventory.init('inventory_opener', 'inventory');
});