<?php
/**
 * Copyright (C) 2011 Visman (visman@inbox.ru)
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if (!defined('PUN'))
	exit;

if (isset($pun_config['o_fbox_files']) && (!$pun_user['is_guest'] || !empty($pun_config['o_fbox_guest'])))
{
	if (strpos(','.$pun_config['o_fbox_files'], ','.basename($_SERVER['PHP_SELF'])) !== false)
	{
		$page_head['jquery'] = (defined('AJAX_JQUERY') ? AJAX_JQUERY : '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>');
		$page_head['fancyboxcss'] = '<link rel="stylesheet" type="text/css" href="style/imports/fancybox.css" />';
		$page_head['fancybox'] = '<script type="text/javascript" src="js/fancybox.js"></script>';
	}
}

