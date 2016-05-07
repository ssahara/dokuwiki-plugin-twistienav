/**
 * AJAX functions for the pagename quicksearch
 *
 * @license  GPL2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Håkan Sandell <sandell.hakan@gmail.com>
 */

var twistienav_plugin = {

    $callerObj: null,

    init: function () {
        if (jQuery('div.youarehere').length !== 0) {
            twistienav_plugin.breadcrumbs('div.youarehere', 'yah_ns');
        }
        if (jQuery('div.trace').length !== 0) {
            twistienav_plugin.breadcrumbs('div.trace', 'bc_ns');
        }
        if (jQuery('div.pageId').length !== 0) {
            twistienav_plugin.pageIdTrace('div.pageId', 'yah_ns');
        }
        return;
    },

    /**
     * Add twisties and link events 
     */
    breadcrumbs: function(div, ns_list){
        var do_search;
        var $traceObj = jQuery(div);
        var $list = JSINFO['plugin_twistienav'][ns_list];

        jQuery(document).click(function(e) {
            twistienav_plugin.clear_results();
        });
        
        do_search = function (caller, namespace) {
            twistienav_plugin.$callerObj = jQuery(caller);
            jQuery.post(
                DOKU_BASE + 'lib/exe/ajax.php',
                {
                    call: 'plugin_twistienav',
                    idx: encodeURI(namespace)
                },
                twistienav_plugin.onCompletion,
                'html'
            );
        };

        // remove current id highlight because it is inherited by popup
        trace = $traceObj.html();
        trace = trace.replace(/<span class="curid">/gi,'')
                     .replace(/<\/span>$/gi,'');
        $traceObj.html(trace);

        // add new twisties
        var linkNo = 0;
        $links = $traceObj.find('a');
        $links.each(function () {
            var ns = $list[linkNo];
            if (ns == false) {
                ns = '';
            }
            if ($list[linkNo] || $list[linkNo] == '') {
                jQuery(document.createElement('span'))
                            .addClass('twistienav_twistie')
                            .show()
                            .insertAfter(this)
                            .click(function() {
                                twistie_active = jQuery(this).hasClass('twistienav_down'); 
                                twistienav_plugin.clear_results();
                                if (!twistie_active) {
                                    do_search(this, ns);
                                }
                            });
            }
            linkNo++;
        });
    },

    /**
     * Turn 'pageId' element into a minimalistic hierarchical trace
     */
    pageIdTrace: function(div, ns_list){
        var do_search;
        var $traceObj = jQuery(div);
        var $list = JSINFO['plugin_twistienav'][ns_list];

        jQuery(document).click(function(e) {
            twistienav_plugin.clear_results();
        });
        
        do_search = function (caller, namespace) {
            twistienav_plugin.$callerObj = jQuery(caller);
            jQuery.post(
                DOKU_BASE + 'lib/exe/ajax.php',
                {
                    call: 'plugin_twistienav',
                    idx: encodeURI(namespace)
                },
                twistienav_plugin.onCompletion,
                'html'
            );
        };

        // Replace pageId text by prepared skeleton
        $traceObj.html(JSINFO['plugin_twistienav']['pit_skeleton']);

        // transform links into text "twisties"
        var linkNo = 1;
        $links = $traceObj.find('a');
        $links.each(function () {
            var ns = $list[linkNo];
            if (ns == false) {
                ns = '';
            }
            if ($list[linkNo] || $list[linkNo] == '') {
                jQuery(this)
                            .addClass('twistienav_twistie')
                            .show()
                            .insertAfter(this)
                            .click(function() {
                                twistie_active = jQuery(this).hasClass('twistienav_down'); 
                                twistienav_plugin.clear_results();
                                if (!twistie_active) {
                                    do_search(this, ns);
                                }
                            });
            } else {
                jQuery(this)
                            .addClass('twistienav_twistie')
                            .show()
                            .insertAfter(this)
                            .click(function() {
                                twistie_active = jQuery(this).hasClass('twistienav_down'); 
                                twistienav_plugin.clear_results();
                                if (!twistie_active) {
                                    do_search(this, '');
                                }
                            });
            }
            linkNo++;
        });
    },

    /**
     * Remove output div
     */
    clear_results: function(){
        jQuery('.twistienav_twistie').removeClass('twistienav_down');
        jQuery('#twistienav__popup').remove();
    },

    /**
     * Callback. Reformat and display the results.
     *
     * Namespaces are shortened here to keep the results from overflowing
     * or wrapping
     *
     * @param data The result HTML
     */
    onCompletion: function(data) {
        var pos = twistienav_plugin.$callerObj.position();

        if (data === '') { return; }

        twistienav_plugin.$callerObj.addClass('twistienav_down');

        jQuery(document.createElement('div'))
                        .html(data)
                        .attr('id','twistienav__popup')
                        .css({
                            'position':    'absolute',
                            'top':         (pos.top +16)+'px',
                            'left':        (pos.left+16)+'px'
                            })
                        .show()
                        .insertAfter(twistienav_plugin.$callerObj)
                        .click(function() {
                            twistienav_plugin.clear_results();
                        });
    }
};

jQuery(function () {
    twistienav_plugin.init();
});
