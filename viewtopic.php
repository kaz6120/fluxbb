<?php

/**
 * Copyright (C) 2008-2011 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/ap_poll.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1)
	message($lang_common['Bad request']);

// Load the viewtopic.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/thanks.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/thanks.php';
else
	require PUN_ROOT.'lang/English/thanks.php';
	
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/thanks2.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/thanks2.php';
else
	require PUN_ROOT.'lang/English/thanks2.php';

// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid)
{
	$result = $db->query('SELECT topic_id, posted FROM '.$db->prefix.'posts WHERE id='.$pid) or error('Unable to fetch topic ID', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	list($id, $posted) = $db->fetch_row($result);

	// Determine on what page the post is located (depending on $forum_user['disp_posts'])
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'posts WHERE topic_id='.$id.' AND posted<'.$posted) or error('Unable to count previous posts', __FILE__, __LINE__, $db->error());
	$num_posts = $db->result($result) + 1;

	$_GET['p'] = ceil($num_posts / $pun_user['disp_posts']);
}

// If action=new, we redirect to the first new post (if any)
else if ($action == 'new')
{
	if (!$pun_user['is_guest'])
	{
		// We need to check if this topic has been viewed recently by the user
		$tracked_topics = get_tracked_topics();
		$last_viewed = isset($tracked_topics['topics'][$id]) ? $tracked_topics['topics'][$id] : $pun_user['last_visit'];

		$result = $db->query('SELECT MIN(id) FROM '.$db->prefix.'posts WHERE topic_id='.$id.' AND posted>'.$last_viewed) or error('Unable to fetch first new post info', __FILE__, __LINE__, $db->error());
		$first_new_post_id = $db->result($result);

		if ($first_new_post_id)
		{
			header('Location: viewtopic.php?pid='.$first_new_post_id.'#p'.$first_new_post_id);
			exit;
		}
	}

	// If there is no new post, we go to the last post
	header('Location: viewtopic.php?id='.$id.'&action=last');
	exit;
}

// If action=last, we redirect to the last post
else if ($action == 'last')
{
	$result = $db->query('SELECT MAX(id) FROM '.$db->prefix.'posts WHERE topic_id='.$id) or error('Unable to fetch last post info', __FILE__, __LINE__, $db->error());
	$last_post_id = $db->result($result);

	if ($last_post_id)
	{
		header('Location: viewtopic.php?pid='.$last_post_id.'#p'.$last_post_id);
		exit;
	}
}


// AP Poll
else if ($action == 'ap_vote') 
{
	ap_poll_vote($id, $pun_user['id']);
}
// /AP Poll

// Fetch some info about the topic
if (!$pun_user['is_guest'])
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, t.last_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'topic_subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.first_post_id, t.last_post_id, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());

if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_topic = $db->fetch_assoc($result);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

// Can we or can we not post replies?
if ($cur_topic['closed'] == '0')
{
	if (($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1' || $is_admmod)
		$post_link = "\t\t\t".'<p class="postlink conr"><a href="post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a></p>'."\n";
	else
		$post_link = '';
}
else
{
	$post_link = $lang_topic['Topic closed'];

	if ($is_admmod)
		$post_link .= ' / <a href="post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a>';

	$post_link = '   <p class="postlink conr">'.$post_link.'</p>'."\n";
}


// Add/update this topic in our list of tracked topics
if (!$pun_user['is_guest'])
{
	$tracked_topics = get_tracked_topics();
	$tracked_topics['topics'][$id] = time();
	set_tracked_topics($tracked_topics);
}


// Determine the post offset (based on $_GET['p'])
$num_pages = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_user['disp_posts'] * ($p - 1);

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'viewtopic.php?id='.$id);


// Add relationship meta tags
$page_head = array();
$page_head['up'] = '<link rel="up" href="viewforum.php?id='.$cur_topic['forum_id'].'" title="'.pun_htmlspecialchars($cur_topic['forum_name']).'" />';

if ($num_pages > 1)
{
	if ($p > 1)
	{
		$page_head['first'] = '<link rel="first" href="viewtopic.php?id='.$id.'&amp;p=1" title="'.sprintf($lang_common['Page'], 1).'" />';
		$page_head['prev'] = '<link rel="prev" href="viewtopic.php?id='.$id.'&amp;p='.($p-1).'" title="'.sprintf($lang_common['Page'], $p-1).'" />';
	}
	if ($p < $num_pages)
	{
		$page_head['next'] = '<link rel="next" href="viewtopic.php?id='.$id.'&amp;p='.($p+1).'" title="'.sprintf($lang_common['Page'], $p+1).'" />';
		$page_head['last'] = '<link rel="last" href="viewtopic.php?id='.$id.'&amp;p='.$num_pages.'" title="'.sprintf($lang_common['Page'], $num_pages).'" />';
	}
}


if ($pun_config['o_censoring'] == '1')
	$cur_topic['subject'] = censor_words($cur_topic['subject']);


$quickpost = false;
if ($pun_config['o_quickpost'] == '1' &&
	($cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1')) &&
	($cur_topic['closed'] == '0' || $is_admmod))
{
	// Load the post.php language file
	require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

	$required_fields = array('req_email' => $lang_common['Email'], 'req_message' => $lang_common['Message']);
	$quickpost = true;
}

if (!$pun_user['is_guest'] && $pun_config['o_topic_subscriptions'] == '1')
{
	if ($cur_topic['is_subscribed'])
		// I apologize for the variable naming here. It's a mix of subscription and action I guess :-)
		$subscraction = "\t\t".'<p class="subscribelink clearb"><span>'.$lang_topic['Is subscribed'].' - </span><a href="misc.php?action=unsubscribe&amp;tid='.$id.'">'.$lang_topic['Unsubscribe'].'</a></p>'."\n";
	else
		$subscraction = "\t\t".'<p class="subscribelink clearb"><a href="misc.php?action=subscribe&amp;tid='.$id.'">'.$lang_topic['Subscribe'].'</a></p>'."\n";
}
else
	$subscraction = '';

if ($pun_config['o_feed_type'] == '1')
	$page_head['feed'] = '<link rel="alternate" type="application/rss+xml" href="extern.php?action=feed&amp;tid='.$id.'&amp;type=rss" title="'.$lang_common['RSS topic feed'].'" />';
else if ($pun_config['o_feed_type'] == '2')
	$page_head['feed'] = '<link rel="alternate" type="application/atom+xml" href="extern.php?action=feed&amp;tid='.$id.'&amp;type=atom" title="'.$lang_common['Atom topic feed'].'" />';

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_topic['forum_name']), pun_htmlspecialchars($cur_topic['subject']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

?>
<div class="linkst">
 <div class="inbox crumbsplus">
  <ul id="crumbs-top">
   <li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
   <li><a href="viewforum.php?id=<?php echo $cur_topic['forum_id'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
   <li><a href="viewtopic.php?id=<?php echo $id ?>"><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></a></li>
  </ul>
  <div class="pagepost">
    <p class="pagelink conl"><?php echo $paging_links ?></p>
    <?php echo $post_link ?>
<!-- AddToAny BEGIN -->
<p class="a2a_kit a2a_default_style">
<a class="a2a_dd" href="http://www.addtoany.com/share_save">シェア</a>
<span class="a2a_divider"></span>
<a class="a2a_button_facebook"></a>
<a class="a2a_button_twitter"></a>
</p>
<script type="text/javascript" src="http://static.addtoany.com/menu/page.js"></script>
<!-- AddToAny END -->
   </div>
  <div class="clearer"></div>
 </div>
</div>

<?php


require PUN_ROOT.'include/parser.php';

$post_count = isset($_GET['pcount']) ? intval($_GET['pcount']) : 0; // Keep track of post numbers

// Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$id.(isset($_GET['lpid']) ? ' AND id > '.intval($_GET['lpid']) : '').' ORDER BY id LIMIT '.$start_from.','.$pun_user['disp_posts']) or error('Unable to fetch post IDs', __FILE__, __LINE__, $db->error());

$post_ids = array();
for ($i = 0;$cur_post_id = $db->result($result, $i);$i++)
	$post_ids[] = $cur_post_id;

if (empty($post_ids))
	error('The post table and topic table seem to be out of sync!', __FILE__, __LINE__);

// AP Poll
$ap_current_poll = ap_poll_info($id, $pun_user['id']);
// /AP Poll

// Retrieve the posts (and their respective poster/online status)
$result = $db->query('SELECT u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.use_pm, u.num_posts, u.registered, u.admin_note, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.id IN ('.implode(',', $post_ids).') ORDER BY p.id', true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
while ($cur_post = $db->fetch_assoc($result))
{
	$post_count++;
	$user_avatar = '';
	$user_info = array();
	$user_contacts = array();
	$post_actions = array();
	$is_online = '';
	$signature = '';

$users_thanks = array();
	
	$result_thanks = $db->query('SELECT thanks_by, thanks_by_id FROM '.$db->prefix.'thanks WHERE post_id='.$cur_post['id']) or error('Unable to fetch thanks info', __FILE__, __LINE__, $db->error());
	while ($thanks = $db->fetch_assoc($result_thanks))
	{
		if ($pun_user['g_view_users'] == '1' && $thanks['thanks_by_id'] > 1)
			$users_thanks[] = '<a href="profile.php?id='.$thanks['thanks_by_id'].'">'.pun_htmlspecialchars($thanks['thanks_by']).'</a>';
		else
			$users_thanks[] = pun_htmlspecialchars($thanks['thanks_by']);
	}
	$num_thanks = count($users_thanks);
	
$users_thanks2 = array();
	
	$result_thanks2 = $db->query('SELECT thanks_by, thanks_by_id FROM '.$db->prefix.'thanks2 WHERE post_id='.$cur_post['id']) or error('Unable to fetch thanks info', __FILE__, __LINE__, $db->error());
	while ($thanks2 = $db->fetch_assoc($result_thanks2))
	{
		if ($pun_user['g_view_users'] == '1' && $thanks2['thanks_by_id'] > 1)
			$users_thanks2[] = '<a href="profile.php?id='.$thanks2['thanks_by_id'].'">'.pun_htmlspecialchars($thanks2['thanks_by']).'</a>';
		else
			$users_thanks2[] = pun_htmlspecialchars($thanks2['thanks_by']);
	}
	$num_thanks2 = count($users_thanks2);


	// If the poster is a registered user
	if ($cur_post['poster_id'] > 1)
	{
		if ($pun_user['g_view_users'] == '1')
			$username = '<a href="profile.php?id='.$cur_post['poster_id'].'">'.pun_htmlspecialchars($cur_post['username']).'</a>';
		else
			$username = pun_htmlspecialchars($cur_post['username']);

		$user_title = get_title($cur_post);

		if ($pun_config['o_censoring'] == '1')
			$user_title = censor_words($user_title);

		// Format the online indicator
		$is_online = ($cur_post['is_online'] == $cur_post['poster_id']) ? '<strong>'.$lang_topic['Online'].'</strong>' : '<span>'.$lang_topic['Offline'].'</span>';

		if ($pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0')
		{
			if (isset($user_avatar_cache[$cur_post['poster_id']]))
				$user_avatar = $user_avatar_cache[$cur_post['poster_id']];
			else
				$user_avatar = $user_avatar_cache[$cur_post['poster_id']] = generate_avatar_markup($cur_post['poster_id']);
		}

		// We only show location, register date, post count and the contact links if "Show user info" is enabled
		if ($pun_config['o_show_user_info'] == '1')
		{
			if ($cur_post['location'] != '')
			{
				if ($pun_config['o_censoring'] == '1')
					$cur_post['location'] = censor_words($cur_post['location']);

				$user_info[] = '<dd><span>'.$lang_topic['From'].' '.pun_htmlspecialchars($cur_post['location']).'</span></dd>';
			}

			$user_info[] = '<dd><span>'.$lang_topic['Registered'].' '.format_time($cur_post['registered'], true).'</span></dd>';

			if ($pun_config['o_show_post_count'] == '1' || $pun_user['is_admmod'])
				$user_info[] = '<dd><span>'.$lang_topic['Posts'].' '.forum_number_format($cur_post['num_posts']).'</span></dd>';

			// Now let's deal with the contact links (Email and URL)
			if ((($cur_post['email_setting'] == '0' && !$pun_user['is_guest']) || $pun_user['is_admmod']) && $pun_user['g_send_email'] == '1')
				$user_contacts[] = '<span class="email"><a href="mailto:'.$cur_post['email'].'">'.$lang_common['Email'].'</a></span>';
			else if ($cur_post['email_setting'] == '1' && !$pun_user['is_guest'] && $pun_user['g_send_email'] == '1')
				$user_contacts[] = '<span class="email"><a href="misc.php?email='.$cur_post['poster_id'].'">'.$lang_common['Email'].'</a></span>';

			if ($pun_config['o_pms_enabled'] == '1' && !$pun_user['is_guest'] && $pun_user['g_pm'] == '1' && $pun_user['use_pm'] == '1' && $cur_post['use_pm'] == '1')
			{
				$pid = isset($cur_post['poster_id']) ? $cur_post['poster_id'] : $cur_post['id'];
				$user_contacts[] = '<span class="email"><a href="pms_send.php?uid='.$pid.'">'.$lang_pms['PM'].'</a></span>';
			}

			if ($cur_post['url'] != '')
			{
				if ($pun_config['o_censoring'] == '1')
					$cur_post['url'] = censor_words($cur_post['url']);

				$user_contacts[] = '<span class="website"><a href="'.pun_htmlspecialchars($cur_post['url']).'">'.$lang_topic['Website'].'</a></span>';
			}
		}

		if ($pun_user['is_admmod'])
		{
			$user_info[] = '<dd><span><a href="moderate.php?get_host='.$cur_post['id'].'" title="'.$cur_post['poster_ip'].'">'.$lang_topic['IP address logged'].'</a></span></dd>';

			if ($cur_post['admin_note'] != '')
				$user_info[] = '<dd><span>'.$lang_topic['Note'].' <strong>'.pun_htmlspecialchars($cur_post['admin_note']).'</strong></span></dd>';
		}
	}
	// If the poster is a guest (or a user that has been deleted)
	else
	{
		$username = pun_htmlspecialchars($cur_post['username']);
		$user_title = get_title($cur_post);

		if ($pun_user['is_admmod'])
			$user_info[] = '<dd><span><a href="moderate.php?get_host='.$cur_post['id'].'" title="'.$cur_post['poster_ip'].'">'.$lang_topic['IP address logged'].'</a></span></dd>';

		if ($pun_config['o_show_user_info'] == '1' && $cur_post['poster_email'] != '' && !$pun_user['is_guest'] && $pun_user['g_send_email'] == '1')
			$user_contacts[] = '<span class="email"><a href="mailto:'.$cur_post['poster_email'].'">'.$lang_common['Email'].'</a></span>';
	}

	// Generation post action array (quote, edit, delete etc.)
	if (!$is_admmod)
	{
		if (!$pun_user['is_guest'])
			$post_actions[] = '<li class="postreport"><span><a href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a></span></li>';

		if ($cur_topic['closed'] == '0')
		{
			if ($cur_post['poster_id'] == $pun_user['id'])
			{
				if ((($start_from + $post_count) == 1 && $pun_user['g_delete_topics'] == '1') || (($start_from + $post_count) > 1 && $pun_user['g_delete_posts'] == '1'))
					$post_actions[] = '<li class="postdelete"><span><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a></span></li>';
				//if ($pun_user['g_edit_posts'] == '1')
					//$post_actions[] = '<li class="postedit"><span><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';
				if ($pun_user['g_edit_posts'] == '1') 
					$post_actions[] = '<li class="postedit"><span id="menu'.$cur_post['id'].'"><a onmouseover="ape_menu_hovered = true;" onmouseout="ape_menu_hovered = false;" onclick="if (ape_show_menu('.$cur_post['id'].')) {return true;} else {return false;}" href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';
			}

			if (($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1')
				$post_actions[] = '<li class="postquote"><span><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a></span></li>';

                        if (($cur_topic['post_replies'] == '' && $pun_user['g_can_thanks'] == '1'))
                                
				$post_actions[] = '<li class="postthanks2"><span><a href="thanks2.php?tid='.$id.'&amp;pid='.$cur_post['id'].'#p'.$cur_post['id'].'">'.$lang_thanks2['Say Thanks'].'</a></span></li>';
			
                        /*	
                        if (($cur_topic['post_replies'] == '' && $pun_user['g_can_thanks'] == '1'))
				$post_actions[] = '<li class="postthanks"><span><a href="thanks.php?tid='.$id.'&amp;pid='.$cur_post['id'].'">'.$lang_thanks['Say Thanks'].'</a></span></li>';
                        */
		}
	}
	else
	{
		$post_actions[] = '<li class="postreport"><span><a href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a></span></li>';
		$post_actions[] = '<li class="postdelete"><span><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a></span></li>';
		//$post_actions[] = '<li class="postedit"><span><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';
		//$post_actions[] = '<li class="postquote"><span><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a></span></li>';
		$post_actions[] = '<li class="postedit"><span id="menu'.$cur_post['id'].'"><a onmouseover="ape_menu_hovered = true;" onmouseout="ape_menu_hovered = false;" onclick="if (ape_show_menu('.$cur_post['id'].')) {return true;} else {return false;}" href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a></span></li>';
		$post_actions[] = '<li class="postquote"><span><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a></span></li>';    
		$post_actions[] = '<li class="postthanks2"><span><a href="thanks2.php?tid='.$id.'&amp;pid='.$cur_post['id'].'#p'.$cur_post['id'].'">'.$lang_thanks2['Say Thanks'].'</a></span><img src="img/thumbup.png" width="31" height="26" align="absbottom" oncontextmenu="return false"></li>';
		//$post_actions[] = '<li class="postthanks"><span><a href="thanks.php?tid='.$id.'&amp;pid='.$cur_post['id'].'">'.$lang_thanks['Say Thanks'].'</a></span></li>';
	}

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

	// Do signature parsing/caching
	if ($pun_config['o_signatures'] == '1' && $cur_post['signature'] != '' && $pun_user['show_sig'] != '0')
	{
		if (isset($signature_cache[$cur_post['poster_id']]))
			$signature = $signature_cache[$cur_post['poster_id']];
		else
		{
			$signature = parse_signature($cur_post['signature']);
			$signature_cache[$cur_post['poster_id']] = $signature;
		}
	}

	// Default Avatar by Gizzmo - Start
	if ($cur_post['poster_id'] > 1)
	{
		if ($user_avatar == '' && $pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0')
		{
			// was the default memeber avatar previosly found
			if (!isset($default_member_avatar))
			{
				// start with using the provided avatar
				$default_member_avatar = '<img src="'.$pun_config['o_base_url'].'/misc.php?gizz_default_avatar_img=1" width="64" height="64" alt="" />';

				// then look for a uploaded avatar
				foreach (array('jpg', 'gif', 'png') as $cur_type)
				{
					$path = $pun_config['o_avatars_dir'].'/member.'.$cur_type;

					if (file_exists(PUN_ROOT.$path) && $img_size = @getimagesize(PUN_ROOT.$path))
					{
						$default_member_avatar = '<img src="'.$pun_config['o_base_url'].'/'.$path.'" '.$img_size[3].' alt="" />';
						break;
					}
				}
			}

			// Set and cache $user_avatar with the default member avatar
			$user_avatar = $user_avatar_cache[$cur_post['poster_id']] = $default_member_avatar;
		}
	}
	else
	{
		// check and cache if the 'noguest' file exists
		if (!isset($use_guest_avatar))
			$use_guest_avatar = !file_exists($pun_config['o_avatars_dir'].'/noguest');

		if ($use_guest_avatar && $pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0')
		{
			// was the guest avatar previosly found
			if (!isset($default_guest_avatar))
			{
				// start with using the provided avatar
				$default_guest_avatar = '<img src="'.$pun_config['o_base_url'].'/misc.php?gizz_default_avatar_img=2" width="64" height="64" alt="" />';

				// then look for a uploaded avatar
				foreach (array('jpg', 'gif', 'png') as $cur_type)
				{
					$path = $pun_config['o_avatars_dir'].'/guest.'.$cur_type;

					if (file_exists(PUN_ROOT.$path) && $img_size = @getimagesize(PUN_ROOT.$path))
					{
						$default_guest_avatar = '<img src="'.$pun_config['o_base_url'].'/'.$path.'" '.$img_size[3].' alt="" />';
						break;
					}
				}
			}

			// Set $user_avatar with the default guest avatar
			$user_avatar = $default_guest_avatar;
		}
	}
	// Default Avatar by Gizzmo - END

?>
<div id="p<?php echo $cur_post['id'] ?>" class="blockpost<?php echo ($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?><?php if ($cur_post['id'] == $cur_topic['first_post_id']) echo ' firstpost'; ?><?php if ($post_count == 1) echo ' blockpost1'; ?>">
 <h2><span><span class="conr">#<?php echo ($start_from + $post_count) ?></span> <a href="viewtopic.php?pid=<?php echo $cur_post['id'].'#p'.$cur_post['id'] ?>"><?php echo format_time($cur_post['posted']) ?></a></span></h2>
  <div class="box">
   <div class="inbox">
    <div class="postbody">
     <div class="postleft">
      <dl>
       <dt><strong><?php echo $username ?></strong></dt>
       <dd class="usertitle"><strong><?php echo $user_title ?></strong></dd>
<?php if ($user_avatar != '') echo '       <dd class="postavatar">'.$user_avatar.'</dd>'."\n"; ?>
<?php if (count($user_info)) echo "       ".implode("\n       ", $user_info)."\n"; ?>
<?php if (count($user_contacts)) echo '       <dd class="usercontacts">'.implode(' ', $user_contacts).'</dd>'."\n"; ?>
<?php 
if ($post_count > 0) {
// Load the profile.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

    echo '       <dd><span><a href="search.php?action=show_user_topics&amp;user_id='. $cur_post['poster_id'] .'">' .$lang_profile['Show topics'].'</a></span></dd>' . "\n" . 
         '       <dd><span><a href="search.php?action=show_user_posts&amp;user_id='. $cur_post['poster_id'] .'">'.$lang_profile['Show posts'].'</a></span></dd>' . "\n";
}
?>
      </dl>
     </div>
     <div class="postright">
       <h3><?php if ($cur_post['id'] != $cur_topic['first_post_id']) echo $lang_topic['Re'].' '; ?><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></h3>
       <div class="postmsg" id="post<?php echo $cur_post['id'] ?>">
        <?php echo $cur_post['message']."\n" ?>
<?php if ($cur_post['edited'] != '') echo "\t\t\t\t\t\t".'<p class="postedit"><em>'.$lang_topic['Last edit'].' '.pun_htmlspecialchars($cur_post['edited_by']).' ('.format_time($cur_post['edited']).')</em></p>'."\n"; ?>
<?php if ($cur_post['id'] == $cur_topic['first_post_id']) ap_poll_display($id, $ap_current_poll) ?>
       </div>
<?php if ($signature != '') echo '     <div class="postsignature postmsg"><hr />'.$signature.'</div>'."\n"; ?>
      </div>
     </div>
    </div>
    <div class="inbox">
     <div class="postfoot clearb">
      <div class="postfootleft"><?php if ($cur_post['poster_id'] > 1) echo '<p>'.$is_online.'</p>'; ?></div>
<?php if (count($post_actions)) echo '      <div class="postfootright">'."\n       ".'<ul>'."\n        ".implode("\n        ", $post_actions)."\n       ".'</ul>'."\n      ".'</div>'."\n" ?>
   </div>
  </div>
 </div>
</div>

<?php if ($num_thanks2 != '0')
{
?>
<div class="thanks">
<img src="img/thumbup.png" width="31" height="26" align="absbottom" oncontextmenu="return false"> 
<?php
	if ( $num_thanks2 == '1')
		echo $lang_thanks2['Thanks one'].implode(', ', $users_thanks2);
	else
		echo sprintf($lang_thanks2['Thanks'], $num_thanks2).implode(', ', $users_thanks2);
?>
</div>
<?php
}
?>

<?php

}

?>
<div id="aqp"></div>
<div class="postlinksb">
 <div class="inbox crumbsplus">
  <div class="pagepost">
   <p class="pagelink conl"><?php echo $paging_links ?></p>
   <?php echo $post_link ?>
  </div>
  <ul id="crumbs-bottom">
   <li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
   <li><a href="viewforum.php?id=<?php echo $cur_topic['forum_id'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
   <li><a href="viewtopic.php?id=<?php echo $id ?>"><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></a></li>
</ul>
<?php echo $subscraction ?>
		<div class="clearer"></div>
	</div>
</div>

<?php

// Display quick post if enabled
if ($quickpost)
{

$cur_index = 1;

?>
<div id="quickpost" class="blockform">
 <h2><span><?php echo $lang_topic['Quick post'] ?></span></h2>
  <div class="box">
   <form id="quickpostform" method="post" action="post.php?tid=<?php echo $id ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
   <div class="inform">
    <fieldset>
     <legend><?php echo $lang_common['Write message legend'] ?></legend>
      <div class="infldset txtarea">
      <input type="hidden" name="form_sent" value="1" />
      <input type="hidden" name="form_user" value="<?php echo pun_htmlspecialchars($pun_user['username']) ?>" />
<?php if ($pun_config['o_topic_subscriptions'] == '1' && ($pun_user['auto_notify'] == '1' || $cur_topic['is_subscribed'])): ?>
      <input type="hidden" name="subscribe" value="1" />
<?php endif; ?>
<?php

if ($pun_user['is_guest'])
{
	$email_label = ($pun_config['p_force_guest_email'] == '1') ? '<strong>'.$lang_common['Email'].' <span>'.$lang_common['Required'].'</span></strong>' : $lang_common['Email'];
	$email_form_name = ($pun_config['p_force_guest_email'] == '1') ? 'req_email' : 'email';

?>
						<label class="conl required"><strong><?php echo $lang_post['Guest name'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><input type="text" name="req_username" value="<?php if (isset($_POST['req_username'])) echo pun_htmlspecialchars($username); ?>" size="25" maxlength="25" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
						<label class="conl<?php echo ($pun_config['p_force_guest_email'] == '1') ? ' required' : '' ?>"><?php echo $email_label ?><br /><input type="text" name="<?php echo $email_form_name ?>" value="<?php if (isset($_POST[$email_form_name])) echo pun_htmlspecialchars($email); ?>" size="50" maxlength="80" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
						<div class="clearer"></div>
<?php

	echo "\t\t\t\t\t\t".'<label class="required"><strong>'.$lang_common['Message'].' <span>'.$lang_common['Required'].'</span></strong><br />';
}
else
	echo "\t\t\t\t\t\t".'<label>';

?>
<textarea name="req_message" rows="7" cols="75" tabindex="<?php echo $cur_index++ ?>"></textarea></label>
<?php /* FluxToolBar */
if (file_exists(FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php'))
	include FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php';
else
{
	require_once PUN_ROOT.'include/cache_fluxtoolbar.php';
	generate_ftb_cache('quickform');
	require FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php';
}
?>
						<ul class="bblinks">
							<li><span><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a> <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a> <?php echo ($pun_config['p_message_bbcode'] == '1' && $pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a> <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
						</ul>
					</div>
				</fieldset>
			</div>
			<script type="text/javascript">
				var aqp_last_post_id = <?php echo $cur_topic['last_post_id'] ?>; 
				var aqp_post_count = <?php echo $start_from + $post_count ?>;
				var aqp_tid = <?php echo $id ?>;
			</script>
			<p class="buttons">
				<input type="submit" name="submit" onclick="if (aqp_post(this.form)) {return true;} else {return false;}" tabindex="<?php echo $cur_index++ ?>" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /> 
				<input type="submit" name="preview" value="<?php echo $lang_topic['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" />
				<span id="aqp-icon" style="background: url(img/ajax_quick_post/loading.gif) no-repeat; padding: 1px 8px; margin-left: 5px; display: none;"></span>
			</p>
		</form>
	</div>
</div>
<?php

}

// Increment "num_views" for topic
if ($pun_config['o_topic_views'] == '1')
	$db->query('UPDATE '.$db->prefix.'topics SET num_views=num_views+1 WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

$forum_id = $cur_topic['forum_id'];
$footer_style = 'viewtopic';
require PUN_ROOT.'footer.php';
