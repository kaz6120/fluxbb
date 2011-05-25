<?php
/**
 * Copyright (C) 2011 Visman (visman@inbox.ru)
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_VERSION', '1.2.0');
define('PLUGIN_REVISION', 2);
define('PLUGIN_NAME', 'Fancybox for FluxBB');
define('PLUGIN_URL', pun_htmlspecialchars(get_base_url(true).'/admin_loader.php?plugin='.$_GET['plugin']));
define('PLUGIN_FILES', 'viewtopic.php,search.php,pmsnew.php');
$tabindex = 1;

// Load language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/fancybox.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/fancybox.php';
else
	require PUN_ROOT.'lang/English/fancybox.php';

$fd_str = 'require PUN_ROOT.\'include/fancybox.php\';';

if (file_exists(PUN_ROOT.'include/header.php'))
	$prefhf = PUN_ROOT.'include/header.php';
else
	$prefhf = PUN_ROOT.'header.php';


$arr_files = array(
	$prefhf,
);
$arr_search = array(
	'echo implode("\n", $page_head)."\n";',
);
$arr_new = array(
	$fd_str."\n\n".'%search%',
);

// установка изменений в файлы
function InstallModInFiles ()
{
	global $arr_files, $arr_search, $arr_new, $lang_fb;
	
	$max = count($arr_files);
	$errors = array();

	for ($i=0; $i < $max; $i++)
	{
		$file_content = file_get_contents($arr_files[$i]);
		if ($file_content === false)
		{
			$errors[] = $arr_files[$i].$lang_fb['Error open file'];
			continue;
		}
		$search = str_replace('%search%', $arr_search[$i], $arr_new[$i]);
		if (strpos($file_content, $search) !== false)
		{
			continue;
		}
		if (strpos($file_content, $arr_search[$i]) === false)
		{
			$errors[] = $arr_files[$i].$lang_fb['Error search'];
			continue;
		}
		$file_content = str_replace($arr_search[$i], $search, $file_content);
		$fp = fopen($arr_files[$i], 'wb');
		if ($fp === false)
		{
			$errors[] = $arr_files[$i].$lang_fb['Error save file'];
			continue;
		}
		fwrite ($fp, $file_content);
		fclose ($fp);
	}
	
	return $errors;
}

// удаление изменений в файлы
function DeleteModInFiles ()
{
	global $arr_files, $arr_search, $arr_new, $lang_fb;

	$max = count($arr_files);
	$errors = array();

	for ($i=0; $i < $max; $i++)
	{
		$file_content = file_get_contents($arr_files[$i]);
		if ($file_content === false)
		{
			$errors[] = $arr_files[$i].$lang_fb['Error open file'];
			continue;
		}
		$search = str_replace('%search%', '', $arr_new[$i]);
		if (strpos($file_content, $search) === false)
		{
			$errors[] = $arr_files[$i].$lang_fb['Error delete'];
			continue;
		}
		$file_content = str_replace($search, '', $file_content);
		$fp = fopen($arr_files[$i], 'wb');
		if ($fp === false)
		{
			$errors[] = $arr_files[$i].$lang_fb['Error save file'];
			continue;
		}
		fwrite ($fp, $file_content);
		fclose ($fp);
	}

	return $errors;
}

// Установка плагина/мода
if (isset($_POST['installation']))
{
	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name LIKE "o_fbox_%"') or error('Unable to remove config entries', __FILE__, __LINE__, $db->error());;
	$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES(\'o_fbox_guest\', \'0\')') or error('Unable to insert into table config.', __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES(\'o_fbox_files\', \''.$db->escape(PLUGIN_FILES).'\')') or error('Unable to insert into table config.', __FILE__, __LINE__, $db->error());

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();
	
	$err = InstallModInFiles();
	if (empty($err))
		redirect(PLUGIN_URL, $lang_fb['Red installation']);

	$pun_config['o_redirect_delay'] = 30;
	redirect(PLUGIN_URL, implode('<br />', $err));
}

// Обновления параметров
else if (isset($_POST['update']))
{
	$gst = isset($_POST['guest_on']) ? 1 : 0;
	$files = isset($_POST['files']) ? array_map('pun_trim', $_POST['files']) : array();
	$fls = array();
	foreach ($files as $file)
	{
		$file = str_replace(array('/','\\','\'','`','"'), array('','','','',''), $file);
		if (substr($file, -4) == '.php' && file_exists(PUN_ROOT.$file))
			$fls[] = $file;
	}

	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name LIKE "o_fbox_%"') or error('Unable to remove config entries', __FILE__, __LINE__, $db->error());;
	$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES(\'o_fbox_guest\', \''.$gst.'\')') or error('Unable to insert into table config.', __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES(\'o_fbox_files\', \''.$db->escape(implode(',', $fls)).'\')') or error('Unable to insert into table config.', __FILE__, __LINE__, $db->error());

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();

	redirect(PLUGIN_URL, $lang_fb['Reg update']);
}

// Удаление мода
else if (isset($_POST['delete']))
{
	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name LIKE "o_fbox_%"') or error('Unable to remove config entries', __FILE__, __LINE__, $db->error());;

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();
	
	$err = DeleteModInFiles();
	if (empty($err))
		redirect(PLUGIN_URL, $lang_fb['Red delete']);

	$pun_config['o_redirect_delay'] = 30;
	redirect(PLUGIN_URL, implode('<br />', $err));
}

$file_content = file_get_contents($prefhf);
if ($file_content === false)
	message(pun_htmlspecialchars($prefhf.$lang_fb['Error open file']));
if (strpos($file_content, $fd_str) !== false)
	$f_inst = true;
else
	$f_inst = false;


// Display the admin navigation menu
generate_admin_menu($plugin);
?>
	<div id="loginza" class="plugin blockform">
		<h2><span><?php echo PLUGIN_NAME.' v.'.PLUGIN_VERSION ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_fb['plugin_desc'] ?></p>
				<form action="<?php echo PLUGIN_URL ?>" method="post">
					<p>
<?php
if (!$f_inst)
{
?>
						<input type="submit" name="installation" value="<?php echo $lang_fb['installation'] ?>" />&nbsp;<?php echo $lang_fb['installation_info'] ?><br />
					</p>
				</form>
			</div>
		</div>
<?php
} else {
?>
						<input type="submit" name="delete" value="<?php echo $lang_fb['delete'] ?>" />&nbsp;<?php echo $lang_fb['delete_info'] ?><br /><br />
					</p>
				</form>
			</div>
		</div>

		<h2 class="block2"><span><?php echo $lang_fb['configuration'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo PLUGIN_URL ?>">
				<p class="submittop"><input type="submit" name="update" value="<?php echo $lang_fb['update'] ?>" tabindex="<?php echo $tabindex++ ?>" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_fb['legend'] ?></legend>
						<div class="infldset">
						<table cellspacing="0">
							<tr>
								<td>
									<label><input type="checkbox" name="guest_on" value="1" tabindex="<?php echo $tabindex++ ?>"<?php echo (empty($pun_config['o_fbox_guest'])) ? '' : ' checked="checked"' ?> />&#160;&#160;<?php echo $lang_fb['guest info'] ?></label>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_fb['legend2'] ?></legend>
						<div class="infldset">
						<table cellspacing="0">
<?php

	$d = dir(PUN_ROOT);
	$ar_file = array();
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, -4) == '.php' && substr($entry, 0, 5) != 'admin' && !in_array($entry, array('db_update.php', 'install.php', 'extern.php', 'pjq.php', 're.php')))
			$ar_file[] = $entry;
	}
	$d->close();

	natcasesort($ar_file);
	
	foreach ($ar_file as $id => $file)
	{

?>
							<tr>
								<td>
									<label><input type="checkbox" name="files[<?php echo $id ?>]" value="<?php echo pun_htmlspecialchars($file) ?>" tabindex="<?php echo $tabindex++ ?>"<?php echo (strpos(','.$pun_config['o_fbox_files'], ','.$file) !== false) ? ' checked="checked"' : '' ?> />&#160;&#160;<?php echo pun_htmlspecialchars($file) ?></label>
								</td>
							</tr>
<?php

	}

?>
						</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="update" value="<?php echo $lang_fb['update'] ?>" tabindex="<?php echo $tabindex++ ?>" /></p>
			</form>
		</div>
<?php
}
?>
	</div>
<?php
