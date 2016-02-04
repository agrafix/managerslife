/**
 * ManagersLife GameEngine
 *
 * (c) 2011 by Alexander Thiemann
 * www.agrafix.net
 *
 */

const TILE_SIZE = 32;

const CHAR_CENTER_X = 18;
const CHAR_CENTER_Y = 25;

var GE_mapHelper = {
	canWalk: function(x, y) {
		var tolerated = Math.floor(TILE_SIZE / 4);

		var mp = this.toTileCoords(x+tolerated, y+tolerated);

		var tileID = mp.y * GameMapConfig.width + mp.x;
		var chk1 = (GameMaps[GE.showMap].objects[tileID] == 0);

		var mp2 = this.toTileCoords(x+TILE_SIZE-tolerated, y+TILE_SIZE-tolerated);

		var tileID2 = mp2.y * GameMapConfig.width + mp2.x;
		var chk2 = (GameMaps[GE.showMap].objects[tileID2] == 0);

		return (chk1 && chk2);
	},

	toTileCoords: function(x, y) {
		x = Math.floor(x / TILE_SIZE);
		y = Math.floor(y / TILE_SIZE);

		return {'x': x, 'y': y};
	},

	fromTileCoords: function(x, y) {
		x = x * TILE_SIZE;
		y = y * TILE_SIZE;

		return {'x': x, 'y': y};
	}
};

function GE_spriteManager() {
	this.images = {};

	this.image_definitions = [];
	this.definition_size = 0;

	this.spriteSize = TILE_SIZE;
}

GE_spriteManager.prototype.define = function(imageID, startTileID) {
	this.image_definitions.reverse();
	this.image_definitions.push({'im': imageID, 'tile': startTileID});
	this.image_definitions.reverse();
	this.definition_size++;
};

GE_spriteManager.prototype.load = function(imageID, path, onComplete) {
	this.images[imageID] = new Image();
	this.images[imageID].src = path;
	this.images[imageID].onload = onComplete;
};

GE_spriteManager.prototype.drawSprite2 = function(ctx, spriteID, x, y) {
	if (spriteID == 0) {
		return;
	}

	var imageID = ''; // figure out imageID
	for(var i = 0; i < this.definition_size; i++) {
		if (spriteID >= this.image_definitions[i]['tile']) {
			imageID = this.image_definitions[i]['im'];
			spriteID -= this.image_definitions[i]['tile'];
			break;
		}
	}

	var img = this.images[imageID];
	var h = img.height / TILE_SIZE;
	var w = img.width / TILE_SIZE;

	var row = Math.floor(spriteID / w);
	var col = (spriteID - row*w) - 1;

	if (col < 0) {
		//console.log("Error. Col error with sprite "+ spriteID + " col:" + col);
		//return;
		// @TODO BUG HERE...
		col = w-1;
		row--;
		//console.log("New Col: " + col + " R: " + row);
		//console.log("POS: " + (col*TILE_SIZE) + "|" + (row*TILE_SIZE) + "");
		//return;
	}

	try {

	ctx.drawImage(img, col*TILE_SIZE, row*TILE_SIZE, TILE_SIZE, TILE_SIZE, x, y, TILE_SIZE, TILE_SIZE);

	} catch (e) {
		console.log("Error: H: " + h + " W: "+ w + " im: " + imageID + " sprite: " + spriteID + " col " + col + " row " + row);
	}
};

function GE_object(id, type, x, y) {
	this.id = id;
	this.type = type;
	this.x = x;
	this.y = y;
}

GE_object.prototype.clicked = function() {
	var data = {};
	data["id"] = this.id;

	var inDiv = new GE_interactDiv($("#mapWrapper"), "");

	inDiv.npcType = "Object_" + this.type;
	inDiv.npcID = this.id;

	MF.apiPost(inDiv.npcType, 'interact', data, function (json) {
		inDiv.handleJsonResponse(json);
	});
};

var GE_charImgs = {};

