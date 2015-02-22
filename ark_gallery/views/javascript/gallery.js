
// Add a click event to the document that will close the arkViewer when any 
// unhandled click event occurs.
$(document).click(function(){
    $('#ark_viewer').fadeOut(
        function() {
            $(this).find('.viewed_image').remove();
            $(this).find('.forward_button').hide();
            $(this).find('.back_button').hide();
        }
    );
});

(function($) {
    $.fn.arkViewer = function() {

        var imageRegex = /\.png|\.jpg|\.gif/;

        this.filter(function() {
            // Check that this is a link tag with an image extension in its href attribute.
            return (this.tagName == 'A') && imageRegex.test(this.href);
        }).click(function(e) {
//            alert('arkViewerClickEvent');
            var jThisLink = $(this);
            var jThumbnail = jThisLink.find('img');

            // Get the next and previous links if they exist. They must be a link with 
            // an href attribute that matches the imageRegex specified above.
            var jNextLink = jThisLink.next('a').filter(function(){
                return imageRegex.test(this.href);
            });

            var jPreviousLink = jThisLink.prev('a').filter(function(){
                return imageRegex.test(this.href);
            });

            var devicePixelRatio = jThisLink.devicePixelRatio();
//            console.log('oioi');
//            return false;

            // Returns the maximum width and height that will fit on the screen with 
            // the extra space specified by surroundingPixelDistance. 
            function fitToScreen(originalWidth, originalHeight, surroundingPixelDistance) {
                var jWindow = $(window);
                var availableWidth = jWindow.width() - (2 * surroundingPixelDistance);
                var availableHeight = jWindow.height() - (2 * surroundingPixelDistance);

                // Begin with this as a placeholder.
                var newWidth = originalWidth;
                var newHeight = originalHeight;

                // Now correct for if the new image height is larger than the viewport.
                if (newHeight > availableHeight) {
                    var heightRatio = availableHeight / originalHeight;

                    newHeight = availableHeight;
                    newWidth = originalWidth * heightRatio;
                }

                if (newWidth > availableWidth) {
                    var widthRatio = availableWidth / originalWidth;

                    newWidth = availableWidth;
                    newHeight = originalHeight * widthRatio;
                }

                return {'width':newWidth, 'height':newHeight};
            }

            var jViewer = $('#ark_viewer');

            var viewer_hidden = (jViewer.css('display') == 'none');
//            console.log('Viewer hidden:' + viewer_hidden.toString());
            if( viewer_hidden )
            {
                jViewer.fadeIn();
            }

            var viewed_image_exists = jViewer.find('.viewed_image').length > 0;
//            console.log('viewed_image_exists:' + viewed_image_exists);

            // Get rid of the previous image.
            if( viewed_image_exists )
            {
                jViewer.find('.viewed_image').fadeOut(
                    function() {
                        $(this).remove();
                    }
                );
            }

            // Get the space around the viewer in one direction for use in sizing the image.
            var pixelDistanceAroundImage =
                parseInt(jViewer.css('border-left-width'))
                    + parseInt(jViewer.css('padding-left'));

            // Now that we've got the pixelDistanceAroundViewer, if the viewer
            // was hidden, then we'll assign a reasonably appropriate size for it.
            if (viewer_hidden) {
                // Guess that the zoomed image will be twice as large as the original image.
                var width = (jThumbnail.width() * 2) / devicePixelRatio;
                var height = (jThumbnail.height() * 2) / devicePixelRatio;

                // Check that our guess will at least fit on the screen.
                var newWidthAndHeight = fitToScreen(width, height, 1.5 * pixelDistanceAroundImage);

                jViewer.css({
                    'width':width,
                    'height':height,
                    'margin-left': - ( (width/2) + pixelDistanceAroundImage),
                    'margin-top': - ( (height/2) + pixelDistanceAroundImage)
                });
            }

            var jBackButton = jViewer.find('img.back_button');
            var jForwardButton = jViewer.find('img.forward_button');

            // If there's a previous or next link thumbnail, then show the previous and next 
            // buttons and assign appropriate callbacks to them.

            function backButtonCallback() {
//				alert('backButtonCallback');
                jPreviousLink.click();
                return false;
            }

            function forwardButtonCallback() {
//				alert('forwardButtonCallback');
                jNextLink.click();
                return false;
            }

            if (jPreviousLink.length > 0) {
                jBackButton.finish();
                jBackButton.unbind('click');
                jBackButton.click(backButtonCallback);
                jBackButton.fadeIn();
            }
            else {
                jBackButton.stop();
                jBackButton.unbind('click');
                jBackButton.fadeOut();
            }

            if (jNextLink.length > 0) {
                jForwardButton.stop();
                jForwardButton.unbind('click');
                jForwardButton.click(forwardButtonCallback);
                jForwardButton.fadeIn();
            }
            else {
                jForwardButton.stop();
                jForwardButton.unbind('click');
                jForwardButton.fadeOut();
            }

            // Show the spinner.
            var jLoadingSpinner = jViewer.find('.loadingSpinner');
            jLoadingSpinner.show();

            // Create the image.
            // Get address.
            var imageAddress = jThisLink.attr('href');
//			alert('Image address: ' + imageAddress);

            var jImage = $('<img class="viewed_image" src="' + imageAddress + '" />');
            jViewer.prepend(jImage);

            // Hide the image until it has completely loaded and we have animated the 
            // viewer to the correct dimensions of the loaded image.
            jImage.hide();

            // Attach image loaded callback to the image so the 
            // overlay can resize itself appropriately when the image 
            // has loaded and we know what size it is.
            jImage.load(function(){
                // Inside an event callback, 'this' refers to the DOM element that 
                // the event occurred on. In this case, an image.

                var naturalWidth = jImage.naturalWidth() / devicePixelRatio;
                var naturalHeight = jImage.naturalHeight() / devicePixelRatio;
//				alert('naturalWidth:' + naturalWidth + ' naturalHeight:' + naturalHeight);

                var newWidthAndHeight = fitToScreen(naturalWidth, naturalHeight, (1.5 * pixelDistanceAroundImage));
                var newWidth = newWidthAndHeight.width;
                var newHeight = newWidthAndHeight.height;
//				alert('newWidth:' + newWidth + ' newHeight:' + newHeight);

                var newMarginLeft = - ( (newWidth/2) + pixelDistanceAroundImage);
                var newMarginTop = - ( (newHeight/2) + pixelDistanceAroundImage);
//				alert('newMarginLeft:' + newMarginLeft + 'newMarginTop:' + newMarginTop);

                jViewer.animate(
                    {
                        'width':newWidth,
                        'height':newHeight,
                        'margin-left':newMarginLeft,
                        'margin-top':newMarginTop
                    },
                    {
                        'queue':false,
                        'complete':function() {
                            jLoadingSpinner.hide();

                            // We do this rather than call fadeIn() because of 
                            // an image flash bug on ios that makes the image flash 
                            // visible for a split second before fading in.
                            jImage.css({'opacity':0});
                            jImage.animate({'opacity':1.0});
                            jImage.show();

//							jThis.fadeIn();
//							alert('animation complete');
                        }
                    }
                );

//				alert('image loaded.');
                return false;
            });

            return false;
        });
    }
}(jQuery));

$(document).ready(function() {
//    $('a.ark_gallery').click(function(e){
//        alert('oioi');
//    })
//    alert('oioi');
    $('a.ark_gallery').arkViewer();
});

//$('a.ark_gallery').arkViewer();

