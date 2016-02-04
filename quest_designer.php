<?php
include "include.php";

$data = array_merge(Config::getConfig('products'), Config::getConfig('resources'));

foreach ($data as $k=>$v) {
	if (isset($v["needs"]) && is_array($v["needs"][0])) {
		$data[$k]["type"] = "product";
	} else {
		$data[$k]["type"] = "resource";
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Quest-Designer Manager's Life</title>
	<script src="./static/js/jquery.js" type="text/javascript"></script>
	<script src="./static/js/jquery-ui.js" type="text/javascript"></script>

	<link rel="stylesheet" type="text/css" href="./static/css/black-tie/jquery-ui.css" />

	<script type="text/javascript">
	var rpData = <?php echo json_encode($data); ?>;

	var rpNeeds = [];

	function setQuest() {
		var nstr = '';

		var invTotal = 0; // total Investment Costs

		var timeToProduce = 0;

		var ffirst = true;

		for (var k in rpNeeds) {
			if(rpNeeds[k]['amount'] != 0) {
				nstr += "\t\t\t" + (ffirst ? "" : ",") + "{\n";
				nstr += '\t\t\t"type": "' + rpNeeds[k]['type'] + '",\n';
				nstr += '\t\t\t"name": "' + rpNeeds[k]['name'] + '",\n';
				nstr += '\t\t\t"amount": "' + rpNeeds[k]['amount'] + '"\n';
				nstr += "\t\t\t}\n";

				ffirst = false;

				// what does the machine cost?
				var t = rpNeeds[k]['name'];
				var investmentCosts = (rpData[t].hasOwnProperty("machine") ? rpData[t].machine.cost : 0);

				// how long will it take
				var ttt = 1;

				if (rpData[t].hasOwnProperty("machine")) {
					ttt = (rpNeeds[k]['amount'] / rpData[t].machine.prod_per_hour);
				}

				if (ttt > timeToProduce) {
					timeToProduce = ttt;
				}

				// how much are the running costs?
				if (rpData[t].hasOwnProperty("machine")) {
					investmentCosts += Math.ceil(ttt) * rpData[t].machine.running_cost;
				}

				// how much do needed ress cost?
				if (!rpData[t].hasOwnProperty("needs")) {
					investmentCosts += rpData[t].base_price * rpNeeds[k]['amount'];
				}
				else {
					// ress
					if (rpNeeds[k]['type'] == 'resource') {
						for (var j in rpData[t].needs) {
							var el = rpData[t].needs[j];
							investmentCosts += rpData[el].base_price * rpNeeds[k]['amount'];
						}
					}
					else { // prod
						for (var j in rpData[t].needs) {
							var el = rpData[t].needs[j].ress;
							investmentCosts += rpData[el].base_price * rpNeeds[k]['amount'] * rpData[t].needs[j].amount;
						}
					}
				}

				invTotal += investmentCosts;
			}
		}

		var maxTime = Math.ceil((timeToProduce+12) / 24);

		$("#maxtime").text(Math.round(timeToProduce) + " Stunden = " + Math.round((timeToProduce) / 24) + " Tage = " + Math.round(((timeToProduce) / 24) / 30) + " Monate");

		$("#inv").text(invTotal);

		var cash = $("#cash").val();
		var profit = (cash - invTotal);

		var percentage = 0;

		if (profit > invTotal) {
			percentage = 0;
		} else {
			percentage = Math.floor((profit / invTotal)*100);
		}

		$("#profit_percentage").text(percentage);

		if (percentage > 100) {
			percentage = 100;
		}

		$("#progressbar").progressbar("value", percentage);

		$("#profit").text(profit);

		var tpl = '\t"' + $("#uid").val() + '": {\n'
	        +'\t\t"title": "' + $("#title").val() + '",\n'
	        +'\t\t"text": "' + $("#desc").val() + '",\n'
	        +'\t\t"needs": [\n' + nstr
	        +'\t\t],\n'
	        +'\t\t"time": ' + maxTime + ',\n'
	        +'\t\t"investmentCosts": ' + invTotal + ',\n'
	        +'\t\t"level": ' + $("#level").val() + ',\n'
	        +'\t\t"profit": ' + profit + ',\n'
	        +'\t\t"oncomplete": {\n'
	        +'\t\t\t"xp": ' + $("#xp").val() + ',\n'
	        +'\t\t\t"cash": ' + cash + '\n'
	        +'\t\t}\n'
	        +'\t},\n';

        $("#output").val(tpl);
	}

	$(function() {
		$("#title, #desc, #cash, #level, #xp, #uid").bind('keyup', function() {
			setQuest();
		});

		$("#progressbar").progressbar({
			value: 0
		});

		$("#btn").click(function() {
			var mkey = rpNeeds.push({'type': 'product', 'name': 'calculator', 'amount': 0}) - 1;

			var span = $("<div>").attr('id', 'rp_div_' + mkey);

			span.append($("<input>")
									.val("0")
									.css('width', '40px')
									.attr('title', mkey)
									.bind('keyup', function() {
				rpNeeds[$(this).attr('title')]['amount'] = $(this).val();
				setQuest();
			}));
			span.append($("<span>").text("x "));

			var select = $("<select>");
			select.attr('title', mkey);
			select.change(function() {
				var t = $(this).val();
				var i = $(this).attr('title');

				rpNeeds[i]['name'] = t;
				rpNeeds[i]['type'] = rpData[t]['type'];

				setQuest();
			});

			span.append(select);
			for (var key in rpData) {
				select.append($("<option>").text(key));
			}

			$("#nress").append(span);
		}).button();

	});
	</script>
</head>
<body>

<h1>Quest-Designer</h1>

<h2>Einstellungen</h2>
Unique-ID: <input id="uid" type="text" /> <br />
Titel: <input id="title" type="text" /> <br />
Beschreibung: <br />
<textarea id="desc"></textarea> <br />

Benötigte Rohstoffe: <button id="btn" class="ui-state-default ui-corner-all">
						<span class="ui-icon ui-icon-circle-plus"></span>
					</button>
<div id="nress"></div>
<br /> <br />
Belohung: <input id="cash" type="text" /> Cash. <br />
Investment-Costs: <span id="inv">0</span> <br />
Profit: <span id="profit">0</span> (<span id="profit_percentage">0</span> %)<br />
<div id="progressbar"></div>
<br />

Belohnung-XP: <input id="xp" type="text" /> <br />

Level: <input id="level" type="text" /> <br />

Dauer: <span id="maxtime">-</span>


<h2>Ausgabe</h2>
<textarea id="output" style="width:100%;height:400px;"></textarea>

</body>
</html>