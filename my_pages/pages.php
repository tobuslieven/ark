<?php

class My_Pages extends Ark_Controller
{
    protected $web_accessible_methods = array('welcome');

    public function welcome()
    {
        $this->get_controller('html');

        // Create the welcome page contents view variables.
        $welcome_view_variables = array();
        $main_content = $this->get_view('welcome', $welcome_view_variables, TRUE);

        // Create outer page view variables.
        $view_variables = array();
        $view_variables['main_content'] = $main_content;
        $view_variables['title'] = 'Welcome';
//        $view_variables['header'] = '<h1>ello</h1>';
//        $view_variables['header'] = '';
        // Because the outer page method we're using allows NULL for the 'header' content and
        // the view it invokes deals with that by invoking a view called 'header', the following
        // has the same effect as not bothering to set the 'header' content which is to invoke
        // the header view in the appropriate layout module.
//        $view_variables['header'] = NULL;

        $this->get_controller('layout');
        $this->layout->one_column_responsive_view($view_variables);
    }
}