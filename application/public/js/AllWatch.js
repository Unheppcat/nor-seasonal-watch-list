(function(window, $) {
    $('#select_season').change(function (e) {
        e.preventDefault()
        const control = $(e.target)
        const val = control.val()
        // noinspection JSUnresolvedFunction
        window.location.replace(Routing.generate('all_watch_index') + '?season=' + val)
    })

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        $.each( $.fn.dataTable.tables(true), function () {
            $(table).DataTable().columns.adjust();
        });
    })

    $('#select_show').change(function (e) {
        e.preventDefault()
        const control = $(e.target)
        const val = control.val()
        // noinspection JSUnresolvedFunction
        window.location.replace(Routing.generate('all_watch_index') + '#' + val)
    })

    $('.all_watch_bar_activity_chart').each( function () {
        const ctx = document.getElementById($(this).attr('id'))
        const data = $(this).data('scores')
        const maxScore = $(this).data('maxscore')
        let stepSize, maxChartTick
        if (maxScore < 6) {
            stepSize = 1
            maxChartTick = maxScore + 1
        } else {
            stepSize = Math.max(Math.floor(maxScore / 6), 1)
            maxChartTick = stepSize * (6 + 1)
        }
        // noinspection JSUnusedLocalSymbols
        const myChart = new Chart(ctx, {
            type: 'horizontalBar',
            defaults: {
                horizontalBar: {

                },
                global: {
                    title: {
                        display: false
                    }
                }
            },
            data: {
                labels: [
                    'Finished',
                    'Watching',
                    'Paused',
                    'PTW',
                    'Dropped'
                ],
                datasets: [{
                    data: data,
                    borderColor: '#aaaaaa',
                    borderWidth: 1,
                    backgroundColor: [
                        '#007eb9ff',  // blue         / finished
                        '#007eb9aa',  // mid blue     / watching
                        '#007eb966',  // weaker blue  / paused (#6c757d)
                        '#007eb922',  // weakest blue / ptw
                        '#000000ff'   // black        / dropped
                    ]
                }]
            },
            options: {
                scales: {
                    xAxes: [{
                        ticks: {stepSize: stepSize, maxRotation: 0, min: 0, max: maxChartTick}
                    }],
                    yAxes: [{
                        gridLines: {display: false}
                    }]
                },
                responsive: false,
                legend: {
                    display: false
                },
                animation: {
                    duration: 0
                }
            }
        })
    })

    $('.all_watch_bar_score_chart').each( function () {
        const ctx = document.getElementById($(this).attr('id'))
        const data = $(this).data('scores')
        const maxScore = $(this).data('maxscore')
        let stepSize, maxChartTick
        if (maxScore < 6) {
            stepSize = 1
            maxChartTick = maxScore + 1
        } else {
            stepSize = Math.max(Math.floor(maxScore / 6), 1)
            maxChartTick = stepSize * (6 + 1)
        }
        // noinspection JSUnusedLocalSymbols
        const myChart = new Chart(ctx, {
            type: 'horizontalBar',
            defaults: {
                horizontalBar: {

                },
                global: {
                    title: {
                        display: false
                    }
                }
            },
            data: {
                labels: [
                    'Th8a should',
                    'Highly favorable',
                    'Favorable',
                    'Neutral',
                    'Unfavorable'
                ],
                datasets: [{
                    data: data,
                    borderColor: '#aaaaaa',
                    borderWidth: 1,
                    backgroundColor: [
                        '#c80d0d',  // th8a red / th8a should
                        '#c80d0d88',  // weak red / highly favorable (#ff6145)
                        '#c80d0d44',  // salmon / favorable (#ffa47f)
                        '#00000022',  // gray-600 / neutral (#6c757d)
                        '#000000'   // black / unfavorable
                    ]
                }]
            },
            options: {
                scales: {
                    xAxes: [{
                        ticks: {stepSize: stepSize, maxRotation: 0, min: 0, max: maxChartTick}
                    }],
                    yAxes: [{
                        gridLines: {display: false}
                    }]
                },
                responsive: false,
                legend: {
                    display: false
                },
                animation: {
                    duration: 0
                }
            }
        })
    })

    const containerValues = {}
    let currentAnchorId = ''
    const changeSelector = function () {
        // noinspection JSUnresolvedVariable
        const anchorId = document.querySelector('#' + getKeyForMax(containerValues)).dataset.anchorid
        if (currentAnchorId !== anchorId) {
            currentAnchorId = anchorId
            document.querySelector('#select_show [value="show_target_' + anchorId + '"]').selected = true
        }
    }
    const showContainers = document.querySelectorAll('.show_container')
    const observer = new IntersectionObserver(function (entries) {
        for (const entry of entries) {
            // if (entry['isIntersecting'] === true) {
                containerValues[entry['target'].id] = entry['intersectionRatio']
                setTimeout(() => {changeSelector()}, 1000)
            // }
        }
    }, { threshold: [0.2, 0.4, 0.6, 0.8]})
    showContainers.forEach(showContainer => {
        observer.observe(showContainer)
    })

    const getKeyForMax = function (dict) {
        let keyForMax = '';
        let max = 0;
        for (const [key, value] of Object.entries(dict)) {
            if (value > max) {
                max = value
                keyForMax = key
            }
        }
        return keyForMax
    }
})(window, jQuery);
