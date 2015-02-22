<?php
/**
 * Created by PhpStorm.
 * User: tobylieven
 * Date: 20/07/2014
 * Time: 15:00
 */


// This used to be a part of the ark_controller Class.
// It has been largely replaced by PHPStorm's debugging tools and Xdebug's
// improved error reporting.

// This is the custom error handler that is set by the __construct() function of this class when it is
// run for the first time.
// int $errno The error level E_STRICT etc.
// string $errstr Contains the error message.
// string $errfile Optional. The error location of the file where the error was triggered.
// int $errline Optional. The line in the file where the error was triggered.
// array $errcontext Optional. The symbol table containing all the variables in the scope where the error occured.
public function _error_handler($errno, $errstr, $errfile, $errline, $errcontext = NULL)
{
    // Don't do anything if error reporting is turned off. This is how the '@' error supression operator works,
    // ie it still generates an error, so this error handler function will be called, but it temporarily sets
    // error reporting to 0. It's up to us in this function to make sure that we respect that.
    if ( 0 == error_reporting () ) {
        // Error reporting is currently turned off or suppressed with @
        return;
    }

    // This next section is very useful for less fancy error reporting when the fancy stuff
    // breaks for some reason. Just uncomment it and it'll print out the error and backtrace
    // fairly nicely without trying to be too clever and inserting the error as a message into
    // the page with an output filter.
    /*		ob_end_clean();
            print '$errno: ' . $errno . "\n";
            print '$errstr: ' . $errstr . "\n";
            print '$errfile: ' . $errfile . "\n";
            print '$errline: ' . $errline . "\n\n";

    //		print "debug_print_backtrace(): \n";
    //		print_r( debug_backtrace() );
            $backtrace_array = debug_backtrace();
            foreach( $backtrace_array as $function )
            {
                if( array_key_exists('file', $function) )     {print 'file: '; print_r( $function['file'] ); print "\n";}
                if( array_key_exists('line', $function) )     {print 'line: '; print_r( $function['line'] ); print "\n";}
                if( array_key_exists('function', $function) ) {print 'function: '; print_r( $function['function'] ); print "\n";}
                if( array_key_exists('class', $function) )    {print 'class: '; print_r( $function['class'] ); print "\n";}
    //			if( array_key_exists('args', $function) )     {print 'args: '; print_r( $function['args'] ); print "\n";}
                if( array_key_exists('args', $function) )     {print 'args: '; print_r( $this->_get_function_arguments_string($function) ); print "\n";}
                print "\n";
            }
            print "\n";
            die();
    */

    // ------------------------
    // Clearing Buffered Output
    // ------------------------
    // To really clear any output that has been buffered so far, you need to be sure to call
    // the function ob_end_clean() once for every unmatched call to ob_start(). These unmatched
    // calls to ob_start() will happen most frequently in tritas if there is an error in a view
    // file. That's because the get_view() method calls ob_start() to begin buffering which will
    // not be matched if the function does not return in the normal way ie if an error is thrown
    // in the view file. This can build up because the whole point of HMVC is that calls to get_view()
    // can be nested.
    // The function ob_get_level() is the key to ensuring that these calls are matched.
    for( $output_buffering_level = ob_get_level(); $output_buffering_level > 1; $output_buffering_level-- )
    {
        ob_end_clean();
    }

    // --------------------------------------------------------
    // Corralling The Error Message And Function Call Backtrace
    // --------------------------------------------------------
    // Get the bactrace array.
    $backtrace_array = debug_backtrace();

    // Remove the error handler function call from the backtrace to remove a
    // potential source of confusion.
    array_shift($backtrace_array);

    // If the next function call is trigger_error(), then also remove that because
    // I think that is just noise really as it's just the programmers equivalent of
    // php's way of triggering an error.
    if( $backtrace_array[0]['function'] == 'trigger_error' )
    {
        array_shift($backtrace_array);
    }

    // Only try to get details from the backtrace array for the main error message if
    // the backtrace array still has at least 1 function call in it.
    if( count($backtrace_array) != 0 )
    {
        $error_function_name = $this->_get_function_name_string($backtrace_array[0]);
        $error_function_arguments = $this->_get_function_arguments_string($backtrace_array[0]);
    }
    else
    {
        $error_function_name = '';
        $error_function_arguments = '';
    }
    $error_level_string = $this->_get_error_level_string($errno);

    // Put the error data into an array.
    // The error level, the error message, the file the error happened in,
    // the function the error happened in, the line the error happened on,
    // the arguments to the function the error happened in.
    $error_details_array = array
    (
        'error_level' => $error_level_string,
        'error_message_string' => $errstr,
        'error_file' => $errfile,
        'error_function_name' => $error_function_name,
        'error_line' => $errline,
        'error_arguments' => $error_function_arguments,
    );

    // Put all the backtrace data into nested arrays.
    $backtrace_details_array = array();
    foreach( $backtrace_array as $function_call )
    {
        $function_call_name = $this->_get_function_name_string($function_call);
        $function_call_arguments = $this->_get_function_arguments_string($function_call);

        // The file the function was called in, the name of the function that was called,
        // the arguments to the function.
        $my_function_call = $this->_get_function_details_array($function_call);

        $backtrace_details_array[] = $my_function_call;
    }

//		ob_end_clean();

    //t_print($error_details_array, 'error details: ');
    //t_print($backtrace_details_array, 'backtrace: ');
    //die();
    /*
            // Load the error view into a string.
            $view_variables = array
            (
                'controller' => $this,
                'error_details' => $error_details_array,
                'backtrace_details' => $backtrace_details_array
            );

            // ---------------------------------------------------
            // Outputting The Error Message And Any Other Messages
            // ---------------------------------------------------
            // This is a much simpler and more robust approach than was used before. Previously, the
            // errors were all inserted as messages and the page was allowed to continue execution
            // as well as it could. Often this was not so well and the error messages that resulted
            // were a little confusing. Instead the new system just prints out any messages
            // that have been set using $controller->add_message('blah') using the normal and
            // fairly simple (and therefore error resistant) insert_messages output filter. And
            // then uses the error view to display the errors that have been collected and then dies!
            $this->get_controller('output_filters_controller');
            print $this->output_filters_controller->insert_messages('<:messages:>');

            $this->get_view('php_error_view', $view_variables);
            die();
    */
}

