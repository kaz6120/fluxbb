<?php

/**
 * Copyright (C) 2008-2011 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if (isset($_GET['action']))
	define('PUN_QUIET_VISIT', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


// Load the misc.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/misc.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;


if ($action == 'rules')
{
	if ($pun_config['o_rules'] == '0' || ($pun_user['is_guest'] && $pun_user['g_read_board'] == '0' && $pun_config['o_regs_allow'] == '0'))
		message($lang_common['Bad request']);

	// Load the register.php language file
	require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_register['Forum rules']);
	define('PUN_ACTIVE_PAGE', 'rules');
	require PUN_ROOT.'header.php';

?>
<div id="rules" class="block">
	<div class="hd"><h2><span><?php echo $lang_register['Forum rules'] ?></span></h2></div>
	<div class="box">
		<div id="rules-block" class="inbox">
			<div class="usercontent"><?php echo $pun_config['o_rules_message'] ?></div>
		</div>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


else if ($action == 'markread')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission']);

	$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$pun_user['logged'].' WHERE id='.$pun_user['id']) or error('Unable to update user last visit data', __FILE__, __LINE__, $db->error());

	// Reset tracked topics
	set_tracked_topics(null);

	redirect('index.php', $lang_misc['Mark read redirect']);
}


// Mark the topics/posts in a forum as read?
else if ($action == 'markforumread')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission']);

	$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($fid < 1)
		message($lang_common['Bad request']);

	$tracked_topics = get_tracked_topics();
	$tracked_topics['forums'][$fid] = time();
	set_tracked_topics($tracked_topics);

	redirect('viewforum.php?id='.$fid, $lang_misc['Mark forum read redirect']);
}


else if (isset($_GET['email']))
{
	if ($pun_user['is_guest'] || $pun_user['g_send_email'] == '0')
		message($lang_common['No permission']);

	$recipient_id = intval($_GET['email']);
	if ($recipient_id < 2)
		message($lang_common['Bad request']);

	$result = $db->query('SELECT username, email, email_setting FROM '.$db->prefix.'users WHERE id='.$recipient_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	list($recipient, $recipient_email, $email_setting) = $db->fetch_row($result);

	if ($email_setting == 2 && !$pun_user['is_admmod'])
		message($lang_misc['Form email disabled']);


	if (isset($_POST['form_sent']))
	{
		// Clean up message and subject from POST
		$subject = pun_trim($_POST['req_subject']);
		$message = pun_trim($_POST['req_message']);

		if ($subject == '')
			message($lang_misc['No email subject']);
		else if ($message == '')
			message($lang_misc['No email message']);
		else if (pun_strlen($message) > PUN_MAX_POSTSIZE)
			message($lang_misc['Too long email message']);

		if ($pun_user['last_email_sent'] != '' && (time() - $pun_user['last_email_sent']) < $pun_user['g_email_flood'] && (time() - $pun_user['last_email_sent']) >= 0)
			message(sprintf($lang_misc['Email flood'], $pun_user['g_email_flood']));

		// Load the "form email" template
		$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/form_email.tpl'));

		// The first row contains the subject
		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = pun_trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = pun_trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
		$mail_message = str_replace('<sender>', $pun_user['username'], $mail_message);
		$mail_message = str_replace('<board_title>', $pun_config['o_board_title'], $mail_message);
		$mail_message = str_replace('<mail_message>', $message, $mail_message);
		$mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'].' '.$lang_common['Mailer'], $mail_message);

		require_once PUN_ROOT.'include/email.php';

		pun_mail($recipient_email, $mail_subject, $mail_message, $pun_user['email'], $pun_user['username']);

		$db->query('UPDATE '.$db->prefix.'users SET last_email_sent='.time().' WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

		redirect(htmlspecialchars($_POST['redirect_url']), $lang_misc['Email sent redirect']);
	}


	// Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to the users profile after the email is sent)
	if (!empty($_SERVER['HTTP_REFERER']))
	{
		$referrer = parse_url($_SERVER['HTTP_REFERER']);
		// Remove www subdomain if it exists
		if (strpos($referrer['host'], 'www.') === 0)
			$referrer['host'] = substr($referrer['host'], 4);

		$valid = parse_url(get_base_url());
		// Remove www subdomain if it exists
		if (strpos($valid['host'], 'www.') === 0)
			$valid['host'] = substr($valid['host'], 4);

		if ($referrer['host'] == $valid['host'] && preg_match('#^'.preg_quote($valid['path']).'/(.*?)\.php#i', $referrer['path']))
			$redirect_url = $_SERVER['HTTP_REFERER'];
	}

	if (!isset($redirect_url))
		$redirect_url = 'profile.php?id='.$recipient_id;

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Send email to'].' '.pun_htmlspecialchars($recipient));
	$required_fields = array('req_subject' => $lang_misc['Email subject'], 'req_message' => $lang_misc['Email message']);
	$focus_element = array('email', 'req_subject');
	define('PUN_ACTIVE_PAGE', 'index');
	require PUN_ROOT.'header.php';

?>
<div id="emailform" class="blockform">
	<h2><span><?php echo $lang_misc['Send email to'] ?> <?php echo pun_htmlspecialchars($recipient) ?></span></h2>
	<div class="box">
		<form id="email" method="post" action="misc.php?email=<?php echo $recipient_id ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Write email'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="redirect_url" value="<?php echo pun_htmlspecialchars($redirect_url) ?>" />
						<label class="required"><strong><?php echo $lang_misc['Email subject'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<input class="longinput" type="text" name="req_subject" size="75" maxlength="70" tabindex="1" /><br /></label>
						<label class="required"><strong><?php echo $lang_misc['Email message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
						<textarea name="req_message" rows="10" cols="75" tabindex="2"></textarea><br /></label>
						<p><?php echo $lang_misc['Email disclosure note'] ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="3" accesskey="s" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


else if (isset($_GET['report']))
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission']);

	$post_id = intval($_GET['report']);
	if ($post_id < 1)
		message($lang_common['Bad request']);

	if (isset($_POST['form_sent']))
	{
		// Clean up reason from POST
		$reason = pun_linebreaks(pun_trim($_POST['req_reason']));
		if ($reason == '')
			message($lang_misc['No reason']);
		else if (strlen($reason) > 65535) // TEXT field can only hold 65535 bytes
			message($lang_misc['Reason too long']);

		if ($pun_user['last_email_sent'] != '' && (time() - $pun_user['last_email_sent']) < $pun_user['g_email_flood'] && (time() - $pun_user['last_email_sent']) >= 0)
			message(sprintf($lang_misc['Report flood'], $pun_user['g_email_flood']));

		// Get the topic ID
		$result = $db->query('SELECT topic_id FROM '.$db->prefix.'posts WHERE id='.$post_id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$topic_id = $db->result($result);

		// Get the subject and forum ID
		$result = $db->query('SELECT subject, forum_id FROM '.$db->prefix.'topics WHERE id='.$topic_id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		list($subject, $forum_id) = $db->fetch_row($result);

		// Should we use the internal report handling?
		if ($pun_config['o_report_method'] == '0' || $pun_config['o_report_method'] == '2')
			$db->query('INSERT INTO '.$db->prefix.'reports (post_id, topic_id, forum_id, reported_by, created, message) VALUES('.$post_id.', '.$topic_id.', '.$forum_id.', '.$pun_user['id'].', '.time().', \''.$db->escape($reason).'\')' ) or error('Unable to create report', __FILE__, __LINE__, $db->error());

		// Should we email the report?
		if ($pun_config['o_report_method'] == '1' || $pun_config['o_report_method'] == '2')
		{
			// We send it to the complete mailing-list in one swoop
			if ($pun_config['o_mailing_list'] != '')
			{
				$mail_subject = sprintf($lang_common['Report notification'], $forum_id, $subject);
				$mail_message = sprintf($lang_common['Report message 1'], $pun_user['username'], get_base_url().'/viewtopic.php?pid='.$post_id.'#p'.$post_id)."\n";
				$mail_message .= sprintf($lang_common['Report message 2'], $reason)."\n";
				$mail_message .= "\n".'--'."\n".$lang_common['Email signature'];

				require PUN_ROOT.'include/email.php';

				pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
			}
		}

		$db->query('UPDATE '.$db->prefix.'users SET last_email_sent='.time().' WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

		redirect('viewtopic.php?pid='.$post_id.'#p'.$post_id, $lang_misc['Report redirect']);
	}

	// Fetch some info about the post, the topic and the forum
	$result = $db->query('SELECT f.id AS fid, f.forum_name, t.id AS tid, t.subject FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$post_id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$cur_post = $db->fetch_assoc($result);

	if ($pun_config['o_censoring'] == '1')
		$cur_post['subject'] = censor_words($cur_post['subject']);

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_misc['Report post']);
	$required_fields = array('req_reason' => $lang_misc['Reason']);
	$focus_element = array('report', 'req_reason');
	define('PUN_ACTIVE_PAGE', 'index');
	require PUN_ROOT.'header.php';

?>
<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_post['fid'] ?>"><?php echo pun_htmlspecialchars($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="viewtopic.php?pid=<?php echo $post_id ?>#p<?php echo $post_id ?>"><?php echo pun_htmlspecialchars($cur_post['subject']) ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_misc['Report post'] ?></strong></li>
		</ul>
	</div>
</div>

<div id="reportform" class="blockform">
	<h2><span><?php echo $lang_misc['Report post'] ?></span></h2>
	<div class="box">
		<form id="report" method="post" action="misc.php?report=<?php echo $post_id ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Reason desc'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<label class="required"><strong><?php echo $lang_misc['Reason'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br /><textarea name="req_reason" rows="5" cols="60"></textarea><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


else if ($action == 'subscribe')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission']);

	$topic_id = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
	$forum_id = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($topic_id < 1 && $forum_id < 1)
		message($lang_common['Bad request']);

	if ($topic_id)
	{
		if ($pun_config['o_topic_subscriptions'] != '1')
			message($lang_common['No permission']);

		// Make sure the user can view the topic
		$result = $db->query('SELECT 1 FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$topic_id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$result = $db->query('SELECT 1 FROM '.$db->prefix.'topic_subscriptions WHERE user_id='.$pun_user['id'].' AND topic_id='.$topic_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result))
			message($lang_misc['Already subscribed topic']);

		$db->query('INSERT INTO '.$db->prefix.'topic_subscriptions (user_id, topic_id) VALUES('.$pun_user['id'].' ,'.$topic_id.')') or error('Unable to add subscription', __FILE__, __LINE__, $db->error());

		redirect('viewtopic.php?id='.$topic_id, $lang_misc['Subscribe redirect']);
	}

	if ($forum_id)
	{
		if ($pun_config['o_forum_subscriptions'] != '1')
			message($lang_common['No permission']);

		// Make sure the user can view the forum
		$result = $db->query('SELECT 1 FROM '.$db->prefix.'forums AS f LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$pun_user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$forum_id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$result = $db->query('SELECT 1 FROM '.$db->prefix.'forum_subscriptions WHERE user_id='.$pun_user['id'].' AND forum_id='.$forum_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result))
			message($lang_misc['Already subscribed forum']);

		$db->query('INSERT INTO '.$db->prefix.'forum_subscriptions (user_id, forum_id) VALUES('.$pun_user['id'].' ,'.$forum_id.')') or error('Unable to add subscription', __FILE__, __LINE__, $db->error());

		redirect('viewforum.php?id='.$forum_id, $lang_misc['Subscribe redirect']);
	}
}


else if ($action == 'unsubscribe')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission']);

	$topic_id = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
	$forum_id = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($topic_id < 1 && $forum_id < 1)
		message($lang_common['Bad request']);

	if ($topic_id)
	{
		if ($pun_config['o_topic_subscriptions'] != '1')
			message($lang_common['No permission']);

		$result = $db->query('SELECT 1 FROM '.$db->prefix.'topic_subscriptions WHERE user_id='.$pun_user['id'].' AND topic_id='.$topic_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_misc['Not subscribed topic']);

		$db->query('DELETE FROM '.$db->prefix.'topic_subscriptions WHERE user_id='.$pun_user['id'].' AND topic_id='.$topic_id) or error('Unable to remove subscription', __FILE__, __LINE__, $db->error());

		redirect('viewtopic.php?id='.$topic_id, $lang_misc['Unsubscribe redirect']);
	}

	if ($forum_id)
	{
		if ($pun_config['o_forum_subscriptions'] != '1')
			message($lang_common['No permission']);

		$result = $db->query('SELECT 1 FROM '.$db->prefix.'forum_subscriptions WHERE user_id='.$pun_user['id'].' AND forum_id='.$forum_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_misc['Not subscribed forum']);

		$db->query('DELETE FROM '.$db->prefix.'forum_subscriptions WHERE user_id='.$pun_user['id'].' AND forum_id='.$forum_id) or error('Unable to remove subscription', __FILE__, __LINE__, $db->error());

		redirect('viewforum.php?id='.$forum_id, $lang_misc['Unsubscribe redirect']);
	}
}

// Default Avatar by Gizzmo - Start
else if (isset($_GET['gizz_default_avatar_img']))
{
	$avatar = intval($_GET['gizz_default_avatar_img']);
	$avatars = array(
		// User
		1 => array(
			'type' => 'png',
			'size' => '4322',
			'code' => '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'
		),
		// Guest
		2 => array(
			'type' => 'png',
			'size' => '5959',
			'code' => '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'
		)
	);

	// make sure a non defined images isnt tried to display
	if (array_key_exists($avatar, $avatars))
	{
		header("Content-type: image/".$avatars[$avatar]['type']);
		header("Content-length: ".$avatars[$avatar]['size']);
		exit(base64_decode($avatars[$avatar]['code']));
	}
}
// Default Avatar by Gizzmo - END

else
	message($lang_common['Bad request']);
