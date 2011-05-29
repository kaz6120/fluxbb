<?php

/**
 * Copyright (C) 2010 Justgizzmo.com
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);

else if ($pun_user['g_view_users'] == '0' || $pun_user['g_um_view_map'] == '0')
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$page_head = array(
	// The Libs
	'jquery'			=> '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>',
	'googleapi'			=> '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>',

	// Context Menu
	'contextmenuCSS'	=> '<link type="text/css" rel="stylesheet" href="usermap/contextMenu/style.css" />',
	'contextmenuJS'		=> '<script type="text/javascript" src="usermap/contextMenu/code.js"></script>',

	// The Core
	'css'				=> '<link type="text/css" rel="stylesheet" media="screen" href="usermap/style.css" />',
	'core'				=> '<script type="text/javascript" src="usermap/script.js"></script>'
);

// code
ob_start();
?>
<script type="text/javascript">
$(function(){
	UserMap.defaults = {
		center:  [<?php echo $pun_config['o_um_default_lat'].','.$pun_config['o_um_default_lng']?>],
		zoom: <?php echo $pun_config['o_um_default_zoom']?>,
		height: <?php echo $pun_config['o_um_height']?>,
		scrollwheel: <?php echo ($pun_user['um_scrollwheel'])? 'true': 'false'?>,
		fitmap: <?php echo ($pun_config['o_um_fit_map'])? 'true': 'false'?>
	};
<?php
	$options = array();

	if (isset($id))
		$options['id'] = $id;

	if ($pun_user['g_id'] == PUN_ADMIN)
		$options['saveLoc'] = array(
			$lang_usermap['Save as default location'],
			$pun_config['o_base_url'],
		);
?>

	new UserMap(<?php if (!empty($options)) echo json_encode($options)?>).main();
});
</script>

<?php
$page_head[] = trim(ob_get_contents());
ob_end_clean();


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_usermap['User map']);
define('PUN_ACTIVE_PAGE', 'User map');
require PUN_ROOT.'header.php';

?>
<div class='block2col'>
	<div class='blockmenu' id='usermap_userlist'>
		<h2><span><?php echo $lang_usermap['User list']?></span></h2>
		<div class='box'>
			<div class='inbox'>
				<span><?php echo $lang_usermap['No users']?></span>
			</div>
		</div>
	</div>
	<div class='block'>
		<h2><span><?php echo $lang_usermap['User map']?></span></h2>
		<div class='box' id='user_map_canvas'></div>
	</div>
</div>
<?php

require PUN_ROOT.'footer.php';