function GE_char(charTypeId, name, id, x, y) {

	var imageURL = APP_DIR + "static/images/chars/char" + charTypeId + ".png";

	this.imageLoaded = false;

	this.is_npc = false;
	this.npcType = "";

	this.ownPlayer = false;

	this._speech = "";
	this._speechTimer = null;

	this.pos = {'x': x, 'y': y};
	this._moveTo = {'x': x, 'y': y};
	this.name = name;
	this.id = id;

	this.fontColor = "#000000";

	this.state = 1;
	this.isMoving = false;

	this.movingDir = -1;

	this.destroyed = false;

	//this.stateTimer = window.setInterval(this.animate, 1000);
	this.stateTimer = window.setInterval(function(thisObj) { thisObj.animate(); }, 100, this);
	/*this.stateTimer = window.setInterval((function (thisObj) {
		return thisObj.animate;
	})(this), 1000);*/
	this.animate();

	// check if image is already loaded
	if (GE_charImgs.hasOwnProperty(imageURL)) {
		this.image = GE_charImgs[imageURL];
		this.loadComplete();

	} else {
		this.image = new Image();
		this.image.src = imageURL;
		this.image.onload = this.loadComplete();
	}
}

GE_char.prototype.speak = function(text) {
	this._speech = text;

	var me = this;

	if (this._speechTimer != null) {
		window.clearTimeout(this._speechTimer);
	}

	this._speechTimer = window.setTimeout(function() {
		me._speech = "";
	}, 10000); // keep speech for 10 secs
};

GE_char.prototype.animate = function() {
	//console.log((this.isMoving ? 'y' : 'n') + ".." + this.movingDir);
	if (this.movingDir == -1) {
		this.state = 7;
		return;
	}

	var offset = this.movingDir * 3;

	if (!this.isMoving) {
		this.state = offset + 1;
		return;
	}

	var internalState = this.state - offset;

	if (internalState < 0 || internalState > 2) {
		internalState = 1;
	}

	internalState++;

	if (internalState > 2) {
		internalState = 0;
	}

	this.state = offset + internalState;
};

GE_char.prototype.loadComplete = function() {
	this.imageLoaded = true;
};

GE_char.prototype.teleportTo = function(x, y) {
	this.pos = {'x': x, 'y': y};
	this._moveTo = {'x': x, 'y': y};
	this.isMoving = false;
	this.animate();
}

GE_char.prototype.moveTo = function(x, y) {
	this._moveTo = {'x': x, 'y': y};
};

GE_char.prototype.unstuck = function() {
	// make sure player's not stuck somewhere!
	if (!GE_mapHelper.canWalk(this.pos.x, this.pos.y)) {
		var y = this.pos.y;

		while (!GE_mapHelper.canWalk(this.pos.x, y) && y < TILE_SIZE * GameMapConfig.height) {
			y++;
		}

		this.pos.y = y;
	}
};

GE_char.prototype.move = function() {
	if (this.destroyed) {
		return;
	}

	this.unstuck();

	if (this.pos.x != this._moveTo.x
			|| this.pos.y != this._moveTo.y) {


		var directions = ['x', 'y'];

		for (var i in directions) {
			var d = directions[i];

			var delta = this.pos[d] - this._moveTo[d];

			if (delta == 0) {
				continue;
			}

			var coord = this.pos[d];
			var internalDir = 0;

			if (delta > 0) {
				coord -= (Math.abs(delta) >= 2 ? 2 : 1);
				internalDir = -1;
			} else {
				coord += (Math.abs(delta) >= 2 ? 2 : 1);
				internalDir = +1;
			}

			if (d == 'x') {
				if (!GE_mapHelper.canWalk(coord, this.pos.y)) {
					if (!this.ownPlayer) {
						this.pos.x = this._moveTo.x;
						continue;
					}

					this._moveTo.x = this.pos.x;
					continue;
				} else {
					this.pos.x = coord;
					this.isMoving = true;

					if (internalDir == 1) {
						this.movingDir = 1;
					} else {
						this.movingDir = 3;
					}
					break;
				}
			} else {
				if (!GE_mapHelper.canWalk(this.pos.x, coord)) {
					if (!this.ownPlayer) {
						this.pos.y = this._moveTo.y;
						continue;
					}

					this._moveTo.y = this.pos.y;
					continue;
				} else {
					this.pos.y = coord;
					this.isMoving = true;

					if (internalDir == 1) {
						this.movingDir = 2;
					} else {
						this.movingDir = 0;
					}
					break;
				}
			}
		}

	} else {
		this.isMoving = false;
	}
};

