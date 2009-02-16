<div class="wrap">
<h2><?php _e('Configure your WBP')?></h2>
<p>
<?php _e('Hey dude, you\'ve installed your WBP well');?> 
</p>
<form method="post">
Key: <input type="input" name="key" value="<?php echo wbp_html::get_key()?>" /><br/>
<?php if (wbp_html::get_crawl_link()): ?>
<p>Todays crawl URL: <a href="<?php echo wbp_html::get_crawl_link();?>"><?php echo wbp_html::get_crawl_link()?></a></p>
<?php endif; ?>
<input type="submit" value="Set key" />
</form>
<h2><?php _e('Blogs at the Planetarium')?></h2>
<?php echo $GLOBALS['msg'] ?>
<form method="post">
    <input type="input" name="url" value="http://" />
    <input type="submit" name="submit" value="Add blog!" />
</form>
<br />
<!-- I am not good in design, so I copy it -->
<table class="widefat" id="active-plugins-table">
	<thead>
	<tr>
		<th scope="col"><?php _e("Title");?></th>
		<th scope="col"><?php _e("URL");?></th>
		<th scope="col"><?php _e("RSS");?></th>
        <th></th>
	</tr>
	</thead>
	<tbody class="plugins">
    <?php foreach(wbp_blogs::GetAll() as $url=>$blog) : ?>
	<tr class='active'>
		<td class='name'><a href="<?php echo $url?>"><?php echo $blog['title']?></a></td>
		<td class='url'><?php echo $url?></td>
		<td class='rss'><?php echo $blog['rss']?></td>
		<td class='togl action-links'><a href="options-general.php?page=wbp/wbp.php&del=<?php echo md5($url);?>" class="delete"><?php _e("Delete it!");?></a></td>
	</tr><?php endforeach; ?>	</tbody>
</table>

</div>
