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
        // Store $conf['start'] setting value
        $JSINFO['conf']['start'] = $conf['start'];
        // List the number of sub-namespaces and pages for each "youarehere" namespace (excluding start page)
        if ($conf['youarehere']) {
            $parts = explode(':', $ID);
            $count = count($parts);
            $part = '';
            for($i = 0; $i < $count - 1; $i++) {
                $part .= $parts[$i].':';
                if ($part == $conf['start']) continue; // Skip startpage
                array_push($namespaces, rtrim($part, ":"));
            }
        }
        // List the number of sub-namespaces and pages for each namespace in breadcrumbs (excluding start page)
        if ($conf['breadcrumbs']) {
            $crumbs = breadcrumbs();
            // get namespaces currently in $crumbs
            foreach ($crumbs as $crumbId => $crumb) {
                if (getNS($crumbId) != null) {
                    array_push($namespaces, getNS($crumbId));
                }
            }
        }
        // Cleanup multiple values in $namespaces
        $namespaces = array_unique($namespaces);
//dbg($namespaces);
        if (count($namespaces > 0)) {
            foreach ($namespaces as $namespace) {
                $elements = 0;
                $path = $conf['savedir']."/pages/".str_replace(":", "/", $namespace);
                foreach (new DirectoryIterator($path) as $fileInfo) {
                    if ($fileInfo->isDot()) continue;
                    if (($fileInfo->isDir()) or (($fileInfo->isFile()) && ($fileInfo->getExtension() == "txt") && ($fileInfo->getFilename() != $conf['start'].".txt"))) {
                        $elements++;
                    }
                }
                $JSINFO['plugin_twistienav']['ns_elements'][$namespace] = $elements;
            }
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

       if (count($data) != 0) {
            echo '<ul>';
            foreach($data as $item){
                if (strcmp(noNs($item['id']),$conf['start'])) {
                    if (p_get_metadata($item['id'], 'plugin_croissant_bctitle') != null) {
                        $title = p_get_metadata($item['id'], 'plugin_croissant_bctitle');
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
