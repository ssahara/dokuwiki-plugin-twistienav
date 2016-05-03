<?php
/**
 * DokuWiki Plugin twistienav (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Håkan Sandell <sandell.hakan@gmail.com>
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

        $namespaces = array();
        $yah_ns = array(0 => '');
        $bc_ns = array();
        // Store $conf['start'] setting value
        $JSINFO['conf']['start'] = $conf['start'];
        // List namespaces for YOUAREHERE breadcrumbs
        if ($conf['youarehere'] == 1) {
            $parts = explode(':', $ID);
            $count = count($parts);
            $part = '';
            for($i = 0; $i < $count - 1; $i++) {
                $part .= $parts[$i].':';
                if ($part == $conf['start']) continue; // Skip startpage
                $elements = 0;
                // Check corresponding path for subfolders and pages (excluding start pages)
                $path = $conf['savedir']."/pages/".str_replace(":", "/", rtrim($part, ":"));
                if (is_dir($path)) {
                    foreach (new DirectoryIterator($path) as $fileInfo) {
                        if ($fileInfo->isDot()) continue;
                        if (($fileInfo->isDir()) or (($fileInfo->isFile()) && ($fileInfo->getExtension() == "txt") && ($fileInfo->getFilename() != $conf['start'].".txt") && ($fileInfo->getFilename() != "topbar.txt"))) {
                            $elements++;
                        }
                    }
                    if ($elements > 0) {
                        $yah_ns[$i+1] = rtrim($part, ":");
                    }

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
                //array_push($namespaces, getNS($crumbId));
                $elements = 0;
                // Check corresponding path for subfolders and pages (excluding start.txt and topbar.txt pages)
                $path = $conf['savedir']."/pages/".str_replace(":", "/", getNS($crumbId));
                if (is_dir($path)) {
                    foreach (new DirectoryIterator($path) as $fileInfo) {
                        if ($fileInfo->isDot()) continue;
                        if (($fileInfo->isDir()) or (($fileInfo->isFile()) && ($fileInfo->getExtension() == "txt") && ($fileInfo->getFilename() != $conf['start'].".txt") && ($fileInfo->getFilename() != "topbar.txt"))) {
                            $elements++;
                        }
                    }
                    if ($elements > 0) {
                        $bc_ns[$i] = getNS($crumbId);
                    }
                }
            }
            $JSINFO['plugin_twistienav']['bc_ns'] = $bc_ns;
        }
    }

    function handle_ajax_call(&$event, $params) {
        global $conf;

        if($event->data != 'plugin_twistienav') return;
        $event->preventDefault();
        $event->stopPropagation();

        $idx  = cleanID($_POST['idx']);
        $dir  = utf8_encodeFN(str_replace(':','/',$idx));

        $data = array();
        search($data,$conf['datadir'],'search_index',array('ns' => $idx),$dir);

        if (!plugin_isdisabled('pagetitle')) {
            $pagetitleHelper = plugin_load('helper', 'pagetitle');
        }

        if (count($data) != 0) {
            echo '<ul>';
            foreach($data as $item){
                if (strcmp(noNs($item['id']),$conf['start'])) {
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
                    } elseif ($conf['useheading'] && $title_tmp=p_get_first_heading($item['id'].':'.$conf['start'],FALSE)) {
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
