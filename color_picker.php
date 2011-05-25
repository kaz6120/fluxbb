<?php
/***********************************************************************

  Copyright (C) 2010 Mpok
  javascript code Copyright (C) 2003-2005 Frosty
  License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher

************************************************************************/

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);

require PUN_ROOT.'lang/'.$pun_user['language'].'/fluxtoolbar.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang_common['lang_identifier'] ?>" lang="<?php echo $lang_common['lang_identifier'] ?>" dir="<?php echo $lang_common['lang_direction'] ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Color Picker</title>
  <style type="text/css">
  body {
	margin: 0;
	background-color: #ffffff;
  }
  .main {
	float: left;
	margin: 20px;
  }
  .black_border {
	border: 1px solid #000000;
  }
  .cross {
	cursor: crosshair;
  }
  .detail {
	margin-top: 20px;
  }
  .detail td {
	background-color: #000000;
	width: 20px;
	height: 3px;
	border: none;
  }
  .valid {
	clear: left;
	text-align: center;
  }
  .valid input {
	vertical-align: middle;
  }
  .result {
	background: #000000;
	width: 100px;
	height: 35px;
  }
  .but_val {
	width: 70px;
  }
  </style>
<!--[if IE]>
  <style type="text/css">
  .main {
	margin-left: 10px;
  }
  </style>
<![endif]-->
  <script type="text/javascript">
/* <![CDATA[ */
	// Déposé par Frosty sur www.toutjavascript.com
	// 27/05/2003 - Ajout compatibilité IE5 sur MacOS
	// 05/06/2003 - Ajout compatibilité Mozilla
	// 05/09/2005 - Correction d'un bug (clic sur la bordure de la palette principale)
	// 06/09/2005 - Ajout de la possibilité de sélectionner une couleur en déplaçant la souris
	//                      sur les palettes (bouton gauche enfoncé)

	/*****************************************************************
	* Script Color Picker écrit par Frosty (Maxime Pacary) - Mai 2003
	******************************************************************/

	// var. globale
	var detail = 50; // nombre de nuances de couleurs dans la barre de droite

	var textarea = window.opener.document.getElementById('req_message');

	// ne pas modifier
	var strhex = "0123456789ABCDEF";
	var i;
	var is_mouse_down = false;
	var is_mouse_over = false;

	// conversion decimal (0-255) => hexa
	function dechex(n) {
		return strhex.charAt(Math.floor(n / 16)) + strhex.charAt(n % 16);
	}

	// détection d'un clic/mouvement souris sur la "palette" (à gauche)
	function compute_color(e)
	{
		var x = e.offsetX ? e.offsetX : (e.target ? e.clientX-e.target.x : 0);
		var y = e.offsetY ? e.offsetY : (e.target ? e.clientY-e.target.y : 0);

		// calcul de la couleur à partir des coordonnées du clic
		var part_width = document.all ? document.all.color_picker.width / 6 : document.getElementById('color_picker').width / 6;
		var part_detail = detail / 2;
		var im_height = document.all ? document.all.color_picker.height : document.getElementById('color_picker').height;

		var red = (x >= 0) * (x < part_width) * 255
				+ (x >= part_width) * (x < 2 * part_width) * (2 * 255 - x * 255 / part_width)
				+ (x >= 4 * part_width) * (x < 5 * part_width) * (-4 * 255 + x * 255 / part_width)
				+ (x >= 5 * part_width) * (x < 6 * part_width) * 255;
		var blue = (x >= 2 * part_width) * (x < 3 * part_width) * (-2 * 255 + x * 255 / part_width)
				+ (x >= 3 * part_width) * (x < 5 * part_width) * 255
				+ (x >= 5 * part_width) * (x < 6 * part_width) * (6 * 255 - x * 255 / part_width);
		var green = (x >= 0) * (x < part_width) * (x * 255 / part_width)
				+ (x >= part_width) * (x < 3 * part_width) * 255
				+ (x >= 3 * part_width) * (x < 4 * part_width) * (4 * 255 - x * 255 / part_width);

		var coef = (im_height - y) / im_height;

		// composantes de la couleur choisie sur la "palette"
		red = 128 + (red - 128) * coef;
		green = 128 + (green - 128) * coef;
		blue = 128 + (blue - 128) * coef;

		// mise à jour de la couleur finale
		changeFinalColor('#' + dechex(red) + dechex(green) + dechex(blue));

		// mise à jour de la barre de droite en fonction de cette couleur
		for (i = 0; i < detail; i++)
		{
			if ((i >= 0) && (i < part_detail))
			{
				var final_coef = i / part_detail;
				var final_red = dechex(255 - (255 - red) * final_coef);
				var final_green = dechex(255 - (255 - green) * final_coef);
				var final_blue = dechex(255 - (255 - blue) * final_coef);
			}
			else
			{
				var final_coef = 2 - i / part_detail;
				var final_red = dechex(red * final_coef);
				var final_green = dechex(green * final_coef);
				var final_blue = dechex(blue * final_coef);
			}
			var color = final_red + final_green + final_blue;
			document.all ? document.all('gs' + i).style.backgroundColor = '#' + color : document.getElementById('gs' + i).style.backgroundColor = '#' + color;
		}
	}

	// pour afficher la couleur finale choisie
	function changeFinalColor(color)
	{
		document.forms['colpick_form'].elements['btn_choose_color'].style.backgroundColor = color;
	}

	// "renvoyer" la couleur en cliquant sur OK
	function send_color()
	{
		function encloseSelection(prefix, suffix, fn)
		{
			textarea.focus();
			var start, end, sel, scrollPos, subst;

			if (typeof(window.opener.document["selection"]) != "undefined")
				sel = window.opener.document.selection.createRange().text;
			else if (typeof(textarea["setSelectionRange"]) != "undefined")
			{
				start = textarea.selectionStart;
				end = textarea.selectionEnd;
				scrollPos = textarea.scrollTop;
				sel = textarea.value.substring(start, end);
			}

			if (sel.match(/ $/))
			{
				// exclude ending space char, if any
				sel = sel.substring(0, sel.length - 1);
				suffix = suffix + " ";
			}

			if (typeof(fn) == 'function')
				var res = (sel) ? fn(sel) : fn('');
			else
				var res = (sel) ? sel : '';

			subst = prefix + res + suffix;

			if (typeof(window.opener.document["selection"]) != "undefined")
			{
				var range = window.opener.document.selection.createRange().text = subst;
				textarea.caretPos -= suffix.length;
			}
			else if (typeof(textarea["setSelectionRange"]) != "undefined")
			{
				textarea.value = textarea.value.substring(0, start) + subst + textarea.value.substring(end);
				if (sel)
					textarea.setSelectionRange(start + subst.length, start + subst.length);
				else
					textarea.setSelectionRange(start + prefix.length, start + prefix.length);
				textarea.scrollTop = scrollPos;
			}
		}	

		var new_color = document.forms['colpick_form'].elements['btn_choose_color'].style.backgroundColor;
		var exp_rgb = new RegExp("rgb", "g");

		if (exp_rgb.test(new_color))
		{
			var exp_extract = new RegExp("[0-9]+", "g");
			var tab_rgb = new_color.match(exp_extract);

			new_color = '#' + dechex(parseInt(tab_rgb[0])) + dechex(parseInt(tab_rgb[1])) + dechex(parseInt(tab_rgb[2]));
		}

		encloseSelection('', '', function(str) {return '[color=' + new_color + ']' + str + '[/color]'});

		window.opener.focus();
		window.close();
	}

	window.focus();
