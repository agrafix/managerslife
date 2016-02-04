$(function() {
	$("#loginBtn").button();
	$("#register_link").button();

	MF.handleForm('form_Login', ['username', 'password'], 'site', 'login', function(json) {
		$("#subcontent").slideUp();
		$("#subcontent").empty();

		//window.location.replace(APP_DIR + "game/index");
		var url = APP_DIR + "game/index";

		$(location).attr('href',url);

		return "Login erfolgreich!";
	});

	$("#newsPopup").dialog({ autoOpen: false,
							 minWidth: 480,
							 minHeight: 200
							});

	$(".linked").click(function() {
		var id = $(this).attr('id').replace('n', '');

		$("#newsPopup").append($("<img>").attr('src', IMG_DIR + 'ajax-loader.gif')
										 .attr('alt', 'Loading...')
										 .css('margin-top', 30));
		$("#newsPopup").css('text-align', 'center');
		$("#newsPopup").dialog("open");

		MF.apiGet('site', 'news', {'id': id}, function(json) {
			if (!json.success) {
				alert(json.error);
				return;
			}

			var $popup = $("#newsPopup");
			$popup.empty();
			$popup.css('text-align', 'left');

			$popup.append($("<div>").html("geschrieben am "
											+ json.date + " von <b>"
											+ json.author + "</b>")
									.css('margin-bottom', 10)
									.css('text-align', 'center'));

			$popup.append($("<div>").html(json.text)
									.css('margin', 5));

			if (json.link != "") {
				/*$popup.append($("<a>").attr('href', json.link)
									  .attr('target', '_blank')
									  .css('border', 'none')
									  .css('margin-top', 15)
									  .html("&raquo; mehr")
									  .button());*/

				$popup.dialog( "option", "buttons", {
					"mehr": function() { $(this).dialog("close"); window.open(json.link,'_newtab'); }
				});
			} else {
				$popup.dialog( "option", "buttons", {} );
			}

			$popup.dialog( "option", "title", json.title);
		}, true);
	});
});