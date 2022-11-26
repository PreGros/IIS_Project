/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import './styles/globals.scss';

// start the Stimulus application
import './bootstrap';

// require('jquery');
// require('bootstrap');
// import $ from 'jquery';
// create global $ and jQuery variables
// global.$ = global.jQuery = $;
// var $  = require('jquery');
// var dt = require('datatables.net')(window, $);
window.$ = window.jQuery = require('jquery'); //changed
require('bootstrap');
require('datatables.net-bs5');
require('datatables.net-autofill-bs5');
require('datatables.net-buttons');
require('datatables.net-buttons-bs5');
require('datatables.net-buttons/js/buttons.colVis.js');
require('datatables.net-colreorder-bs5');
require('datatables.net-datetime');
require('datatables.net-fixedcolumns-bs5');
require('datatables.net-fixedheader-bs5');
require('datatables.net-keytable-bs5');
require('datatables.net-responsive-bs5');
require('datatables.net-rowgroup-bs5');
require('datatables.net-rowreorder-bs5');
require('datatables.net-scroller-bs5');
require('datatables.net-searchbuilder-bs5');
require('datatables.net-searchpanes-bs5');
require('datatables.net-select-bs5');
require('datatables.net-staterestore-bs5');

$.fn.initDataTables = function(config, options) {

    //Update default used url, so it reflects the current location (useful on single side apps)
    $.fn.initDataTables.defaults.url = window.location.origin + window.location.pathname;

    var root = this,
        config = $.extend({}, $.fn.initDataTables.defaults, config),
        state = ''
    ;

    // Load page state if needed
    switch (config.state) {
        case 'fragment':
            state = window.location.hash;
            break;
        case 'query':
            state = window.location.search;
            break;
    }
    state = (state.length > 1 ? deparam(state.substr(1)) : {});
    var persistOptions = config.state === 'none' ? {} : {
        stateSave: true,
        stateLoadCallback: function(s, cb) {
            // Only need stateSave to expose state() function as loading lazily is not possible otherwise
            return null;
        }
    };

    return new Promise((fulfill, reject) => {
        // Perform initial load
        $.ajax(typeof config.url === 'function' ? config.url(null) : config.url, {
            method: config.method,
            data: {
                _dt: config.name,
                _init: true
            }
        }).done(function(data) {
            var baseState;

            // Merge all options from different sources together and add the Ajax loader
            var dtOpts = $.extend({}, data.options, typeof config.options === 'function' ? {} : config.options, options, persistOptions, {
                ajax: function (request, drawCallback, settings) {
                    if (data) {
                        data.draw = request.draw;
                        drawCallback(data);
                        data = null;
                        if (Object.keys(state).length) {
                            var api = new $.fn.dataTable.Api(settings);
                            var merged = $.extend(true, {}, api.state(), state);

                            api
                                .order(merged.order)
                                .search(merged.search.search)
                                .page.len(merged.length)
                                .page(merged.start / merged.length)
                                .draw(false);
                        }
                    } else {
                        request._dt = config.name;
                        $.ajax(typeof config.url === 'function' ? config.url(dt) : config.url, {
                            method: config.method,
                            data: request
                        }).done(function(data) {
                            drawCallback(data);
                        })
                    }
                }
            });

            if (typeof config.options === 'function') {
                dtOpts = config.options(dtOpts);
            }

            root.html(data.template);
            var dt = $('table', root).DataTable(dtOpts);
            if (config.state !== 'none') {
                dt.on('draw.dt', function(e) {
                    var data = $.param(dt.state()).split('&');

                    // First draw establishes state, subsequent draws run diff on the first
                    if (!baseState) {
                        baseState = data;
                    } else {
                        var diff = data.filter(el => { return baseState.indexOf(el) === -1 && el.indexOf('time=') !== 0; });
                        switch (config.state) {
                            case 'fragment':
                                history.replaceState(null, null, window.location.origin + window.location.pathname + window.location.search
                                    + '#' + decodeURIComponent(diff.join('&')));
                                break;
                            case 'query':
                                history.replaceState(null, null, window.location.origin + window.location.pathname
                                    + '?' + decodeURIComponent(diff.join('&') + window.location.hash));
                                break;
                        }
                    }
                })
            }

            fulfill(dt);
        }).fail(function(xhr, cause, msg) {
            console.error('DataTables request failed: ' + msg);
            reject(cause);
        });
    });
};

