<?php

// Don't be too proud to steal when you need to. 

class Ark_Output_Filters extends Ark_Controller
{
	protected static $filters_start_group  = array();
	protected static $filters_second_group = array();
	protected static $filters_middle_group = array();
	protected static $filters_third_group = array();
	protected static $filters_end_group  = array();
	
	public function __construct()
	{
		parent::__construct();
		
		// Place the messages filter into the css group, but place it in there first, so 
		// that it will run before the css and javascript filters are run, ie giving the 
		// messages filter/view the chance to add its own css and javascript if it wants to,
		// without clogging up another one of the available groups.
		$this->add_filter('insert_messages', 'second_group');
		$this->add_filter('insert_css_and_javascript', 'second_group');
//		$this->add_filter('html_tidy', 'third_group');
	}
	
	public function add_filter( $method_name, $group = 'middle_group' )
	{
		$filter_exists = method_exists( $this, $method_name );
		
		if( ! $filter_exists )
		{
			trigger_error
			(
				  "No output filter named \"{$method_name}\" is available. " 
				. "Try defining a method with that name in the Output_Filters class or "
				. "fixing a typo in the filter name you're requesting."
				, E_USER_ERROR
			);
			return FALSE;
		}
		
		switch($group)
		{
			case 'start_group':
				self::$filters_start_group[] = $method_name;
				break;
				
			case 'second_group':
				self::$filters_second_group[] = $method_name;
				break;
				
			case 'middle_group':
				self::$filters_middle_group[] = $method_name;
				break;
				
			case 'third_group':
				self::$filters_third_group[] = $method_name;
				break;
				
			case 'end_group':
				self::$filters_end_group[] = $method_name;
				break;
				
			default:
				trigger_error
				(
					  "No ouput filter group called \"{$group}\" exists." 
					. 'You can add your output filter to the "start_group", "second_group", "middle_group", '
					. '"third_group", "end_group". The default is "middle_group".'
					, E_USER_ERROR
				);
				return FALSE;
				break;
		}
		
		return TRUE;
	}
	
	public function remove_filter( $method_name, $group = 'all' )
	{
		if( ! in_array( $group, array('all', 'start_group', 'second_group', 'middle_group', 'third_group', 'end_group') ) )
		{
			trigger_error
			(
				  "No ouput filter group called \"{$group}\" exists." 
				. 'You can add your output filter to the "start_group", "second_group", "middle_group", "third_group", "end_group" or the default "all". '
				, E_USER_ERROR
			);
		}
		
		$filter_exists = method_exists( $this, $method_name );
		
		if( ! $filter_exists )
		{
			trigger_error
			(
				  "No output filter named \"{$method_name}\" is available." 
				. "Try defining a method with that name in the Output_Filters class or "
				. "fixing a typo in the filter you're requesting."
				, E_USER_ERROR
			);
			return FALSE;
		}
		
		// Remove the named filter from the relevant filters groups.
		if( $group == 'all' || $group == 'start_group'  ) $this->_remove_value_from_array( $method_name, self::$filters_start_group );
		if( $group == 'all' || $group == 'second_group'  ) $this->_remove_value_from_array( $method_name, self::$filters_second_group );
		if( $group == 'all' || $group == 'middle_group' ) $this->_remove_value_from_array( $method_name, self::$filters_middle_group );
		if( $group == 'all' || $group == 'third_group'  ) $this->_remove_value_from_array( $method_name, self::$filters_third_group );
		if( $group == 'all' || $group == 'end_group'  ) $this->_remove_value_from_array( $method_name, self::$filters_end_group );
	}
	
	private function _remove_value_from_array( $value, &$array )
	{
		$index = array_search($value, $array);
		if( $index != FALSE )
		{
			unset($array[$index]);
			$array = array_values($array);
			return TRUE;
		}
		return FALSE;
	}
	
	public function run_filters( &$string )
	{
		$all_filters = array_merge
		(
			self::$filters_start_group,
			self::$filters_second_group,
			self::$filters_middle_group,
			self::$filters_third_group,
			self::$filters_end_group
		);
		
		foreach( $all_filters as $method_name )
		{
			$filter_exists = method_exists( $this, $method_name );
			
			if( ! $filter_exists )
			{
				trigger_error
				(
					  "No output filter named \"{$method_name}\" is available." 
					. "Try defining a method with that name in the Output_Filters class or "
					. "fixing a typo in the filter name you're requesting."
					, E_USER_ERROR
				);
			}
			
			$string = $this->{$method_name}($string);
		}
		
		return $string;
	}
	
	public function insert_messages( $page )
	{
		$messages_variables = array( 'controller' => $this, 'messages' => $this->_get_messages() );
		$messages = $this->get_view( 'messages_view', $messages_variables, TRUE);
		
		$search = '<:messages:>';
		$replace = $messages;
		$page = str_replace( $search, $replace, $page );

		return $page;
	}
	
