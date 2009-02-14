<?php

$rblog = new lastRss;
$blogs = wbp_blogs::GetAll();
foreach($blogs as $url => $blog) {
    echo "$url<br/>\n";
    flush();
    $content = $rblog->Get($blog['rss']);
    if ($content===false) {
        /* probably changes the RSS address?*/
        $content = $rblog->Get($url);
        if ($content===false) {
            /* page is down? */
            continue;
        }
        /* */
        $rss = isset($GLOBALS['frss']) ? $GLOBALS['frss'] : $url;
        $title = isset($result['title']) ? $result['title'] : $rss;
        $blogs[$url] = array("rss"=>$rss,"title"=>$title);
    }

    foreach($content['items'] as $item) {
        if (isset($item['atom:summary'])) { 
            $item['description'] = $item['atom:summary'];
        }
        $tags = isset($item['category']) ? $item['category'] : array();
        $date = isset($item['pubDate']) ? strtotime($item['pubDate']) : time();
        $npost = array();
        $npost['post_title']   = $item['title'];
        $npost['post_content'] = isset($item['content:encoded']) ?
        $item['content:encoded'] : $item['description']; 
        $npost['post_status']  = 'publish';
        $npost['post_author']  = 1;
        $npost['post_date']    = date("Y-m-d H:i:s",$date);
        $npost['tags_input']   = implode(",",$tags);
        $npost['post_content'] .= "<h2>".__("Read more at ").'<a href="'.$item['link'].'">'.$item['title'].'</a></h2>';
        wp_insert_post( $npost );
    }
}

/* something could change, so re-update it */
update_option('wbp_blogs',$blogs);
?>
