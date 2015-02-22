<li class="message array_message">
	<div class="message_label" >
		<?php 
			echo 
				$label 
				. '<br>Message from ' 
				. $function_call_details['function_call_file'] 
				. ' on line ' . $function_call_details['function_call_line'];
		?>
	</div>
	<pre class="message_body"><?php print_r($message); ?></pre>
</li>
