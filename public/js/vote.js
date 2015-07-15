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

	$('.filterLetter').click(function(e) {
		$('.filterLetter').parent('li').removeClass('active');
		$(e.delegateTarget).parent('li').addClass('active');

		var letter = $(e.delegateTarget).data('letter');
		if(letter == "all") {
			$('.movierow').show();
		} else if(letter == "new") {
			$('.movierow').hide();
			$('.newmovie').show();
		} else if(letter == "meh") {
			$('.movierow').hide();
			$('.vote_meh').each(function(i,e) {
				if($(e).is(':checked')) {
					var id = $(e).data('movie');
					$('#movierow_'+id).show();
				}
			});
		} else {
			$('.movierow').hide();
			$('.firstletter_' + letter).show();
		}
	});

	$('#fakeSaveButton').click(function(e) {
		alert("Was steht oben? Du musst nicht auf Speichern klicken, Pfeifenjohnny!");
	});

	function submitSuggestion() {
		var url = $('#suggestionLink').val();
		if(url == "") {
			$('#suggestionErrorAlert').removeClass('hidden').addClass('alertFadeout');
			window.setTimeout(function() { $('#suggestionErrorAlert').removeClass('alertFadeout').addClass('hidden'); }, 5000);
		} else if(!url.match(/^(.*)?imdb(.*)\/tt[0-9]{7}(.*)?/i)) {
			$('#suggestionErrorUrlAlert').removeClass('hidden').addClass('alertFadeout');
			window.setTimeout(function() { $('#suggestionErrorUrlAlert').removeClass('alertFadeout').addClass('hidden'); }, 5000);
		} else {
			$.ajax({
				method: "post",
				url: "/vote/suggest",
				data: { "link": url }
			}).done(function() {
				$('#suggestionForm').addClass('hidden');
				$('#suggestionLink').val('');
				$('#suggestionAlert').removeClass('hidden').addClass('alertFadeout');
				window.setTimeout(function() { $('#suggestionAlert').removeClass('alertFadeout').addClass('hidden'); $('#suggestionForm').removeClass('hidden'); }, 5000);
			});
		}
	}

	$('#suggestionLink').keypress(function(e) {
		if(e.keyCode == 13) {
			submitSuggestion();
		}
	});

	$('#suggestionButton').click(function(e) {
		submitSuggestion();
	});

	function commit(t) {
		$.ajax({
			method: "post",
			url: "/vote/commitment",
			data: { "type": t }
		}).done(function() {
			if(t == "in") {
				$('#commitmentInButton').addClass('hidden');
				$('#commitmentOutButton').removeClass('hidden');
				$('#commitmentInText').removeClass('hidden');
				$('#commitmentOutText').addClass('hidden');
			} else {
				$('#commitmentOutButton').addClass('hidden');
				$('#commitmentInButton').removeClass('hidden');
				$('#commitmentOutText').removeClass('hidden');
				$('#commitmentInText').addClass('hidden');
			}
		});
	}

	$('#commitmentInButton').click(function(e) {
		commit('in');
	});

	$('#commitmentOutButton').click(function(e) {
		commit('out');
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
