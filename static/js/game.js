$(function() {
	$("#imgLogoff").click(function() {
		// apiController, apiFunction, params, callbackFunction
		MF.apiGet('user', 'logoff', {}, function() {
			$(location).attr('href', APP_DIR + "site/index");
		});
	});

	$(".premiumbox").click(function() {
		$(location).attr('href', APP_DIR + "game/premium");
	});

	var updaterFunc = function() {
		if (!MF.isFocused) {
			return; // dont waste resources
		}

		MF.apiGet('user', 'data', {}, function(json) {
			if (!json.success) {
				MF.postMessage('red_box', 'Map-Fehler', json.error);
				return;
			}
			$("#user_cash").text(MF.formatCash(json.cash));
			$("#user_level").text(json.level);
			$("#user_reputation").text(json.xp);
			$("#players_online").text(json.players_online);
		});

	};

	updaterFunc();
	window.setInterval(updaterFunc, 5000);

	// keep alive function
	var keepAlive = function() {
		MF.apiSend('user', 'keepAlive', {});
	};
	keepAlive();
	window.setInterval(keepAlive, 60000);

	// tick clock
	var tickClock = function() {
		var $clock = $("#clock");
		var time = $clock.text().split(":");

		var hour = time[0]*1;
		var min = time[1]*1;
		var sec = time[2]*1;

		sec++;

		if (sec >= 60) {
			sec = 0;
			min++;
		}

		if (min >= 60) {
			min = 0;
			hour++;
		}

		if (hour == 24) {
			hour = 0;
		}

		$clock.text((hour >= 10 ? hour : "0" + hour)
		 + ":" + (min >= 10 ? min : "0" + min)
		 + ":" + (sec >= 10 ? sec : "0" + sec));
	};

	window.setInterval(tickClock, 1000);
});