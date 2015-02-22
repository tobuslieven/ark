<?php

class My_Router
{
	public function resolve_query( $original_query )
	{
		// This switch acts as a router for our application's queries. The defaults mean controller 
		// and method pairs that aren't specifically re routed just get returned as they came in.
		
		$query_array = explode( '/', $original_query );
		
		$query_array_length = count( $query_array );

        $default_controller_name = 'pages';
        $default_method_name = 'welcome';
		
		if( $query_array_length > 0 && (strlen($query_array[0]) > 0) ) $controller_public_name = $query_array[0];
		else $controller_public_name = $default_controller_name;
		
		if( $query_array_length > 1 && (strlen($query_array[1]) > 0) ) $method_public_name = $query_array[1];
		else $method_public_name = $default_method_name;
		
		if( $query_array_length > 2 ) $arguments_array = array_slice( $query_array, 2 );
		else $arguments_array = array();
		
		switch( $controller_public_name )
		{
//			case 'welcome':
//				// The internal name of the controller is...
//				$controller_name = 'index';
//
//				switch( $method_public_name )
//				{
//					case 'hello':
//						// The internal name of the method is...
//						$method_name = "index";
//
//						$resolved_query = $controller_name . '/' . $method_name . '/' . implode('/', $arguments_array);
//
//						return $resolved_query;
//
//					default:
////						// By default just use the method name as it appeared in the query.
////						// If it doesn't exist the core load method will ensure they get a 404.
//                        $resolved_query = $controller_public_name . '/' . $method_public_name . '/' . implode('/', $arguments_array);
//
//						return $resolved_query;
//				}
//
//				break;
			
			default:
				// By default just use the controller and method names from the query, or
                // the defaults held in the same variables if no controller or method name
                // was specified.
				// In either case if the controller and method relating to the query don't
                // exist the core load method will ensure they get a 404.

                $resolved_query = $controller_public_name . '/' . $method_public_name . '/' . implode('/', $arguments_array);

                return $resolved_query;
		}
	}
}
