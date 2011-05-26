<?php
/***********************************************************************/

// Some info about your mod.
$mod_title	= 'FluxToolBar';
$mod_version	= '2.0';
$release_date	= '2010-07-04';
$author		= 'Mpok - Fluxbb.fr';
$author_email	= 'mpok@fluxbb.fr';

// Versions of FluxBB this mod was created for. A warning will be displayed, if versions do not match
$fluxbb_versions= array('1.4.0');

// Set this to false if you haven't implemented the restore function (see below)
$mod_restore	= true;

// This following function will be called when the user presses the "Install" button
function install()
{
	global $db;

	// Start transaction
	$db->start_transaction();

	// Create "toolbar_conf" table
	$schema = array(
		'FIELDS' => array(
			'conf_name' => array(
				'datatype' => 'VARCHAR(40)',
				'allow_null' => false,
				'default' => '\'\''
			),
			'conf_value' => array(
				'datatype' => 'VARCHAR(40)',
				'allow_null' => false,
				'default' => '\'\''
			)
		),
		'PRIMARY KEY' => array('conf_name')
	);
	$db->create_table('toolbar_conf', $schema) or error('Unable to create toolbar_conf table', __FILE__, __LINE__, $db->error());

	// Create "toolbar_tags" table
	$schema = array(
		'FIELDS' => array(
			'name' => array(
				'datatype' => 'VARCHAR(20)',
				'allow_null' => false,
				'default' => '\'\''
			),
			'code' => array(
				'datatype' => 'VARCHAR(20)',
				'allow_null' => false,
				'default' => '\'\''
			),
			'enable_form' => array(
				'datatype' => 'TINYINT(1)',
				'allow_null' => false,
				'default' => '0'
			),
			'enable_quick' => array(
				'datatype' => 'TINYINT(1)',
				'allow_null' => false,
				'default' => '0'
			),
			'image' => array(
				'datatype' => 'VARCHAR(40)',
				'allow_null' => false,
				'default' => '\'\''
			),
			'func' => array(
				'datatype' => 'TINYINT(1)',
				'allow_null' => false,
				'default' => '0'
			),
			'position' => array(
				'datatype' => 'TINYINT(2) UNSIGNED',
				'allow_null' => false,
				'default' => '1'
			)
		),
		'PRIMARY KEY' => array('name')
	);
	$db->create_table('toolbar_tags', $schema) or error('Unable to create toolbar_tags table', __FILE__, __LINE__, $db->error());

	// Insert default configuration
	$config = array(
		'enable_form'		=>	'1',
		'enable_quickform'	=>	'0',
		'img_pack'		=>	'smooth',
		'nb_smilies'		=>	'12',
		'pop_up_width'		=>	'240',
		'pop_up_height'		=>	'200',
		'button_size'		=>	'4096',
		'button_width'		=>	'32',
		'button_height'		=>	'32'
	);
	while (list($conf_name, $conf_value) = @each($config))
		$db->query('INSERT INTO '.$db->prefix.'toolbar_conf (conf_name, conf_value) VALUES(\''.$db->escape($conf_name).'\', \''.$db->escape($conf_value).'\')') or error('Unable to insert in toolbar_conf table', __FILE__, __LINE__, $db->error());

	// Insert default tags
	$tags = array(
		"'smilies', '', '1', '1', 'bt_smilies.png', '0', '0'",
		"'bold', 'b', '1', '1', 'bt_bold.png', '0', '1'",
		"'italic', 'i', '1', '1', 'bt_italic.png', '0', '2'",
		"'underline', 'u', '1', '1', 'bt_underline.png', '0', '3'",
		"'strike', 's', '1', '1', 'bt_strike.png', '0', '4'",
		"'sup', 'sup', '1', '0', 'bt_sup.png', '0', '5'",
		"'sub', 'sub', '1', '0', 'bt_sub.png', '0', '6'",
		"'heading', 'h', '1', '1', 'bt_size_plus.png', '0', '7'",
		"'left', 'left', '1', '0', 'bt_align_left.png', '0', '8'",
		"'right', 'right', '1', '0', 'bt_align_right.png', '0', '9'",
		"'center', 'center', '1', '0', 'bt_align_center.png', '0', '10'",
		"'justify', 'justify', '1', '0', 'bt_align_justify.png', '0', '11'",
		"'color', 'color', '1', '1', 'bt_color.png', '0', '12'",
		"'q', 'q', '1', '0', 'bt_quote.png', '0', '13'",
		"'acronym', 'acronym', '1', '0', 'bt_acronym.png', '1', '14'",
		"'img', 'img', '1', '1', 'bt_img.png', '2', '15'",
		"'code', 'code', '1', '1', 'bt_pre.png', '0', '16'",
		"'quote', 'quote', '1', '1', 'bt_bquote.png', '1', '17'",
		"'link', 'url', '1', '1', 'bt_link.png', '2', '18'",
		"'email', 'email', '1', '1', 'bt_email.png', '2', '19'",
		"'video', 'video', '1', '0', 'bt_video.png', '3', '20'",
		"'li', '*', '1', '1', 'bt_li.png', '0', '21'",
		"'list', 'list', '1', '1', 'bt_ul.png', '1', '22'"
	);
	foreach ($tags as $tag)
		$db->query('INSERT INTO '.$db->prefix.'toolbar_tags (name, code, enable_form, enable_quick, image, func, position) VALUES ('.$tag.')') or error('Unable to insert in toolbar_tags table', __FILE__, __LINE__, $db->error());

	// End transaction
	$db->end_transaction();
}

