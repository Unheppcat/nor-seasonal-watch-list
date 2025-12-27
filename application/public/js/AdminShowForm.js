/**
 * Initialize Select2 for the related shows field
 */
$(document).ready(function() {
    const $relatedShowsField = $('#show_relatedShows');

    if ($relatedShowsField.length > 0) {
        // Get the current show ID to exclude from search results
        const currentShowId = $relatedShowsField.data('current-show-id') || null;

        // Initialize Select2 with AJAX
        $relatedShowsField.select2({
            ajax: {
                url: Routing.generate('admin_show_search'),
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        term: params.term || '',
                        exclude: currentShowId
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: 'Search for shows by title...',
            minimumInputLength: 2,
            allowClear: true,
            width: '100%',
            theme: 'bootstrap-5'
        });
    }
});
