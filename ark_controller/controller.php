<?php

class Ark_Controller
{
	protected  $web_accessible_methods = array();
	public $controller_name = NULL;
	private static $static_router = NULL;
	private static $first_run = TRUE;
	
	// These are just stashed so we don't have to resolve them more than once.
    // The controllers can be loaded by any controller, so their class names are held in a shared,
    // static array whereas models and views are particular to each controller.
	private static $controller_classes = array();
    private $model_classes = array();
    private $view_paths = array();
	
	// The css and javascript paths are added by various modules during execution and then added into 
	// the head of the document by the output filter controller in index.php, just before the output
	// is sent out by apache. The output class accesses them directly as its derived from this class. Messages ditto.
	protected static $css_relative_paths = array();
	protected static $javascript_relative_paths = array();
	protected static $messages = array();
	
	// You don't need to pass in the router; that parameter is only used in index.php
	public function __construct( $router = NULL )
	{
		if( self::$first_run == TRUE )
		{
			// ------------------------------------------------------------------------------------------
			// Do stuff here that will only get run the first time an object of this class is instantiated.
			// ------------------------------------------------------------------------------------------
			
			// Error Handler
//			set_error_handler( array($this, '_error_handler') );
			
			// Router
			// The first time an object of this class is created (in index.php), the router
			// needs to be passed in. If that has happened, then assign it to the static 
			// member variable to be available for all later calls.
			if
			( 
				   $router != NULL
				&& self::$static_router == NULL
				&& get_class($router) == 'My_Router'
			)
			{
				self::$static_router = $router;
			}
			else
			{
				trigger_error
				(
					  "The first time Ark_Controller is instantiated it needs the router to be passed in. "
					. "This should be done by index.php, so if you see this error, something has gone quite wrong."
					, E_USER_ERROR
				);
			}
			
			self::$first_run = FALSE;
		}
		
		$this->controller_name = $this->_get_this_controller_name();

		$this->router = self::$static_router;
	}
	
	// This should just check that the method in the controller requested is at least 
	// web accessible (ie it has some entry in the permissions table), and if it does, then 
	// call it. It is the responsibility of the page generating method itself to determine 
	// its output based on the permissions of the user.
	public function call_from_external_query( $query )
	{
        global $my_config;

        // The unresolved path is passed to this function in index.php so we'll resolve it here.
		$resolved_query = $this->router->resolve_query( $query );
		$query_array = explode( '/', $resolved_query );
		$query_array_length = count( $query_array );
		
		// The router should have provided defaults by now, but as it's a user altered class, 
		// it might have been mucked up, so we'll check for defaults again here.
		
		// This will throw an error if no defaults have been provided by the router.
		if ( ($query_array_length > 0) && (strlen($query_array[0]) > 0) )
		{
			$controller_name = $query_array[0];
		}
		else
		{
			trigger_error
			(
				  "The router gave a query with no controller name. It should provide a default."
				. "The unrouted query was: \"{$my_config['ark_query_unrouted']}\""
				. "The routed query was: \"{$my_config['ark_query']}\""
				, E_USER_ERROR
			);
		}
		
		if ( $query_array_length > 1 ) 
		{
			$method_name = $query_array[1];
		}
		else 
		{
			trigger_error
			(
				  "The router gave a query with no method name. It should provide a default."
				. "The unrouted query was: \"{$my_config['ark_query_unrouted']}\""
				. "The routed query was: \"{$my_config['ark_query']}\""
				, E_USER_ERROR
			);
		}
		
		if ( $query_array_length > 2 ) 
		{
			$arguments_array = array_slice( $query_array, 2 );
		}
		else 
		{
			trigger_error
			(
				  "The router gave a query with no method arguments. It should provide an empty array as a default."
				. "The unrouted query was: \"{$my_config['ark_query_unrouted']}\""
				. "The routed query was: \"{$my_config['ark_query']}\""
				, E_USER_ERROR
			);
		}
		
		$this->get_controller( $controller_name );
		
		// If we get this far without an error, then the controller has been loaded.
		if ( method_exists( $this->{$controller_name}, $method_name ) )
		{
			if ( $this->{$controller_name}->_method_is_web_accessible( $method_name ) )
			{
                // This is the bit that actually does it!
				call_user_func_array( array( $this->{$controller_name}, $method_name ), $arguments_array );
			}
			else
			{
				trigger_error
				(
					  "The resolved query \"{$resolved_query}\" tried to call \"{$controller_name}->{$method_name}\". "
					. "But that method is not web accessible because it's not listed in \$web_accessible_methods"
					, E_USER_ERROR
				);
			}
		}
		else
		{
			trigger_error
			(
				  "The resolved query \"{$resolved_query}\" tried to call \"{$controller_name}->{$method_name}\". "
				. "But that method does not exist."
				, E_USER_ERROR
			);
		}
	}