GE_char.prototype.speechBubble = function(ctx, text) {
	var messure = ctx.measureText(text);

	var w = messure.width;
	var h = 20;

	var x = this.pos.x;
	var y = this.pos.y;

	ctx.beginPath();
	ctx.strokeStyle="black";
	ctx.lineWidth="1";
	ctx.fillStyle="rgba(255, 255, 255, 0.8)";

	ctx.moveTo(x, y);
	ctx.lineTo(x + (w*0.2), y);
	ctx.lineTo(x + (w*0.2), y+10);
	ctx.lineTo(x + (w*0.3), y);
	ctx.lineTo(x + (w), y);

	ctx.quadraticCurveTo(x + (w*1.1), y, x + (w*1.1), y-(h*0.2)); // corner: right-bottom

	ctx.lineTo(x + (w*1.1), y-(h*0.8)); // right

	ctx.quadraticCurveTo(x + (w*1.1), y-h, x + (w), y-h); // corner: right-top

	ctx.lineTo(x, y-h); // top

	ctx.quadraticCurveTo(x - (w*0.1), y-h, x - (w*0.1), y-(h*0.8)); // corner: left-top

	ctx.lineTo(x - (w*0.1), y-(h*0.2)); // left

	ctx.quadraticCurveTo(x - (w*0.1), y, x, y); // corner: left-bottom

	ctx.fill();
	ctx.stroke();
	ctx.closePath();

	ctx.textAlign = 'left';
	ctx.fillStyle = this.fontColor;
	ctx.fillText(text, x, y-6);
};

GE_char.prototype.draw = function(ctx) {
	if (this.destroyed) {
		return;
	}

	var spriteID = this.state;

	var internalWidth = 37; // -1 on both sides!
	var internalHeight = 50; // -12 on both sides

	var row = Math.floor(spriteID / 3);
	var col = (spriteID - row*3);

	var x = internalWidth * col + 1;
	var y = internalHeight * row + 6;

	//console.log("r " + row + " c " + col + " x " + x + " y " + y + " iW" + internalWidth + " iH" + internalHeight);

	var dy = this.pos.y - internalHeight*0.4;

	ctx.drawImage(this.image, x, y, internalWidth, internalHeight, this.pos.x, dy, internalWidth, internalHeight);

	ctx.textAlign = 'center';
	ctx.fillStyle = this.fontColor;
	ctx.fillText(this.name, this.pos.x + internalWidth/2, dy-1);

	if (this._speech != "") {
		this.speechBubble(ctx, this._speech);
	}

};

GE_char.prototype.destroy = function() {
	clearInterval(this.stateTimer);
	this.destroyed = true;
};

GE_char.prototype.isClicked = function() {
	if (!this.is_npc || this.npcType == "") {
		if (!this.is_npc) {
			var data = {};
			data["id"] = this.id;

			var inDiv = new GE_interactDiv($("#mapWrapper"), "Spieler " + this.name);

			inDiv.npcType = 'human';
			inDiv.npcID = this.id;

			MF.apiPost(inDiv.npcType, 'interact', data, function (json) {
				inDiv.handleJsonResponse(json);
			});
		}

		return;
	}

	var data = {};
	data["id"] = this.id;

	var inDiv = new GE_interactDiv($("#mapWrapper"), "NPC " + this.name);

	inDiv.npcType = this.npcType;
	inDiv.npcID = this.id;

	MF.apiPost(inDiv.npcType, 'interact', data, function (json) {
		inDiv.handleJsonResponse(json);
	});
};

