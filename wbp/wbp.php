<?php
/*
Plugin Name: Wordpress' Blog Planetarium
Plugin URI: http://cesar.la/wbp/
Description: Turn your Wordpress blog into a blogs' planetarioum. 
Author: Cesar D. Rodas
Version: 1.0
Author URI: http://cesar.la/
*/ 

wbp_safe_include("http.php");
wbp_safe_include("rss.php");

function wbp_safe_include($page) {
    static $file=null;
    if ($file===null)$file=dirname(__FILE__);
    $page = $file."/".$page;
    if (!is_file($page)) {
        die("Error while trying to include $page and fails, please reinstall wbp plugin");
    } 
    include($page);
}

class wbp_blogs {
    function GetAll() {
        $blogs = get_option('wbp_blogs');
        return is_array($blogs) ? $blogs : array();
    }

    function GetNumber() {
        return count(wbp_blogs::GetAll());
    }

    function Add($url) {
        $blogs = wbp_blogs::GetAll();
        if (isSet($blogs[$url])) {
            $GLOBALS['msg'] = __("The blog already exists");
            return false;
        }
        $rss = new lastRss;
        $result = $rss->Get($url);
        if ($result===false) {
            $GLOBALS['msg'] = __("The page doesn't look as a blog");
            return false;
        } else  {
            $GLOBALS['msg'] = __("Added!");
        }
        $rss = isset($GLOBALS['frss']) ? $GLOBALS['frss'] : $url;
        $title = isset($result['title']) ? $result['title'] : $rss;
        $blogs[$url] = array("rss" => $rss,"title"=>$title);
        /* */
        update_option('wbp_blogs',$blogs);
        /* */
        wp_insert_link(array("link_name" => $title,"link_url"=>$url));
        return true;
    }
}

class wbp_html  {
    function wbp_html() {
        add_action('admin_menu', $this->fnc('menu_admin'));
        add_action('admin_notices', $this->fnc('warning'));
        $this->delete();
    }

    function warning() {
        if (wbp_blogs::GetNumber() == 0) {
            wbp_safe_include("view-warning.php");
        }
    }


    function menu_admin() {
        add_options_page('Blogs Planetarium', 'Blogs Planetarium', 8, __FILE__, wbp_html::fnc('admin') );
    } 

    function delete() {
        /* Delete ?*/
        if (isset($_GET['del'])) {
            $blogs = wbp_blogs::GetAll();
            foreach(array_keys($blogs) as $url) {
                if ($_GET['del']==md5($url)) {
                    unset($blogs[$url]);
                    break;
                }
            }
            update_option('wbp_blogs',$blogs);
        }
    }

    function admin() {
        if (isset($_POST['submit'])) {
            if (wbp_blogs::Add($_POST['url'],$GLOBALS['msg']))  {
                $GLOBALS['msg'] = __("Blog added!");
            }
        }
        wbp_safe_include("view-admin.php");
    }

    function exec() {
        if (isset($GLOBALS['argv']) || ($_GET['wbp_key'] == get_option('wbp_key') && $_SERVER['REMOTE_ADDR'] == get_option('wbp_ip'))) {
            wbp_safe_include("daemon.php");
            exit;
        }
    }

    function fnc($func) {
        return array('wbp_html',$func);
    }
}

/**
 *  Exec the plug-in
 *
 */
$c=new wbp_html;

?>
