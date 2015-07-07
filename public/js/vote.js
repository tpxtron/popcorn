$(document).ready(function() {

	$('input[type=radio]').click(function(e) {
		var id = $(e.target).data('movie');
		var value = $(e.target).val();
		$.ajax({
			method: "post",
			url: "/vote",
			data: { "movieId": id, "value": value }
		}).done(function() {
			$("#movierow_" + id).addClass('flash');
			window.setTimeout(function() { $("#movierow_" + id).removeClass('flash'); }, 1000);
		});
	});

});