function GE_interactDiv($parent, title) {

	if ($("#interactDiv").length == 0) {
		$parent.append($("<div>").attr('id', 'interactDiv'));
	}

	this.title = '';

	if (title !== undefined) {
		this.title = title;
	}

	this.div = $("#interactDiv");
	this.div.addClass('mapPopup');

	var load = $("<img>").attr('src', IMG_DIR + 'ajax-loader.gif')
	 					 .attr('alt', 'Lädt...');
	this.div.append(load);

	var inDiv = this;

	this.div.dialog({
		autoOpen: true,
		width:700,
		height: 400,
		maxHeight: 600,
		modal:true,
		title: this.title,
		draggable: true,

		beforeClose: function(event, ui) {
			inDiv.clear();
		}
	});

	this.menuOptions = [];
	this.text = "";
	this.form = {};

	this.npcID = 0;
	this.npcType = "";
}

GE_interactDiv.prototype.clear = function() {
	this.div.empty();
};

GE_interactDiv.prototype.close = function() {
	this.div.remove();
};

GE_interactDiv.prototype.setText = function(text) {
	this.text = text;
};

GE_interactDiv.prototype.handleJsonResponse = function(json) {

	var inDiv = this;

	if (!json.success) {
		MF.postMessage('red_box', 'NPC-Fehler', json.error);
		inDiv.close();
		return;
	}

	if (json.hasOwnProperty("speak")) {
		if (json.speak != "") {
			GE.players["n" + inDiv.npcID].speak(json.speak);
		}
	}

	if (json.hasOwnProperty("redir")) {
		$(location).attr('href', APP_DIR + "game/" + json.redir);
		return;
	}

	if (json.hasOwnProperty("load")) {

		MF.apiPost(inDiv.npcType, json.load, {"id": inDiv.npcID}, function (json) {
			inDiv.handleJsonResponse(json);
		});

		return;
	}

	// quests
	if (json.hasOwnProperty("quest") && json.hasOwnProperty("maintext")) {
		json.maintext += json.quest;
	}
	// end quests

	inDiv.setText(json.maintext);

	if (json.hasOwnProperty("options")) {
		$.each(json.options, function(k, v) {
			if (json.hasOwnProperty("options_desc")) {
				if (json.options_desc.hasOwnProperty(k)) {
					inDiv.addOptionHandler(v, k, json.options_desc[k]);
				} else {
					inDiv.addOptionHandler(v, k);
				}
			} else {
				inDiv.addOptionHandler(v, k);
			}
		});
	}

	if (json.hasOwnProperty("form")) {
		inDiv.form = json.form;
	}

	/*
	inDiv.addOption('Tschüss!', function() {
		$(this).parent().parent().parent().remove();
	});
	*/

	inDiv.render();
};

GE_interactDiv.prototype.addOptionHandler = function(description, func, lDesc) {

	var inDiv = this;

	var data = {"id": inDiv.npcID};

	this.addOption(description, function() {
		var load = $("<img>").attr('src', IMG_DIR + 'ajax-loader-small.gif')
							 .attr('alt', 'Lädt...');

		$(this).parent().append(load);

		MF.apiPost(inDiv.npcType, func, data, function (json) {
			inDiv.handleJsonResponse(json);
		});
	}, lDesc);
}

GE_interactDiv.prototype.addOption = function(description, callback, lDesc) {
	var d = (lDesc === undefined ? '' : lDesc);
	this.menuOptions.push({'desc': description, 'call': callback, 'pretext': d});
};

