/**
 * ManagersLife PokerEngine
 *
 * (c) 2011 by Alexander Thiemann
 * www.agrafix.net
 *
 */

function PE_cardHandler() {
	this.cards = ['a', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'j', 'q', 'k'];
	this.colors = ['clubs','spades', 'hearts', 'diamonds'];

	this.images = {};
}

PE_cardHandler.prototype.load = function(type, path, onComplete) {
	this.images[type] = new Image();
	this.images[type].src = path;
	this.images[type].onload = onComplete;
};

PE_cardHandler.prototype.drawTurnedCard = function(ctx, px, py, size, angle) {


	ctx.save();

	if (angle === undefined) {
		angle = 0;
	}

	if (size === undefined) {
		size = {x: 1, y: 1};
	}

	if (angle != 0) {
		ctx.translate(px, py);
		ctx.rotate(angle*(Math.PI/180));
		px = 0;
		py = 0;
	}

	ctx.drawImage(this.images['card_back'], 0, 0, 72, 98, px, py, 73*size.x, 98*size.y);

	ctx.restore();
};

PE_cardHandler.prototype.turnCard = function(ctx, card, color, px, py, turnState) {
	// background
	ctx.clearRect(px, py, px+72, py+98);

	// first the back
	if (turnState <= 50) {
		this.drawTurnedCard(ctx, px, py, {x:1, y:1-(turnState/50)});
	}
	else {
		this.drawCard(ctx, card, color, px, py, {x:1, y:(turnState-50)/50});
	}

	return turnState+1;
};

PE_cardHandler.prototype.drawCard = function(ctx, card, color, px, py, size, angle) {

	ctx.save();

	if (size === undefined) {
		size = {x: 1, y: 1};
	}

	if (angle === undefined) {
		angle = 0;
	}

	if (angle != 0) {
		ctx.translate(px, py);
		ctx.rotate(angle*(Math.PI/180));
		px = 0;
		py = 0;
	}

	var i = -1;
	for(var x in this.cards) {
		i++;

		if (this.cards[x] == card) {
			break;
		}
	}

	var j = -1;
	for (var x in this.colors) {
		j++;

		if (this.colors[x] == color) {
			break;
		}
	}

	var posX = 73 * i;
	var posY = 98 * j;

	ctx.drawImage(this.images['cards'], posX, posY, 72, 98, px, py, 73*size.x, 98*size.y);

	ctx.restore();
};

PE_cardHandler.prototype.drawChip = function(ctx, type, px, py) {
	ctx.save();

	ctx.beginPath();
	ctx.arc(px, py, 7, 0, Math.PI*2, true);
	ctx.closePath();

	ctx.strokeStyle = 'black';
	ctx.stroke();

	var color = "blue";

	if (type == "blue") {
		color = 'rgb(48, 136, 165)';
	} else if(type == "red") {
		color = 'rgb(165, 48, 48)';
	} else if(type == "black") {
		color = 'rgb(33, 25, 25)';
	} else {
		color = 'rgb(165, 163, 48)';
	}

	ctx.fillStyle = color;
	ctx.fill();

	ctx.restore();
};

var PE_buttons = {
	draw: function() {
		var controlDiv = $('#controls');
		if (!PE.my_turn) {
			controlDiv.empty();
		} else {
			for(var i = 0; i < PE.my_options.length; i++) {
				console.log(i);
				var htmlClass = "ctrl_" + PE.my_options[i];

				if(!controlDiv.find('.' + htmlClass).length) {
					controlDiv.append(
						$("<button>").addClass(htmlClass)
									 .text(PE.my_options[i])
									 .button()
									 .click(PE_buttons.sendFN(PE.my_options[i]))
					);
				}
			}
		}
	},

	sendFN: function(opt) {
		if (opt != "raise") {
			return function() {
				$('#controls').empty();
				MF.apiSend('poker', 'play', {'do': opt});
			};
		} else {
			return function() {

				var diff = PE.maxbid - PE.my_bid;
				if (diff < 0) {
					diff = 0;
				}

				var cash = $("#user_cash").text() * 1;
				diff += cash;

				$("<div>")
				.html("<p>Um wie viel möchtest du erhöhen?</p>"
				+     "<input type='number' min='0' max='" + diff + "' autocomplete='off' value='0' id='amount' /> "
				+     "<img src='" + IMG_DIR + "icons/money.png' alt='Money' />")
				.dialog({
					autoOpen: true,
					closeOnEscape: false,
					modal:true,
					title: 'Poker-Tisch: Raise',
					draggable: true,
					resizable: false,
					closable: false,

					buttons: {
						"Raise": function() {
							MF.apiSend('poker', 'play', {'do': opt, 'amount': $("#amount").val()});
						 	$('#controls').empty();
						 	$(this).dialog("close");
						 }
					},

					close: function() {
						$(this).remove();
					}
				});
			};
		}
	},

	waitingForPlayers: function() {

		if ($("#waitingDiv").length) {
			return;
		}

		$("<div>")
				.attr('id', 'waitingDiv')
				.html("Warte auf Mitspieler...")
				.dialog({
					autoOpen: true,
					closeOnEscape: false,
					modal:false,
					title: 'Poker-Tisch: Warten',
					draggable: true,
					resizable: false,
					closable: false,

					close: function() {
						$(this).remove();
					}
		});
	}
};

