<?php
/**
 * Copyright (C) 2011 Visman (visman@inbox.ru)
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

if (isset($pun_config['o_uploadile_exts']))
{
	if ($pun_user['g_id'] == PUN_ADMIN || $id == $pun_user['id'])
	{
		$flag_uplmod = false;
		if ($pun_user['g_id'] == PUN_ADMIN)
			$flag_uplmod = true;
		else if ($pun_user['g_moderator'] == 1)
		{
			$aconf = unserialize($pun_config['o_uploadile_other']);
			if ($aconf['maxs_modo'] > 0 && $aconf['limit_modo'] > 0)
				$flag_uplmod = true;
		}
		else
		{
			$aconf = unserialize($pun_config['o_uploadile_other']);
			if ($aconf['maxs_memb'] > 0 && $aconf['limit_memb'] > 0)
				$flag_uplmod = true;
		}
		if ($flag_uplmod)
		{
			if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/upload.php'))
				require PUN_ROOT.'lang/'.$pun_user['language'].'/upload.php';
			else
				require PUN_ROOT.'lang/English/upload.php';

			echo "\t\t\t\t\t".'<li'.(($page == 'upload') ? ' class="isactive"' : '').'><a href="upfiles.php?id='.$id.'">'.$lang_up['upfiles'].'</a></li>'."\n";
		}
	}
}
