<?php
class Ark_Cinema extends Ark_Controller
{
    public function cinema_view( $view_variables, $return_string_dont_print = FALSE )
    {
        $this->check_argument($view_variables, 'view_variables', 'array');

        $this->check_array($view_variables, 'view_variables', 'youtube_video_id', 'string');

        $this->check_array(
            $view_variables,
            'view_variables',
            'open_button_image_path',
            'string',
            $this->get_image_path('youTubeButton1.png')
        );

        $this->check_array(
            $view_variables,
            'view_variables',
            'close_button_image_path',
            'string',
            $this->get_image_path('youTubeButton2.png')
        );

        return $this->get_view('cinema', $view_variables, $return_string_dont_print);
    }
}