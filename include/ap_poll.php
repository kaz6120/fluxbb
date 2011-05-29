<?php
/**
 * Poll Mod for FluxBB, written by As-Planned.com
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
if (!defined('PUN')) exit;

// Load the language file
global $pun_user;
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/ap_poll.php'))
	require PUN_ROOT.'lang/'.$pun_user['language'].'/ap_poll.php';
else
	require PUN_ROOT.'lang/English/ap_poll.php';

define('AP_POLL_MAX_CHOICES', 15);
define('AP_POLL_ENABLE_PROMOTED', false);

function ap_poll_post($var, $default = null) 
{
	return isset($_POST[$var]) ? $_POST[$var] : $default;
}

/*
Returns true when the current user is an administrator
*/
function ap_poll_is_admin() 
{
	global $pun_user;
	return ($pun_user['g_id'] == PUN_ADMIN);
}

/*
Retrieves poll information into array
*/
function ap_poll_info($tid, $uid = NULL) 
{
	global $db;
	
	$result = $db->query("SELECT question, enabled, promoted FROM " . $db->prefix . "ap_polls WHERE tid = " . $tid) or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) 
	{
		return null;
	}
	
	$poll = $db->fetch_assoc($result);
	$question = $poll['question'];
	$enabled = (int) $poll['enabled'];
	$promoted = (int) $poll['promoted'];
	
	$result = $db->query("SELECT number, choice, votes FROM " . $db->prefix . "ap_polls_choices WHERE tid = " . $tid . " ORDER BY number") or error('Unable to fetch poll choices', __FILE__, __LINE__, $db->error());
	$choices = array();
	$votes = 0;
	while ($choice = $db->fetch_assoc($result)) 
	{
		$choices[(int) $choice['number']] = array('choice' => $choice['choice'], 'votes' => (int) $choice['votes']);
		$votes += (int) $choice['votes'];
	}

	return array(
		'question' => $question, 
		'enabled' => $enabled, 
		'promoted' => $promoted,
		'choices' => $choices, 
		'votes' => $votes,
		'canVote' => is_null($uid) ? NULL : ap_poll_can_vote($tid, $uid)
		);
}

/* 
Displays the form for posting new topics
*/
function ap_poll_form_post($tid) 
{
	if ($tid == 0) 
	{
		ap_poll_form($tid);
	}
}

/*
Displays the form shown when the first post in the topic is editted
*/
function ap_poll_form_edit($tid) 
{
	ap_poll_form($tid);
}

/*
Displays the (partial) form that can be used to get the poll information
from the user
*/
function ap_poll_form($tid) 
{
	global $cur_index;
	global $lang_ap_polls;
	
	$lang = $lang_ap_polls;

	$default_enabled = 0;
	$default_promoted = 0;
	$default_question = '';
	$default_choices = array();

	if ($tid > 0) 
	{
		$info = ap_poll_info($tid);
		
		if (!is_null($info)) 
		{
			$default_enabled = $info['enabled'];
			$default_promoted = $info['promoted'];
			$default_question = $info['question'];
			$default_choices = array();
			foreach ($info['choices'] as $number => $choice)
				$default_choices[$number] = $choice['choice'];
		}
	}
	
	$enabled = (ap_poll_post('ap_poll_enabled', $default_enabled) == 1);
	$promoted = (ap_poll_post('ap_poll_promoted', $default_promoted) == 1);
	$question = pun_htmlspecialchars(ap_poll_post('ap_poll_question', $default_question));
	$choices = ap_poll_post('ap_poll_choices', $default_choices);

	if (!is_array($choices)) 
	{
		error('Incorrect data format for ap_poll_choices', __FILE__, __LINE__);
		return;
	} 
	else 
	{
		$choices = array_map('pun_htmlspecialchars', $choices);
	}
	?>
		<div class="inform">
			<fieldset>
				<legend><?php echo $lang['Form legend'] ?></legend>
				<div class="infldset txtarea">
					<label>
						<input type="checkbox" id="ap_poll_enabled" name="ap_poll_enabled" value="1" <?php if ($enabled) echo 'checked="checked"'?> tabindex="<?php echo $cur_index++ ?>" /> <?php echo $lang['Form enable'] ?>						
					</label>
					
					<?php if (ap_poll_is_admin() && AP_POLL_ENABLE_PROMOTED): ?>
						<label>
							<input type="checkbox" name="ap_poll_promoted" value="1" <?php if ($promoted) echo 'checked="checked"'?> tabindex="<?php echo $cur_index++ ?>" /> <?php echo $lang['Form promoted'] ?>
						</label>
					<?php endif ?>			

					<div id="ap_poll_input" class="<?php if (!$enabled) echo 'ap_poll_hidden'; ?>">
						<label>
							<?php echo $lang['Form question'] ?><br />
							<input class="longinput" type="text" name="ap_poll_question" value="<?php echo $question ?>" tabindex="<?php echo $cur_index++ ?>" size="80" maxlength="100" />
						</label>
						<?php
						for ($i = 0; $i < AP_POLL_MAX_CHOICES; $i++) 
						{
							$choice = isset($choices[$i]) ? $choices[$i] : '';
							?>
							<label>
								<?php printf($lang['Form choice'], $i + 1) ?><br />
								<input class="longinput" type="text" name="ap_poll_choices[<?php echo $i?>]" value="<?php echo $choice ?>" tabindex="<?php echo $cur_index++ ?>" size="80" maxlength="100" />
							</label>
							<?php
						}
						?>						
					</div>
				</div>
			</fieldset>
		</div>
	<?php
}

