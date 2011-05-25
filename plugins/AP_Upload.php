<?php
/**
 * Copyright (C) 2011 Visman (visman@inbox.ru)
 * Copyright (C) 2007  BN (bnmaster@la-bnbox.info)
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_VERSION', '1.2');
define('PLUGIN_NAME', 'Upload');
define('PLUGIN_URL', pun_htmlspecialchars(get_base_url(true).'/admin_loader.php?plugin='.$_GET['plugin']));
define('PLUGIN_LAWS', 'jpg,jpeg,png,gif,mp3,zip,rar,7z');
$tabindex = 1;
$maxidf = 1;

require PUN_ROOT.'include/upload.php';

$sconf = array(
	'thumb' => ($gd ? 1 : 0),
	'thumb_size' => 100,
	'thumb_perc' => 75,
	'limit_memb' => 2097152,
	'limit_modo' => 5242880,
	'maxs_memb' => 1258291,
	'maxs_modo' => 1258291,
	'pic_mass' => 307200,
	'pic_perc' => 75,
	'pic_w' => 1680,
	'pic_h' => 1050,
	);

// Установка плагина/мода
if (isset($_POST['installation']))
{
	$db->add_field('users', 'upload', 'INT(15)', false, 0) or error(sprintf($lang_up['err_3'], 'users'), __FILE__, __LINE__, $db->error());

	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name LIKE "o_uploadile_%"') or error('Unable to remove config entries', __FILE__, __LINE__, $db->error());;
	$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES(\'o_uploadile_exts\', \''.$db->escape(PLUGIN_LAWS).'\')') or error($lang_up['err_insert'], __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES(\'o_uploadile_other\', \''.$db->escape(serialize($sconf)).'\')') or error($lang_up['err_insert'], __FILE__, __LINE__, $db->error());

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();

	redirect(PLUGIN_URL, $lang_up['installation_success']);
}
// Обновления параметров
else if (isset($_POST['update']))
{
	if (isset($_POST['laws']))
	{
		$laws = str_replace(' ', '', $_POST['laws']);
		if (preg_match('/^[0-9a-zA-Z][0-9a-zA-Z,]+[0-9a-zA-Z]$/u', $laws) == 0)
			$laws = PLUGIN_LAWS;
		$laws = strtolower($laws);
	}
	else
		$laws = PLUGIN_LAWS;

	if (isset($_POST['thumb']))
		$sconf['thumb'] = ($_POST['thumb'] == '1' ? 1 : 0);
	if (isset($_POST['thumb_size']) && $_POST['thumb_size'] > 0)
		$sconf['thumb_size'] = intval($_POST['thumb_size']);
	if (isset($_POST['thumb_perc']) && $_POST['thumb_perc'] > 0 && $_POST['thumb_perc'] <= 100)
		$sconf['thumb_perc'] = intval($_POST['thumb_perc']);

	if (isset($_POST['limit_member']) && $_POST['limit_member'] >= 0)
		$sconf['limit_memb'] = intval($_POST['limit_member']);
	if (isset($_POST['limit_modo']) && $_POST['limit_modo'] >= 0)
		$sconf['limit_modo'] = intval($_POST['limit_modo']);
	if (isset($_POST['maxsize_member']) && $_POST['maxsize_member'] >= 0)
		$sconf['maxs_memb'] = intval($_POST['maxsize_member']);
	if (isset($_POST['maxsize_modo']) && $_POST['maxsize_modo'] >= 0)
		$sconf['maxs_modo'] = intval($_POST['maxsize_modo']);

	if (isset($_POST['pic_mass']) && $_POST['pic_mass'] >= 0)
		$sconf['pic_mass'] = intval($_POST['pic_mass']);
	if (isset($_POST['pic_perc']) && $_POST['pic_perc'] > 0 && $_POST['pic_perc'] <= 100)
		$sconf['pic_perc'] = intval($_POST['pic_perc']);
	if (isset($_POST['pic_w']) && $_POST['pic_w'] >= 100)
		$sconf['pic_w'] = intval($_POST['pic_w']);
	if (isset($_POST['pic_h']) && $_POST['pic_h'] >= 100)
		$sconf['pic_h'] = intval($_POST['pic_h']);

	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name LIKE "o_uploadile_%"') or error('Unable to remove config entries', __FILE__, __LINE__, $db->error());;
	$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES(\'o_uploadile_exts\', \''.$db->escape($laws).'\')') or error($lang_up['err_insert'], __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO '.$db->prefix.'config (conf_name, conf_value) VALUES(\'o_uploadile_other\', \''.$db->escape(serialize($sconf)).'\')') or error($lang_up['err_insert'], __FILE__, __LINE__, $db->error());

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();

	redirect(PLUGIN_URL, $lang_up['update_success']);
}
// Удаление мода
else if (isset($_POST['restore']))
{
	$db->drop_field('users', 'upload') or error('Unable to drop upload field', __FILE__, __LINE__, $db->error());

	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name LIKE "o_uploadile_%"') or error('Unable to remove config entries', __FILE__, __LINE__, $db->error());;

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();
	
	redirect(PLUGIN_URL, $lang_up['restore_success']);
}

if (isset($pun_config['o_uploadile_exts']))
	$aconf = unserialize($pun_config['o_uploadile_other']);
else
{
	$pun_config['o_uploadile_exts'] = PLUGIN_LAWS;
	$aconf = $sconf;
	$aconf['thumb'] = 0;
	define('PLUGIN_OFF', 1);
}

$extsup = explode(',', $pun_config['o_uploadile_exts'].','.strtoupper($pun_config['o_uploadile_exts']));
$mem = 'img/members/';
$regx = '/^img\/members\/(\d+)\/(.+)\.([0-9a-zA-Z]+)$/i';

// Удаление файлов
if (isset($_POST['supprimer']) && $_POST['supprimer'] != NULL && isset($_POST['boucle_id']))
{
	$error = 0;
	$maxidf = intval($_POST['boucle_id']);

	if (is_dir(PUN_ROOT.$mem))
	{
		$au = array();
		for ($u = 1 ; $u < $maxidf ; $u++)
		{
			if (isset($_POST['supprimer_'.$u]))
			{
				$fichier = pun_trim($_POST['supprimer_'.$u]);
				preg_match($regx, $fichier, $fi);
				if (!isset($fi[1]) || !isset($fi[2]) || !isset($fi[3])) continue;

				$f = parse_file($fi[2].'.'.$fi[3]);
				$dir = $mem.$fi[1].'/';
				if (is_file(PUN_ROOT.$dir.$f))
				{
					$au[$fi[1]] = $fi[1];
					$d1 = $d2 = unlink(PUN_ROOT.$dir.$f);
					if (is_file(PUN_ROOT.$dir.'mini_'.$f))
						$d2 = unlink(PUN_ROOT.$dir.'mini_'.$f);
					if (!$d1 || !$d2)
						$error++;
				}
			}
		}

		if (!defined('PLUGIN_OFF'))
		{
			foreach ($au as $user)
			{
				// Считаем общий размер файлов юзера
				$upload = dir_size($mem.$user.'/', $extsup);
				$db->query('UPDATE '.$db->prefix.'users SET upload=\''.$upload.'\' WHERE id='.$user) or error($lang_up['err_insert'], __FILE__, __LINE__, $db->error());
			}
		}
	}
	if ($error == 0)
		redirect(PLUGIN_URL, $lang_up['delete_success']);
	else
	{
		$pun_config['o_redirect_delay'] = 5;
		redirect(PLUGIN_URL, $lang_up['err_delete']);
	}
}

// Display the admin navigation menu
generate_admin_menu($plugin);
?>
	<div id="uploadile" class="plugin blockform">
		<h2><span><?php echo PLUGIN_NAME.' v.'.PLUGIN_VERSION ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_up['plugin_desc'] ?></p>
				<form action="<?php echo PLUGIN_URL ?>" method="post">
					<p>
<?php

$stthumb = '<br />';
	
if (defined('PLUGIN_OFF'))
{
?>
						<input type="submit" name="installation" value="<?php echo $lang_up['installation'] ?>" />&nbsp;<?php echo $lang_up['installation_info'] ?><br />
					</p>
				</form>
			</div>
		</div>
<?php
} else {

	if ($aconf['thumb'] == 1 && $gd)
		$stthumb = '<p class="submittop"><input type="submit" name="update_thumb" value="'.$lang_up['update_thumb'].'" /></p>'."\n";
	if ($gd)
	{
		$disbl = '';
		$gd_vers = gd_info();
		$gd_vers = $gd_vers['GD Version'];
	}
	else
	{
		$disbl = '" disabled="disabled';
		$gd_vers = '-';
	}

?>
						<input type="submit" name="update" value="<?php echo $lang_up['update'] ?>" />&nbsp;<?php echo $lang_up['update_info'] ?><br />
						<input type="submit" name="restore" value="<?php echo $lang_up['restore'] ?>" />&nbsp;<?php echo $lang_up['restore_info'] ?><br /><br />
					</p>
				</form>
			</div>
		</div>
		<h2 class="block2"><span><?php echo $lang_up['configuration'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo PLUGIN_URL ?>">
				<p class="submittop"><input type="submit" name="update" value="<?php echo $lang_up['update'] ?>" tabindex="<?php echo $tabindex++ ?>" /></p>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_up['legend_2'] ?></legend>
						<div class="infldset">
						<table cellspacing="0">
							<tr>
								<th scope="row"><label for="laws"><?php echo $lang_up['laws'] ?></label></th>
								<td>
									<input type="text" name="laws" size="50" maxlength="250" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($pun_config['o_uploadile_exts']) ?>" />
									<?php echo $lang_up['laws_info']."\n" ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label>GD Version</label></th>
								<td><?php echo pun_htmlspecialchars($gd_vers) ?></td>
							</tr>
							<tr>
								<th scope="row"><label for="pic_mass"><?php echo $lang_up['pictures'] ?></label></th>
								<td>
									<?php echo $lang_up['for pictures']."\n" ?>
									<input type="text" name="pic_mass" size="8" maxlength="8" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['pic_mass']).$disbl ?>" />&nbsp;<?php echo $lang_up['bytes'].":\n" ?><br />
									&nbsp;*&nbsp;<?php echo $lang_up['to jpeg'] ?><br />
									&nbsp;*&nbsp;<?php echo $lang_up['Install quality']."\n" ?>
									<input type="text" name="pic_perc" size="4" maxlength="3" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['pic_perc']).$disbl ?>" />&nbsp;%<br />
									&nbsp;*&nbsp;<?php echo $lang_up['Size not more']."\n" ?>
									<input type="text" name="pic_w" size="4" maxlength="4" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['pic_w']).$disbl ?>" />&nbsp;x
									<input type="text" name="pic_h" size="4" maxlength="4" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['pic_h']).$disbl ?>" />&nbsp;<?php echo $lang_up['px']."\n" ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="thumb"><?php echo $lang_up['thumb'] ?></label></th>
								<td>
									<input type="radio" tabindex="<?php echo ($tabindex++).$disbl ?>" name="thumb" value="1"<?php if ($aconf['thumb'] == 1) echo ' checked="checked"' ?> /> <strong><?php echo $lang_up['oui'] ?></strong>
									&#160;&#160;&#160;
									<input type="radio" tabindex="<?php echo ($tabindex++).$disbl ?>" name="thumb" value="0"<?php if ($aconf['thumb'] == 0) echo ' checked="checked"' ?> /> <strong><?php echo $lang_up['non'] ?></strong>
									<br />
									&nbsp;*&nbsp;<?php echo $lang_up['thumb_size']."\n" ?>
									<input type="text" name="thumb_size" size="4" maxlength="4" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['thumb_size']).$disbl ?>" />&nbsp;<?php echo $lang_up['px']."\n" ?><br />
									&nbsp;*&nbsp;<?php echo $lang_up['quality']."\n" ?>
									<input type="text" name="thumb_perc" size="4" maxlength="3" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['thumb_perc']).$disbl ?>" />&nbsp;%
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="maxsize_member"><?php echo $lang_up['maxsize_member'] ?></label></th>
								<td>
									<input type="text" name="maxsize_member" size="15" maxlength="15" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['maxs_memb']) ?>" />&nbsp;<?php echo $lang_up['bytes']."\n"  ?>
									<?php echo $lang_up['maxsize_info']."\n"  ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="limit_member"><?php echo $lang_up['limit_member'] ?></label></th>
								<td>
									<input type="text" name="limit_member" size="15" maxlength="15" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['limit_memb']) ?>" />&nbsp;<?php echo $lang_up['bytes']."\n"  ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="maxsize_modo"><?php echo $lang_up['maxsize_modo'] ?></label></th>
								<td>
									<input type="text" name="maxsize_modo" size="15" maxlength="15" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['maxs_modo']) ?>" />&nbsp;<?php echo $lang_up['bytes']."\n"  ?>
									<?php echo $lang_up['maxsize_info']."\n"  ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="limit_modo"><?php echo $lang_up['limit_modo'] ?></label></th>
								<td>
									<input type="text" name="limit_modo" size="15" maxlength="15" tabindex="<?php echo $tabindex++ ?>" value="<?php echo pun_htmlspecialchars($aconf['limit_modo']) ?>" />&nbsp;<?php echo $lang_up['bytes']."\n"  ?>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
					<p class="submitend"><input type="submit" name="update" value="<?php echo $lang_up['update'] ?>" tabindex="<?php echo $tabindex++ ?>" /></p>
					<div class="inform">
					<fieldset>
						<legend><?php echo $lang_up['legend_1'] ?></legend>
						<label for="mo"><?php echo $lang_up['mo'] ?></label> <input type="text" name="mo" id="mo" size="15" tabindex="<?php echo $tabindex++ ?>" /> <input type="button" value="<?php echo $lang_up['convert'] ?>" tabindex="<?php echo $tabindex++ ?>" onclick="javascript:document.getElementById('ko').value=document.getElementById('mo').value*1024; document.getElementById('o').value=document.getElementById('mo').value*1048576;" />
						<label for="ko"><?php echo $lang_up['ko'] ?></label> <input type="text" name="ko" id="ko" size="15" tabindex="<?php echo $tabindex++ ?>" /> <input type="button" value="<?php echo $lang_up['convert'] ?>" tabindex="<?php echo $tabindex++ ?>" onclick="javascript:document.getElementById('mo').value=document.getElementById('ko').value/1024; document.getElementById('o').value=document.getElementById('ko').value*1024;"/>
						<label for="o"><?php echo $lang_up['o'] ?></label> <input type="text" name="o" id="o" size="15" tabindex="<?php echo $tabindex++ ?>" /> <input type="button" value="<?php echo $lang_up['convert'] ?>" tabindex="<?php echo $tabindex++ ?>" onclick="javascript:document.getElementById('mo').value=document.getElementById('o').value/1048576; document.getElementById('ko').value=(document.getElementById('o').value*1024)/1048576;"/>
					</fieldset>
					</div>
				</div>
			</form>
		</div>
<?php
}
?>
		<h2 class="block2"><span><?php echo $lang_up['fichier_membre'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo PLUGIN_URL ?>">
				<div class="inform">
					<?php echo $stthumb ?>
					<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<thead>
								<tr>
									<th scope="row"><?php echo $lang_up['th0'] ?></th>
									<th scope="row"><?php echo $lang_up['th'] ?></th>
									<th scope="row"><?php echo $lang_up['th2'] ?></th>
									<th style="text-align:center;"><input type="submit" value="<?php echo $lang_up['delete'] ?>" name="supprimer" tabindex="<?php echo $tabindex++ ?>" /></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th class="tc1" scope="row"><?php echo $lang_up['th0'] ?></th>
									<th class="tc1" scope="row"><?php echo $lang_up['th'] ?></th>
									<th class="tc1" scope="row"><?php echo $lang_up['th2'] ?></th>
									<th style="text-align:center;"><input type="submit" value="<?php echo $lang_up['delete'] ?>" name="supprimer" tabindex="<?php echo $tabindex++ ?>" /></th>
								</tr>
							</tfoot>
							<tbody>
<?php

if (is_dir(PUN_ROOT.$mem))
{
	$af = array();
	$ad = scandir(PUN_ROOT.$mem);
	foreach($ad as $f)
	{
		if ($f != '.' && $f != '..' && is_dir(PUN_ROOT.$mem.$f))
		{
			$dir = $mem.$f.'/';
			$open = opendir(PUN_ROOT.$dir);
			while(($file = readdir($open)) !== false)
			{
				if ($file[0] != '.' && is_file(PUN_ROOT.$dir.$file)) // $file != '.' && $file != '..' &&
				{
//					$extension = strtolower(substr(strrchr($file,  '.' ), 1)); // берем расширение файла
					if ($file[0] != '#' && substr($file, 0, 5) != 'mini_') // $file[0] != '.' && in_array($extension, $extsup)
					{
						$time = filemtime(PUN_ROOT.$dir.$file).$file.$f;
						$af[$time] = $dir.$file;
					}
				}
			}
			closedir($open);
		}
	}
	if (!empty($af))
	{
		krsort($af);
		$files = array();
		foreach($af as $time => $file)
		{
			$files[] = $file;
		}
	}
}
if (empty($files))
	echo '<tr><td colspan="4">'.$lang_up['err_2'].'</td></tr>'."\n";
else
{
	$au = array();
	$result = $db->query('SELECT id, username FROM '.$db->prefix.'users WHERE group_id!='.PUN_UNVERIFIED) or error('Unable to fetch user information', __FILE__, __LINE__, $db->error());
	while ($u = $db->fetch_assoc($result))
		$au[$u['id']] = $u['username'];
	$db->free_result($result);

	foreach ($files as $fichier)
	{
		preg_match($regx, $fichier, $fi);
		if (!isset($fi[1]) || !isset($fi[2]) || !isset($fi[3])) continue;
		
		$dir = $mem.$fi[1].'/';
		$size_fichier = file_size(filesize(PUN_ROOT.$fichier));
		$miniature = $dir.'mini_'.$fi[2].'.'.$fi[3];
		if (isset($_POST['update_thumb']) && $_POST['update_thumb'] != NULL && $aconf['thumb'] == 1 && array_key_exists($fi[3],$extimageGD))
			img_resize(PUN_ROOT.$fichier, $dir, 'mini_'.$fi[2], $fi[3], 0, $aconf['thumb_size'], $aconf['thumb_perc']);

?>
								<tr>
									<td class="tc2"><?php echo pun_htmlspecialchars(isset($au[$fi[1]]) ? $au[$fi[1]] : '&nbsp;') ?></td>
									<td class="tc1"><a href="<?php echo pun_htmlspecialchars($fichier) ?>"><?php echo pun_htmlspecialchars($fi[2]) ?></a> [<?php echo pun_htmlspecialchars($size_fichier) ?>].[<?php echo (in_array($fi[3], $extsup) ? pun_htmlspecialchars($fi[3]) : '<span style="color: #ff0000"><strong>'.pun_htmlspecialchars($fi[3]).'</strong></span>') ?>]</td>
<?php
		if (is_file(PUN_ROOT.$miniature))
			echo "\t\t\t\t\t\t\t\t\t".'<td class="tc2" style="text-align:center;"><a href="'.pun_htmlspecialchars($fichier).'"><img src="'.pun_htmlspecialchars($miniature).'" alt="'.pun_htmlspecialchars($fi[2]).'" /></a></td>'."\n";
		else
			echo "\t\t\t\t\t\t\t\t\t".'<td class="tc2" style="text-align:center;">'.$lang_up['no_preview'].'</td>'."\n";
?>
									<td style="text-align:center;"><input type="checkbox" name="supprimer_<?php echo $maxidf++ ?>" value="<?php echo pun_htmlspecialchars($fichier) ?>" tabindex="<?php echo $tabindex++ ?>" /></td>
								</tr>
<?php
	}


}
?>
							</tbody>
						</table>
						<input type="hidden" name="boucle_id" value="<?php echo $maxidf ?>" />
					</div>
				</div>
			</form>
		</div>
	</div>
<?php
