<?php global $ark_config; ?>
<?php foreach( $relative_javascript_paths as $relative_javascript_path ): ?>
<script type="text/javascript" src="<?php echo $relative_javascript_path; ?>"></script>
<?php endforeach; ?>