/**
 * Provide global component defaults.
 */
$.fn.initDataTables.defaults = {
    method: 'POST',
    state: 'fragment',
    url: window.location.origin + window.location.pathname
};

/**
 * Server-side export.
 */
$.fn.initDataTables.exportBtnAction = function(exporterName, settings) {
    settings = $.extend({}, $.fn.initDataTables.defaults, settings);

    return function(e, dt) {
        const params = $.param($.extend({}, dt.ajax.params(), {'_dt': settings.name, '_exporter': exporterName}));

        // Credit: https://stackoverflow.com/a/23797348
        const xhr = new XMLHttpRequest();
        xhr.open(settings.method, settings.method === 'GET' ? (settings.url + '?' +  params) : settings.url, true);
        xhr.responseType = 'arraybuffer';
        xhr.onload = function () {
            if (this.status === 200) {
                let filename = "";
                const disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }

                const type = xhr.getResponseHeader('Content-Type');

                let blob;
                if (typeof File === 'function') {
                    try {
                        blob = new File([this.response], filename, { type: type });
                    } catch (e) { /* Edge */ }
                }

                if (typeof blob === 'undefined') {
                    blob = new Blob([this.response], { type: type });
                }

                if (typeof window.navigator.msSaveBlob !== 'undefined') {
                    // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
                    window.navigator.msSaveBlob(blob, filename);
                }
                else {
                    const URL = window.URL || window.webkitURL;
                    const downloadUrl = URL.createObjectURL(blob);

                    if (filename) {
                        // use HTML5 a[download] attribute to specify filename
                        const a = document.createElement("a");
                        // safari doesn't support this yet
                        if (typeof a.download === 'undefined') {
                            window.location = downloadUrl;
                        }
                        else {
                            a.href = downloadUrl;
                            a.download = filename;
                            document.body.appendChild(a);
                            a.click();
                        }
                    }
                    else {
                        window.location = downloadUrl;
                    }

                    setTimeout(function() { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
                }
            }
        };

        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.send(settings.method === 'POST' ? params : null);
    }
};

/**
 * Convert a querystring to a proper array - reverses $.param
 */
function deparam(params, coerce) {
    var obj = {},
        coerce_types = {'true': !0, 'false': !1, 'null': null};
    $.each(params.replace(/\+/g, ' ').split('&'), function (j, v) {
        var param = v.split('='),
            key = decodeURIComponent(param[0]),
            val,
            cur = obj,
            i = 0,
            keys = key.split(']['),
            keys_last = keys.length - 1;

        if (/\[/.test(keys[0]) && /\]$/.test(keys[keys_last])) {
            keys[keys_last] = keys[keys_last].replace(/\]$/, '');
            keys = keys.shift().split('[').concat(keys);
            keys_last = keys.length - 1;
        } else {
            keys_last = 0;
        }

        if (param.length === 2) {
            val = decodeURIComponent(param[1]);

            if (coerce) {
                val = val && !isNaN(val) ? +val              // number
                    : val === 'undefined' ? undefined         // undefined
                        : coerce_types[val] !== undefined ? coerce_types[val] // true, false, null
                            : val;                                                // string
            }

            if (keys_last) {
                for (; i <= keys_last; i++) {
                    key = keys[i] === '' ? cur.length : keys[i];
                    cur = cur[key] = i < keys_last
                        ? cur[key] || (keys[i + 1] && isNaN(keys[i + 1]) ? {} : [])
                        : val;
                }

            } else {
                if ($.isArray(obj[key])) {
                    obj[key].push(val);
                } else if (obj[key] !== undefined) {
                    obj[key] = [obj[key], val];
                } else {
                    obj[key] = val;
                }
            }

        } else if (key) {
            obj[key] = coerce
                ? undefined
                : '';
        }
    });

    return obj;
}

