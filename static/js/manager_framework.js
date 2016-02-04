/**
 * The Managerslife JavaScript Framework
 *
 * (c) 2011 by www.managerslife.de
 *
 * Coding by Alexander Thiemann
 */

var MF = {

		isFocused: true,

		iconize: function(text) {
			if (text === undefined) {
				return '';
			}

			text = text.replace(/\{([a-z0-9_]*) title="([^"]*)"\s*\}/ig, '<img src="' + IMG_DIR + 'icons/$1.png" alt="$1" title="$2" />');
			text = text.replace(/\{([a-z0-9_]*)\}/ig, '<img src="' + IMG_DIR + 'icons/$1.png" alt="$1" title="$1" />');
			return text;
		},

		formatCash: function(cash) {
			var input = cash.toString();
			var counter = 1;
			var output = '';

			if (input.length <= 3) {
				return input;
			}

			for(var i=input.length-1;i>=0;i--) {
				output = input[i] + output;
				if ((counter%3) == 0) {
					output = "." + output;
				}

				counter++;
			}

			return output;
		},

		focusHandlers: function() {
			function onBlur() {
			    MF.isFocused = false;
			};
			function onFocus(){
				MF.isFocused = true;
			};

			if (/*@cc_on!@*/false) { // check for Internet Explorer
			    document.onfocusin = onFocus;
			    document.onfocusout = onBlur;
			} else {
			    window.onfocus = onFocus;
			    window.onblur = onBlur;
			}
		},

		handleForm: function(formID, fields, apiController, apiFunction, callbackFunction) {
			$("#" + formID).submit(function(event) {
				event.preventDefault();

				var p = {};

				for (var i in fields) {
					var type = $("input[name=" + fields[i] + "]").attr('type');

					if (type == 'text' || type == 'password' || type == 'hidden') {
						p[fields[i]] = $("input[name=" + fields[i] + "]").val();
					} else if (type == 'checkbox') {
						if ($("input[name=" + fields[i] + "]").is(':checked')) {
							p[fields[i]] = $("input[name=" + fields[i] + "]").val();
						}
					} else {
						alert("Unknown type: " + type);
					}
				}

				MF.rLoad();
				MF.apiPost(apiController, apiFunction, p, MF.rWrapper(callbackFunction));
			});
		},

		/**
		 * post message in ajax div
		 * @param type supported types: green_box|red_box|ajax_loader
		 * @param title title of message
		 * @param content message content
		 */
		postMessage : function(type, title, content) {

			if (content == "not_loggedin") {
				$(location).attr('href', APP_DIR + "site/index/main/session_expired");
			}

			var $div = $("#ajax_responder");

			$div.empty();
			$div.removeClass('green_box red_box ajax_loader');

			$div.addClass(type);

			var heading = $("<h2>").text(title);
			var p = $("<p>").text(content);

			$div.append(heading);
			$div.append(p);

			$div.fadeIn();

			window.setTimeout(function() {
				$div.fadeOut();
			}, 5000);

		},

		/**
		 * Shows loading icon
		 *
		 */
		rLoad : function() {
			$("#ajax_responder").fadeOut();

			$("#ajax_responder").empty();
			$("#ajax_responder").removeClass('green_box red_box ajax_loader');
			$("#ajax_responder").addClass('ajax_loader');

			$("#ajax_responder").fadeIn();
		},

		/**
		 * Wrapper for AJAX-Returns (automatic error handling)
		 * The customWrapper-Function is only called on success
		 *
		 * @param customWrapper Function that takes the json as argument and returns success msg
		 * @returns {Function}
		 */
		rWrapper : function(customWrapper) {
			if (customWrapper === undefined) {
				customWrapper = function() { return ""; };
			}

			return (function(CW) {
				return function(json) {
					var title = '';
					var content = '';
					var type = '';

					if (!json.success) {
						title = 'Fehler';
						content = json.error;
						type = 'red_box';

					} else {
						title = 'Erfolg';
						content = CW(json);
						type = 'green_box';
					}

					MF.postMessage(type, title, content);

			};
			})(customWrapper);
 		},

 		/**
 		 * make an Ajax-Call and don't care about response
 		 *
 		 * @param apiController
 		 * @param apiFunction
 		 * @param params
 		 */
		apiSend : function(apiController, apiFunction, params) {
			this.apiGet(apiController, apiFunction, params, function() {}, false);
		},

		/**
		 * Ajax-Get-Call
		 *
		 * @param apiController
		 * @param apiFunction
		 * @param params
		 * @param callbackFunction
		 * @param allow_caching
		 */
		apiGet : function(apiController, apiFunction, params, callbackFunction, allow_caching) {

			if (params === undefined) {
				params = {};
			}

			if (callbackFunction === undefined) {
				callbackFunction = function() {};
			}

			var paramStr = "";

			$.each(params, function(key, value) {
				paramStr += "/" + value;
			});

			var nocache = {};
			if (!allow_caching) {
				nocache["__"] = new Date().getTime();
			}

			$.getJSON(APP_DIR + "ajax/" + apiController + "/" + apiFunction + paramStr, nocache, callbackFunction);
		},

		/**
		 * Ajax-Post call
		 *
		 * @param apiController
		 * @param apiFunction
		 * @param params
		 * @param callbackFunction
		 * @param allow_caching
		 */
		apiPost : function(apiController, apiFunction, params, callbackFunction, allow_caching) {

			if (params === undefined) {
				params = {};
			}

			if (callbackFunction === undefined) {
				callbackFunction = function() {};
			}

			// send secure hash
			params["fSecureHash"] = SECURE_HASH;

			var nocache = "";

			if (!allow_caching) {
				nocache = "?__=" + new Date().getTime();
			}
			$.post(APP_DIR + "ajax/" + apiController + "/" + apiFunction + nocache, params, callbackFunction, "json");
		}
};

// add focus handlers to document
MF.focusHandlers();