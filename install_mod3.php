<?php

/***********************************************************************/

// Some info about your mod.
$mod_title			= 'Usermap';
$mod_version		= '1.1.1';
$release_date		= 'April 13th 2011';
$author				= 'Gizzmo';
$author_email		= 'justgiz@gmail.com';

// Versions of FluxBB this mod was created for. Minor variations (i.e. 1.2.4 vs 1.2.5) will be allowed, but a warning will be displayed.
$fluxbb_versions	= array('1.4.5');

// Set this to false if you haven't implemented the restore function (see below)
$mod_restore		= true;

// This following function will be called when the user presses the "Install" button
function install()
{
	global $db, $db_type, $pun_config;

	/* Config*/
	$config = array(
		'o_um_default_lat'		=> "'0'",
		'o_um_default_lng'		=> "'0'",
		'o_um_default_zoom'		=> "'1'",
		'o_um_height'			=> "'500'",
		'o_um_fit_map'			=> "'0'"
	);

	foreach ($config as $conf_name => $conf_value)
	{
		if (!array_key_exists($conf_name, $pun_config))
		{
			$db->query('INSERT INTO '.$db->prefix."config (conf_name, conf_value) VALUES('$conf_name', $conf_value)")
				or error('Unable to insert `'.$conf_name.'` into table '.$db->prefix.'config.', __FILE__, __LINE__, $db->error());
		}
	}

	/* Users */
	$db->add_field('users', 'um_lat', 'DOUBLE', true) or error('Unable to add `um_lat` field to the `'.$db->prefix.'user` table.', __FILE__, __LINE__, $db->error());
	$db->add_field('users', 'um_lng', 'DOUBLE', true) or error('Unable to add `um_lng` field to the `'.$db->prefix.'user` table.', __FILE__, __LINE__, $db->error());
	$db->add_field('users', 'um_scrollwheel', 'TINYINT(1)', false, '0') or error('Unable to add `um_scrollwheel` field to the `'.$db->prefix.'user` table.', __FILE__, __LINE__, $db->error());

	/* Group */
	$db->add_field('groups', 'g_um_view_map', 'TINYINT(1)', false, '1') or error('Unable to add `g_um_view_map` field to the `'.$db->prefix.'groups` table.', __FILE__, __LINE__, $db->error());
	$db->add_field('groups', 'g_um_add_to_map', 'TINYINT(1)', false, '1') or error('Unable to add `g_um_add_to_map` field to the `'.$db->prefix.'groups` table.', __FILE__, __LINE__, $db->error());
	$db->add_field('groups', 'g_um_icon', 'VARCHAR(50)', false, 'white.png') or error('Unable to add `g_um_icon` field to the `'.$db->prefix.'groups` table.', __FILE__, __LINE__, $db->error());

	/* Set the default icons for the groups */
	$db->query('UPDATE `'.$db->prefix.'groups` SET `g_um_icon` = \'red.png\' WHERE `g_id` = 1') or error('Unable to update admin group.', __FILE__, __LINE__, $db->error());
	$db->query('UPDATE `'.$db->prefix.'groups` SET `g_um_icon` = \'blue.png\' WHERE `g_id` = 2') or error('Unable to update moderator group.', __FILE__, __LINE__, $db->error());

	// update cache, we added config options
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();
}

