(function($){
    $.fn.arkCinema = function(){
        // In this context 'this' is a jQuery object that is a collection of DOM
        // objects probably acquired via something like $('div.videoToBeHidden').

        // For now we'll be assuming it's been called on a responsively resizing
        // youtube containing div. These are achieved slightly oddly, their height
        // being determined by a percentage valued padding on the div. We'll need
        // to save the padding that's been put on the div so we can animate back
        // to that specific value when we reveal the div on the button click.

        // We'll iterate over each of them, hiding them and adding the youtube
        // button which will reveal them.
        this.filter('div').each(function(){
            jCinemaContainer = $(this);

            jCinemaHider = jCinemaContainer.find('div.ark_cinema_hider');
            jYoutubeContainer = jCinemaHider.find('div.ark_youtube_container');
            jOpenButton = jCinemaContainer.find('img.ark_cinema_open_button');
            jCloseButton = jCinemaContainer.find('img.ark_cinema_close_button');

            var ytHeightPixels = parseInt(jYoutubeContainer.css('padding-bottom'));
            var ytWidthPixels = parseInt(jYoutubeContainer.css('width'));
            var ytRatio = (ytHeightPixels / ytWidthPixels);
            var hiderMarginBottom = parseInt(jCinemaHider.css('margin-bottom'));

            jOpenButton.click(function(){
                var currentWidth = ytRatio * parseInt(jCinemaContainer.css('width'));

                // Animate open the hider div to the appropriate height then on
                // completion set its height to auto to make it properly responsive.
                jCinemaHider.animate(
                    {
                        'height': currentWidth,
                        'margin-bottom': hiderMarginBottom
                    },
                    {
                        'complete': function(){
                            $(this).css('height', 'auto');
                        }
                    }
                );
                // Hide the open button and display the close button.
                jOpenButton.css('display','none');
                jCloseButton.css('display','block');

                return false;
            });

            jCloseButton.click(function(){
                // Animate closed the hider div.
                jCinemaHider.animate({
                    'height': 0,
                    'margin-bottom': 0
                });
                // Hide the close button and display the open button.
                jCloseButton.css('display','none');
                jOpenButton.css('display','block');

                return false;
            });

            // Show the open button.
            jOpenButton.css({'display':'block'});

            // Hide the video.
            jCinemaHider.css({
                'height': 0,
                'margin-bottom': 0
            });

            return false;
        });
    }

}(jQuery));

$(document).ready(function(){
    $('div.ark_cinema').arkCinema();
})