    public function get_controller( $controller_name )
    {
        if( isset($this->{$controller_name}) )
        {
            // Already loaded into this controller so no need to do anything.
        }
        else
        {
            if( isset( self::$controller_classes[$controller_name] ) )
            {
                // Some other controller has loaded this before, so just instantiate a new one.
                $this->{$controller_name} = new self::$controller_classes[ $controller_name ];
            }
            else
            {
                // We'll have to load it for the first time.
                $absolute_paths = $this->_get_module_paths(TRUE, $controller_name);

                $ark_controller_path = $absolute_paths['ark'] . $controller_name . ".php";
                $my_controller_path = $absolute_paths['my'] . $controller_name . ".php";

                $my_controller_class_name = $this->_variable_case_to_class_case( "my_{$controller_name}" );
                $ark_controller_class_name = $this->_variable_case_to_class_case( "ark_{$controller_name}" );

                // First try to load the my_ version of the controller.
                if( $this->_folder_is_on($absolute_paths['my']) && file_exists($my_controller_path) )
                {
                    // Include the ark_ version if it exists and is on, so that the my_ version
                    // can inherit from it.
                    if( $this->_folder_is_on($absolute_paths['ark']) && file_exists($ark_controller_path) )
                    {
                        require_once $ark_controller_path;

                        if ( class_exists($ark_controller_class_name) )
                        {
                            // That's ok. The overriding my_ version can inherit from the ark_ class.
                        }
                        else
                        {
                            // The ark version is on and exists, indicating an attempt to override it,
                            //  but wasn't defined in the file where it was expected. This is an error.
                            trigger_error
                            (
                                "You asked for a controller named \"{$controller_name}\". "
                                . "The my_ version was found so we're loading that. The ark_ version "
                                . "is also on and the file was found indicating you wish to override it, "
                                . "but it wasn't defined in the file at \"{$ark_controller_path}\"."
                                . "It should be called \"{$ark_controller_class_name}\"."
                                , E_USER_ERROR
                            );
                        }
                    }

                    // Now get the the my_ version.
                    require_once $my_controller_path;

                    if ( class_exists($my_controller_class_name) )
                    {
                        $controller_class_name = $my_controller_class_name;
                    }
                    else
                    {
                        trigger_error
                        (
                            "You asked for a controller named \"{$controller_name}\". "
                            . "The my_ version of this controller is on and the file was found, "
                            . "but it wasn't defined in the file at \"{$my_controller_path}\". "
                            . "It should be called \"{$my_controller_class_name}\"."
                            , E_USER_ERROR
                        );
                    }
                }
                // Next try to load the ark_ version of the controller.
                else if( $this->_folder_is_on($absolute_paths['ark']) && file_exists($ark_controller_path) )
                {
                    // Instantiate the ark_ version.
                    require_once $ark_controller_path;

                    if ( class_exists($ark_controller_class_name) )
                    {
                        $controller_class_name = $ark_controller_class_name;
                    }
                    else
                    {
                        trigger_error
                        (
                            "You asked for a controller named \"{$controller_name}\". "
                            . "The ark_ version of this controller is on and the file was found, "
                            . "but it wasn't defined in the file at \"{$ark_controller_path}\"."
                            . "It should be called \"{$ark_controller_class_name}\"."
                            , E_USER_ERROR
                        );
                    }
                }
                else
                {
                    trigger_error
                    (
                        "You asked for a controller named \"{$controller_name}\". "
                        . "It was looked for at \"{$my_controller_path}\", and at  \"{$ark_controller_path}\", "
                        . "but the folder was off or the file couldn't be found."
                        , E_USER_ERROR
                    );
                }

                // If we got here without any errors, then we can instantiate the class safely.
                self::$controller_classes[ $controller_name ] = $controller_class_name;

                $loaded_instance = new $controller_class_name;

                $this->{$controller_name} = $loaded_instance;

                return $loaded_instance;
            }
        }
    }
	
