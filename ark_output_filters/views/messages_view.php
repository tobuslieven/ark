<?php if( is_array($messages) && count($messages) ): ?>
	<?php 
		global $tritas_config;
		$controller->get_css('messages.css');
		$controller->get_javascript('messages.js');
	?>
	<div id="messages">
		<h2>Messages</h2>
		<ul>
			<?php foreach( $messages as $message ): ?>
				<!-- Load the view appropriate for the current type of message. -->
				<?php 
					// You want to pass the $controller on to the individual message views.
					$message['controller'] = $controller;
				?>
				<?php $this->get_view( $message['type'] . '_view', $message ); ?>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>