(function($){
    $.sequentialAnimations = {
        defaultOptions : {
            animationDelay : 500,
            animationSpeed : 1000,
            animateFrom : {},
            animateTo : {},
            callback : null,
            initialDelay : false
        }
    }

    $.fn.extend({
        sequentialAnimations: function(newOptions){
            var options = $.extend($.sequentialAnimations.defaultOptions, newOptions),
                elements = this,
                elementsLength = elements.length,
                elementIdx = 0;

            elements.css(options.animateFrom).each(function(i){
                var i = (options.initialDelay) ? i + 1 : i ,
                    delay = i * options.animationDelay,
                    animationSpeed = options.animationSpeed,
                    element = $(this);

                element.delay(delay).animate(options.animateTo, animationSpeed, function(){
                    elementIdx++;
                    element.removeClass('hiddenForAnimation');
                    if(typeof options.callback === 'function' && elementIdx >= elementsLength) options.callback();
                });
            });

            return this;
        }
    });
})(jQuery);
