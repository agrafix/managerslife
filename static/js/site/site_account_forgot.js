$(function() {
	$("#forgotBtn").button();

	MF.handleForm('form_Forgot', ['resetparam', 'action'], 'site', 'account', function(json) {
		$("#subcontent").slideUp();
		$("#subcontent").empty();

		return json.t;
	});
});