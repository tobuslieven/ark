

// ------------------------
// Device Pixel Ratio Addon
// ------------------------
// Retina displays and other screens with pixel densities significantly higher than was standard
// (circa 2005) are encouraged by the css specification to use virtual pixels which are comprised
// of multiple (usually 1.5 or 2) hardware pixels. This allows them to display websites designed
// for a 320 wide 1st generation iPhones reasonably well.
// Sometimes you want to know the real width, eg to avoid zooming into an image too much in an
// image viewer. So javascript introduced window.devicePixelRatio, but it's not supported on
// some older but still relevant browsers (previous versions of internet explorer and firefox).
// So this returns the real value if it does exit and returns a reasonable default if it doesn't.
// Also MY FIRST JQUERY PLUGIN yay!
(function($) {
    $.fn.devicePixelRatio = function() {
        if ("devicePixelRatio" in window && window.devicePixelRatio > 0) {
            return window.devicePixelRatio;
        }
        else{
            return 1;
        }
    }
}(jQuery));

// -------------------------------------
// Natural Width and Height jQuery Addon
//-------------------------------------

// Adds .naturalWidth() and .naturalHeight() methods to jQuery
// for retreaving a normalized naturalWidth and naturalHeight.

// Modern browsers (including IE9) provide naturalWidth and naturalHeight properties to IMG elements.
// These properties contain the actual, non-modified width and height of the image.
// IE8 and less don't support naturalWidth, so this provides a work around.

// This returns the width or height in device independent pixels (dips) sometimes refered to as
// "css pixels" or "virtual pixels". To get the actual width of the image I *think* you should
// divide by the devicePixelRatio, which is provide by another small jquery plugin higher up int the file.

// This came from http://www.jacklmoore.com/notes/naturalwidth-and-naturalheight-in-ie/

(function($){
    var
        props = ['Width', 'Height'],
        prop;

    while (prop = props.pop()) {
        (function (natural, prop) {
            $.fn[natural] =
                (natural in new Image())
                    ?
                    function () {
                        return this[0][natural];
                    }
                    :
                    function () {
                        var
                            node = this[0],
                            img,
                            value;

                        if (node.tagName.toLowerCase() === 'img') {
                            img = new Image();
                            img.src = node.src,
                                value = img[prop];
                        }
                        return value;
                    };
        }('natural' + prop, prop.toLowerCase())
            );
    }
}(jQuery));