<?php
Class Ark_Html extends Ark_Controller
{
    function img_view($view_variables, $return_string_dont_print = FALSE)
    {
        // The user will most often want the image path to resolve to an image in
        // the view/images/ folder of the module that they are writing, not this html module,
        // so by default we need to get the next controller up the stack trace and give
        // that as the name of the module relative to which the image paths should be resolved.

//        // TEST TO CAUSE ERROR
//        $view_variables['image_path'] = NULL;

        // Check required parameters and apply defaults to optional array keys.
        $this->check_argument($view_variables, 'view_variables', 'array');
        $this->check_array($view_variables, 'view_variables', 'image_path', 'string');

        $this->check_array($view_variables, 'view_variables', 'class', 'string', '');

        return $this->get_view('img', $view_variables, $return_string_dont_print);
    }

    function a_view($view_variables, $return_string_dont_print = FALSE)
    {
        // Check required parameters and apply defaults to optional array keys.
        $this->check_argument($view_variables, 'view_variables', 'array');
        $this->check_array($view_variables, 'view_variables', 'class', 'string', '');

        // Get view.
        return $this->get_view('a', $view_variables, $return_string_dont_print);
    }
}
