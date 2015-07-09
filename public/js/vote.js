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

	$('#fakeSaveButton').click(function(e) {
		alert("Was steht oben? Du musst nicht auf Speichern klicken, Pfeifenjohnny!");
	});

	function resizeCheck() {
		if(window.innerHeight > window.innerWidth) {
			$('.visible-portrait').removeClass('hidden').addClass('visible-xs');
			$('.hidden-portrait').addClass('hidden');
		} else {
			$('.visible-portrait').addClass('hidden').removeClass('visible-xs');
			$('.hidden-portrait').removeClass('hidden');
		}
	}

	$(window).resize(function() {
		resizeCheck();
	});

	resizeCheck();

});
