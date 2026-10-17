jQuery(function($){
    $('.carunit-slide').each(function(){
        var $slider = $(this);
        var $track = $slider.find('.carunit-slide-track');
        var autoplay = $slider.data('autoplay') == 1;
        var speed = $slider.data('speed') || 3000;
        var direction = $slider.data('direction') || 'rtl';
        var index = 0;
        var items = $track.children().length;

        function slide() {
            var itemWidth = $track.children().first().outerWidth(true);
            var move = direction === 'rtl' ? index * itemWidth : -index * itemWidth;
            $track.css('transform', 'translateX(' + move + 'px)');
        }

        $slider.find('.carunit-slide-next').on('click', function(){
            index = (index + 1) % items;
            slide();
        });
        $slider.find('.carunit-slide-prev').on('click', function(){
            index = (index - 1 + items) % items;
            slide();
        });

        if (autoplay) {
            setInterval(function(){
                index = (index + 1) % items;
                slide();
            }, speed);
        }
    });
});
