<?php

//
// Last Topics mod by adaur
//

// Make sure no one attempts to run this script "directly"... it would be bad
if (!defined('PUN'))
	exit;
	
// Load the viewforum.php language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php'))
       require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';
else
       require PUN_ROOT.'lang/English/forum.php';
	   
	$show = isset($show) ? $show : NULL;

    if ($show < '1' || $show > '50' || $show == '') $show = '5'; // Don't mess up with the numbers :P
    
	if ($pun_user['g_id'] == PUN_ADMIN)
    {
		$result = $db->query('SELECT t.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.num_views, t.moved_to, t.forum_id, t.sticky FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=3) WHERE t.moved_to IS NULL ORDER BY t.sticky DESC, t.last_post DESC LIMIT '.$show) or error('Unable to get the admin\'s topic list', __FILE__, __LINE__, $db->error());
    }   
	elseif ($pun_user['is_guest'])
	{
		$result = $db->query('SELECT t.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.num_views, t.moved_to, t.forum_id, t.sticky FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=3) WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL ORDER BY t.sticky DESC, t.last_post DESC LIMIT '.$show) or error('Unable to get the guest\'s topic list', __FILE__, __LINE__, $db->error());
	}
    else
    {
		$result = $db->query('SELECT t.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.num_views, t.moved_to, t.forum_id, t.sticky FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL ORDER BY t.sticky DESC, t.last_post DESC LIMIT '.$show) or error('Unable to get the member\'s topic list', __FILE__, __LINE__, $db->error());
    }
	?>			
 <div id="idx1" class="blocktable">
  <h2><span><?php echo $lang_common['Last topics'] ?></span></h2>
   <div class="box">
   <div class="inbox">
    <table cellspacing="0">
     <thead>
      <tr>
       <th class="tcl" scope="col"><?php echo $lang_common['Topic']; ?></th>
       <th class="tc2" scope="col"><?php echo $lang_common['Replies'] ?></th>
       <?php if ($pun_config['o_topic_views'] == '1'): ?> <th class="tc3" scope="col"><?php echo $lang_forum['Views'] ?></th> <?php endif; ?>
       <th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
      </tr>
     </thead>
    <tbody>
		<?php
		while ($cur_topic = $db->fetch_assoc($result)) {
		
			if ($pun_config['o_censoring'] == '1')
				$cur_topic['subject'] = censor_words($cur_topic['subject']);

			$date = format_time($cur_topic['posted']);
			
            // Sticky subject
            $status_text = ($cur_topic['sticky'] == '1') ? '<span class="stickytext">'.$lang_forum['Sticky'].'</span> ' : '';

			$subject = $status_text . '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';
			
			if (!$pun_user['is_guest'])
			{
				$tracked_topics = get_tracked_topics();
			}
			
			if (!$pun_user['is_guest'] && $cur_topic['last_post'] > $pun_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$cur_topic['forum_id']]) || $tracked_topics['forums'][$cur_topic['forum_id']] < $cur_topic['last_post']) && $cur_topic['moved_to'] == null)
			{
				$item_status = ' inew';
				$icon_type = 'icon icon-new';
				$subject = '<strong>'.$subject.'</strong>';
			}
			else
			{
				$item_status = '';
				$icon_type = 'icon';
			}
			?>
     <tr<?php if ($item_status != '') echo ' class="'.trim($item_status).'"'; ?>>
      <td class="tcl">
       <div class="intd">
        <div class="<?php echo $icon_type ?>"><div class="nosize"></div></div>
        <div class="tclcon"><?php echo $subject; ?></div>
       </div>
      </td>
      <td class="tc2"><?php echo $cur_topic['num_replies'] ?></td>
      <?php if ($pun_config['o_topic_views'] == '1'): ?> <td class="tc3"><?php echo $cur_topic['num_views'] ?></td> <?php endif; ?><td class="tcr"><?php echo '<a href="viewtopic.php?pid='.$cur_topic['last_post_id'].'#p'.$cur_topic['last_post_id'].'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['last_poster']).'</span>'; ?></td>
      </tr>
		<?php
		} // That's all folks!
		?>
    </tbody>
   </table>
  </div>
 </div>
</div>