	public function get_view( $view_name, $template_arguments = array(), $return_string_dont_print = FALSE )
	{
        // Check if the path to this view has already been resolved.
		if( isset( $this->view_paths[$view_name] ) )
		{
			$path_to_view_file = $this->view_paths[$view_name];
		}
		// If we didn't find a stashed path to the requested view, then find it the old fashioned way.
		else
		{
            // We'll have to load it for the first time.
            $absolute_paths = $this->_get_module_paths();

            $ark_view_path = $absolute_paths['ark'] .  "views/" . $view_name . ".php";
            $my_view_path = $absolute_paths['my'] .  "views/" . $view_name . ".php";
            $theme_view_path = $absolute_paths['theme'] .  "views/" . $view_name . ".php";

            if( $this->_folder_is_on($absolute_paths['theme']) && file_exists($theme_view_path) )
            {
                $path_to_view_file = $theme_view_path;
            }
            else if( $this->_folder_is_on($absolute_paths['my']) && file_exists($my_view_path) )
            {
                $path_to_view_file = $my_view_path;
            }
            else if( $this->_folder_is_on($absolute_paths['ark']) && file_exists($ark_view_path) )
            {
                $path_to_view_file = $ark_view_path;
            }
            else
            {
                // Couldn't find the view anywhere, so trigger an error.
                trigger_error
                (
                    "The file for the view named {$view_name} could not be found its module is off. "
                    . "It was looked for in the following places: \n"
                    . $theme_view_path . "\n"
                    . $my_view_path . "\n"
                    . $ark_view_path . "\n"
                    , E_USER_ERROR
                );
            }
        }

        // There's no error if this file isn't present as it's optional.
        $view_functions_path = rtrim($path_to_view_file, '.php') . '_functions.php';
        @include_once($view_functions_path);

        // Now we've got the path to the correct view file, we can stash it so that a subsequent request for
        // the same view will be able to find it more quickly.
        $this->view_paths[$view_name] = $path_to_view_file;

        if( $return_string_dont_print == TRUE )
        {
            return $this->_parse_template( $path_to_view_file, $template_arguments, $return_string_dont_print );
        }
        else
        {
            $this->_parse_template( $path_to_view_file, $template_arguments, $return_string_dont_print );
            return;
        }
	}

    public function get_model( $model_name )
    {
        // Check if the path to this model has already been resolved.
        if( isset($this->{$model_name}) )
        {
            // Already loaded into this controller so no need to do anything.
        }
        else
        {
            if( array_key_exists($model_name, self::$model_classes) )
            {
                // Some other controller has loaded this before, so just instantiate a new one.
                $this->{$model_name} = new self::$model_classes[ $model_name ]($this);
            }
            else
            {
                // We'll have to load it for the first time.
                $absolute_paths = $this->_get_module_paths();

                $ark_model_path = $absolute_paths['ark'] .  "/models/" . $model_name . ".php";
                $my_model_path = $absolute_paths['my'] .  "/models/" . $model_name . ".php";

                $my_model_name = "my_{$model_name}";
                $ark_model_name = "ark_{$model_name}";

                $my_model_class_name = $this->_variable_case_to_class_case( $my_model_name );
                $ark_model_class_name = $this->_variable_case_to_class_case( $ark_model_name );

                // First try to load the my_ version of the model.
                if( $this->_folder_is_on($absolute_paths['my']) && file_exists($my_model_path) )
                {
                    // Include the ark_ version if it exists and is on, so that the my_ version
                    // can inherit from it.
                    if( $this->_folder_is_on($absolute_paths['ark']) && file_exists($ark_model_path) )
                    {
                        require_once $ark_model_path;

                        if ( class_exists($ark_model_class_name) )
                        {
                            // That's ok. The overriding my_ version can inherit from the ark_ class.
                        }
                        else
                        {
                            // The ark version is on and exists, indicating an attempt to override it,
                            //  but wasn't defined in the file where it was expected. This is an error.
                            trigger_error
                            (
                                "You asked for a model named \"{$model_name}\". "
                                . "The my_ version was found so we're loading that. The _ark version "
                                . "is also on and the file was found indicating you wish to override it, "
                                . "but it wasn't defined in the file at \"{$ark_model_path}\". "
                                . "It should be called \"{$ark_model_name}\"."
                                , E_USER_ERROR
                            );
                        }
                    }

                    // Now instantiate the my_ version.
                    require_once $my_model_path;

                    if ( class_exists($my_model_class_name) )
                    {
                        $model_class_name = $my_model_class_name;
                    }
                    else
                    {
                        trigger_error
                        (
                            "You asked for a model named \"{model_name}\". "
                            . "The my_ version of this model is on and the file was found, "
                            . "but it wasn't defined in the file at \"{$my_model_path}\". "
                            . "It should be called \"{$my_model_name}\"."
                            , E_USER_ERROR
                        );
                    }
                }
                // Next try to load the ark_ version of the model.
                else if( $this->_folder_is_on($absolute_paths['ark']) && file_exists($ark_model_path) )
                {
                    // Instantiate the ark_ version.
                    require_once $ark_model_path;

                    if ( class_exists($ark_model_class_name) )
                    {
                        $model_class_name = $ark_model_class_name;
                    }
                    else
                    {
                        trigger_error
                        (
                            "You asked for a model named \"{$model_name}\". "
                            . "The ark_ version of this model is on and the file was found, "
                            . "but it wasn't defined in the file at \"{$ark_model_path}\". "
                            . "It should be called \"{$ark_model_name}\"."
                            , E_USER_ERROR
                        );
                    }
                }
                else
                {
                    trigger_error
                    (
                        "You asked for a model named \"{$model_name}\". "
                        . "It was looked for at \"{$my_model_path}\", and at  \"{$ark_model_path}\", "
                        . "but the folder was off or the file couldn't be found."
                        , E_USER_ERROR
                    );
                }

                // If we got here without any errors, then we can instantiate the class safely.
                $this->model_classes[ $model_name ] = $model_class_name;

                $loaded_instance = new $model_class_name($this);

                $this->{$model_name} = $loaded_instance;

                return $loaded_instance;
            }
        }
    }

