<?php

/**
 * Copyright (C) 2010 Justgizzmo.com
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script 'directly'
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

// Load the a language file
if (file_exists(PUN_ROOT.'/usermap/lang/'.$admin_language.'_admin.php'))
	require PUN_ROOT.'usermap/lang/'.$admin_language.'_admin.php';
else
	require PUN_ROOT.'usermap/lang/English_admin.php';

// This plugin's url
$plugin_url = 'admin_loader.php?plugin=AP_Usermap_Settings.php';

// Update group
if (isset($_POST['save_group']))
{
	confirm_referrer($plugin_url);

	// Is this the admin group? (special rules apply)
	$is_admin_group = (isset($_POST['group_id']) && $_POST['group_id'] == PUN_ADMIN) ? true : false;

	$um_view_map = (isset($_POST['um_view_map']) && $_POST['um_view_map'] == '1') || $is_admin_group ? '1' : '0';
	$um_add_to_map = ((isset($_POST['um_add_to_map']) && $_POST['um_add_to_map'] == '1') || $is_admin_group) && $um_view_map == '1' ? '1' : '0';
	$um_icon = (isset($_POST['um_icon']))? pun_trim($_POST['um_icon']): 'white.png';

	// make sure the selected icon actully exists
	if (!file_exists(PUN_ROOT.'usermap/img/icons/'.$um_icon))
		$um_icon = 'white.png';

	$db->query('UPDATE '.$db->prefix.'groups SET g_um_view_map='.$um_view_map.', g_um_add_to_map='.$um_add_to_map.', g_um_icon=\''.$db->escape($um_icon).'\' WHERE g_id='.intval($_POST['group_id'])) or error('Unable to update group', __FILE__, __LINE__, $db->error());

	redirect($plugin_url, $lang_usermap_admin['Group updated redirect']);
}

// group page
else if (isset($_GET['edit_group']))
{
	$group_id = intval($_GET['edit_group']);
	if ($group_id < 1)
		message($lang_common['Bad request']);

	$result = $db->query('SELECT g_id, g_title, g_um_icon, g_um_view_map, g_um_add_to_map FROM '.$db->prefix.'groups WHERE g_id='.$group_id) or error('Unable to fetch user group info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$group = $db->fetch_assoc($result);

	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script>
<script type='text/javascript'>
$(function(){
	$('#um_icon_select').change(function(){
		var icon = $(this).children('option:selected').attr('value');
		$('#um_icon').attr('src', '<?php echo $pun_config['o_base_url'] ?>/usermap/img/icons/'+icon);
	});
});
</script>
	<div class='blockform'>
		<h2><span><?php echo $lang_usermap_admin['User map settings']?></span></h2>
		<div class='box'>
			<form id='usermap_groups' method='post' action='<?php echo $plugin_url?>'>
				<div class='inform'>
					<input type='hidden' name='group_id' value='<?php echo $group['g_id'] ?>' />
					<fieldset>
						<legend><?php echo sprintf($lang_usermap_admin['Cur group'], $group['g_title']);?></legend>
						<div class='infldset'>
							<table class='aligntop' cellspacing='0'>
<?php if ($group['g_id'] != PUN_GUEST): ?>								<tr>
									<th scope='row'><?php echo $lang_usermap_admin['User map icon']?></th>
									<td>
										<img id='um_icon' src='<?php echo $pun_config['o_base_url'].'/usermap/img/icons/'.$group['g_um_icon']?>'/>
										<select id='um_icon_select' name='um_icon'>
<?php
	foreach (glob(PUN_ROOT.'usermap/img/icons/*.{png,jpg,jpeg,gif}',GLOB_BRACE) as $icon)
	{
		$entry = basename($icon);

		echo "\t\t\t\t\t\t\t\t\t\t".'<option value=\''.$entry.'\''.(($group['g_um_icon'] == $entry )?' selected=\'selected\'': '').'>'.$entry.'</option>'."\n";
	}
?>
										</select>
										<span><?php echo $lang_usermap_admin['User map icon info']?></span>
									</td>
								</tr>
<?php endif; if ($group['g_id'] != PUN_ADMIN): ?>								<tr>
									<th scope='row'><?php echo $lang_usermap_admin['User map viewing']?></th>
									<td>
										<input type='radio' name='um_view_map' value='1'<?php if ($group['g_um_view_map'] == '1') echo ' checked=\'checked\'' ?> />&nbsp;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&nbsp;&nbsp;
										<input type='radio' name='um_view_map' value='0'<?php if ($group['g_um_view_map'] == '0') echo ' checked=\'checked\'' ?> />&nbsp;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_usermap_admin['User map viewing info']?></span>
									</td>
								</tr>
<?php if ($group['g_id'] != PUN_GUEST): ?>								<tr>
									<th scope='row'><?php echo $lang_usermap_admin['User map add to']?></th>
									<td>
										<input type='radio' name='um_add_to_map' value='1'<?php if ($group['g_um_add_to_map'] == '1') echo ' checked=\'checked\'' ?> />&nbsp;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&nbsp;&nbsp;
										<input type='radio' name='um_add_to_map' value='0'<?php if ($group['g_um_add_to_map'] == '0') echo ' checked=\'checked\'' ?> />&nbsp;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_usermap_admin['User map add to info']?></span>
									</td>
								</tr>
<?php endif; endif;?>							</table>
						</div>
					</fieldset>
				</div>
				<p class='submitend'><input type='submit' name='save_group' value='<?php echo $lang_admin_common['Save'] ?>' tabindex='26' /></p>
			</form>
		</div>
	</div>

<?php

}

else if (isset($_POST['save_options']))
{
	confirm_referrer($plugin_url);

	$form = array(
		'um_default_lat'		=> floatval($_POST['form']['um_default_lat']),
		'um_default_lng'		=> floatval($_POST['form']['um_default_lng']),
		'um_default_zoom'		=> intval($_POST['form']['um_default_zoom']),
		'um_height'				=> intval($_POST['form']['um_height']),
		'um_fit_map'			=> $_POST['form']['um_fit_map'] != '1' ? '0' : '1',
	);

	// if varibles were empty use the default
	if (($form['um_default_lng'] == '') && ($form['um_default_lat'] == '')) $form['um_default_zoom'] = 0;
	if ($form['um_default_lat'] == '') $form['um_default_lat'] = 0;
	if ($form['um_default_lng'] == '') $form['um_default_lng'] = 0;
	if ($form['um_height'] == '') $form['um_default_lng'] = 500;

	// Make sure the height is between 30 and 1000
	if ($form['um_height'] < 300)
		$form['um_height'] = 300;
	else if ($form['um_height'] > 1000)
		$form['um_height'] = 1000;

	foreach ($form as $key => $input)
	{
		// Only update values that have changed
		if (array_key_exists('o_'.$key, $pun_config) && $pun_config['o_'.$key] != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$db->escape($input).'\'';
			else
				$value = 'NULL';

			$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$value.' WHERE conf_name=\'o_'.$db->escape($key).'\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());
		}
	}

	// Regenerate the config cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();

	redirect($plugin_url, $lang_usermap_admin['Options updated redirect']);
}

else
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div id='usermap_plugin' class='blockform'>
		<h2><span><?php echo $lang_usermap_admin['User map settings']?></span></h2>
		<div class='box'>
			<form id='usermap_options' method='post' action='<?php echo $plugin_url ?>'>
				<div class='inform' id='um'>
					<fieldset>
						<legend><?php echo $lang_usermap_admin['Options']?></legend>
						<div class='infldset'>
							<p><?php echo $lang_usermap_admin['Options info']?></p>
							<table class='aligntop' cellspacing='0'>
								<tr>
									<th scope='row'><?php echo $lang_usermap_admin['Default latitude']?></th>
									<td>
										<input type='text' name='form[um_default_lat]' size='35' maxlength='255' value='<?php echo pun_htmlspecialchars((isset($_GET['lat']) && is_numeric($_GET['lat']))? $_GET['lat']: $pun_config['o_um_default_lat']) ?>' />
									</td>
								</tr>
								<tr>
									<th scope='row'><?php echo $lang_usermap_admin['Default longitude']?></th>
									<td>
										<input type='text' name='form[um_default_lng]' size='35' maxlength='255' value='<?php echo pun_htmlspecialchars((isset($_GET['lng']) && is_numeric($_GET['lng']))? $_GET['lng']: $pun_config['o_um_default_lng']) ?>' />
									</td>
								</tr>
								<tr>
									<th scope='row'><?php echo $lang_usermap_admin['Default zoom']?></th>
									<td>
										<select name='form[um_default_zoom]'>
<?php
	$default_zoom = (isset($_GET['z'])  && is_numeric($_GET['z']))? $_GET['z']: $pun_config['o_um_default_zoom'];

	for ($x=0;$x<=19;$x++)
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value=\''.$x.'\''.(($x == $default_zoom)? ' selected=\'selected\'':'').'>'.$x.'</option>'."\n";
?>
										</select>
										<span><?php echo $lang_usermap_admin['Default zoom info']?></span>
									</td>
								</tr>
								<tr>
									<th scope='row'><?php echo $lang_usermap_admin['Fit map zoom']?></th>
									<td>
										<input type='radio' name='form[um_fit_map]' value='1'<?php if ($pun_config['o_um_fit_map'] == '1') echo ' checked=\'checked\'' ?> />&nbsp;<strong><?php echo $lang_admin_common['Yes'] ?></strong>&nbsp;&nbsp;
										<input type='radio' name='form[um_fit_map]' value='0'<?php if ($pun_config['o_um_fit_map'] == '0') echo ' checked=\'checked\'' ?> />&nbsp;<strong><?php echo $lang_admin_common['No'] ?></strong>
										<span><?php echo $lang_usermap_admin['Fit map zoom info']?></span>
									</td>
								</tr>
								<tr>
									<th scope='row'><?php echo $lang_usermap_admin['Map height']?></th>
									<td>
										<input type='text' name='form[um_height]' size='5' maxlength='4' value='<?php echo pun_htmlspecialchars($pun_config['o_um_height']) ?>' />
										<span><?php echo $lang_usermap_admin['Map height info']?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class='submitend'><input type='submit' name='save_options' value='<?php echo $lang_admin_common['Save changes']?>'/></p>
			</form>

			<div class='fakeform'>
				<div class='inform'>
					<fieldset>
						<legend><?php echo $lang_usermap_admin['Groups']?></legend>
						<div class='infldset'>
							<p><?php echo $lang_usermap_admin['Groups info']?></p>
							<table cellspacing='0'>
<?php
	$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups ORDER BY g_id') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

	while ($cur_group = $db->fetch_assoc($result))
	{

?>
								<tr>
									<th scope='row'><a href='<?php echo $plugin_url .'&edit_group='.$cur_group['g_id']?>'><?php echo $lang_usermap_admin['Edit']?></a></th>
									<td><?php echo pun_htmlspecialchars($cur_group['g_title'])?></td>
								</tr>
<?php

	} // endwhile;

?>
							</table>
						</div>
					</fieldset>
				</div>
			</div>
		</div>
	</div>
<?php

}

// Note that the script just ends here. The footer will be included by admin_loader.php