/*
Validates the poll form, adding any errors it encounters to the array 
provided as the argument
*/
function ap_poll_form_validate(&$errors) 
{
	global $lang_ap_polls;
	$lang = $lang_ap_polls;
	
	$enabled = (ap_poll_post('ap_poll_enabled', 0) == 1);
	// promoted not required for validation
	$question = ap_poll_post('ap_poll_question');
	$choices = ap_poll_post('ap_poll_choices', array());

	if ($enabled) 
	{
		if (empty($question))
			$errors[] = $lang['No question'];
		else if (pun_strlen($question) > 100)
			$errors[] = $lang['Question too long'];
	
		if (!is_array($choices)) 
		{
			$errors[] = $lang['Incorrect data for choices'];
		}
		else 
		{
			$choice_count = 0;
			for ($i = 0; $i < AP_POLL_MAX_CHOICES; $i++) 
			{
				if (isset($choices[$i]) && !empty($choices[$i])) 
				{
					if (pun_strlen($choices[$i]) > 100) 
					{
						$errors[] = sprintf($lang['Choice too long'], $i + 1);
					}
					$choice_count++;
				}
			}
			
			if ($choice_count < 2)
				$errors[] = $lang['Not enough choices'];
		}
	}
}

/*
Save the poll information to the database
*/
function ap_poll_save($tid) 
{
	global $db;
	$cur_choices = array();
	
	// Check if poll fields were in form by checking if question field
	// was part of the post data. If not, return without action
	$question = ap_poll_post('ap_poll_question');
	if (is_null($question)) 
	{
		return;
	}

	$enabled = (ap_poll_post('ap_poll_enabled', 0) == 1);
	$promoted = (ap_poll_post('ap_poll_promoted', 0) == 1);
	$question = ap_poll_post('ap_poll_question');
	$choices = ap_poll_post('ap_poll_choices', array());	

	// If the poll isn't enabled, disable it and don't save info
	if (!$enabled) 
	{
		$db->query("UPDATE " . $db->prefix . "ap_polls SET enabled = 0 WHERE tid = " . $tid) or error('Unable to disable poll', __FILE__, __LINE__, $db->error());
		return;
	}

	// Check if a poll is already attached to this topic. If it is, update it.
	// Also fetch the current choices
	$result = $db->query("SELECT 1 FROM " . $db->prefix . "ap_polls WHERE tid = " . $tid) or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))	
	{
		$db->query("UPDATE " . $db->prefix . "ap_polls SET enabled = 1, question = '" . $db->escape($question) . "' WHERE tid = " . $tid) or error('Unable to update poll', __FILE__, __LINE__, $db->error());
		
		$result = $db->query("SELECT number FROM " . $db->prefix . "ap_polls_choices WHERE tid = " . $tid) or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());
		while ($choice = $db->fetch_assoc($result))
			$cur_choices[(int) $choice['number']] = true;
		
	// If the poll is new, add it.
	} 
	else 
	{
		$db->query("INSERT INTO " . $db->prefix . "ap_polls (tid, question) VALUES (" . $tid . ",'" . $db->escape($question) . "')") or error('Unable to create poll', __FILE__, __LINE__, $db->error());		
	}	

	if (ap_poll_is_admin()) 
	{
		$db->query("UPDATE " . $db->prefix . "ap_polls SET promoted = " . ($promoted ? 1 : 0) . " WHERE tid = " . $tid) or error('Unable to update poll promoted status', __FILE__, __LINE__, $db->error());
	}
	
	// Update the choices. First, update and insert new choices
	for ($i = 0; $i < AP_POLL_MAX_CHOICES; $i++)
	{
		if (isset($choices[$i]) && !empty($choices[$i]))
		{
		
			// If the choice with this number already existed, only update
			// the choice text.
			if (isset($cur_choices[$i])) 
			{
				$db->query("UPDATE " . $db->prefix . "ap_polls_choices SET choice = '" . $db->escape($choices[$i]) . "' WHERE number = " . $i . " AND tid = " . $tid) or error('Unable to update poll choice', __FILE__, __LINE__, $db->error());
				unset($cur_choices[$i]);
				
			// If the choice is new, insert it
			}
			else
			{
				$db->query("INSERT INTO " . $db->prefix . "ap_polls_choices (tid, number, choice) VALUES (" . $tid . "," . $i . ",'" . $db->escape($choices[$i]) . "')") or error('Unable to create poll choice', __FILE__, __LINE__, $db->error());			
			}
		}
	}
	
	// Now, process choices that have been removed
	// Note that this may destroy votes
	foreach ($cur_choices as $number => $tmp) 
	{
		$db->query("DELETE FROM " . $db->prefix . "ap_polls_choices WHERE number = " . $number . " AND tid = " . $tid) or error('Unable to remove poll choice', __FILE__, __LINE__, $db->error());
	}
}

