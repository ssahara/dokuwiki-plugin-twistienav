<?php
/**
 * DokuWiki Plugin twistienav (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  H�kan Sandell <sandell.hakan@gmail.com>
 * @maintainer: Simon Delage <simon.geekitude@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_twistienav extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'populate_jsinfo', array());
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call', array());
    }

    function populate_jsinfo(&$event, $params) {
        global $JSINFO, $conf, $ID;
        global $excluded;

        $namespaces = array();
        $yah_ns = array(0 => '');
        $bc_ns = array();
        $pit_ns = array();
        // Store settings values in JSINFO
        $JSINFO['conf']['start'] = $conf['start'];
        $JSINFO['conf']['breadcrumbs'] = $conf['breadcrumbs'];
        $JSINFO['conf']['youarehere'] = $conf['youarehere'];
        $JSINFO['plugin_twistienav']['twistiemap'] = $this->getConf('twistieMap');
        $JSINFO['plugin_twistienav']['style'] = $this->getConf('style');
        if ($this->getConf('exclusions') != null) {
            $exclusions = $this->getConf('exclusions');
            $exclusions = str_replace("start", $conf['start'], $exclusions);
            $exclusions = str_replace("sidebar", $conf['sidebar'], $exclusions);
            $excluded = explode(",", $exclusions);
        } else {
            $excluded = array();
        }
        // List namespaces for YOUAREHERE breadcrumbs
        if (($conf['youarehere'] == 1) or ($this->getConf('pageIdTrace')) or ($this->getConf('pageIdExtraTwistie'))) {
            $parts = explode(':', $ID);
            $count = count($parts);
            $part = '';
            for($i = 0; $i < $count - 1; $i++) {
                $part .= $parts[$i].':';
                if ($part == $conf['start']) continue; // Skip start page
                $elements = 0;
                // Get index of current crumb namespace
                $idx  = cleanID(getNs($part));
                $dir  = utf8_encodeFN(str_replace(':','/',$idx));
                $data = array();
                search($data,$conf['datadir'],'search_index',array('ns' => $idx),$dir);
                // Count pages that are not in configured exclusions
                foreach ($data as $item) {
                    if (!in_array(noNs($item['id']), $excluded)) {
                        $elements++;
                    }
                }
                // If there's at least one page that isn't excluded, prepare JSINFO data for that crumb
                if ($elements > 0) {
                    $yah_ns[$i+1] = $idx;
                }
            }
            $JSINFO['plugin_twistienav']['yah_ns'] = $yah_ns;
        }
        // List namespaces for TRACE breadcrumbs
        if ($conf['breadcrumbs']) {
            $crumbs = breadcrumbs();
            // get namespaces currently in $crumbs
            $i = -1;
            foreach ($crumbs as $crumbId => $crumb) {
                $i++;
                // Don't do anything unless 'startPagesOnly' setting is off or current breadcrumb leads to a namespace start page 
                if (($this->getConf('startPagesOnly') == 0) or (strpos($crumbId, $conf['start']) !== false)) {
                    $elements = 0;
                    // Get index of current crumb namespace
                    $idx  = cleanID(getNs($crumbId));
                    $dir  = utf8_encodeFN(str_replace(':','/',$idx));
                    $data = array();
                    search($data,$conf['datadir'],'search_index',array('ns' => $idx),$dir);
                    // Count pages that are not in configured exclusions
                    foreach ($data as $item) {
                        if (!in_array(noNs($item['id']), $excluded)) {
                            $elements++;
                        }
                    }
                    // If there's at least one page that isn't excluded, prepare JSINFO data for that crumb
                    if ($elements > 0) {
                        $bc_ns[$i] = $idx;
                    }
                }
            }
            $JSINFO['plugin_twistienav']['bc_ns'] = $bc_ns;
        }
        // Build 'pageIdTrace' skeleton if required
        if (($this->getConf('pageIdTrace')) or ($this->getConf('pageIdExtraTwistie'))) {
            $skeleton = "<span>";
            if ($this->getConf('pageIdTrace')) {
                $parts = explode(':', $ID);
                $count = count($parts);
                $part = '';
                for($i = 1; $i < $count; $i++) {
                    $part .= $parts[$i-1].':';
                    if ($part == $conf['start']) continue; // Skip startpage
                    if (isset($yah_ns[$i])) {
                        $skeleton .= '<a href="javascript:void(0)">'.$parts[$i-1].'</a>:';
                    } else {
                        $skeleton .= $parts[$i-1].':';
                    }
                }
                $skeleton .= end($parts);
            } else {
                $skeleton .= $ID;
            }
            if ($this->getConf('pageIdExtraTwistie')) {
                $skeleton .= '<a href="javascript:void(0)" class="twistienav_extratwistie'.' '.$this->getConf('style');
                if ($this->getConf('twistieMap')) { $skeleton .= ' twistienav_map'; }
                $skeleton .= '"></a>';
            }
            $skeleton .= "</span>";
            $JSINFO['plugin_twistienav']['pit_skeleton'] = $skeleton;
        }
    }

    function handle_ajax_call(&$event, $params) {
        global $conf;

        // Process AJAX calls from 'plugin_twistienav' or 'plugin_twistienav_pageid'
        if (($event->data != 'plugin_twistienav') && ($event->data != 'plugin_twistienav_pageid')) return;
        $event->preventDefault();
        $event->stopPropagation();

        $idx  = cleanID($_POST['idx']);
        $dir  = utf8_encodeFN(str_replace(':','/',$idx));
        $exclusions = $this->getConf('exclusions');
        // If AJAX caller is from 'pageId' we don't wan't to exclude start pages
        if ($event->data == 'plugin_twistienav_pageid') {
            $exclusions = str_replace("start", "", $exclusions);
        } else {
            $exclusions = str_replace("start", $conf['start'], $exclusions);
        }
        $exclusions = str_replace("sidebar", $conf['sidebar'], $exclusions);

        $data = array();
        search($data,$conf['datadir'],'search_index',array('ns' => $idx),$dir);

        if (!plugin_isdisabled('pagetitle')) {
            $pagetitleHelper = plugin_load('helper', 'pagetitle');
        }

        if (count($data) != 0) {
            echo '<ul>';
            foreach($data as $item){
                if (strpos($exclusions, noNs($item['id'])) === false) {
                    // Get Croissant plugin page title if it exists
                    $croissantTitle = p_get_metadata($item['id'], 'plugin_croissant_bctitle');
                    // Get PageTitle plugin page title if it exists
                    if ($pagetitleHelper != null) {
                        $pagetitleTitle = $pagetitleHelper->tpl_pagetitle($item['id'], false);
                        // By default, if 'pagetitle' isn't set, the helper offers page id instead and we don't want that
                        if ($pagetitleTitle == $item['id']) {
                            $pagetitleTitle = null;
                        }
                    }

                    if ($croissantTitle != null) {
                        $title = $croissantTitle;
                    // haven't been abble to use this meta tag from PageTitle plugin
                    //} elseif (p_get_metadata($item['id'], 'shorttitle') != null) {
                    //    $title = p_get_metadata($item['id'], 'shorttitle');
                    } elseif ($pagetitleTitle!= null) {
                        $title = $pagetitleTitle;
                    } elseif ($conf['useheading'] && $title_tmp=p_get_first_heading($item['id'],FALSE)) {
                        $title=$title_tmp;
                    } else {
                        $title=hsc(noNs($item['id']));
                    }
                    if ($item['type'] == 'd') {
                        echo '<li><a href="'.wl($item['id'].':').'" class="twistienav_ns">'.$title.'</a></li>';
                    } else {
                        echo '<li>'.html_wikilink(':'.$item['id'], $title).'</li>';
                    }
                }
            }
            echo '</ul>';
        }
    }
}
