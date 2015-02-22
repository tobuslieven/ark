<div id="main_content">
    <?php
        $this->get_controller('cinema');

        $cinema_view_variables = array('youtube_video_id' => '6Gk3joJpaUY');

        $this->cinema->cinema_view($cinema_view_variables);
    ?>

    <h1 id="strap_line">A Physicsy Puzzler Faster than the Speed of Thought</h1>
    <p>Smash the blocks in rainbow order to collect coins, unlock jewels and beat the timer.</p>
    <!--    <p>Blocks have many effects as you pile on the multipliers to be the richest in the galaxy.</p>-->
        <p>Smashing stuff does stuff, so do it until you have more money than Davey Crockett.</p>
    <!--    <p>Then do it more, cos you're not just in it for the money. No, you love this game.</p>-->
    <?php
        $this->get_controller('gallery');

        $gallery_view_variables = array(
            'gallery_items' => array(
                array(
                    'thumbnail_path' => $this->get_image_path('stash1_320px.png'),
                    'resource_path' => $this->get_image_path('stash1_full.png'),
                ),
                array(
                    'thumbnail_path' => $this->get_image_path('title1_320px.png'),
                    'resource_path' => $this->get_image_path('title1_full.png'),
                ),
                array(
                    'thumbnail_path' => $this->get_image_path('title2_320px.png'),
                    'resource_path' => $this->get_image_path('title2_full.png'),
                ),
                array(
                    'thumbnail_path' => $this->get_image_path('stash2_320px.png'),
                    'resource_path' => $this->get_image_path('stash2_full.png'),
                ),
            ),
        );

        $this->gallery->gallery_view($gallery_view_variables);
    ?>
    <p>Customize your stash then share pictures of your riches for fun and to prove the great justice of your culture.</p>
    <p>In the best deal since the establishment of the Federal Reserve, all this can be yours for only 99 cents.</p>
    <h2 id="strap_line">SmashTrix Available Now at the App Store!</h2>
</div>