/*
Remove a poll, used when a topic is deleted
*/
function ap_poll_delete($tid) 
{
	global $db;
	
	$db->query("DELETE FROM " . $db->prefix . "ap_polls WHERE tid = " . $tid) or error('Unable to remove poll', __FILE__, __LINE__, $db->error());	
	$db->query("DELETE FROM " . $db->prefix . "ap_polls_choices WHERE tid = " . $tid) or error('Unable to remove poll choices', __FILE__, __LINE__, $db->error());	
	$db->query("DELETE FROM " . $db->prefix . "ap_polls_voted WHERE tid = " . $tid) or error('Unable to remove poll voted list', __FILE__, __LINE__, $db->error());	
}

/*
Returns true when the given user is allowed to cast a vote for the poll
*/
function ap_poll_can_vote($tid, $uid) 
{
	global $db;
	global $pun_user;
	$result = $db->query("SELECT 1 FROM " . $db->prefix . "ap_polls_voted WHERE tid = " . $tid . " AND uid = " . $uid) or error('Unable to fetch poll voted info', __FILE__, __LINE__, $db->error());
	return ($db->num_rows($result) == 0) && !$pun_user['is_guest'];
}

/*
Display the poll to users
*/
function ap_poll_display($tid, $info) 
{
	global $db;
	global $lang_ap_polls;
	
	$lang = $lang_ap_polls;

	if (is_null($info)) return;
	if (!$info['enabled']) return;
	
	$can_vote = $info['canVote'];

	$max = 0;
	foreach ($info['choices'] as $choice) 
	{
		if ($choice['votes'] > $max) $max = $choice['votes'];
	}
	$maxPercent = $info['votes'] == 0 ? 1 : 100 * (float) $max / $info['votes'];

	?>
	<fieldset class="ap_poll">
		<p><?php echo htmlspecialchars($info['question']) ?></p>
		<form method="post" action="viewtopic.php?id=<?php echo $tid ?>&amp;action=ap_vote">
		<table>
			<?php 
			foreach ($info['choices'] as $number => $choice) 
			{
				if (empty($choice)) continue;
				
				if ($info['votes'] == 0) 
				{
					$percent = 0;
				}
				else
				{
					$percent = round(100 * (float) $choice['votes'] / $info['votes']);
				}
				?>
				<tr>
					<th>
						<?php 
						if ($can_vote) 
						{
							printf('<label><input type="radio" name="ap_vote" value="%d" /> %s</label>', 
								$number, htmlspecialchars($choice['choice']));
						}
						else
						{
							echo htmlspecialchars($choice['choice']);
						}		
						?>
					</th>
					<td class="percent"><?php echo $percent ?>%</td>
					<td class="results">
						<div class="bar" style="width: <?php echo round(150 * (float) $percent / $maxPercent)?>px"><div class="top"></div></div>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<div class="total"><?php printf($lang['Vote total'], $info['votes']) ?></div>
		<?php if ($can_vote): ?>
			<input type="submit" value="<?php echo $lang['Vote button'] ?>" />
		<?php endif ?>
		</form>
	</fieldset>
	<?php
}

/*
Cast a vote
*/
function ap_poll_vote($tid, $uid) 
{
	global $db;

	$vote = ap_poll_post('ap_vote');
	$can_vote = ap_poll_can_vote($tid, $uid);
	
	if (is_null($vote) || !$can_vote) return;

	// Note that when vote has a non-integer value, this will be mapped to the first choice
	// This is not a problem, since valid votes will always contain an integer value
	$number = (int) $vote;

	$db->query("UPDATE " . $db->prefix . "ap_polls_choices SET votes = votes + 1 WHERE tid = " . $tid . " AND number = " . $number) or error('Unable to save vote', __FILE__, __LINE__, $db->error());	
	$db->query("INSERT INTO " . $db->prefix . "ap_polls_voted (tid, uid) VALUES (" . $tid . "," . $uid . ")") or error('Unable to save vote', __FILE__, __LINE__, $db->error());		
	
}