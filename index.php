<?php

//trigger_error("here's an error", E_USER_ERROR);

//echo phpinfo();

function t_print( $printable, $label = '', $clear_output = False )
{
	if( $clear_output )
	{
		ob_end_clean();
	}
	
	echo '<pre> ' . $label;
	if( is_array($printable) )
	{
		print_r( $printable );
	}
	else 
	{
		echo $printable;
	}
	echo '</pre>';
	
	if( $clear_output )
	{
		ob_end_flush();
		exit;
	}
}

//// ERROR HANDLER
//// bool handler ( int $errno , string $errstr [, string $errfile [, int $errline [, array $errcontext ]]] )
//function t_temp_error_handler() {
//
//	echo('Need to work on error handler in index.php');
//}
//
//set_error_handler(t_temp_error_handler);

// ---------------------------------------------------------------
// Start The Session
// ---------------------------------------------------------------
// 
// Well, might as well do it now right near the start right?
// 
session_start();
//session_unset();

// Reset the one shot globals. These are used by modules that want to do something only
// once per page; for example the gallery module which wants to add the viewer div html only
// once on any page regardless of how many galleries are added to the page.
$_SESSION['ark_one_shot_globals'] = array();

// ---------------------------------------------------------------
// Sort Out Some Paths
// ---------------------------------------------------------------
// 
// Prepare the $my_config array with some useful paths.
// 
$current_directory = rtrim(getcwd(), '/') . '/';
require_once( $current_directory . 'my_config.php' );

// The script_filename is actually the absolute disk path of this index.php file.
// By subtracting the server document root from the beginning and the simple filename from 
// the end, we get the path from the server document root to the ark installation folder.
$webroot_to_installation_root = str_replace
(
	array( $_SERVER['DOCUMENT_ROOT'], 'index.php' ),
	array( '', '' ), 
	$_SERVER[ 'SCRIPT_FILENAME' ] 
);

// The url of the root of our installation is the base url plus the path from 
// the webroot of our server to the root of our installation.
$my_config[ 'installation_url' ] = $my_config[ 'base_url' ] . $webroot_to_installation_root;

// Sort out the most useful locations' disk paths. The rtrim() and .'/' makes sure there's 
// exactly one '/' on the end.
$my_config[ 'installation_root' ] = $current_directory;

// ---------------------------------------------------------------
// Sort Out The Query
// ---------------------------------------------------------------
// 
// Sort out the part of the url that we'll use to determine the request that's being made to the 
// server, with the path from our web root to our installation chopped off the front of it.
$my_config[ 'ark_query_unrouted' ] = str_replace( '/' . $webroot_to_installation_root, '', $_SERVER[ 'REQUEST_URI' ] );

// Load the router class.
$path_to_my_router  = $my_config[ 'installation_root' ] . 'my_router.php';
require_once( $path_to_my_router );
$my_router = new My_Router;

$my_config[ 'ark_query' ] = $my_router->resolve_query( $my_config['ark_query_unrouted'] );

//t_print( $my_config, '$my_config: ');
//t_print( $_SERVER, '$_SERVER: ');

// ---------------------------------------------------------------
// Use The ark_Controller Class To Execute The Query
// ---------------------------------------------------------------
//
// Use the wonderful controller class to execute the query. Do it in an ob_start/end 
// block so we can grab the output and run filters on it.

ob_start();

	// Might as well say it here. We'll be using utf8.
	header("Content-type: text/html; charset=utf-8");
	
	// Include the base controller and model classes.
	$path_to_ark_controller = $my_config[ 'installation_root' ] . 'ark_controller/controller.php';
	$path_to_ark_model = $my_config[ 'installation_root' ] . 'ark_controller/models/model.php';
	require_once( $path_to_ark_controller );
	require_once( $path_to_ark_model );
	
	// Create base controller instance.
	$ark_controller = new Ark_Controller( $my_router );
	
	// Also create the user class. Now anyone can access it as a global.
	// As an aside: you could even override this user class by putting a route which redirected any ark_user
	// request to your own class eg my_user, which presumably would include and inherit from ark_user.
    // Eh? Why couldn't you just override it in the normal way by creating a my_user directory?
	$user = $ark_controller->get_controller( 'user' );
	
	// This is the thing that really executes the whole program.
	$ark_controller->call_from_external_query( $my_config['ark_query_unrouted'] );
	
	$ark_output = ob_get_contents();
ob_end_clean();

// ---------------------------------------------------------------
// Run All The Output Filters To Add CSS, Tidy HTML Etc.
// ---------------------------------------------------------------
// 
// There are some things that can't be done during normal output in a 
// view. For instance: html standards strongly encourage CSS to 
// be included in the head tag, but because of our lovely hierarchical 
// structure, our modules don't get the chance to add CSS until they're
// executed which happens after the outer page template has been run.
// So the modules add the css paths to a static variable in the core class 
// and then one of the output filters places the paths into the output 
// has been built up in the buffer. 

$ark_controller->get_controller('output_filters');
$ark_output = $ark_controller->output_filters->run_filters( $ark_output );

// Tadaaa! Nothing too fancy, not too many pointless classes, just print the output.
print $ark_output;
	

