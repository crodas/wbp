<?php
/*
 ======================================================================
 lastRSS 0.9.1
 
 Simple yet powerfull PHP class to parse RSS files.
 
 by Vojtech Semecky, webmaster @ oslab . net
 
 Latest version, features, manual and examples:
     http://lastrss.oslab.net/

 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 lf the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ======================================================================
*/

/**
* lastRSS
* Simple yet powerfull PHP class to parse RSS files.
*/
class lastRSS {
    // -------------------------------------------------------------------
    // Public properties
    // -------------------------------------------------------------------
    var $default_cp = 'UTF-8';
    var $CDATA = 'nochange';
    var $cp = '';
    var $items_limit = 0;
    var $stripHTML = False;
    var $date_format = '';
    var $cache_dir='';

    // -------------------------------------------------------------------
    // Private variables
    // -------------------------------------------------------------------
    var $channeltags = array ('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
    var $itemtags = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source','atom:summary','content:encoded');
    var $imagetags = array('title', 'url', 'link', 'width', 'height');
    var $textinputtags = array('title', 'description', 'name', 'link');

    // -------------------------------------------------------------------
    // Parse RSS file and returns associative array.
    // -------------------------------------------------------------------
    function Get ($rss_url) {
        // If CACHE ENABLED
        if ($this->cache_dir != '') {
            $cache_file = $this->cache_dir . '/rsscache_' . md5($rss_url);
            $timedif = @(time() - filemtime($cache_file));
            if ($timedif < $this->cache_time) {
                // cached file is fresh enough, return cached array
                $result = unserialize(join('', file($cache_file)));
                // set 'cached' to 1 only if cached file is correct
                if ($result) $result['cached'] = 1;
            } else {
                // cached file is too old, create new
                $result = $this->Parse($rss_url);
                $serialized = serialize($result);
                if ($f = @fopen($cache_file, 'w')) {
                    fwrite ($f, $serialized, strlen($serialized));
                    fclose($f);
                }
                if ($result) $result['cached'] = 0;
            }
        }
        // If CACHE DISABLED >> load and parse the file directly
        else {
            $result = $this->Parse($rss_url);
            if ($result) $result['cached'] = 0;
        }
        // return result
        return $result;
    }
    
    // -------------------------------------------------------------------
    // Modification of preg_match(); return trimed field with index 1
    // from 'classic' preg_match() array output
    // -------------------------------------------------------------------
    function my_preg_match ($pattern, $subject) {
        // start regullar expression
        preg_match($pattern, $subject, $out);

        // if there is some result... process it and return it
        if(isset($out[1])) {
            // Process CDATA (if present)
            $out[1] = str_replace(array("<![CDATA[","]]>"),array("",""),$out[1]);
            if ($this->CDATA == 'content') { // Get CDATA content (without CDATA tag)
                $out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
            } elseif ($this->CDATA == 'strip') { // Strip CDATA
                $out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
            }

            // If code page is set convert character encoding to required
            if ($this->cp != '')
                //$out[1] = $this->MyConvertEncoding($this->rsscp, $this->cp, $out[1]);
                $out[1] = iconv($this->rsscp, $this->cp.'//TRANSLIT', $out[1]);
            // Return result
            return trim($out[1]);
        } else {
        // if there is NO result, return empty string
            return '';
        }
    }

    function my_preg_match_all ($pattern, $subject) {
        preg_match_all($pattern, $subject, $out);
        $return = array();
        if(isset($out[0][0])) {
            $out[0][0] = str_replace(array("<![CDATA[","]]>"),array("",""),$out[0][0]);
            foreach(explode("\n",$out[0][0]) as $found) {
                if (trim(strip_tags($found))=="") continue;
                $return[] = html_entity_decode(trim(strip_tags($found)),ENT_QUOTES);
            }
        }
        return $return;
    }
    // -------------------------------------------------------------------
    // Replace HTML entities &something; by real characters
    // -------------------------------------------------------------------
    function unhtmlentities ($string) {
        // Get HTML entities table
        $trans_tbl = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES);
        // Flip keys<==>values
        $trans_tbl = array_flip ($trans_tbl);
        // Add support for &apos; entity (missing in HTML_ENTITIES)
        $trans_tbl += array('&apos;' => "'");
        // Replace entities by values
        return html_entity_decode(strtr ($string, $trans_tbl),ENT_QUOTES);
    }

    function get_html($url) {
        $http=new http_class;
        $http->timeout=0;
        $http->data_timeout=0;
        $http->debug=0;
        $http->html_debug=1;
        $http->user_agent="WBP/1.0 (http://cesar.la/wbp; Cesar Rodas)";
        $http->follow_redirect=1;
        $http->redirection_limit=5;
        $http->exclude_address="";
        $http->request_method= "GET";
        /* */
        $http->GetRequestArguments($url,$arguments); 
        /* */
        $err = $http->Open($arguments);
        if ($err!="") return false;
        /* */
        $arguments['Headers']['Accept-encoding'] = "gzip"; 
        /* */
        $err = $http->SendRequest($arguments); 
        if ($err!="") return false;
        $err = $http->ReadReplyHeaders($headers);
        if ($err!="") return false;

        /* */
        if ($http->response_status != 200) { 
            $http->Close();
            return false;
        }
        /* */
        $buffer="";
        while (1) {
            $err=$http->ReadReplyBody($r,1024);
            if ($err!="") { return false; }
            if ($r=="") break;
            $buffer .= $r; 
        }
        $http->Close();
        if (isset($headers['content-encoding']) && 
            strtolower($headers['content-encoding']) == 'gzip') 
        { 
            $buffer = substr($buffer, 10); 
            $buffer = gzinflate($buffer);
        }
        return $buffer;
    }

