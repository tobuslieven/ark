<?php $this->get_css('gallery.css'); ?>
<?php $this->get_javascript('gallery.js'); ?>
<?php //$class = ( ($class === '') ? ('ark_gallery ' . $class) : 'ark_gallery' ); ?>
<?php $class = 'ark_gallery' . ( ($class === '') ? '' : ' ' . $class ); ?>
<div class="<?php echo $class; ?>" >
    <?php
    $count = 0;
    foreach( $gallery_items as $gallery_item )
    {
        $first_of_set = (($count == 0) ? ' first_of_set' : '');
        $item_class = $gallery_item['class'] == '' ? '' : ' ' . $gallery_item['class'];
        $item_class = 'ark_gallery' . $first_of_set . $item_class;

        $this->html->a_view(
            array(
                'href' => $gallery_item['resource_path'],
                'content' => $this->html->img_view(
                    array(
                        'image_path' => $gallery_item['thumbnail_path'],
                        'class' => $item_class
                    ),
                    TRUE
                ),
                'class' => $item_class
            )
        );
        $count++;
    }
    ?>
    <?php // If the ark_viewer html hasn't been added to the page yet, add it now. ?>
    <?php if( ! $this->one_shot_global('viewer_loaded') ): ?>
        <?php $this->one_shot_global('viewer_loaded', TRUE); ?>
        <div id="ark_viewer">
            <img class="loadingSpinner" src="<?php echo $this->get_image_path('pageLoader_wheelThrobBlackBG.gif'); ?>" />
            <div class="background"></div>
            <img class="forward_button" src="<?php echo $this->get_image_path('forwardButton.png'); ?>" />
            <?php
//                $this->html->img_view(
//                    array(
//                        'image_path' => $this->get_image_path('forwardButton.png'),
//                        'class' => 'forwardButton'
//                    )
//                );
//            ?>
            <img class="back_button" src="<?php echo $this->get_image_path('backButton.png'); ?>" />
            <img class="close_button" src="<?php echo $this->get_image_path('closeButton.png'); ?>" />
        </div>
    <?php endif; ?>
</div>
