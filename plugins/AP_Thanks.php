<?php

/**
 * Copyright (C) 2008-2010 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

##
##
##  A few notes of interest for aspiring plugin authors:
##
##  1. If you want to display a message via the message() function, you
##     must do so before calling generate_admin_menu($plugin).
##
##  2. Plugins are loaded by admin_loader.php and must not be
##     terminated (e.g. by calling exit()). After the plugin script has
##     finished, the loader script displays the footer, so don't worry
##     about that. Please note that terminating a plugin by calling
##     message() or redirect() is fine though.
##
##  3. The action attribute of any and all <form> tags and the target
##     URL for the redirect() function must be set to the value of
##     $_SERVER['REQUEST_URI']. This URL can however be extended to
##     include extra variables (like the addition of &amp;foo=bar in
##     the form of this example plugin).
##
##  4. If your plugin is for administrators only, the filename must
##     have the prefix "AP_". If it is for both administrators and
##     moderators, use the prefix "AMP_". This example plugin has the
##     prefix "AMP_" and is therefore available for both admins and
##     moderators in the navigation menu.
##
##  5. Use _ instead of spaces in the file name.
##
##  6. Since plugin scripts are included from the FluxBB script
##     admin_loader.php, you have access to all FluxBB functions and
##     global variables (e.g. $db, $pun_config, $pun_user etc).
##
##  7. Do your best to keep the look and feel of your plugins' user
##     interface similar to the rest of the admin scripts. Feel free to
##     borrow markup and code from the admin scripts to use in your
##     plugins. If you create your own styles they need to be added to
##     the "base_admin" style sheet.
##
##  8. Plugins must be released under the GNU General Public License or
##     a GPL compatible license. Copy the GPL preamble at the top of
##     this file into your plugin script and alter the copyright notice
##     to refrect the author of the plugin (i.e. you).
##
##


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Load the admin_bans.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_thanks.php';

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);


if (isset($_POST['save']))
{
	if (isset($_POST['groups_thanks_old']))
	{
		$result = $db->query('SELECT g_id, g_can_thanks FROM '.$db->prefix.'groups WHERE g_id!='.PUN_ADMIN) or error('Unable to fetch group list', __FILE__, __LINE__, $db->error());
		while ($cur_group = $db->fetch_assoc($result))
		{
			$groups_thanks_new = isset($_POST['groups_thanks_new'][$cur_group['g_id']]) ? '1' : '0';
			if ($groups_thanks_new != $_POST['groups_thanks_old'][$cur_group['g_id']])
				$db->query('UPDATE '.$db->prefix.'groups SET g_can_thanks='.$groups_thanks_new.' WHERE g_id='.$cur_group['g_id']) or error('Unable to insert group thanks permissions', __FILE__, __LINE__, $db->error());
		}
			
	}
	
	// Regenerate the quick jump cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_quickjump_cache();

	redirect('admin_loader.php?plugin=AP_Thanks.php', $lang_admin_thanks['Thanks updated redirect']);
}
else
{


// Display the admin navigation menu
generate_admin_menu($plugin);

?>
	<div id="exampleplugin" class="blockform">
		<h2><span><?php echo $lang_admin_thanks['Plugin title'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_admin_thanks['Explanation 1'] ?></p>
				<p><?php echo $lang_admin_thanks['Explanation 2'] ?></p>
			</div>
		</div>

		<h2 class="block2"><span><?php echo $lang_admin_thanks['Form title 1'] ?></span></h2>
		<div class="box">
			<form id="example" method="post" action="<?php echo pun_htmlspecialchars($_SERVER['REQUEST_URI']) ?>&amp;foo=bar">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_thanks['Legend text'] ?></legend>
						<div class="infldset">
							<table id="groupperms" cellspacing="0">
							<thead>
								<tr>
									<th class="atcl">&#160;</th>
									<th><?php echo $lang_admin_thanks['Say Thank you label'] ?></th>
								</tr>
							</thead>
							<tbody>
                                    <?php
									
									$result = $db->query('SELECT g_id, g_title, g_can_thanks FROM '.$db->prefix.'groups WHERE g_id!='.PUN_ADMIN) or error('Unable to fetch groups info', __FILE__, __LINE__, $db->error());
									while ($list_groups = $db->fetch_assoc($result))
									{
										$groups_thanks = ($list_groups['g_can_thanks'] != '0') ? true : false;
										?>
										<tr>
											<th class="atcl"><?php echo pun_htmlspecialchars($list_groups['g_title']) ?></th>
											<td>
                                            	<input type="hidden" name="groups_thanks_old[<?php echo $list_groups['g_id'] ?>]" value="<?php echo ($groups_thanks) ? '1' : '0'; ?>" />
												<input type="checkbox" name="groups_thanks_new[<?php echo $list_groups['g_id'] ?>]" value="1"<?php echo ($groups_thanks) ? ' checked="checked"' : ''; ?> />
											</td>
										</tr>
										<?php
									}
									?>
                                    </tbody>
							</table>	
						</div>
					</fieldset>
				</div>
                <p class="submitend"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></p>
			</form>
		</div>
	</div>
<?php
}
// Note that the script just ends here. The footer will be included by admin_loader.php