GE_interactDiv.prototype.render = function() {
	this.clear();

	// render text
	var p = $("<div>");
	p.html(MF.iconize(this.text));

	this.div.append(p);

	// parse for <a> tags and make sure they are handled correctly
	var localThis = this;
	$.each($("a", p), function(k, el) {
		var a = $(el);

		if (a.attr('href').indexOf('#') === 0) {
			// is internal!
			var target = a.attr('href').replace('#', '');

			a.attr('href', '');

			var data = {};
			data["id"] = localThis.npcID;

			a.button();

			a.click((function(nDiv, nType, nTarget, nData) {
				return function(e) {
					e.preventDefault();

					MF.apiPost(nType, nTarget, nData, function (json) {
						nDiv.handleJsonResponse(json);
					});
				}
			})(localThis, localThis.npcType, target, data));
		}
	});

	// render form
	if (this.form.hasOwnProperty("elements")) {
		var fieldset = $("<fieldset>");

		var form = $("<form>");
		form.append(fieldset);

		var formElements = this.form.elements;
		var target = this.form.target;
		var inDiv = this;

		form.submit(function(event) {
			event.preventDefault();

			var data = {};
			data["id"] = inDiv.npcID;

			for (var i in formElements) {
				var el = formElements[i];
				var fid = 'inp_' + el.name;

				if (el.type == 'checkbox') {
					if ($("#" + fid).is(':checked')) {
						data[el.name] = $("#" + fid).val();
					}
				} else {
					data[el.name] = $("#" + fid).val();
				}
			}

			MF.apiPost(inDiv.npcType, target, data, function (json) {
				inDiv.handleJsonResponse(json);
			});

		});

		for (var i in formElements) {
			var el = formElements[i];
			var fid = 'inp_' + el.name;

			if (el.type != "hidden") {
				fieldset.append($("<label>").attr("for", fid).text(el.desc));
			}

			var html;

			var isDate = false;

			if (el.type == "textarea") {
				html = $("<textarea>");
			} else if (el.type == "select") {
				html = $("<select>");

				$.each(el.options, function(k, v) {
					var o = $("<option>").attr('value', k).text(v);

					if (el.value == k) {
						o.attr('selected', true);
					}

					html.append(o);
				});

			} else {
				html = $("<input>");

				if (el.type == 'date') {
					el.type = 'text';
					isDate = true;
				}

				html.attr('type', el.type);
			}

			html.attr('id', fid);

			html.attr('name', el.name);
			if (el.hasOwnProperty("css")) {
				html.attr('style', el.css);
			}

			if (el.hasOwnProperty("value") && el.type != 'select') {
				html.val(el.value);
			}

			if (el.type == 'checkbox' && el.hasOwnProperty("checked") && el.checked == true) {
				html.attr('checked', 'checked');
			}

			if (isDate) {
				html.datepicker({ minDate: 0, maxDate: 60 });
				html.datepicker( "option", $.datepicker.regional["de"] );
			}

			fieldset.append(html);
			//fieldset.append($("<br />"));
		}
		form.append(fieldset);

		form.append($("<input>").attr('type', 'submit').attr('value', 'Weiter').button());

		this.div.append(form);
	}

	// render options
	var ul = $("<ul>");

	for (var i in this.menuOptions) {
		var el = this.menuOptions[i];

		var li = $("<li>");
		ul.append(li);

		if (el.pretext != '') {
			var desc = $("<span>");
			desc.html(MF.iconize(el.pretext));
			li.append(desc);
		}

		li.append($('<br />'));

		var a =  $("<a>").click(el.call).css('cursor', 'pointer').html(MF.iconize(el.desc)).button();
		li.append(a);
	}

	this.div.append(ul);
	this.div.fadeIn();

	this.menuOptions = [];
	this.text = "";
	this.form = {};
};

