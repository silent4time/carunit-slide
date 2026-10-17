jQuery(function($){
    function updatePreview(sliderId) {
        var ids = $('.carunit-product-ids[data-slider="' + sliderId + '"]').val() || '';
        var $preview = $('.carunit-preview[data-slider="' + sliderId + '"] .preview-box');
        $preview.empty();
        if (!ids) {
            $preview.html('<span style="color:#9ca3af">محصولی انتخاب نشده</span>');
            return;
        }
        // simple visual from selected chips
        $('.carunit-selected[data-slider="' + sliderId + '"] .carunit-chip').each(function(){
            var name = $(this).data('name') || '';
            var img = $(this).data('img') || '';
            var html = '<div class="preview-item">';
            if (img) html += '<img src="' + img + '">';
            html += '<div>' + name.substring(0, 18) + '</div></div>';
            $preview.append(html);
        });
    }

    function renderSelected(sliderId) {
        var $hidden = $('.carunit-product-ids').filter(function(){ return $(this).closest('.carunit-card').find('.carunit-product-search').data('slider') == sliderId; });
        // better selector
        $hidden = $('input.carunit-product-ids').eq(sliderId - 1);
        var ids = ($hidden.val() || '').split(',').filter(Boolean);
        var $box = $('.carunit-selected[data-slider="' + sliderId + '"]');
        $box.empty();
        ids.forEach(function(id){
            // chips will be rebuilt on add; for now keep existing
        });
    }

    $('.carunit-product-search').on('input', function(){
        var $input = $(this);
        var slider = $input.data('slider');
        var term = $input.val();
        var $results = $('.carunit-results[data-slider="' + slider + '"]');
        if (term.length < 2) { $results.empty(); return; }
        $.get(carunitSlide.ajaxurl, {
            action: 'carunit_slide_search',
            term: term,
            nonce: carunitSlide.nonce
        }, function(data){
            $results.empty();
            data.forEach(function(p){
                var img = p.image ? '<img src="' + p.image + '">' : '';
                $results.append('<div class="carunit-result-item" data-id="' + p.id + '" data-name="' + p.name + '" data-img="' + (p.image||'') + '">' + img + '<span>' + p.name + '</span></div>');
            });
        });
    });

    $(document).on('click', '.carunit-result-item', function(){
        var $item = $(this);
        var slider = $item.closest('.carunit-results').data('slider');
        var id = $item.data('id');
        var name = $item.data('name');
        var img = $item.data('img');
        var $hidden = $('.carunit-card').eq(slider-1).find('.carunit-product-ids');
        var current = ($hidden.val() || '').split(',').filter(Boolean);
        if (current.indexOf(String(id)) === -1) {
            current.push(id);
            $hidden.val(current.join(','));
            var chip = '<div class="carunit-chip" data-id="' + id + '" data-name="' + name + '" data-img="' + img + '">' +
                (img ? '<img src="' + img + '">' : '') +
                '<span>' + name.substring(0, 20) + '</span>' +
                '<span class="remove" data-id="' + id + '">×</span></div>';
            $('.carunit-selected[data-slider="' + slider + '"]').append(chip);
            updatePreview(slider);
        }
        $item.closest('.carunit-results').empty();
        $('.carunit-product-search[data-slider="' + slider + '"]').val('');
    });

    $(document).on('click', '.carunit-chip .remove', function(){
        var id = $(this).data('id');
        var $chip = $(this).closest('.carunit-chip');
        var slider = $chip.closest('.carunit-selected').data('slider');
        var $hidden = $('.carunit-card').eq(slider-1).find('.carunit-product-ids');
        var current = ($hidden.val() || '').split(',').filter(function(x){ return x != id; });
        $hidden.val(current.join(','));
        $chip.remove();
        updatePreview(slider);
    });

    // init previews
    for (var i = 1; i <= 4; i++) updatePreview(i);
});