    // -------------------------------------------------------------------
    // Parse() is private method used by Get() to load and parse RSS file.
    // Don't use Parse() in your scripts - use Get($rss_file) instead.
    // -------------------------------------------------------------------
    function Parse ($rss_url,$auto=true) {
        // Open and load RSS file
        if ($rss_content=$this->get_html($rss_url)) {
            // Parse document encoding
            $result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si", $rss_content);
            // if document codepage is specified, use it
            if ($result['encoding'] != '')
                { $this->rsscp = $result['encoding']; } // This is used in my_preg_match()
            // otherwise use the default codepage
            else
                { $this->rsscp = $this->default_cp; } // This is used in my_preg_match()

            // Parse CHANNEL info
            /*preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);*/
            foreach($this->channeltags as $channeltag)
            {
                //if (count($out_channel)==0) continue;
                $temp = $this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'smi", $rss_content);
                if ($temp != '') $result[$channeltag] = $temp; // Set only if not empty
            }
            // If date_format is specified and lastBuildDate is valid
            if ($this->date_format != '' && ($timestamp = strtotime($result['lastBuildDate'])) !==-1) {
                        // convert lastBuildDate to specified date format
                        $result['lastBuildDate'] = date($this->date_format, $timestamp);
            }

            // Parse TEXTINPUT info
            preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $rss_content, $out_textinfo);
                // This a little strange regexp means:
                // Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
            if (isset($out_textinfo[2])) {
                foreach($this->textinputtags as $textinputtag) {
                    $temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
                    if ($temp != '') $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
                }
            }
            // Parse IMAGE info
            preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
            if (isset($out_imageinfo[1])) {
                foreach($this->imagetags as $imagetag) {
                    $temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
                    if ($temp != '') $result['image_'.$imagetag] = $temp; // Set only if not empty
                }
            }
            // Parse ITEMS
            preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
            $rss_items = $items[2];
            $i = 0;
            $result['items'] = array(); // create array even if there are no items
            foreach($rss_items as $rss_item) {
                // If number of items is lower then limit: Parse one item
                if ($i < $this->items_limit || $this->items_limit == 0) {
                    foreach($this->itemtags as $itemtag) {
                        $temp = $this->my_preg_match("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
                        if ($temp != '') $result['items'][$i][$itemtag] = $temp; // Set only if not empty
                    }
                    $result['items'][$i]['category'] = $this->my_preg_match_all("'<category.*>(.*?)</category>'si",$rss_item);
                    // Strip HTML tags and other bullshit from DESCRIPTION
                    if ($this->stripHTML && $result['items'][$i]['description'])
                        $result['items'][$i]['description'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['description'])));
                    // Strip HTML tags and other bullshit from TITLE
                    if ($this->stripHTML && $result['items'][$i]['title'])
                        $result['items'][$i]['title'] = strip_tags($this->unhtmlentities(strip_tags($result['items'][$i]['title'])));
                    // If date_format is specified and pubDate is valid
                    if ($this->date_format != '' && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !==-1) {
                        // convert pubDate to specified date format
                        $result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
                    }
                    // Item counter
                    $i++;
                }
            }

            $result['items_count'] = $i;
            if ($auto && (!isset($result['title']) ||  $result['items_count'] == 0)) {
                $rss = $this->autoDiscovery($rss_content);
                if ($rss == false) return false;
                return $this->Parse($rss,false);
            }
            return $result;
        }
        else // Error in opening return False
        {
            return False;
        }
    }

    function autoDiscovery($content) {
        $return = false;
        $pattern = "/<link(.*?)>/i";
        preg_match_all($pattern,$content,$content);
        if (! is_array($content) ) return false;
        foreach($content[0] as $info) {                
            preg_match("/type\s*=\s*['|\"|\s*](.*?)['|\"|>]/i",$info,$type);
            preg_match("/rel\s*=\s*['|\"|\s*](.*?)['|\"|>]/i",$info,$rel);
            /*
            **    "rel" property must be 'alternate' if exists
            **    "type" must be 'application/rss+xml'         
            */
            if (
                (!isset($rel[1]) || strtolower($rel[1]) == 'alternate') && 
                strtolower($type[1]) == 'application/rss+xml'
            ) { /* The RSS address is found! */
                preg_match("/href\s*=\s*['|\"|\s*](.*?)['|\"|>]/i",$info,$href);
                $return = $href[1];
                $GLOBALS['frss'] = $return;
                break; /* why search more? */
            } 
        }
        return $return; 
    }
}

?>
