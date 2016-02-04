$(function() {
	$("#dbaTabs").tabs();
	$("#dbaTabs").tabs('select', '#dbaTabs-2');
	$(".svBtn").button();

	$('.duplicateLink').click(function() {
		var id = $(this).attr('title');

		$("#duplicateForm input[name=entryId]").val(id);
		$("#duplicateForm").submit();
	});

	$('.editLink').click(function() {
		var id = $(this).attr('title');
		$("#actionLabel").text("#" + id + " bearbeiten");

		$("#editForm input[name=entryId]").val(id);
		$("#editForm input[name=entryAction]").val("edit");

		var fields = $(this).parent().siblings();

		var clearFields = [];

		$.each(fields, function(k, el) {
			var c = $(el).attr('class');
			if (c !== undefined) {
				$("#editForm #" + c).val($(el).text());
				clearFields.push(c);
			}
		});

		$("#quitEdit").show();
		$("#quitEdit").text("[bearbeiten beenden]");
		$("#quitEdit").button();
		$("#quitEdit").click((function(fs) {
			return function() {
				$("#editForm input[name=entryId]").val("");
				$("#editForm input[name=entryAction]").val("add");
				$("#actionLabel").text("hinzufügen");
				$(this).hide();

				for(var i in fs) {
					var f = fs[i];
					$("#editForm #" + f).val("");
				}
			}
		})(clearFields));

		$("#dbaTabs").tabs('select', '#dbaTabs-3');
	});

	$('.deleteLink').click(function() {
		var id = $(this).attr('title');


		$("#deleteId").text(id);
		$("#delForm input[name=entryId]").val(id);

		$("#deleteDialog").dialog({
			autoOpen: true,
			title:'Löschung von Eintrag #' + id + ' bestätigen',

			buttons: {
				"Ja": function() {
					$("#delForm").submit();
				},
				"Nein": function() {
					$(this).dialog("close");
				}
			}
		});
	});
});