    public function get_css( $file_name )
    {
        // Check if the path to this css has already been resolved.
        $controller_css_name = $this->controller_name . '_' . $file_name;

        if( isset( self::$css_relative_paths[$controller_css_name] ) )
        {
            // As its been loaded on this page already, we shouldn't load it again.
        }
        else
        {
            $absolute_paths = $this->_get_module_paths();
            $relative_paths = $this->_get_module_paths(FALSE);

            $ark_css_path = $absolute_paths['ark'] . 'views/css/' . $file_name;
            $my_css_path = $absolute_paths['my'] . 'views/css/' . $file_name;
            $theme_css_path = $absolute_paths['theme'] . 'views/css/' . $file_name;

            if( $this->_folder_is_on($absolute_paths['theme']) && file_exists($theme_css_path) )
            {
                $relative_path_to_css_file = $relative_paths['theme'] . 'views/css/' . $file_name;
            }
            else if( $this->_folder_is_on($absolute_paths['my']) && file_exists($my_css_path) )
            {
                $relative_path_to_css_file = $relative_paths['my'] . 'views/css/' . $file_name;
            }
            else if( $this->_folder_is_on($absolute_paths['ark']) && file_exists($ark_css_path) )
            {
                $relative_path_to_css_file = $relative_paths['ark'] . 'views/css/' . $file_name;
            }
            else
            {
                // Couldn't find the css anywhere, so trigger an error.
                trigger_error
                (
                    "The css file named {$file_name} could not be found or its folder was off."
                    . "It was looked for in the following places: \n"
                    . $theme_css_path . "\n"
                    . $my_css_path . "\n"
                    . $ark_css_path . "\n"
                    , E_USER_ERROR
                );
            }

            // Save path for access by the output filter and so we know not to load it again.
            self::$css_relative_paths[$controller_css_name] = $relative_path_to_css_file;
        }
    }