var GE = {
	canvasContext: null,

	player: null,

	players: {},

	objects: {},

	showMap: 'main',

	s: new GE_spriteManager,

	init: function(cContext) {
		this.canvasContext = cContext;

		this.canvasContext.font = "bold 12px sans-serif";
		this.canvasContext.fillText("Lädt...", 50, 50);

		for (var i = 1; i <= 9; i++) {
			new GE_char((i < 10 ? "0" + i : i), "", 0, 0, 0); // just load
		}

		this.s.define('baseIMG', 0);
		this.s.define('baseIMG2', 570);

		this.s.load('baseIMG', APP_DIR + "static/images/tiles/tileset02.png", function() {
			GE.s.load('baseIMG2', APP_DIR + "static/images/tiles/tileset03.png", function() {
				GE.mainloop();
			});
		});
	},

	loadObjects: function() {
		MF.apiPost('map', 'updateObjects', {}, function (json) {
			if (!json.success) {
				return;
			}

			GE.objects = {};
			for(var i in json.o) {
				var obj = json.o[i];

				GE.objects["x"+obj.x+"y"+obj.y] = new GE_object(obj.id, obj.type, obj.x, obj.y);
			}
		});
	},

	mainloop: function() {
		var fps = 30;

		// attach event listener for clicks
		$('#mainMap').click(function(e) {
			var globalOffsets = $("#mainMap").offset();

			// get click position
			var x = e.pageX - globalOffsets.left;
			var y = e.pageY - globalOffsets.top;

			x = Math.floor(x);
			y = Math.floor(y);

			// check for clicks on npcs
			var tileCoords = GE_mapHelper.toTileCoords(x, y);
			var ownCoords = GE_mapHelper.toTileCoords(GE.player.pos.x, GE.player.pos.y);

			var dist = Math.sqrt(Math.pow(tileCoords.x - ownCoords.x, 2) + Math.pow(tileCoords.y - ownCoords.y, 2));

			if (dist <= 2) { // only allow clicks on nearby npcs

				var exitFunc = false;

				// clicked on item?
				var itmIndex = "x"+tileCoords.x+"y"+tileCoords.y;

				if (GE.objects.hasOwnProperty(itmIndex)) {
					var object = GE.objects[itmIndex];
					object.clicked();
					return;

				}

				// clicked on npc/player ?
				$.each(GE.players, function(k, p) {
					var player = GE.players[k];

						// you can click on every player now
						var pTileCoords = GE_mapHelper.toTileCoords(player.pos.x, player.pos.y);

						if (pTileCoords.x == tileCoords.x && pTileCoords.y == tileCoords.y) {
							// turn to npc
							if ((pTileCoords.x - ownCoords.x) == -1) {
								GE.player.movingDir = 3;
							} else if ((pTileCoords.x - ownCoords.x) == 1) {
								GE.player.movingDir = 1;
							} else if ((pTileCoords.y - ownCoords.y) == -1) {
								GE.player.movingDir = 2;
							} else if ((pTileCoords.y - ownCoords.y) == 1) {
								GE.player.movingDir = 0;
							}

							// fire isclicked function
							GE.players[k].isClicked();

							exitFunc = true;
						}
				});

				if (exitFunc) {
					return;
				}

			}

			// move character
			var mCoords = GE_mapHelper.fromTileCoords(tileCoords.x, tileCoords.y);
			GE.player.moveTo(mCoords.x+16 - CHAR_CENTER_X, mCoords.y+16 - Math.floor(CHAR_CENTER_Y/2));
			//GE.player.moveTo(x - CHAR_CENTER_X, y - Math.floor(CHAR_CENTER_Y/2));

		});

		// load objects for current map
		GE.loadObjects();

		// run main loop
		window.setInterval(function() {
			GE.tick();

		}, (1000 / fps));

		// run ajax-updating loop
		/*window.setInterval(function() {
			GE.mapUpdate();
		}, 1000);*/
		GE.mapUpdate();

	},

	mapUpdate: function() {
		if (!MF.isFocused) {
			window.setTimeout(function() { GE.mapUpdate(); }, 2500);
			return; // dont waste performance...
		}

		var data = GE_mapHelper.toTileCoords(this.player.pos.x + CHAR_CENTER_X,
				this.player.pos.y + CHAR_CENTER_Y);

		MF.apiPost('map', 'update', data, function (json) {
			// long polling ajax
			window.setTimeout(function() { GE.mapUpdate(); }, 1000);
			// end

			if (!json.success) {
				if (json.error == 'moved_to_far') {
					var mp = GE_mapHelper.fromTileCoords(json.player_position[0], json.player_position[1]);
					GE.player.teleportTo(mp.x, mp.y);
					return;
				}
				MF.postMessage('red_box', 'Map-Fehler', json.error);
				return;
			}

			// if block has chaned, update my coords & update objects
			if (json.map != GE.showMap) {
				GE.showMap = json.map;
				var mp = GE_mapHelper.fromTileCoords(json.player_position[0], json.player_position[1]);
				GE.player.teleportTo(mp.x, mp.y);

				GE.loadObjects();
			}

			// first update existing players
			$.each(GE.players, function(k, p) {

				if (json.players[k] === undefined) {
					// player not visible anymore
					GE.players[k].destroy();
					delete(GE.players[k]);
				} else {
					// update pos
					var mp = GE_mapHelper.fromTileCoords(json.players[k].x, json.players[k].y)
					GE.players[k].moveTo(mp.x, mp.y);
				}
			});

			// now add new players
			$.each(json.players, function(k, p) {
				if (GE.players[k] === undefined) {
					var name = p.name;
					var mp = GE_mapHelper.fromTileCoords(p.x, p.y);

					var charImg = ((p.character*1) < 10 ? "0" + p.character : p.character);

					GE.players[k] = new GE_char(charImg, name, p.id, mp.x, mp.y);
					GE.players[k].movingDir = p.look_direction*1;

					if (p.is_npc) {
						GE.players[k].fontColor = "#FF0000";
						GE.players[k].is_npc = true;
						GE.players[k].npcType = p.npc_type;
					}
				}
			});
		});

	},

	tick: function() {
		if (!MF.isFocused) {
			return; // dont waste performance...
		}

		this.canvasContext.clearRect(0, 0, 720, 528);

		var x = 0;
		var y = 0;

		for (var i in GameMaps[GE.showMap].ground) {
			var tileID = GameMaps[GE.showMap].ground[i];
			var objID = GameMaps[GE.showMap].objects[i];

			if (tileID != 0) {
				this.s.drawSprite2(this.canvasContext, tileID, x*TILE_SIZE, y*TILE_SIZE);
			}

			if (GameMaps[GE.showMap].hasOwnProperty("ground2")) {
				var tid = GameMaps[GE.showMap].ground2[i];
				if (tid != 0) {
					this.s.drawSprite2(this.canvasContext, tid, x*TILE_SIZE, y*TILE_SIZE);
				}
			}

			if (objID != 0) {
				this.s.drawSprite2(this.canvasContext, objID, x*TILE_SIZE, y*TILE_SIZE);
			}

			x++;

			if (x >= GameMapConfig.width) {
				x = 0;
				y++;
			}
		}

		$.each(this.players, function(k, p) {
			GE.players[k].move();
			GE.players[k].draw(GE.canvasContext);
		});

		this.player.move();
		this.player.draw(this.canvasContext);

		var pTileCoords = GE_mapHelper.toTileCoords(this.player.pos.x, this.player.pos.y);
		this.canvasContext.textAlign = 'left';
		this.canvasContext.fillStyle = '#000';
		this.canvasContext.fillText('POS: ' + pTileCoords.x
									+ '|' + pTileCoords.y, 10, 20);
	}
};