	public function insert_css_and_javascript( $page )
	{
		$css_view_variables = array( 'controller' => $this, 'relative_css_paths' => $this->_get_relative_css_paths() );
		$css = $this->get_view( 'css_view', $css_view_variables, TRUE);


        $unsorted_js = $this->_get_relative_javascript_paths();

        // Each element of the array returned by _get_relative_javascript paths is itself an
        // array of the format:
        // array(
        //     'path' =>'my_module/views/javascript/a_file.js',
        //     'dependencies' => array('simple', 'names', 'of', 'stuff', 'I', 'need'),
        //     'simple_name' => 'nice_name'
        // )
        // The 'simple_name' key can be NULL, but will still exist.

        // First we should check to make sure that all the dependencies that are specified
        // at least exist in the list to try to prevent an infinite loop caused by a user
        // requesting a javascript dependency that doesn't exist or misspelling the name of a dependency.

        // This will not however prevent circular dependencies from causing an infinite loop, so a
        // limit is set lower down in the loop that creates the $ordered_javascript array to deal with
        // that in a simpleish way.
        $all_dependencies = array();
        $all_named_javascripts = array();
        foreach( $unsorted_js as $javascript )
        {
            // Add the simple_name to the $all_named_javascripts array if it has one.
            if( $javascript['simple_name'] != NULL )
            {
                // It's an error for two different javascripts to have the same simple_name.
                if( array_key_exists($javascript['simple_name'], $all_named_javascripts) )
                {
                    $unsorted_javascript_string = print_r($unsorted_js, TRUE);

                    trigger_error
                    (
                        "Two different javascripts with the same simple_name:\"{$javascript['simple_name']}\" "
                        . "were added to this page. The list of javascripts follows:"
                        . "<pre>{$unsorted_javascript_string}</pre>",
                        E_USER_ERROR
                    );
                }
                else
                {
                    $all_named_javascripts[ $javascript['simple_name'] ] = $javascript['simple_name'];
                }
            }

            // Add all dependencies to the $all_dependencies array.
            foreach( $javascript['dependencies'] as $dependency )
            {
                $all_dependencies[ $dependency ] = $dependency;
            }
        }
        foreach( $all_dependencies as $dependency )
        {
            if( ! array_key_exists($dependency, $all_named_javascripts) )
            {
                // Create string of $dependencies that do exist to help the user debug the error.
                $dependency_string = '';
                foreach( $all_named_javascripts as $name )
                {
                    $dependency_string .= ' ' . $name;
                }

                trigger_error
                (
                    "A javascript dependency named\"{$dependency}\" was requested, but no such specifically "
                    . "named javascript was added on this page. The names of the specifically named javascript "
                    . "files added on this page are:{$dependency_string}",
                    E_USER_ERROR
                );
            }
        }

        // Now create the sorted array.
        $sorted_js = array();

        // The keys of this array are the dependencies that already exist in $sorted_js, for easy access.
        $dependencies_added = array();

        // Keep going until there are no javascripts in the unsorted list.
        $loop_count = 0;
        $max_loops = 1000;
        while( (sizeof($unsorted_js) != 0) && ($loop_count < $max_loops) )
        {
            $loop_count++;

            // Go through the $unsorted_js array until we find a javascript that has all of it's
            // dependencies already present in the $sorted_js array.
            foreach( $unsorted_js as $key => $javascript )
            {
                $all_dependencies_present = TRUE;
                // Search $sorted_js for all dependencies of current $javascript.
                foreach( $javascript['dependencies'] as $dependency )
                {
                    if( ! array_key_exists($dependency, $dependencies_added) )
                    {
                        $all_dependencies_present = FALSE;
                    }
                }

                $ok_javascript = NULL;

                // If all dependencies of $javascript are in $sorted_js, save this javascript to be
                // added to sorted list.
                if( $all_dependencies_present )
                {
                    $ok_javascript_key = $key;
                    $ok_javascript = $javascript;
                    break;
                }
            }

            // 1. Remove $ok_javascript from $unsorted_js.
            unset( $unsorted_js[$ok_javascript_key] );
            // 2. Add $ok_javascript to the end of $sorted_js.
            array_push( $sorted_js, $ok_javascript['path'] );
            // 3. Add $ok_javascript simple_name to the $dependencies_added array.
            $dependencies_added[ $ok_javascript['simple_name'] ] = TRUE;
        }

        // Check that we didn't just exit the loop because we exceeded the loop count, indicating either
        // a *very* long list of javascript dependencies or more likely a circular dependency loop.
        if( $loop_count >= $max_loops )
        {
            $unsorted_javascript_string = print_r($unsorted_js, TRUE);

            trigger_error
            (
                "We exceeded {$max_loops} while attempting to order the javascript dependencies. This either "
                . "indicates a *very* long list of javascript dependencies, or a circular dependency loop. "
                . "Try to find it in the following list of javascript files added to this page:"
                . "<pre>{$unsorted_javascript_string}</pre>",
                E_USER_ERROR
            );
        }

        $javascript_view_variables = array( 'controller' => $this, 'relative_javascript_paths' => $sorted_js );
		$javascript = $this->get_view('javascript_view', $javascript_view_variables, TRUE);
		
		$search = array( '<:css:>', '<:javascript:>' );
		$replace = array($css, $javascript);
		$page = str_replace( $search, $replace, $page );
		
		return $page;
	}
	
	public function html_tidy( $page )
	{
		global $tritas_config;
		require_once( dirname(__FILE__) . '/includes/tritas_html_tidy.php' );
		$page = Tritas_HTML_Tidy::tidy($page);
		
		return $page;
	}
}