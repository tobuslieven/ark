<?php

// This is the domain name that points to this server. We could have tried to guess this, 
// but it makes sense that you will be able to fill it in more reliably.
$my_config[ 'base_url' ] = 'http://localhost/';

// This is the path from your server root (pointed to by your domain name) to this tritas 
// installation.
// It's kept separate from the base url so that it's easy to subtract this from the beginning 
// of the url request before the url request is parsed to determine the controller, method and 
// arguments to use. 
// This is done because the installation root is just part of the path to your application,  
// not part of the request to pass to the application.
//$ark_config[ 'webroot_to_installation_root' ] = 'ark/';

$my_config[ 'theme_name' ] = 'theme_sleek';