var GE_chat = {
	chatDiv: null,

	lastTimestamp: 0,

	isBusy: false,

	init: function(divID) {
		this.chatDiv = $("#" + divID);

		window.setInterval(GE_chat.handle, 2500);
	},

	handle: function() {
		if (!MF.isFocused || GE_chat.isBusy) {
			return;
		}

		GE_chat.isBusy = true;

		MF.apiPost('map', 'chat', {'time': GE_chat.lastTimestamp}, function(json) {
			GE_chat.isBusy = false;

			if (!json.success) {
				return;
			}

			GE_chat.lastTimestamp = json.timestamp*1;

			for (var i in json.messages) {
				var msg = json.messages[i];

				var span = $("<span>").addClass('chatMsg_' + msg.type);

				span.append($("<span>").text("[" + msg.time + "] "));

				if (msg.author == 'sys') {
					span.append($("<span>").html(MF.iconize(msg.text)));
					span.append($("<br />"));

					span.removeClass('chatMsg_' + msg.type).addClass('chatMsg_sys');
				}
				else {
					var to = "";

					if (msg.to != "") {
						to = " <i>(zu " + msg.to + ")</i>";
					} else if (msg.type == "private") {
						to = " <i>(zu dir)</i>";
					}

					span.append($("<span>").html(msg.author + to + ": "));

					span.append($("<span>").text(msg.text));

					span.append($("<br />"));

					if (GE.players.hasOwnProperty("p" + msg.pid)) {
						GE.players["p" + msg.pid].speak(msg.text);
					} else if (msg.type == "own") {
						GE.player.speak(msg.text);
					}
				}

				GE_chat.chatDiv.prepend(span);

				$("#chatTab").effect('highlight');

			}
		});
	}
};