    // Any script that wants to allow other scripts to depend on it must specify a unique $simple_name here
    // Then any javascript that wishes to depend on it and so must be added to the page after it, can simply
    // specify that javascript's simple_name in its $dependencies array. The javascript output filter will
    // then sort the array of javascript paths it gets, to ensure proper ordering of dependencies.
    public function get_javascript( $file_name, $dependencies = array(), $simple_name = NULL )
    {
        // Check if the path to this javascript has already been resolved.
        $controller_javascript_name = $this->controller_name . '_' . $file_name;

        if( isset( self::$javascript_relative_paths[$controller_javascript_name] ) )
        {
            // As its been loaded on this page already, we shouldn't load it again.
        }
        else
        {
            $absolute_paths = $this->_get_module_paths();
            $relative_paths = $this->_get_module_paths(FALSE);

            $ark_js_path = $absolute_paths['ark'] . 'views/javascript/' . $file_name;
            $my_js_path = $absolute_paths['my'] . 'views/javascript/' . $file_name;
            $theme_js_path = $absolute_paths['theme'] . 'views/javascript/' . $file_name;

            if( $this->_folder_is_on($absolute_paths['theme']) && file_exists($theme_js_path) )
            {
                $relative_path_to_javascript_file = $relative_paths['theme'] . 'views/javascript/' . $file_name;
            }
            else if( $this->_folder_is_on($absolute_paths['my']) && file_exists($my_js_path) )
            {
                $relative_path_to_javascript_file = $relative_paths['my'] . 'views/javascript/' . $file_name;
            }
            else if( $this->_folder_is_on($absolute_paths['ark']) && file_exists($ark_js_path) )
            {
                $relative_path_to_javascript_file = $relative_paths['ark'] . 'views/javascript/' . $file_name;
            }
            else
            {
                // Couldn't find the view anywhere, so trigger an error.
                trigger_error
                (
                    "The javascript file named {$file_name} could not be found or its folder was off."
                    . "It was looked for in the following places: \n"
                    . $theme_js_path . "\n"
                    . $my_js_path . "\n"
                    . $ark_js_path . "\n"
                    , E_USER_ERROR
                );
            }

            // If the dependencies parameter is the special case 'none', then set it to an empty array.
            // Else ensure that it is an array and add the defaults if they're not already in there.
            if( $dependencies === 'none' )
            {
                $dependencies = array();
            }
            else
            {
                $this->check_argument($dependencies, 'dependencies', 'array');

                if( ! in_array('jquery', $dependencies) )
                    array_push( $dependencies, 'jquery' );

                if( ! in_array('ark_utility', $dependencies) && $simple_name != 'ark_utility' )
                    array_push( $dependencies, 'ark_utility' );
            }

            // Save path for access by the output filter and so we know not to load it again.
            self::$javascript_relative_paths[$controller_javascript_name] = array(
                'path' => $relative_path_to_javascript_file,
                'dependencies' => $dependencies,
                'simple_name' => $simple_name
            );
        }
    }

    // This takes an image path under the views/images/ folder in a module. By default it
    // assumes the appropriate module is the one for $this controller, but a different module
    // name can be passed in.
    public function get_image_path( $image_path_in_images_folder, $module_name = NULL )
    {
        if( $module_name == NULL )
            $module_name = $this->_get_this_controller_name();

        // Choose the image from the theme_, my_, or ark_ folder according to
        // the usual overrides.
        $absolute_paths = $this->_get_module_paths( TRUE, $module_name );
        $relative_paths = $this->_get_module_paths( FALSE, $module_name );

        $ark_image_path = $absolute_paths['ark'] . 'views/images/' . $image_path_in_images_folder;
        $my_image_path = $absolute_paths['my'] . 'views/images/' . $image_path_in_images_folder;
        $theme_image_path = $absolute_paths['theme'] . 'views/images/' . $image_path_in_images_folder;

        if( $this->_folder_is_on($absolute_paths['theme']) && file_exists($theme_image_path) )
        {
            $image_path = $relative_paths['theme'] . 'views/images/' . $image_path_in_images_folder;
        }
        else if( $this->_folder_is_on($absolute_paths['my']) && file_exists($my_image_path) )
        {
            $image_path = $relative_paths['my'] . 'views/images/' . $image_path_in_images_folder;
        }
        else if( $this->_folder_is_on($absolute_paths['ark']) && file_exists($ark_image_path) )
        {
            $image_path = $relative_paths['ark'] . 'views/images/' . $image_path_in_images_folder;
        }
        else
        {
            // If no image was found, trigger an error.
            trigger_error
            (
                "You asked for an image called \"{$image_path_in_images_folder}\". "
                . "It was looked for at \"{$theme_image_path}\", \"{$my_image_path}\" "
                . "and at \"{$ark_image_path}\", "
                . "but the folders were off or the file couldn't be found."
                , E_USER_ERROR
            );
        }

        return $image_path;
    }

