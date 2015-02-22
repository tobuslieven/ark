<?php

class Ark_Layout extends Ark_Controller
{
    // View Methods
    function one_column_responsive_view( $view_variables )
    {
        $default_page_content = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>';

        $this->check_argument($view_variables, 'view_variables', 'array');
        $this->check_array($view_variables, 'view_variables', 'title', 'string', 'Default Title');
        $this->check_array($view_variables, 'view_variables', 'main_content', 'string', $default_page_content);
        $this->check_array($view_variables, 'view_variables', 'header', 'string', NULL, TRUE);
        $this->check_array($view_variables, 'view_variables', 'footer', 'string', NULL, TRUE);

        $this->get_view('one_column_responsive', $view_variables);
    }
}