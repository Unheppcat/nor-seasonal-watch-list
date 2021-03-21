(function(window, $) {

    $('#select_season').change(function (e) {
        e.preventDefault()
        const control = $(e.target)
        const val = control.val()
        window.location.replace('?season=' + val)
    })

})(window, jQuery);
