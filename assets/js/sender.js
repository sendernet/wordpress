jQuery(function ($) {
    $(document.body).on('wc_fragments_refreshed', function () {
        var wc_fragments = JSON.parse(sessionStorage.getItem(wc_cart_fragments_params.fragment_name));
        $.each(wc_fragments, function (key, value) {
            if (key === 'script#sender-track-cart') {
                $(key).text(value);
                $(document.body).trigger('wc_fragments_loaded');
            }
        });

    });

});
