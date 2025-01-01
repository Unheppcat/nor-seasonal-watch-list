(function(window, $) {

    const showTitleOserver = new ResizeObserver(entries => {
        for (let entry of entries) {
            const container = entry.target;
            // If any .show-title element is truncated...
            const titles = container.querySelectorAll('.show-title');
            const truncated = !!Array.from(titles).find(elem => {
                if (!elem.dataset.lineClamp) {
                    return false;
                }
                const lineCount = parseInt(elem.dataset.lineClamp);
                const lineHeight = elem.clientHeight / lineCount;
                // If scroll height is larger than client height by half the line height,
                // consider it truncated.
                return elem.clientHeight + lineHeight * 0.5 < elem.scrollHeight;
            });
            // Add the .truncated class to the container
            container.classList.toggle('truncated', truncated);
        }
    });

    document.querySelectorAll('.show-titles').forEach(elem => {
        showTitleOserver.observe(elem);
    });

    $('.show-titles .expand-link').on('click', function () {
        $(this).closest('.show-titles').addClass('show-titles-no-clamp');
    });

})(window, jQuery);