/* ]]> */
  </script>
</head>
<body>
<form id="colpick_form" action="#" method="post">
	<div class="main cross">
		<img id="color_picker" src="img/fluxtoolbar/colpick.jpg"
			onclick="compute_color(event)"
			onmousedown="is_mouse_down = true; return false;"
			onmouseup="is_mouse_down = false;"
			onmousemove="if (is_mouse_down &amp;&amp; is_mouse_over) compute_color(event); return false;"
			onmouseover="is_mouse_over = true;"
			onmouseout="is_mouse_over = false;"
			alt="Pick a color" />
	</div>
	<table cellspacing="0" cellpadding="0" class="black_border detail cross"><tbody>
<?php
for ($i = 0; $i < 50; $i++)
{
	echo "\t\t".'<tr><td id="gs'.$i.'"'
		.' onclick="changeFinalColor(this.style.backgroundColor)"'
		.' onmousedown="is_mouse_down = true; return false;"'
		.' onmouseup="is_mouse_down = false;"'
		.' onmousemove="if (is_mouse_down &amp;&amp; is_mouse_over) changeFinalColor(this.style.backgroundColor); return false;"'
		.' onmouseover="is_mouse_over = true;"'
		.' onmouseout="is_mouse_over = false;"'
		.'></td></tr>'."\n";
}
?>
	</tbody></table>
	<div class="valid">
		<input type="button" name="btn_choose_color" value="&nbsp;" class="result black_border" />&#160;&#160;&#160;
		<input type="button" name="btn_ok" value="Ok" class="but_val" onclick="send_color();" />&#160;&#160;&#160;
		<input type="button" name="btn_cancel" value="<?php echo $lang_ftb['Cancel'] ?>" class="but_val" onclick="window.close();" />
	</div>
</form>
</body>
</html>