// This following function will be called when the user presses the 'Restore' button (only if $mod_uninstall is true (see above))
function restore()
{
	global $db, $db_type, $pun_config;

	/* Config */
	$db->query('DELETE FROM `'.$db->prefix.'config` WHERE
		`conf_name` = \'o_um_default_lat\' OR
		`conf_name` = \'o_um_default_lng\' OR
		`conf_name` = \'o_um_default_zoom\' OR
		`conf_name` = \'o_um_height\' OR
		`conf_name` = \'o_um_fit_map\' OR
		`conf_name` = \'o_um_find_location\'
	') or error('Unable to remove config items from the `'.$db->prefix.'config` table', __FILE__, __LINE__, $db->error());

	/* Users */
	$db->drop_field('users', 'um_lat') or error('Unable to remove `um_lat` from the `'.$db->prefix.'user` table.', __FILE__, __LINE__, $db->error());
	$db->drop_field('users', 'um_lng') or error('Unable to remove `um_lng` from the `'.$db->prefix.'user` table.', __FILE__, __LINE__, $db->error());
	$db->drop_field('users', 'um_scrollwheel') or error('Unable to remove `um_scrollwheel` from the `'.$db->prefix.'user` table.', __FILE__, __LINE__, $db->error());

	/* Group */
	$db->drop_field('users', 'g_um_view_map') or error('Unable to remove `g_um_view_map` from the `'.$db->prefix.'groups` table.', __FILE__, __LINE__, $db->error());
	$db->drop_field('users', 'g_um_add_to_map') or error('Unable to remove `g_um_add_to_map` from the `'.$db->prefix.'groups` table.', __FILE__, __LINE__, $db->error());
	$db->drop_field('users', 'g_um_icon') or error('Unable to remove `g_um_icon` from the `'.$db->prefix.'groups` table.', __FILE__, __LINE__, $db->error());

	// update cache, we removed config items
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();
}

/***********************************************************************/

// DO NOT EDIT ANYTHING BELOW THIS LINE!

// Circumvent maintenance mode
define('PUN_TURN_OFF_MAINT', 1);
define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

// Make sure we are running a FluxBB version that this mod works with
$version_warning = !in_array($pun_config['o_cur_version'], $fluxbb_versions);

$style = (isset($pun_user)) ? $pun_user['style'] : $pun_config['o_default_style'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php echo pun_htmlspecialchars($mod_title) ?> installation</title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $style.'.css' ?>" />
</head>
<body>

<div id="puninstall" class="pun" style="margin: 0 auto; width: 550px">
	<div class="top-box"><div><!-- Top Corners --></div></div>
	<div class="punwrap">


<?php

if ($pun_user['g_id'] != PUN_ADMIN)
{

?>
		<div class="block">
			<h2><span>Admin Only</span></h2>
			<div class="box">
				<div class="inbox">
					<p>Only a Admin can access this page. If you are the admin, make sure you are <a href="login.php">logged in.</a></p>
				</div>
			</div>
		</div>
<?php

}
else if (isset($_POST['form_sent']))
{
	if (isset($_POST['install']))
	{
		// Run the install function (defined above)
		install();

?>
		<div class="block">
			<h2><span>Installation successful</span></h2>
			<div class="box">
				<div class="inbox">
					<p>Your database has been successfully prepared for <?php echo pun_htmlspecialchars($mod_title) ?>.</p>
				</div>
			</div>
		</div>
<?php

	}
	else
	{
		// Run the restore function (defined above)
		restore();

?>
		<div class="block">
			<h2><span>Restore successful</span></h2>
			<div class="box">
				<div class="inbox">
					<p>Your database has been successfully restored. Dont forget to undo the file changes you did.</p>
				</div>
			</div>
		</div>
<?php

	}
}
else
{

?>
		<div class="blockform">
			<h2><span>Mod installation</span></h2>
			<div class="box">
				<form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
					<div><input type="hidden" name="form_sent" value="1" /></div>
					<div class="inform">
						<p>This script will update your database to work with the following modification:</p>
						<p><strong>Mod title:</strong> <?php echo pun_htmlspecialchars($mod_title).' '.$mod_version ?></p>
						<p><strong>Author:</strong> <?php echo pun_htmlspecialchars($author) ?> (<a href="mailto:<?php echo pun_htmlspecialchars($author_email) ?>"><?php echo pun_htmlspecialchars($author_email) ?></a>)</p>
						<p><strong>Disclaimer:</strong> Mods are not officially supported by FluxBB. Mods generally can't be uninstalled without running SQL queries manually against the database. Make backups of all data you deem necessary before installing.</p>
<?php if ($mod_restore): ?>						<p>If you've previously installed this mod and would like to uninstall it, you can click the Restore button below to restore the database.</p>
<?php endif; if ($version_warning): ?>						<p style="color: #a00"><strong>Warning:</strong> The mod you are about to install was not made specifically to support your current version of FluxBB (<?php echo $pun_config['o_cur_version']; ?>). This mod supports FluxBB versions: <?php echo pun_htmlspecialchars(implode(', ', $fluxbb_versions)); ?>. If you are uncertain about installing the mod due to this potential version conflict, contact the mod author.</p>
<?php endif; ?>					</div>
					<p class='buttons'>
						<input type="submit" name="install" value="Install" />
<?php if ($mod_restore): ?>						<input type="submit" name="restore" value="Restore" />
<?php endif; ?>					</p>
				</form>
			</div>
		</div>
<?php

}

?>

	</div>
	<div class="end-box"><div><!-- Top Corners --></div></div>
</div>

</body>
</html>
<?php

// End the transaction
$db->end_transaction();

// Close the db connection (and free up any result data)
$db->close();