    // Don't need to check or apply default as that can be done in
    // caller's function definition parameter list.
    // Checks the argument type.
    public function check_argument($argument, $argument_name, $required_type)
    {
        $argument_type = gettype($argument);
        $ok = TRUE;

        if( $argument_type == 'object' )
        {
            if( ! isa($argument, $required_type) )
                $ok = FALSE;
        }
        elseif( $argument_type != $required_type )
        {
            $ok = FALSE;
        }

        if( ! $ok )
        {
            trigger_error(
                "Caller expected \${$argument_name} to be of type {$required_type}, but a "
                . $argument_type . " was given instead.",
                E_USER_ERROR
            );
        }
    }

    // Checks an array key's type and applies a default if specified and appropriate.
    public function check_array(&$array, $array_name, $key, $type, $default = NULL, $allow_null = FALSE)
    {
        // First check the arguments to this function.
        // If $allow_null is TRUE, then the $default must be NULL.
        if( $allow_null === TRUE )
        {
            if( $default !== NULL )
            {
                trigger_error(
                    'check_array() expects the $default to be NULL if $allow_null is TRUE',
                    E_USER_ERROR
                );
            }
        }

        $key_exists = array_key_exists($key, $array);
        $ok = TRUE;

        // If it's set, but it's the wrong type, then that's an error
        if( $key_exists )
        {
            $type_at_key = gettype($array[$key]);
            if( $type_at_key == 'object' )
            {
                if( ! is_a($array[$key], $type) )
                    $ok = FALSE;
            }
            else
            {
                if
                (
                    $type_at_key != $type
                    && ! ( $type_at_key == 'NULL' && $allow_null )
                )
                {
                    $ok = FALSE;
                }
            }

            if( ! $ok )
            {
                trigger_error(
                    "Caller expected a {$type} at \${$array_name}[{$key}], but a "
                    . $type_at_key . " was found instead.",
                    E_USER_ERROR
                );
            }
        }
        // If it's not set and there's a default, apply the default.
        // If it's not set and there's NO default, that's an error.
        else
        {
            if( $default !== NULL || $allow_null === TRUE )
            {
                $array[$key] = $default;
            }
            else
            {
                trigger_error(
                    "Caller expected a {$type} at \${$array_name}[{$key}] "
                    . "but NULL was found at that key.",
                    E_USER_ERROR
                );
            }
        }
    }

    // The wipe variable is used to set the variable to NULL as a NULL $value usually just
    // indicates that the user wants to get the value, rather than set it.
    private function one_shot_global( $variable_name, $value = NULL, $wipe = FALSE )
    {
        $main_key = 'ark_one_shot_globals';
        $module_name = $this->controller_name;

        // Set the module's array in the one_shot_globals key if it hasn't already been set.
        if( ! isset($_SESSION[$main_key][$module_name]) ) $_SESSION[$main_key][$module_name] = array();

        // If the specific $variable_name key doesn't exist, set it to NULL.
        if( ! isset($_SESSION[$main_key][$module_name][$variable_name]) )
            $_SESSION[$main_key][$module_name][$variable_name] = NULL;

        // If a value was provided, set it.
        // Else set the key to the requested value.
        if( $value )
            $_SESSION[$main_key][$module_name][$variable_name] = $value;

        // If $wipe was requested, set it to NULL.
        if( $wipe == TRUE )
            $_SESSION[$main_key][$module_name][$variable_name] = NULL;

        return $_SESSION[$main_key][$module_name][$variable_name];
    }

    public function get_one_shot_global( $variable_name )
    {

    }
	
	protected function _get_this_controller_name()
	{
		// Lowercase.
		$name = strtolower( get_class($this) );
		
		// Strip leading ark_ if it's there.
		$len = strlen($name);
		$name = ltrim($name, 'ark_');
		
		// If no leading ark_ was found, try removing a leading 'my_'.
		$newlen = strlen($name);
		if( $len == $newlen )
		{
			$name = ltrim($name, 'my_');
		}
		
		return $name;
	}
	