var PE = {

	ctx: null,

	dimensions: {w: 0, h: 0},

	ch: new PE_cardHandler(),

	center_cards: [],

	my_cards: [],

	my_bid: 0,

	my_turn: false,

	my_options: [],

	pot: 0,

	ttl: 0,

	maxbid: 0,

	lastChatId: 0,

	players: [],

	init: function(ctx, width, height) {
		this.ctx = ctx;

		this.dimensions.w = width;
		this.dimensions.h = height;

		this.ctx.font = "bold 12px sans-serif";
		this.ctx.fillText("Lädt...", 50, 50);

		PE.ch.load('cards', APP_DIR + "static/images/cards.png", function() {
			PE.ch.load('card_back', APP_DIR + "static/images/card_back.png", function() {
				PE.main();
			});
		});
	},

	requestLoop: function() {
		MF.apiPost('poker', 'play', {'chatID': PE.lastChatId}, function(json) {
			window.setTimeout(PE.requestLoop, 1500);

			if (!json.success) {
				return;
			}

			if (json.hasOwnProperty("waiting_for_players")) {
				PE_buttons.waitingForPlayers();
			} else {
				if ($("#waitingDiv").length) {
					$("#waitingDiv").remove();
				}
			}

			PE.lastChatId = json.lastID;

			PE.center_cards = json.center_cards;
			PE.my_cards = (json.hasOwnProperty("my_cards") ? json.my_cards : []);
			PE.my_bid = (json.hasOwnProperty("my_bid") ? json.my_bid : 0);
			PE.players = json.players;
			PE.ttl = json.ttl;

			PE.my_turn = json.myturn;

			if (PE.my_turn) {
				PE.my_options = json.options;
			}

			PE.maxbid = json.maxbid;
			PE.pot = json.pot;

			$.each(json.msg, function(i, msg) {
				var p = $("<span>").html("[" + msg['time'] + "] " + MF.iconize(msg['message']) + "<br />");
				$("#pokerChat").prepend(p);
			});

			PE.main();
		});
	},

	main: function() {

		PE.ctx.clearRect(0, 0, PE.dimensions.w, PE.dimensions.h);

		PE.ctx.fillStyle = "rgb(37, 96, 35)";
		PE.ctx.fillRect(20, 20, PE.dimensions.w-40, PE.dimensions.h-40);

		//PE.ch.drawCard(PE.ctx, 'a', 'hearts', 200, 200, undefined, 90);
		//PE.ch.drawTurnedCard(PE.ctx, 200, 200, {x:0.5,y:0.5}, 0);

		/*var i = 0;

		var _int = window.setInterval(function() {
			i = PE.ch.turnCard(PE.ctx, 'a', 'clubs', 380, 200, i);
			if (i > 100) {window.clearInterval(_int);}
		}, 10);
		*/

		PE.ctx.fillStyle = "rgb(165, 48, 48)";

		// rect for timer
		var percentage = PE.ttl / 60;

		if (percentage < 0) { percentage = 0; }

		PE.ctx.fillRect(25, 160, 40, percentage * (PE.dimensions.h-190));
		PE.ctx.strokeRect(25, 160, 40, PE.dimensions.h-190);

		PE.ctx.fillStyle = "black";

		// player cards
		if (PE.my_cards.length == 2) {
			PE.ctx.fillText('Deine Hand (Dein Einsatz: ' + PE.my_bid + ' Geld)', PE.dimensions.w/3, PE.dimensions.h-120);
			PE.ch.drawCard(PE.ctx, PE.my_cards[0].card, PE.my_cards[0].color, PE.dimensions.w/3, PE.dimensions.h-100);
			PE.ch.drawCard(PE.ctx, PE.my_cards[1].card, PE.my_cards[1].color, PE.dimensions.w/3+73, PE.dimensions.h-100);
		}

		// the control-section
		PE_buttons.draw();

		// cards in center
		var cPos = {x: 0, y: 0};

		for (var i = 0; i < 5; i++) {
			cPos.x = (PE.dimensions.w/3.5)+52*i;
			cPos.y = PE.dimensions.h/2-50;

			if (PE.center_cards.hasOwnProperty(i)) {
				if (PE.center_cards[i].card == "?") {
					PE.ch.drawTurnedCard(PE.ctx, cPos.x, cPos.y, {x:0.7, y:0.7});
				} else {
					PE.ch.drawCard(PE.ctx, PE.center_cards[i].card, PE.center_cards[i].color, cPos.x, cPos.y, {x:0.7, y:0.7});
				}
			}
		}

		// amount in pot
		PE.ctx.fillText('Aktuell im Pot: ' + PE.pot + ' Geld', cPos.x+70, cPos.y - 20);

		// chips
		PE.ch.drawChip(PE.ctx, 'blue', cPos.x+100, cPos.y+20);
		PE.ch.drawChip(PE.ctx, 'black', cPos.x+105, cPos.y+35);
		PE.ch.drawChip(PE.ctx, 'red', cPos.x+100, cPos.y+35);
		PE.ch.drawChip(PE.ctx, 'yellow', cPos.x+127, cPos.y+20);

		// opp cards
		for (var i = 0; i < 8; i++) {
			var oppBase = {x: 90 + 90*i, y: 90, angle: 170 + 4*i};

			if (PE.players.hasOwnProperty(i)) {
				// up to eight opps
				for (var j = 0; j < 2; j++) {
					if (PE.players[i].cards[j].card == "?") {
						PE.ch.drawTurnedCard(PE.ctx, oppBase.x+40*j, oppBase.y-5*j, {x:0.6, y:0.6}, oppBase.angle);
					} else {
						PE.ch.drawCard(PE.ctx, PE.players[i].cards[j].card, PE.players[i].cards[j].color, oppBase.x+40*j, oppBase.y-5*j, {x:0.6, y:0.6}, oppBase.angle);
					}
				}

				PE.ctx.fillText(PE.players[i].username, oppBase.x-40, oppBase.y+30);
				PE.ctx.fillText("Einsatz: " + PE.players[i].bid + " Geld", oppBase.x-40, oppBase.y+50);
			}
		}
	}

};