// ------------------------------------------------------
// Utility functions to get details about function calls.
// ------------------------------------------------------
//
// These functions take an array of information about a function call as provided by
// the php function debug_backtrace().
//
private function _get_function_details_array( $function_call )
{
    $function_call_name = $this->_get_function_name_string($function_call);
    $function_call_arguments = $this->_get_function_arguments_string($function_call);

    // The file the function was called in, the name of the function that was called,
    // the arguments to the function.
    $my_function_call = array
    (
        'function_call_file' => array_key_exists('file', $function_call) ? $function_call['file'] : '',
        'function_call_name' => $function_call_name,
        'function_call_line' => array_key_exists('line', $function_call) ? $function_call['line'] : '',
        'function_call_arguments' => $function_call_arguments,
    );

    return $my_function_call;
}

// Used by the custom error handler to get the full function name of each
// function call in the call stack.
private function _get_function_name_string( $function_call )
{
    if( array_key_exists('type', $function_call) )
    {
        switch( $function_call['type'] )
        {
            case '->':
                $function_identifier = $function_call['class'] . '->' . $function_call['function'] . '()';
                break;

            case '::':
                $function_identifier = $function_call['class'] . '::' . $function_call['function'] . '()';
                break;

            default:
                $function_identifier = $function_call['function'] . '()';
                break;
        }
    }
    else
    {
        // Just go for the default, even though it's repeating the default from the above switch statement.
        $function_identifier = $function_call['function'] . '()';
    }

    return $function_identifier;
}

// Used by the custom error handler to get the arguments for each function in
// the call stack.
private function _get_function_arguments_string( $function_call )
{
    $arg_array = array();
    if( is_array($function_call) && array_key_exists('args', $function_call) )
    {
        foreach( $function_call['args'] as $arg )
        {
            if( is_array($arg) )
            {
                $arg_string = 'array';
            }
            elseif( is_object($arg) )
            {
                $arg_string = 'object';
            }
            elseif( is_string($arg) )
            {
                if( strlen($arg) <= 400 )
                {
                    $arg_string = $arg;
                }
                else
                {
                    $arg_string = substr($arg, 0, 400) . ' truncated';
                }
            }
            elseif( is_null($arg) )
            {
                $arg_string = 'NULL';
            }
            elseif( is_bool($arg) )
            {
                if( $arg == TRUE )
                {
                    $arg_string = 'TRUE';
                }
                else
                {
                    $arg_string = 'FALSE';
                }
            }
            else
            {
                $arg_string = print_r($arg, TRUE);
            }

            $arg_array[] = $arg_string;
        }
    }

    return implode(', ', $arg_array);
}

private function _get_error_level_string( $error_constant )
{
    switch( $error_constant )
    {
        // Some error types can't be caught by a custom error handling function. So they
        // won't turn up in this function in it's original use case in the customer error
        // handler defined above. From the PHP documentation:
        // The following error types cannot be handled with a user defined error handler:
        // E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING,
        // and most of E_STRICT raised in the file where set_error_handler() is called.
        case E_ERROR: $error_level_string = 'E_ERROR'; break;
        case E_WARNING: $error_level_string = 'E_WARNING'; break;
        case E_PARSE: $error_level_string = 'E_PARSE'; break;
        case E_NOTICE: $error_level_string = 'E_NOTICE'; break;
        case E_CORE_ERROR: $error_level_string = 'E_CORE_ERROR'; break;
        case E_CORE_WARNING: $error_level_string = 'E_CORE_WARNING'; break;
        case E_COMPILE_ERROR: $error_level_string = 'E_COMPILE_ERROR'; break;
        case E_COMPILE_WARNING: $error_level_string = 'E_COMPILE_WARNING'; break;
        case E_USER_ERROR: $error_level_string = 'E_USER_ERROR'; break;
        case E_USER_WARNING: $error_level_string = 'E_USER_WARNING'; break;
        case E_USER_NOTICE: $error_level_string = 'E_USER_NOTICE'; break;
        case E_STRICT: $error_level_string = 'E_STRICT'; break;
        case E_RECOVERABLE_ERROR: $error_level_string = 'E_RECOVERABLE_ERROR'; break;
        case E_DEPRECATED: $error_level_string = 'E_DEPRECATED'; break;
        case E_USER_DEPRECATED: $error_level_string = 'E_USER_DEPRECATED'; break;
        case E_ALL: $error_level_string = 'E_ALL'; break;
        default: $error_level_string = 'UNKOWN_ERROR_TYPE'; break;
    }
    return $error_level_string;
}