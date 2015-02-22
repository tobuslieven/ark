<?php

class Ark_Pages extends Ark_Controller
{
	protected $web_accessible_methods = array( 'welcome' );
	
	public function welcome( $arguments_array )
	{
		$view_variables = array
		(
			'main_content' => '<h1>Page Content!!!</h1>'
		);
		
//		$this->get_view('outer_page_view', $view_variables);

        $this->get_controller('layout');
        $this->layout->one_column_responsive_view($view_variables);

	}
}