	// This will take the path to a module and open the info.txt file inside that module.
	// If the module is determined to be on, it will return TRUE, otherwise, FALSE.
	protected function _folder_is_on( $path_to_folder )
	{
		global $my_config;
		
		$path_to_folder = dirname($path_to_folder) . '/';
		 
		$path_to_info_file = $path_to_folder . 'off';

		if( file_exists($path_to_info_file) )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	private function _variable_case_to_class_case( $controller_name )
	{
		$white_spaced = str_replace( '_', ' ', $controller_name );
		$upper_cased = ucwords( $white_spaced );
		$class_name = str_replace( ' ', '_', $upper_cased );
		
		return $class_name;
	}

	// This just checks the method has been listed as accessible, it is the job of the named 
	// method itself to perform any checks on the user's permissions that are needed when it 
	// is called.
	private function _method_is_web_accessible( $method_name )
	{
		return in_array( $method_name, $this->web_accessible_methods );
	}

    // The parameters have silly names to protect them from name clashes with the variables that
    // this templating function creates in order to populate the template that it evaluates.
    private function _parse_template
    (
        $ark___path_to_template_file,
        $ark___template_arguments = array(),
        $ark___return_string_dont_print = FALSE
    )
    {
        // First check that the $template_arguments argument is an array and provide a useful
        // error message if it is not.

        if( ! is_array($ark___template_arguments) ) {
            trigger_error
            (
                "The \$template_arguments argument must be an array. "
                . "You passed in an argument of type: "
                . gettype($ark___template_arguments)
                , E_USER_ERROR
            );
        }

        // If the file exists, parse it, else throw an error.
        if( file_exists($ark___path_to_template_file) )
        {
            // Ok to proceed.
        }
        else
        {
            trigger_error
            (
                "Tried to parse a template at path \"{$ark___path_to_template_file}\". "
                . "But the file does not exist."
                , E_USER_ERROR
            );
        }

        // Convert the template argument key value pairs to variables where
        // the name == key and value == value.
        // Name the loop variables something silly to avoid writing over any of
        // the variables that you're creating.
        foreach( $ark___template_arguments as $ark___key => $ark___value )
        {
            $$ark___key = $ark___value;
        }

        ob_start();

        include($ark___path_to_template_file);

        // Either get the output and return as a string without printing, or just print the output.
        if( $ark___return_string_dont_print == TRUE )
        {
            $ark___contents = ob_get_contents();
            ob_end_clean();
            return $ark___contents;
        }
        else
        {
            ob_end_flush();
        }
    }
	
	// These two are used by the output filter that inserts the css and javascript into the page.
	protected function _get_relative_css_paths()
	{
		return self::$css_relative_paths;
	}

	protected function _get_relative_javascript_paths()
	{
		return self::$javascript_relative_paths;
	}

	protected function _get_messages()
	{
		return self::$messages;
	}

    protected function _get_module_paths( $absolute = TRUE, $controller_name = NULL )
    {
        global $my_config;

        if( $controller_name == NULL)
        {
            // Default is to get the paths for this controller's module.
            $controller_name = $this->controller_name;
        }

        $theme_name = $my_config[ 'theme_name' ];
        $root = $my_config[ 'installation_root' ];

        $ark_controller_name = "ark_{$controller_name}";
        $my_controller_name = "my_{$controller_name}";
        $theme_controller_name = "{$theme_name}_{$controller_name}";

        $paths = array();
        if( $absolute )
        {
            $paths['ark'] = $root . "{$ark_controller_name}/";
            $paths['my'] = $root . "{$my_controller_name}/";
            $paths['theme'] = $root . "{$theme_controller_name}/";
        }
        else
        {
            $paths['ark'] = "{$ark_controller_name}/";
            $paths['my'] = "{$my_controller_name}/";
            $paths['theme'] = "{$theme_controller_name}/";
        }

        return $paths;
    }

    // This searches up the call stack looking for objects that inherit from Ark_Controller and
    // returns the second one that it finds (the first one being the 'this' object).
    // It can be useful for theme methods to know which module they are producing output for so
    // they can find the appropriate resource folder. The canonical example is the img_view method
    // in the html controller which is used to display an image in the caller module's views/images/ folder.
    protected function _get_previous_controller()
    {
        // Get the trace.
        $trace = debug_backtrace();

        // The first object in the trace will be $this controller object, this loop
        // will keep searching up the stack until it finds a controller object that
        // is not equal to $this.
        // object will be the module controller that called the 'this' object.
        $controller_count = 0;
        $controller = NULL;
        foreach($trace as $value)
        {
            isset($value['object']) && is_a($value['object'], 'Ark_Controller') ? $controller_count++ : NULL ;

//            if( isset($value['object'])) echo $value['object']->_get_this_controller_name();

            if( isset($value['object']) && $value['object'] != $this )
            {
                $controller = $value['object'];
                break;
            }
        }

        return $controller;
    }
}
