$(document).ready(function() {
	$('.btn-edit').click(function(e) {
		var id = $(e.delegateTarget).data('movie');
		var row = $('#movierow_'+id).children('td');
		var title = $(row[0]).text();
		var year = $(row[1]).text();
		var imdb_id = $(row[2]).text();

		$('#editModal_id').val(id);
		$('#editModal_title').val(title);
		$('#editModal_year').val(year);
		$('#editModal_imdb_id').val(imdb_id);

		$('#editModal').modal('show');
	});
	$('.btn-delete').click(function(e) {
		var id = $(e.delegateTarget).data('movie');

		$('#deleteModal_id').val(id);
		$('#deleteModal').modal('show');
	});

	$('#btnDropCommitments').click(function(e) {
		if(confirm("Sicher?")) {
			$.ajax({
				method: "post",
				url: "/admin/deletecommitments"
			}).done(function() {
				document.location.reload();
			});
		}
	});
});
