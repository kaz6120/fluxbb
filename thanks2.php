<?php

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

// Load the thanks.php language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/thanks2.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/thanks2.php';
else
	require PUN_ROOT.'lang/English/thanks2.php';


if ($pun_user['g_can_thanks'] == '0')
	message($lang_thanks['No view']);

$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

if ($tid < 1 || $pid < 1)
	message($lang_common['Bad request']);
	
$result = $db->query('SELECT poster  FROM '.$db->prefix.'posts WHERE id='.$pid) or error('Unable to fetch topics info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$name_poster = $db->result($result);

if($name_poster == $pun_user['username'])
{
	redirect('viewtopic.php?pid='.$pid.'#p'.$pid, $lang_thanks2['Thanks redirect self']);
}
else
{
	$result = $db->query('SELECT thanks_by_id  FROM '.$db->prefix.'thanks2 WHERE post_id='.$pid) or error('Unable to fetch thanks info', __FILE__, __LINE__, $db->error());
	//if (!$db->num_rows($result))
		//message($lang_common['Bad request']);
	
	$num_thanks2 = $db->num_rows($result);
		
	for ($i = 0; $i < $num_thanks2; ++$i)
	{
		$thanks_by_id = $db->result($result, $i);
		if($thanks_by_id == $pun_user['id'])
			redirect('viewtopic.php?pid='.$pid, $lang_thanks2['Thanks redirect already']);
	}
	
	$db->query('INSERT INTO '.$db->prefix.'thanks2 (topic_id, post_id, thanks_by_id, thanks_by) VALUES('.$tid.', '.$pid.', '.$pun_user['id'].',  \''.$db->escape($pun_user['username']).'\')') or error('Unable to insert thanks', __FILE__, __LINE__, $db->error());
	redirect('viewtopic.php?pid='.$pid.'#p'.$pid, $lang_thanks2['Thanks redirect ok']);
}

?>