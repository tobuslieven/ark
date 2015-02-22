<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php global $my_config; ?>
<html lang="en-US" >
<head>
    <title><?php echo $title; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"><!--  <meta name="viewport" content="width=device-width" /> -->
    <!--  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" /> -->
    <!-- <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">  -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<!--    <link rel="shortcut icon" href="path/to/favicon.ico">-->
    <:css:>
    <:javascript:>
    <?php $this->get_javascript('jquery-1.11.0.js', 'none', 'jquery'); ?>
    <?php $this->get_javascript('ark_utility.js', array('jquery'), 'ark_utility'); ?>
    <?php $this->get_css('ark_reset.css'); ?>
    <?php $this->get_css('ark_one_column_responsive.css'); ?>
</head>
<body class="one_column_responsive">
<div id="container">
    <:messages:>
    <?php
    // ------
    // Header
    // ------
    // As the header will be the same on most pages, it'll usually be overridden by a
    // header.php view in the user's my_layout module. But if they want to pass in a
    // string instead, then they can do that to override it on any individual page.
    if( $header === NULL )
        $this->get_view('header');
    else
        echo $header;

    // ------------
    // Main Content
    // ------------
    // This will be overridden on pretty much every page by the user.
    echo $main_content;

    // ------
    // Footer
    // ------
    if( $footer === NULL )
        $this->get_view('footer');
    else
        echo $footer;
    ?>
</body>
</html>