window.addEventListener('load', () => {
    fixDateIntervals();
    $('[name^="table"], [name$="table"]').each((index, element) => {
        $(element).html('<div class="dt-loading"><div id="dt_processing" class="dataTables_processing card" style="display: block;">Loading...<div><div></div><div></div><div></div><div></div></div></div></div>')
        $(element).initDataTables($(element).data('settings'), {
            search: {
                return: true,
            },
            ordering: true,
            dom: 
            "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12 table-responsive'tr>>" +
            "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",
            //dom: 'Bfrtipl',
            buttons: [
                {
                    text: 'Reinitialize',
                    action: function (e, dt, node, config) {
                        location.reload();
                    }
                },
                {
                    extend: 'columnsToggle'
                }
            ],
            initComplete: function(settings, json) {
                $("<label class='column-label'>Columns visibility</label>").insertBefore(".dt-buttons");

                $(".buttons-columnVisibility").each((index, el) => {
                    var span = $(el, "span");
                    var value = $(span).text();
                    $(span).text(value.substring(0, 1));
                    $(el).attr('title', value);
                });
                // Setup - add a text input to each footer cell
                // // Apply the search
                // this.api()
                // .columns()
                // .every(function () {
                //     var that = this;

                //     $('input', this.footer()).on('keyup change clear', function () {
                //         if (that.search() !== this.value) {
                //             that.search(this.value).draw();
                //         }
                //     });
                // });
                // // Get number of total records
                // // var recordsTotal = api.context[0].fnRecordsTotal();
                // // $('#events_list h5 span').text(recordsTotal);

                // // Hide some columns
                // //api.columns([4,9]).visible(false);

                // Create tr filter
                // var tr = $('<tr id="filter_search"></tr>');
                // // Count number of cells in a row
                // var nbCells = document.getElementById('dt').rows[0].cells.length;
                // // Generate cells to #filter_search row
                // for (var i = 0; i < nbCells; i++) {
                //     // onclick="stopPropagation(event);"
                //     tr.append('<th><input type="search" placeholder="Search"></th>');
                // }

                // var firstHeaderRow = $('tr', api.table().header());
                // // tr.insertAfter(firstHeaderRow);

                // // $("#filter_search th").eq(5).find('input').datepicker({
                // //     autoclose: true,
                // //     todayHighlight: true,
                // //     language: "fr",
                // //     dateFormat: "dd/mm/yy",
                // // });

                // $("#filter_search input").on('keyup change', function(e) {
                //     if (e.keyCode == 13) {
                //         api
                //             .column($(this).parent().index()+':visible')
                //             .search(this.value)
                //             .draw();
                //     }
                // });

                // $('.buttons-columnVisibility').each(function(index, element) {
                //     $(element).click(function() {
                //         if (api.column(index).visible() === true) {
                //             $('#filter_search th').eq(index).show();
                //         } else {
                //             $('#filter_search th').eq(index).hide();
                //         }
                //     });
                // });
            }
        });
    });

    // var table = $('#example').DataTable();
 
    // $('#example tbody').on('click', 'tr', function () {
    //     if ($(this).hasClass('selected')) {
    //         $(this).removeClass('selected');
    //     } else {
    //         table.$('tr.selected').removeClass('selected');
    //         $(this).addClass('selected');
    //     }
    // });
 
    // $('#button').on('click', function () {
    //     table.row('.selected').remove().draw(false);
    // });
});

function fixDateIntervals(){
    $('.date-interval').each((i, el) => {
        $('div.col-auto').each((i, el) => {
            el.classList.remove('col-auto');
            el.classList.add('col-4');
        });
        $('input[name*=hours]').each((i, el) => {
            el.setAttribute('min', '0');
            el.setAttribute('max', '23');
        });
        $('input[name*=minutes]').each((i, el) => {
            el.setAttribute('min', '0');
            el.setAttribute('max', '59');
        });
        $('input[name*=seconds]').each((i, el) => {
            el.setAttribute('min', '0');
            el.setAttribute('max', '59');
        });
    });
}

$('.ref').on('click', function () {
    var href = $(this).attr('href');
    $(href).parent().parent().removeClass('highlight');
    setTimeout(function() {
        $(href).parent().parent().addClass('highlight');
    }, 5);
});
