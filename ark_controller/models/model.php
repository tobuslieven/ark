<?php
class Ark_Model
{
	// Models can access data from other models and controllers via the controller 
	// that is passed in to their _construct function when they're created.
	private $controller = NULL;
	
	public function __construct( $controller )
	{
		$this->controller = $controller;
	}
}
