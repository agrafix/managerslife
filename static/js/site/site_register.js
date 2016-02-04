$(function() {
	$("#signupBtn").button();

	MF.handleForm('form_Register', ['rules', 'username', 'password', 'password2', 'email', 'by'],
			'site', 'register', function(json) {
		$("#subcontent").slideUp();
		$("#subcontent").empty();

		return json.message;
	});
});