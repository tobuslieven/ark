<?php
    $this->get_css('ark_cinema.css');
    $this->get_javascript('ark_cinema.js');
?>
<div class="ark_cinema">
    <div class="ark_cinema_hider" >
        <div class="ark_youtube_container">
            <image class="loading_spinner" src="/images/pageLoader_wheelThrobberBlackBG.gif">
                <iframe width="640" height="360" src="//www.youtube.com/embed/<?php echo $youtube_video_id; ?>" frameborder="0" allowfullscreen=""></iframe>
        </div>
    </div>
    <img class="ark_cinema_open_button" src="<?php echo $open_button_image_path; ?>">
    <img class="ark_cinema_close_button" src="<?php echo $close_button_image_path; ?>">
</div>