var GE_inventory = {
	opened: false,

	divOpener: null,

	divInventory: null,

	offset: {},

	items: {},

	init: function(divOpenerID, divInventoryID) {
		this.divOpener = $("#" + divOpenerID);
		this.divInventory = $("#" + divInventoryID);

		this.offset = $("#mainMap").offset();

		this.divOpener.css('right', this.offset.left - 30);
		this.divOpener.css('top', this.offset.top);
		this.divOpener.slideDown();

		this.divOpener.hover(
				function() { $(this).addClass('ui-state-hover'); },
				function() { $(this).removeClass('ui-state-hover'); }
			);

		this.divInventory.css('right', this.offset.left + 5);
		this.divInventory.css('top', this.offset.top);

		this.divOpener.click(function() { GE_inventory.toggle(); });

		this.close();

		this.loadInventory();
	},

	loadInventory: function(fn) {

		if (!fn) {
			fn = function() {};
		}

		MF.apiPost('user', 'inventory', {}, function(json) {
			if (!json.success) {
				return;
			}

			GE_inventory.items = json.items;
			fn();
		});
	},

	renderInventory: function() {
		this.divInventory.empty();

		if (this.items.length == 0) {
			var mDiv = $("<div>").addClass('inventoryItem');

			mDiv.append($("<p>").text("Du hast leider keine Items dabei."));

			this.divInventory.append(mDiv);
			return;
		}

		for(var i in this.items) {
			var item = this.items[i];

			var mDiv = $("<div>").addClass('inventoryItem');

			var heading = $("<p>").css('font-weight', 'bold').text((item.amount > 1 ? item.amount + "x " : "") + item.name + " ");

			if (item.is_usable) {

				heading.append($("<a>").text("(" + item.usable_link + ")").css('cursor', 'pointer')
						.click((function(Itype, Iid, Iname) {
							return function() {
								var inDiv = new GE_interactDiv($("#mapWrapper"), Iname);

								inDiv.npcType = Itype;
								inDiv.npcID = Iid;

								MF.apiPost(inDiv.npcType, 'use', {'id': Iid}, function (json) {
									inDiv.handleJsonResponse(json);
								});
							};
						})("Item_" + item.type, item.id, item.name)));
			}

			mDiv.append(heading);

			var desc = $("<p>").text(item.desc);

			mDiv.append(desc);

			this.divInventory.append(mDiv);
		}
	},

	open: function() {
		this.opened = true;

		this.divOpener.empty();
		this.divOpener.html("<span class='ui-icon ui-icon-circle-close'></span>");

		this.loadInventory(function() {
			GE_inventory.renderInventory();
			GE_inventory.divInventory.slideDown();
		});
	},

	close: function() {
		this.opened = false;

		this.divInventory.effect('explode');

		this.divOpener.empty();
		this.divOpener.html("<span class='ui-icon ui-icon-circle-arrow-w'></span>");
	},

	toggle: function() {
		if (this.opened) {
			this.close();
		} else {
			this.open();
		}
	}
};
