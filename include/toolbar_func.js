/***********************************************************************

  Copyright (C) 2010 Mpok
  based on code Copyright (C) 2006 Vincent Garnier
  based on code Copyright (c) 2004 Olivier Meunier and contributors
  License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher

************************************************************************/

function toolBar(textarea, bt_img_path, smilies_img_path)
{
	if (!document.createElement)
		return;

	if ((typeof(document['selection']) == 'undefined')
	&& (typeof(textarea['setSelectionRange']) == 'undefined'))
		return;

	var toolbar = document.createElement('div');
	toolbar.id = 'toolbar';
	toolbar.style.padding = '4px 0';

	var smilies = document.createElement('div');
	smilies.id = 'smilies';
	smilies.style.display = 'none';
	smilies.style.padding = '0.3em 0';

	function addButton(src, title, fn)
	{
		var i = document.createElement('img');
		i.src = bt_img_path + src;
		i.title = title.replace(/&quot;/g, '"');
		i.style.padding = '0 5px 0 0';
		i.onclick = function() { try { fn() } catch (e) { } return false };
		i.tabIndex = 400;
		toolbar.appendChild(i);
	}

	function addSmiley(src, txt)
	{
		var i = document.createElement('img');
		var htxt = txt;
		htxt = htxt.replace(new RegExp(/&amp;/g), '&');
		htxt = htxt.replace(new RegExp(/&quot;/g), '"');
		htxt = htxt.replace(new RegExp(/&lt;/g), '<');
		htxt = htxt.replace(new RegExp(/&gt;/g), '>');
		i.src = smilies_img_path + src;
		i.title = txt;
		i.style.verticalAlign = 'middle';
		i.style.padding = '0 5px 0 0';
		i.onclick = function() { try { encloseSelection(htxt, '') } catch (e) { } return false };
		i.tabIndex = 400;
		smilies.appendChild(i);
	}

	function addSpace(w)
	{
		var s = document.createElement('span');
		s.style.padding = '0 ' + w + 'px 0 0';
		s.appendChild(document.createTextNode(' '));
		toolbar.appendChild(s);
	}

	function encloseSelection(prefix, suffix, fn)
	{
		textarea.focus();
		var start, end, sel, scrollPos, subst;

		if (typeof(document['selection']) != 'undefined')
			sel = document.selection.createRange().text;
		else if (typeof(textarea['setSelectionRange']) != 'undefined')
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

		if (typeof(document['selection']) != 'undefined')
		{
			var range = document.selection.createRange().text = subst;
			textarea.caretPos -= suffix.length;
		}
		else if (typeof(textarea['setSelectionRange']) != 'undefined')
		{
			textarea.value = textarea.value.substring(0, start) + subst + textarea.value.substring(end);
			if (sel)
				textarea.setSelectionRange(start + subst.length, start + subst.length);
			else
				textarea.setSelectionRange(start + prefix.length, start + prefix.length);
			textarea.scrollTop = scrollPos;
		}
	}

	function draw()
	{
		textarea.parentNode.insertBefore(smilies, textarea);
		textarea.parentNode.insertBefore(toolbar, textarea);
	}

	function singleTag(tag)
	{
		var stag = '[' + tag + ']';
		var etag = '[/' + tag + ']';
		encloseSelection(stag, etag);
	}

	function btSingle(img, tag, label)
	{
		addButton(img, label, function() { singleTag(tag) });
	}

	function btPrompt_1(img, tag, label, msg_1)
	{
		addButton(img, label,
			function() {
				encloseSelection('', '',
					function(str) {
						var var_1 = window.prompt(msg_1, '');
						if (!var_1)
							return '[' + tag + ']' + str + '[/' + tag + ']';
						else
							return '[' + tag + '=' + var_1 + ']' + str + '[/' + tag +']';
					});
			});
	}

	function btPrompt_1inside(img, tag, label, msg_1)
	{
		addButton(img, label,
			function() {
				encloseSelection('', '',
					function(str) {
						var var_1 = window.prompt(msg_1, '');
						if (!var_1)
							return str;
						else
							return '[' + tag + ']' + var_1 + '[/' + tag + ']';
					});
			});
	}

	function btPrompt_2(img, tag, label, msg_1, msg_2, reverse)
	{
		addButton(img, label,
			function() {
				encloseSelection('', '',
					function(str) {
						var var_1 = window.prompt(msg_1, '');
						if (!var_1)
							return str;
						var var_2 = window.prompt(msg_2, str);
						if (var_2)
						{
							if (reverse)
								return '[' + tag + '=' + var_2 + ']' + var_1 + '[/' + tag +']';
							else
								return '[' + tag + '=' + var_1 + ']' + var_2 + '[/' + tag +']';
						}
						else
							return '[' + tag + ']' + var_1 + '[/' + tag + ']';
					});
			});
	}

	function btColor(img, label)
	{
		addButton(img, label,
			function() {
				document.getElementById('req_message').focus();
				var width = 380;
				var height = 240;
				window.open('color_picker.php', 'cp', 'alwaysRaised=yes, dependent=yes, resizable=no, location=no, width=' + width + ', height=' + height + ', menubar=no, status=yes, scrollbars=no, menubar=no');
			});
	}

	function btSmilies(img, label)
	{
		addButton(img, label,
			function() {
				var element = document.getElementById('smilies');
				if (element.style.display == 'block' )
				{
					textarea.focus();
					element.style.display = 'none';
				}
				else
				{
					textarea.focus();
					element.style.display = 'block';
				}
			});
	}

	function moreSmilies(txt)
	{
		var l = document.createElement('span');
		l.style.padding = '1em';
		l.style.cursor = 'pointer';
		l.onclick = function() { popup_smilies() };
		l.appendChild(document.createTextNode(txt));
		smilies.appendChild(l);
	}

	function barSmilies(smilies)
	{
		for (var code in smilies)
			addSmiley(smilies[code], code);
	}

	// Methods
	this.addButton		= addButton;
	this.addSmiley		= addSmiley;
	this.addSpace		= addSpace;
	this.draw		= draw;
	this.btSingle		= btSingle;
	this.btPrompt_1		= btPrompt_1;
	this.btPrompt_1inside	= btPrompt_1inside;
	this.btPrompt_2		= btPrompt_2;
	this.btColor		= btColor;
	this.btSmilies		= btSmilies;
	this.moreSmilies	= moreSmilies;
	this.barSmilies		= barSmilies;
}
