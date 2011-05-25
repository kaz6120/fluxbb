<?php
/**
 * Copyright (C) 2011 Visman (visman@inbox.ru)
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Load language file
if (!isset($lang_up))
{
	if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/upload.php'))
		require PUN_ROOT.'lang/'.$pun_user['language'].'/upload.php';
	else
		require PUN_ROOT.'lang/English/upload.php';
}

if (!$pun_user['is_guest'] && isset($pun_config['o_uploadile_exts']))
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
		echo '<script type="text/javascript">'."\n";
		echo '/* <![CDATA[ */'."\n";
		echo 'function PopUp(c,d,a,b,e){window.open(c,d,"top="+(screen.height-b)/3+", left="+(screen.width-a)/2+", width="+a+", height="+b+", "+e);return false};';
		echo 'var all_ul=document.getElementsByTagName("ul"),i=all_ul.length-1;';
		echo 'while (i>-1){';
		echo 'if(all_ul[i].className=="bblinks"){';
		echo 'var ul_html=all_ul[i].innerHTML;';
		echo 'ul_html+="<li><span><a href=\"upfiles.php\" onclick=\"return PopUp(this.href,\'gest\',\'820\',\'430\',\'resizable=yes,location=no,menubar=no,status=no,scrollbars=yes\');\"><strong>'.$lang_up['upfiles'].'</strong></a></span></li>";';
		echo 'all_ul[i].innerHTML=ul_html;';
		echo 'i=0;';
		echo '}';
		echo 'i--';
		echo '}'."\n";
		echo '/* ]]> */'."\n";
		echo '</script>'."\n";
	}
}
