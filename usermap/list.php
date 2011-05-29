<?php

/**
 * Copyright (C) 2010 Justgizzmo.com
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/../');
require PUN_ROOT.'include/common.php';

// Load the profile.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/profile.php';

// do some checks first
if ($pun_user['g_read_board'] == '0' || $pun_user['g_view_users'] == '0' || $pun_user['g_um_view_map'] == '0')
	um_error($lang_common['No permission']);

// Set some headers
header("Content-type: application/json");

if (isset($_GET['id']))
{
	$id = intval($_GET['id']);

	if ($id < 2)
		um_error('invalid id');

	$extra_sql = ' u.id='.$id.' AND';
}
else
	$extra_sql = '';

$result = $db->query('SELECT u.*, g.g_id, g.g_user_title, g.g_um_icon FROM '.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE'.$extra_sql.' u.um_lat IS NOT NULL AND u.um_lng IS NOT NULL AND g.g_um_add_to_map = \'1\' ORDER BY username ASC') or um_error('Unable to get marker list.', __FILE__, __LINE__, $db->error());

$json = array();
while ($user = $db->fetch_assoc($result))
{
	if (isset($id) || isset($_GET['kml']))
	{
		// Username
		$username = '<a href="'.$pun_config['o_base_url'].'/profile.php?id='.$user['id'].'">'.pun_htmlspecialchars($user['username']).'</a>';

		$user_data = array();

		// Avatar
		if ($pun_config['o_avatars'] == '1')
		{
			$avatar_field = generate_avatar_markup($id);
			if ($avatar_field != '')
			{
				$user_data[] = '<dt>'.$lang_profile['Avatar'].'</dt>';
				$user_data[] = '<dd>'.$avatar_field.'</dd>';
			}
		}

		// Title
		$user_title_field = get_title($user);
		$user_data[] = '<dt>'.$lang_common['Title'].'</dt>';
		$user_data[] = '<dd>'.(($pun_config['o_censoring'] == '1') ? censor_words($user_title_field) : $user_title_field).'</dd>';

		// Realname
		if ($user['realname'] != '')
		{
			$user_data[] = '<dt>'.$lang_profile['Realname'].'</dt>';
			$user_data[] = '<dd>'.pun_htmlspecialchars(($pun_config['o_censoring'] == '1') ? censor_words($user['realname']) : $user['realname']).'</dd>';
		}

		// Website
		if ($user['url'] != '')
		{
			$user['url'] = pun_htmlspecialchars(($pun_config['o_censoring'] == '1') ? censor_words($user['url']) : $user['url']);
			$user_data[] = '<dt>'.$lang_profile['Website'].'</dt>';
			$user_data[] = '<dd><span class="website"><a href="'.$user['url'].'">'.$user['url'].'</a></span></dd>';
		}

		// Posts
		$posts_field = '';
		if ($pun_config['o_show_post_count'] == '1' || $pun_user['is_admmod'])
			$posts_field = forum_number_format($user['num_posts']);

		if ($pun_user['g_search'] == '1')
		{
			$quick_searches = array();
			if ($user['num_posts'] > 0)
			{
				$quick_searches[] = '<a href="search.php?action=show_user_topics&amp;user_id='.$id.'">'.$lang_profile['Show topics'].'</a>';
				$quick_searches[] = '<a href="search.php?action=show_user_posts&amp;user_id='.$id.'">'.$lang_profile['Show posts'].'</a>';
			}

			if (!empty($quick_searches))
				$posts_field .= (($posts_field != '') ? ' - ' : '').implode(' - ', $quick_searches);
		}

		if ($posts_field != '')
		{
			$user_data[] = '<dt>'.$lang_common['Posts'].'</dt>';
			$user_data[] = '<dd>'.$posts_field.'</dd>';
		}

		if ($user['num_posts'] > 0)
		{
			$user_data[] = '<dt>'.$lang_common['Last post'].'</dt>';
			$user_data[] = '<dd>'.format_time($user['last_post']).'</dd>';
		}

		// Registered
		$user_data[] = '<dt>'.$lang_common['Registered'].'</dt>';
		$user_data[] = '<dd>'.format_time($user['registered'], true).'</dd>';


		// The html
		ob_start();
?>
<h2><?php echo $username?></h2>
<dl>
	<?php echo implode("\n\t", $user_data)."\n"?>
</dl>
<?php

		$html = str_replace(array("\t","\n"),'',trim(ob_get_contents()));
		ob_end_clean();
	}

	// json for the info window
	$json[]	= array(
		'id' 		=> $user['id'],
		'name'		=> $user['username'],
		'point'		=> array($user['um_lat'],$user['um_lng']),
		'icon'		=> $user['g_um_icon'],
		'html'		=> isset($html)? $html: '',
	);
}

if (isset($_GET['kml']))
{
	// header('') FIlename?
	header("Content-type: application/vnd.google-earth.kml+xml");
	header('Content-Disposition: attachment; filename="usermap.kml"');

	// xml kml header
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<kml xmlns="http://www.opengis.net/kml/2.2">'."\n".'<Document>'."\n";

	$saved_icons = array();
	foreach ($json as $cur)
	{
		$icon = substr($cur['icon'],0,-4);
		$saved_icons[$cur['icon']] = $icon;
?>
	<Placemark id='<?php echo $cur['id']?>'>
		<name><?php echo $cur['name']?></name>
		<styleUrl>#style_<?php echo $icon?></styleUrl>
		<description><![CDATA[<?php echo $cur['html']?>]]></description>
		<Point>
			<coordinates><?php echo $cur['point'][1].','.$cur['point'][0] ?>,0</coordinates>
		</Point>
	</Placemark>
<?php

	}

	foreach ($saved_icons as $file => $icon)
	{
?>
	<Style id="style_<?php echo $icon?>">
		<IconStyle>
			<Icon>
				<href><?php echo $pun_config['o_base_url'].'/usermap/img/icons/'.$file?></href>
			</Icon>
		</IconStyle>
	</Style>
<?php
	}

	// dump($saved_icons);

	// the footer
	echo '</Document>'."\n".'</kml>';

}
else
	echo json_encode($json);

function um_error($message, $file = null, $line = null, $db_error = false)
{
	echo json_encode(array(
		'error'		=> array(
			'msg'	=> $message,
			'file'	=> $file,
			'line'	=> $line,
			'db'	=> $db_error
		)
	));

	exit;
}