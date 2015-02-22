<?php
Class Ark_Gallery extends Ark_Controller
{
    function gallery_view($view_variables, $caller_name = NULL)
    {
        $this->get_controller('html');

        // Check arguments.
        $this->check_argument($view_variables, 'view_variables', 'array');
        $this->check_array($view_variables, 'view_variables', 'gallery_items', 'array');

        $this->check_array($view_variables, 'view_variables', 'class', 'string', '');

        foreach( $view_variables['gallery_items'] as &$gallery_item )
        {
            // Check here that each one is a valid gallery item, ie it has a path
            // in the 'thumbnail_path' key and a path in the 'resource_path' key.
            $this->check_array($gallery_item, 'gallery_item', 'thumbnail_path', 'string');
            $this->check_array($gallery_item, 'gallery_item', 'resource_path', 'string');

            // These keys have defaults.
            $this->check_array($gallery_item, 'gallery_item', 'class', 'string', '');
        }

        $this->get_view('gallery', $view_variables);
    }
}