// This following function will be called when the user presses the "Restore" button (only if $mod_restore is true (see above))
function restore()
{
	global $db;

	$db->drop_table('toolbar_conf') or error('Unable to drop toolbar_conf table', __FILE__, __LINE__, $db->error());
	$db->drop_table('toolbar_tags') or error('Unable to drop toolbar_tags table', __FILE__, __LINE__, $db->error());
}

/***********************************************************************/

// DO NOT EDIT ANYTHING BELOW THIS LINE!

// Circumvent maintenance mode
define('PUN_TURN_OFF_MAINT', 1);
define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

// Make sure we are running a FluxBB version that this mod works with
$version_warning = !in_array($pun_config['o_cur_version'], $fluxbb_versions);

$style = (isset($pun_user)) ? $pun_user['style'] : $pun_config['o_default_style'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo pun_htmlspecialchars($mod_title) ?> installation</title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $style.'.css' ?>" />
</head>
<body>

<div id="punwrap">
<div id="puninstall" class="pun" style="margin: 10% 20% auto 20%">

<?php

if (isset($_POST['form_sent']))
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
			<p>Your database has been successfully prepared for <?php echo pun_htmlspecialchars($mod_title) ?>. See readme.txt for further instructions.</p>
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
			<p>Your database has been successfully restored.</p>
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
		<form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?foo=bar">
			<div><input type="hidden" name="form_sent" value="1" /></div>
			<div class="inform">
				<p>This script will update your database to work with the following modification:</p>
				<p><strong>Mod title:</strong> <?php echo pun_htmlspecialchars($mod_title.' '.$mod_version) ?></p>
				<p><strong>Author:</strong> <?php echo pun_htmlspecialchars($author) ?> (<a href="mailto:<?php echo pun_htmlspecialchars($author_email) ?>"><?php echo pun_htmlspecialchars($author_email) ?></a>)</p>
				<p><strong>Disclaimer:</strong> Mods are not officially supported by FluxBB. Mods generally can't be uninstalled without running SQL queries manually against the database. Make backups of all data you seem necessary before installing.</p>
<?php if ($mod_restore): ?>
				<p>If you've previously installed this mod and would like to uninstall it, you can click the Restore button below to restore the database.</p>
<?php endif; ?>
<?php if ($version_warning): ?>
				<p style="color: #a00"><strong>Warning:</strong> The mod you are about to install was not made specifically to support your current version of FluxBB (<?php echo $pun_config['o_cur_version']; ?>). This mod supports FluxBB versions: <?php echo pun_htmlspecialchars(implode(', ', $fluxbb_versions)); ?>. If you are uncertain about installing the mod due to this potential version conflict, contact the mod author.</p>
<?php endif; ?>
			</div>
			<p class="buttons"><input type="submit" name="install" value="Install" /><?php if ($mod_restore): ?><input type="submit" name="restore" value="Restore" /><?php endif; ?></p>
		</form>
	</div>
</div>
<?php

}

?>

</div>
</div>

</body>
</html>
