$('document').ready(
	function() {
		// Initially the message body should be hidden.
		$('#messages .array_message .message_body').hide();
		
		// Assign a click event to the message label which reveals the message_body.
		$('#messages .array_message .message_label').click(
			function(event) {
				jq_target = $(event.target);
				jq_target.parent().find('.message_body').slideToggle(
					150,
					function () {
						// Force a redraw of the #messages element because the list item circle markers 
						// seem not to get redrawn, which is annoying. Using the forceRedraw jquery 
						// plugin. We even have to use the 'brutal' option, else it doesn't seem to 
						// work when there is only one element in the list :/
						$('#messages').forceRedraw(true);
					}
				);
			}
		);
	}
);
