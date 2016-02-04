$(function() {
	$("#characterTabs").tabs();

	$(".svBtn").button();

	$("#charSelect").selectable({
		stop: function() {
			$( ".ui-selected", this ).each(function() {
				var sel = $(this).attr('title');

				$("input[name=characterImage]").val(sel);
			});
		}
	});

	MF.handleForm('formProfile', ['characterImage'],
			'user', 'updateProfile', function(json) {

		return json.message;
	});

	MF.handleForm('formDelete', ['password'],
			'user', 'deleteUser', function(json) {

		if (json.hasOwnProperty("deleted") && json.deleted) {
			$(location).attr('href', APP_DIR + "site/index");
		}

		return json.message;
	})
});