
<?php
1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
62
63
64
65
66
67
68
69
70
71
72
73
74
75
76
77
78
79
80
81
82
83
84
85
86
87
88
89
90
91
92
93
94
95
96
97
98
99
100
101
102
103
104
105
106
107
108
109
110
111
112
113
114
115
116
117
118
119
120
121
122
123
124
125
126
127
128
129
130
131
132
133
134
135
136
137
138
139
140
141
142
143
144
145
146
147
148
149
150
151
152
153
154
155
156
157
158
159
160
161
162
163
164
165
166
167
168
169
170
171
172
173
174
175
176
177
178
179
180
181
182
183
184
185
186
187
188
189
190
191
192
193
194
195
196
197
198
199
200
201
202
203
204
205
206
207
208
209
210
211
212
213
214
215
216
217
218
219
220
221
222
223
224
225
226
227
228
229
230
231
232
233
234
235
236
237
238
239
240
241
242
243
244
245
246
247
248
249
250
251
252
253
254
255
256
257
258
259
260
261
262
263
264
265
266
267
268
269
270
271
272
273
274
275
276
277
278
279
280
281
282
283
284
285
286
287
288
289
290
291
292
293
294
295
296
297
298
299
300
301
302
303
304
305
306
307
308
309
310
311
312
313
314
315
316
317
318
319
320
321
322
323
324
325
326
327
328
329
330
331
332
333
334
335
336
337
338
339
340
341
342
343
344
345
346
347
348
349
350
351
352
353
354
355
356
357
358
359
360
361
362
363
364
365
366
367
368
369
370
371
372
373
374
375
376
377
378
379
380
381
382
383
384
385
386
387
388
389
390
391
392
393
394
395
396
397
398
399
400
401
402
403
404
405
406
407
408
409
410
411
412
413
414
415
416
417
418
419
420
421
422
423
424
425
426
427
428
429
430
431
432
433
434
435
436
437
438
439
440
441
442
443
444
445
446
447
448
449
450
451
452
453
454
455
456
457
458
459
460
461
462
463
464
465
466
467
468
469
470
471
472
473
474
475
476
477
478
479
480
481
482
483
484
485
486
487
488
489
490
491
492
493
494
495
496
497
498
499
500
501
502
503
504
505
506
507
508
509
510
511
512
513
514
515
516
517
518
519
520
521
522
523
524
525
526
527
528
529
530
531
532
533
534
535
536
537
538
539
540
541
542
543
544
545
546
547
548
549
550
551
552
553
554
555
556
557
558
559
560
561
562
563
564
565
566
567
568
569
570
571
572
573
574
575
576
577
578
579
580
581
582
583
584
585
586
587
588
589
590
591
592
593
594
595
596
597
598
599
600
601
602
603
604
605
606
607
608
609
610
611
612
613
614
615
616
617
618
619
620
621
622
623
624
625
626
627
628
629
630
631
632
633
634
635
636
637
638
639
640
641
642
643
644
645
646
647
648
649
650
651
652
653
654
655
656
657
658
659
660
661
662
663
664
665
666
667
668
669
670
671
672
673
674
675
676
677
678
679
680
681
682
683
684
685
686
687
688
689
690
691
692
693
694
695
696
697
698
699
700
701
702
703
704
705
706
707
708
709
710
711
712
713
714
715
716
717
718
719
720
721
722
723
724
725
726
727
728
729
730
731
732
733
734
735
736
737
738
739
740
741
742
743
744
745
746
747
748
749
750
751
752
753
754
755
756
757
758
759
760
761
762
763
764
765
766
767
768
769
770
771
772
773
774
775
776
777
778
779
780
781
782
783
784
785
786
787
788
789
790
791
792
793
794
795
796
797
798
799
800
801
802
803
804
805
806
807
808
809
810
811
812
813
814
815
816
817
818
819
820
821
822
823
824
825
826
827
828
829
830
831
832
833
834
835
836
837
838
839
840
841
842
843
844
845
846
847
848
849
850
851
852
853
854
855
856
857
858
859
860
861
862
863
864
865
866
867
868
869
870
871
872
873
874
875
876
877
878
879
880
881
882
883
884
885
886
887
888
889
890
891
892
893
894
895
896
897
898
899
900
901
902
903
904
905
906
907
908
909
910
911
912
913
914
915
916
917
918
919
920
921
922
923
924
925
926
927
928
929
930
931
932
933
934
935
936
937
938
939
940
941
942
943
944
945
946
947
948
949
950
951
952
953
954
955
956
957
958
959
960
961
962
963
964
965
966
967
968
969
970
971
972
973
974
975
976
977
978
979
980
981
982
983
984
985
986
987
988
989
990
991
992
993
994
995
996
997
998
999
1000
1001
1002
1003
1004
1005
1006
1007
1008
1009
1010
1011
1012
1013
1014
1015
1016
1017
1018
1019
1020
1021
1022
1023
1024
1025
1026
1027
1028
1029
1030
1031
1032
1033
1034
1035
1036
1037
1038
1039
1040
1041
1042
1043
1044
1045
1046
1047
1048
1049
<?php

/**
 * Copyright (C) 2008-2011 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Global variables
/* regular expression to match nested BBCode LIST tags
'%
\[list                # match opening bracket and tag name of outermost LIST tag
(?:=([1a*]))?+        # optional attribute capture in group 1
\]                    # closing bracket of outermost opening LIST tag
(                     # capture contents of LIST tag in group 2
  (?:                 # non capture group for either contents or whole nested LIST
	[^\[]*+           # unroll the loop! consume everything up to next [ (normal *)
	(?:               # (See "Mastering Regular Expressions" chapter 6 for details)
	  (?!             # negative lookahead ensures we are NOT on [LIST*] or [/LIST]
		\[list        # opening LIST tag
		(?:=[1a*])?+  # with optional attribute
		\]            # closing bracket of opening LIST tag
		|             # or...
		\[/list\]     # a closing LIST tag
	  )               # end negative lookahead assertion (we are not on a LIST tag)
	  \[              # match the [ which is NOT the start of LIST tag (special)
	  [^\[]*+         # consume everything up to next [ (normal *)
	)*+               # finish up "unrolling the loop" technique (special (normal*))*
  |                   # or...
	(?R)              # recursively match a whole nested LIST element
  )*                  # as many times as necessary until deepest nested LIST tag grabbed
)                     # end capturing contents of LIST tag into group 2
\[/list\]             # match outermost closing LIST tag
%iex' */
$re_list = '%\[list(?:=([1a*]))?+\]((?:[^\[]*+(?:(?!\[list(?:=[1a*])?+\]|\[/list\])\[[^\[]*+)*+|(?R))*)\[/list\]%ie';

// Here you can add additional smilies if you like (please note that you must escape single quote and backslash)
$smilies = array(
	':)' => 'smile.png',
	'=)' => 'smile.png',
	':|' => 'neutral.png',
	'=|' => 'neutral.png',
	':(' => 'sad.png',
	'=(' => 'sad.png',
	':D' => 'big_smile.png',
	'=D' => 'big_smile.png',
	':o' => 'yikes.png',
	':O' => 'yikes.png',
	';)' => 'wink.png',
	':/' => 'hmm.png',
	':P' => 'tongue.png',
	':p' => 'tongue.png',
	':lol:' => 'lol.png',
	':mad:' => 'mad.png',
	':rolleyes:' => 'roll.png',
	':cool:' => 'cool.png');




//
// Make sure all BBCodes are lower case and do a little cleanup
//
function preparse_bbcode($text, &$errors, $is_signature = false)
{
	global $pun_config, $lang_common, $lang_post, $re_list;

	if ($is_signature)
	{
		global $lang_profile;

		if (preg_match('%\[/?(?:quote|code|list|h)\b[^\]]*\]%i', $text))
			$errors[] = $lang_profile['Signature quote/code/list/h'];

		global $pun_user;
		if (preg_match('%\[/?(?:video|left|right|center|justify)\b[^\]]*\]%i', $text))
		{
			require PUN_ROOT.'lang/'.$pun_user['language'].'/fluxtoolbar.php';
			$errors[] = $lang_ftb['Signature balises'];
		}
	}

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($text, '[code]', '[/code]', $errors);
		$text = implode("\1", $outside);
	}

	// Tidy up lists
	$temp = preg_replace($re_list, 'preparse_list_tag(\'$2\', \'$1\', $errors)', $text);

	// If the regex failed
	if ($temp === null)
		$errors[] = $lang_common['BBCode list size error'];
	else
		$text = str_replace('*'."\0".']', '*]', $temp);

	if ($pun_config['o_make_links'] == '1')
		$text = do_clickable($text);

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode("\1", $text);
		$text = '';

		$num_tokens = count($outside);
		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
				$text .= '[code]'.$inside[$i].'[/code]';
		}

		unset($inside);
	}

	$temp_text = false;
	if (empty($errors))
		$temp_text = preparse_tags($text, $errors, $is_signature);

	if ($temp_text !== false)
		$text = $temp_text;

	// Remove empty tags
	while (($new_text = strip_empty_bbcode($text, $errors)) !== false)
	{
		if ($new_text != $text)
		{
			$text = $new_text;
			if ($new_text == '')
			{
				$errors[] = $lang_post['Empty after strip'];
				break;
			}
		}
		else
			break;
	}

	return pun_trim($text);
}


//
// Strip empty bbcode tags from some text
//
function strip_empty_bbcode($text, &$errors)
{
	// If the message contains a code tag we have to split it up (empty tags within [code][/code] are fine)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($text, '[code]', '[/code]', $errors);
		$text = implode("\1", $outside);
	}

	// Remove empty tags
	//while (($new_text = preg_replace('/\[(b|u|s|ins|del|em|i|h|colou?r|quote|img|url|email|list)(?:\=[^\]]*)?\]\s*\[\/\1\]/', '', $text)) !== NULL)
    while (($new_text = preg_replace('/\[(b|u|s|ins|del|em|i|h|colou?r|quote|img|url|email|list|acronym|q|sup|sub|left|right|center|justify|video)(?:\=[^\]]*)?\]\[\/\1\]/', '', $text)) !== false)

	{
		if ($new_text != $text)
			$text = $new_text;
		else
			break;
	}

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode("\1", $text);
		$text = '';

		$num_tokens = count($outside);
		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
				$text .= '[code]'.$inside[$i].'[/code]';
		}
	}

	// Remove empty code tags
	while (($new_text = preg_replace('/\[(code)\]\s*\[\/\1\]/', '', $text)) !== NULL)
	{
		if ($new_text != $text)
			$text = $new_text;
		else
			break;
	}

	return $text;
}


//
// Check the structure of bbcode tags and fix simple mistakes where possible
//
function preparse_tags($text, &$errors, $is_signature = false)
{
	global $lang_common, $pun_config;

	// Start off by making some arrays of bbcode tags and what we need to do with each one

	// List of all the tags
	$tags = array('quote', 'code', 'b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'img', 'list', '*', 'h', 'acronym', 'q', 'sup', 'sub', 'left', 'right', 'center', 'justify', 'video');
	// List of tags that we need to check are open (You could not put b,i,u in here then illegal nesting like [b][i][/b][/i] would be allowed)
	$tags_opened = $tags;
	// and tags we need to check are closed (the same as above, added it just in case)
	$tags_closed = $tags;
	// Tags we can nest and the depth they can be nested to (only quotes)
	$tags_nested = array('quote' => $pun_config['o_quote_depth'], 'list' => 5, '*' => 5);
	// Tags to ignore the contents of completely (just code)
	$tags_ignore = array('code');
	// Block tags, block tags can only go within another block tag, they cannot be in a normal tag
	$tags_block = array('quote', 'code', 'list', 'h', '*', 'left', 'right', 'center', 'justify');
	// Inline tags, we do not allow new lines in these
	$tags_inline = array('b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'h', 'acronym', 'q', 'sup', 'sub', 'video');
	// Tags we trim interior space
	$tags_trim = array('img', 'video');
	// Tags we remove quotes from the argument
	$tags_quotes = array('url', 'email', 'img', 'video');
	// Tags we limit bbcode in
	$tags_limit_bbcode = array(
		'*' 	=> array('b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'list', 'img', 'code', 'acronym', 'q', 'sup', 'sub', 'video'),
		'list' 	=> array('*'),
		'url' 	=> array('b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'img', 'acronym', 'q', 'sup', 'sub'),
		'email' => array('b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'img', 'acronym', 'q', 'sup', 'sub'),
		'img' 	=> array(),
		'h'		=> array('b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email'),
		'video'  => array()
	);
	// Tags we can automatically fix bad nesting

	$tags_fix = array('quote', 'b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'h');

	$split_text = preg_split("/(\[[\*a-zA-Z0-9-\/]*?(?:=.*?)?\])/", $text, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

	$open_tags = array('post');
	$open_args = array('');
	$opened_tag = 0;
	$new_text = '';
	$current_ignore = '';
	$current_nest = '';
	$current_depth = array();
	$limit_bbcode = $tags;

	foreach ($split_text as $current)
	{
		if ($current == '')
			continue;

		// Are we dealing with a tag?
		if (substr($current, 0, 1) != '[' || substr($current, -1, 1) != ']')
		{
			// It's not a bbcode tag so we put it on the end and continue

			// If we are nested too deeply don't add to the end
			if ($current_nest)
				continue;

			$current = str_replace("\r\n", "\n", $current);
			$current = str_replace("\r", "\n", $current);
			if (in_array($open_tags[$opened_tag], $tags_inline) && strpos($current, "\n") !== false)
			{
				// Deal with new lines
				$split_current = preg_split("/(\n\n+)/", $current, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
				$current = '';

				if (!pun_trim($split_current[0], "\n")) // The first part is a linebreak so we need to handle any open tags first
					array_unshift($split_current, '');

				for ($i = 1; $i < count($split_current); $i += 2)
				{
					$temp_opened = array();
					$temp_opened_arg = array();
					$temp = $split_current[$i - 1];
					while (!empty($open_tags))
					{
						$temp_tag = array_pop($open_tags);
						$temp_arg = array_pop($open_args);

						if (in_array($temp_tag , $tags_inline))
						{
							array_push($temp_opened, $temp_tag);
							array_push($temp_opened_arg, $temp_arg);
							$temp .= '[/'.$temp_tag.']';
						}
						else
						{
							array_push($open_tags, $temp_tag);
							array_push($open_args, $temp_arg);
							break;
						}
					}
					$current .= $temp.$split_current[$i];
					$temp = '';
					while (!empty($temp_opened))
					{
						$temp_tag = array_pop($temp_opened);
						$temp_arg = array_pop($temp_opened_arg);
						if (empty($temp_arg))
							$temp .= '['.$temp_tag.']';
						else
							$temp .= '['.$temp_tag.'='.$temp_arg.']';
						array_push($open_tags, $temp_tag);
						array_push($open_args, $temp_arg);
					}
					$current .= $temp;
				}

				if (array_key_exists($i - 1, $split_current))
					$current .= $split_current[$i - 1];
			}

			if (in_array($open_tags[$opened_tag], $tags_trim))
				$new_text .= pun_trim($current);
			else
				$new_text .= $current;

			continue;
		}

		// Get the name of the tag
		$current_arg = '';
		if (strpos($current, '/') === 1)
		{
			$current_tag = substr($current, 2, -1);
		}
		else if (strpos($current, '=') === false)
		{
			$current_tag = substr($current, 1, -1);
		}
		else
		{
			$current_tag = substr($current, 1, strpos($current, '=')-1);
			$current_arg = substr($current, strpos($current, '=')+1, -1);
		}
		$current_tag = strtolower($current_tag);

		// Is the tag defined?
		if (!in_array($current_tag, $tags))
		{
			// It's not a bbcode tag so we put it on the end and continue
			if (!$current_nest)
				$new_text .= $current;

			continue;
		}

		// We definitely have a bbcode tag

		// Make the tag string lower case
		if ($equalpos = strpos($current,'='))
		{
			// We have an argument for the tag which we don't want to make lowercase
			if (strlen(substr($current, $equalpos)) == 2)
			{
				// Empty tag argument
				$errors[] = sprintf($lang_common['BBCode error empty attribute'], $current_tag);
				return false;
			}
			$current = strtolower(substr($current, 0, $equalpos)).substr($current, $equalpos);
		}
		else
			$current = strtolower($current);

		// This is if we are currently in a tag which escapes other bbcode such as code
		if ($current_ignore)
		{
			if ('[/'.$current_ignore.']' == $current)
			{
				// We've finished the ignored section
				$current = '[/'.$current_tag.']';
				$current_ignore = '';
			}

			$new_text .= $current;

			continue;
		}

		if ($current_nest)
		{
			// We are currently too deeply nested so lets see if we are closing the tag or not
			if ($current_tag != $current_nest)
				continue;

			if (substr($current, 1, 1) == '/')
				$current_depth[$current_nest]--;
			else
				$current_depth[$current_nest]++;

			if ($current_depth[$current_nest] <= $tags_nested[$current_nest])
				$current_nest = '';

			continue;
		}

		// Check the current tag is allowed here
		if (!in_array($current_tag, $limit_bbcode) && $current_tag != $open_tags[$opened_tag])
		{
			$errors[] = sprintf($lang_common['BBCode error invalid nesting'], $current_tag, $open_tags[$opened_tag]);
			return false;
		}

		if (substr($current, 1, 1) == '/')
		{
			// This is if we are closing a tag

			if ($opened_tag == 0 || !in_array($current_tag, $open_tags))
			{
				// We tried to close a tag which is not open
				if (in_array($current_tag, $tags_opened))
				{
					$errors[] = sprintf($lang_common['BBCode error no opening tag'], $current_tag);
					return false;
				}
			}
			else
			{
				// Check nesting
				while (true)
				{
					// Nesting is ok
					if ($open_tags[$opened_tag] == $current_tag)
					{
						array_pop($open_tags);
						array_pop($open_args);
						$opened_tag--;
						break;
					}

					// Nesting isn't ok, try to fix it
					if (in_array($open_tags[$opened_tag], $tags_closed) && in_array($current_tag, $tags_closed))
					{
						if (in_array($current_tag, $open_tags))
						{
							$temp_opened = array();
							$temp_opened_arg = array();
							$temp = '';
							while (!empty($open_tags))
							{
								$temp_tag = array_pop($open_tags);
								$temp_arg = array_pop($open_args);

								if (!in_array($temp_tag, $tags_fix))
								{
									// We couldn't fix nesting
									$errors[] = sprintf($lang_common['BBCode error no closing tag'], array_pop($temp_opened));
									return false;
								}
								array_push($temp_opened, $temp_tag);
								array_push($temp_opened_arg, $temp_arg);

								if ($temp_tag == $current_tag)
									break;
								else
									$temp .= '[/'.$temp_tag.']';
							}
							$current = $temp.$current;
							$temp = '';
							array_pop($temp_opened);
							array_pop($temp_opened_arg);

							while (!empty($temp_opened))
							{
								$temp_tag = array_pop($temp_opened);
								$temp_arg = array_pop($temp_opened_arg);
								if (empty($temp_arg))
									$temp .= '['.$temp_tag.']';
								else
									$temp .= '['.$temp_tag.'='.$temp_arg.']';
								array_push($open_tags, $temp_tag);
								array_push($open_args, $temp_arg);
							}
							$current .= $temp;
							$opened_tag--;
							break;
						}
						else
						{
							// We couldn't fix nesting
							$errors[] = sprintf($lang_common['BBCode error no opening tag'], $current_tag);
							return false;
						}
					}
					else if (in_array($open_tags[$opened_tag], $tags_closed))
						break;
					else
					{
						array_pop($open_tags);
						array_pop($open_args);
						$opened_tag--;
					}
				}
			}

			if (in_array($current_tag, array_keys($tags_nested)))
			{
				if (isset($current_depth[$current_tag]))
					$current_depth[$current_tag]--;
			}

			if (in_array($open_tags[$opened_tag], array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$open_tags[$opened_tag]];
			else
				$limit_bbcode = $tags;

			$new_text .= $current;

			continue;
		}
		else
		{
			// We are opening a tag
			if (in_array($current_tag, array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$current_tag];
			else
				$limit_bbcode = $tags;

			if (in_array($current_tag, $tags_block) && !in_array($open_tags[$opened_tag], $tags_block) && $opened_tag != 0)
			{
				// We tried to open a block tag within a non-block tag
				$errors[] = sprintf($lang_common['BBCode error invalid nesting'], $current_tag, $open_tags[$opened_tag]);
				return false;
			}

			if (in_array($current_tag, $tags_ignore))
			{
				// It's an ignore tag so we don't need to worry about what's inside it
				$current_ignore = $current_tag;
				$new_text .= $current;
				continue;
			}

			// Deal with nested tags
			if (in_array($current_tag, $open_tags) && !in_array($current_tag, array_keys($tags_nested)))
			{
				// We nested a tag we shouldn't
				$errors[] = sprintf($lang_common['BBCode error invalid self-nesting'], $current_tag);
				return false;
			}
			else if (in_array($current_tag, array_keys($tags_nested)))
			{
				// We are allowed to nest this tag

				if (isset($current_depth[$current_tag]))
					$current_depth[$current_tag]++;
				else
					$current_depth[$current_tag] = 1;

				// See if we are nested too deep
				if ($current_depth[$current_tag] > $tags_nested[$current_tag])
				{
					$current_nest = $current_tag;
					continue;
				}
			}

			// Remove quotes from arguments for certain tags
			if (strpos($current, '=') !== false && in_array($current_tag, $tags_quotes))
			{
				$current = preg_replace('#\['.$current_tag.'=("|\'|)(.*?)\\1\]\s*#i', '['.$current_tag.'=$2]', $current);
			}

			if (in_array($current_tag, array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$current_tag];

			$open_tags[] = $current_tag;
			$open_args[] = $current_arg;
			$opened_tag++;
			$new_text .= $current;
			continue;
		}
	}

	// Check we closed all the tags we needed to
	foreach ($tags_closed as $check)
	{
		if (in_array($check, $open_tags))
		{
			// We left an important tag open
			$errors[] = sprintf($lang_common['BBCode error no closing tag'], $check);
			return false;
		}
	}

	if ($current_ignore)
	{
		// We left an ignore tag open
		$errors[] = sprintf($lang_common['BBCode error no closing tag'], $current_ignore);
		return false;
	}

	return $new_text;
}


//
// Preparse the contents of [list] bbcode
//
function preparse_list_tag($content, $type = '*', &$errors)
{
	global $lang_common, $re_list;

	if (strlen($type) != 1)
		$type = '*';

	if (strpos($content,'[list') !== false)
	{
		$content = preg_replace($re_list, 'preparse_list_tag(\'$2\', \'$1\', $errors)', $content);
	}

	$items = explode('[*]', str_replace('\"', '"', $content));

	$content = '';
	foreach ($items as $item)
	{
		if (pun_trim($item) != '')
			$content .= '[*'."\0".']'.str_replace('[/*]', '', pun_trim($item)).'[/*'."\0".']'."\n";
	}

	return '[list='.$type.']'."\n".$content.'[/list]';
}


//
// Truncate URL if longer than 55 characters (add http:// or ftp:// if missing)
//
function handle_url_tag($url, $link = '', $bbcode = false)
{
	$url = pun_trim($url);
	$full_url = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $url);
	if (strpos($url, 'www.') === 0) // If it starts with www, we add http://
		$full_url = 'http://'.$full_url;
	else if (strpos($url, 'ftp.') === 0) // Else if it starts with ftp, we add ftp://
		$full_url = 'ftp://'.$full_url;
	else if (!preg_match('#^([a-z0-9]{3,6})://#', $url)) // Else if it doesn't start with abcdef://, we add http://
		$full_url = 'http://'.$full_url;

	// Ok, not very pretty :-)
	if ($bbcode)
	{
		if ($full_url == $link)
			return '[url]'.$link.'[/url]';
		else
			return '[url='.$full_url.']'.$link.'[/url]';
	}
	else
	{
		if ($link == '' || $link == $url)
		{
			$url = pun_htmlspecialchars_decode($url);
			$link = utf8_strlen($url) > 55 ? utf8_substr($url, 0 , 39).' … '.utf8_substr($url, -10) : $url;
			$link = pun_htmlspecialchars($link);
		}
		else
			$link = stripslashes($link);

		return '<a href="'.$full_url.'">'.$link.'</a>';
	}
}


//
// Turns an URL from the [img] tag into an <img> tag or a <a href...> tag
//
function handle_img_tag($url, $is_signature = false, $alt = null)
{
	global $lang_common, $pun_user;

	if ($alt == null)
		$alt = basename($url);

	$img_tag = '<a href="'.$url.'">&lt;'.$lang_common['Image link'].' - '.$alt.'&gt;</a>';

	if ($is_signature && $pun_user['show_img_sig'] != '0')
		$img_tag = '<img class="sigimage" src="'.$url.'" alt="'.$alt.'" />';
	else if (!$is_signature && $pun_user['show_img'] != '0')
		$img_tag = '<span class="postimg"><img src="'.$url.'" alt="'.$alt.'" /></span>';

	return $img_tag;
}


//
// Parse the contents of [list] bbcode
//
function handle_list_tag($content, $type = '*')
{
	global $re_list;

	if (strlen($type) != 1)
		$type = '*';

	if (strpos($content,'[list') !== false)
	{
		$content = preg_replace($re_list, 'handle_list_tag(\'$2\', \'$1\')', $content);
	}

	$content = preg_replace('#\s*\[\*\](.*?)\[/\*\]\s*#s', '<li><p>$1</p></li>', pun_trim($content));

	if ($type == '*')
		$content = '<ul>'.$content.'</ul>';
	else
		if ($type == 'a')
			$content = '<ol class="alpha">'.$content.'</ol>';
		else
			$content = '<ol class="decimal">'.$content.'</ol>';

	return '</p>'.$content.'<p>';
}


//
// Convert BBCodes to their HTML equivalent
//
function do_bbcode($text, $is_signature = false)
{
	global $lang_common, $pun_user, $pun_config, $re_list;

	if (strpos($text, '[quote') !== false)
	{
		$text = preg_replace('#\[quote\]\s*#', '</p><div class="quotebox"><blockquote><div><p>', $text);
		$text = preg_replace('#\[quote=(&quot;|&\#039;|"|\'|)(.*?)\\1\]#se', '"</p><div class=\"quotebox\"><cite>".str_replace(array(\'[\', \'\\"\'), array(\'&#91;\', \'"\'), \'$2\')." ".$lang_common[\'wrote\']."</cite><blockquote><div><p>"', $text);
		$text = preg_replace('#\s*\[\/quote\]#S', '</p></div></blockquote></div><p>', $text);
	}

	if (!$is_signature)
	{
		$pattern[] = $re_list;
		$replace[] = 'handle_list_tag(\'$2\', \'$1\')';
	}

	$pattern[] = '#\[b\](.*?)\[/b\]#ms';
	$pattern[] = '#\[i\](.*?)\[/i\]#ms';
	$pattern[] = '#\[u\](.*?)\[/u\]#ms';
	$pattern[] = '#\[s\](.*?)\[/s\]#ms';
	$pattern[] = '#\[del\](.*?)\[/del\]#ms';
	$pattern[] = '#\[ins\](.*?)\[/ins\]#ms';
	$pattern[] = '#\[em\](.*?)\[/em\]#ms';
	$pattern[] = '#\[colou?r=([a-zA-Z]{3,20}|\#[0-9a-fA-F]{6}|\#[0-9a-fA-F]{3})](.*?)\[/colou?r\]#ms';
	$pattern[] = '#\[h\](.*?)\[/h\]#ms';
	$pattern[] = '#\[acronym\](.*?)\[/acronym\]#ms';
	$pattern[] = '#\[acronym=(.*?)\](.*?)\[/acronym\]#ms';
	$pattern[] = '#\[q\](.*?)\[/q\]#ms';
	$pattern[] = '#\[sup\](.*?)\[/sup\]#ms';
	$pattern[] = '#\[sub\](.*?)\[/sub\]#ms';
	$pattern[] = '#\[left\](.*?)\[/left\]#ms';
	$pattern[] = '#\[right\](.*?)\[/right\]#ms';
	$pattern[] = '#\[center\](.*?)\[/center\]#ms';
	$pattern[] = '#\[justify\](.*?)\[/justify\]#ms';
	$pattern[] = '#\[video\]([^\[<]*?)/video/([^_\[<]*?)_([^\[<]*?)\[/video\]#ms';
	$pattern[] = '#\[video=([0-9]+),([0-9]+)\]([^\[<]*?)/video/([^_\[<]*?)_([^\[<]*?)\[/video\]#ms';
	$pattern[] = '#\[video\]([^\[<]*?)/(v/|watch\?v=)([^\[<]*?)\[/video\]#ms';
	$pattern[] = '#\[video=([0-9]+),([0-9]+)\]([^\[<]*?)/(v/|watch\?v=)([^\[<]*?)\[/video\]#ms';

	$replace[] = '<strong>$1</strong>';
	$replace[] = '<em>$1</em>';
	$replace[] = '<span class="bbu">$1</span>';
	$replace[] = '<span class="bbs">$1</span>';
	$replace[] = '<del>$1</del>';
	$replace[] = '<ins>$1</ins>';
	$replace[] = '<em>$1</em>';
	$replace[] = '<span style="color: $1">$2</span>';
	$replace[] = '</p><h5>$1</h5><p>';
	$replace[] = '<acronym>$1</acronym>';
	$replace[] = '<acronym title="$1">$2</acronym>';
	$replace[] = '<q>$1</q>';
	$replace[] = '<sup>$1</sup>';
	$replace[] = '<sub>$1</sub>';
	$replace[] = '</p><p style="text-align: left">$1</p><p>';
	$replace[] = '</p><p style="text-align: right">$1</p><p>';
	$replace[] = '</p><p style="text-align: center">$1</p><p>';
	$replace[] = '</p><p style="text-align: justify">$1</p><p>';
	$replace[] = '<object type="application/x-shockwave-flash" data="http://www.dailymotion.com/swf/video/$2" width="480" height="384"><param name="movie" value="http://www.dailymotion.com/swf/video/$2" /><param name="allowFullScreen" value="true" /><param name="allowScriptAccess" value="always" /><p>Flash required</p></object>';
	$replace[] = '<object type="application/x-shockwave-flash" data="http://www.dailymotion.com/swf/video/$4" width="$1" height="$2"><param name="movie" value="http://www.dailymotion.com/swf/video/$4" /><param name="allowFullScreen" value="true" /><param name="allowScriptAccess" value="always" /><p>Flash required</p></object>';
	$replace[] = '<object type="application/x-shockwave-flash" data="http://www.youtube.com/v/$3" width="425" height="344"><param name="movie" value="http://www.youtube.com/v/$3" /><param name="allowFullScreen" value="true" /><param name="allowScriptAccess" value="always" /><p>Flash required</p></object>';
	$replace[] = '<object type="application/x-shockwave-flash" data="http://www.youtube.com/v/$5" width="$1" height="$2"><param name="movie" value="http://www.youtube.com/v/$5" /><param name="allowFullScreen" value="true" /><param name="allowScriptAccess" value="always" /><p>Flash required</p></object>';


	if (($is_signature && $pun_config['p_sig_img_tag'] == '1') || (!$is_signature && $pun_config['p_message_img_tag'] == '1'))
	{
		$pattern[] = '#\[img\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e';
		$pattern[] = '#\[img=([^\[]*?)\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e';
		if ($is_signature)
		{
			$replace[] = 'handle_img_tag(\'$1$3\', true)';
			$replace[] = 'handle_img_tag(\'$2$4\', true, \'$1\')';
		}
		else
		{
			$replace[] = 'handle_img_tag(\'$1$3\', false)';
			$replace[] = 'handle_img_tag(\'$2$4\', false, \'$1\')';
		}
	}

	$pattern[] = '#\[url\]([^\[]*?)\[/url\]#e';
	$pattern[] = '#\[url=([^\[]+?)\](.*?)\[/url\]#e';
	$pattern[] = '#\[email\]([^\[]*?)\[/email\]#';
	$pattern[] = '#\[email=([^\[]+?)\](.*?)\[/email\]#';

	$replace[] = 'handle_url_tag(\'$1\')';
	$replace[] = 'handle_url_tag(\'$1\', \'$2\')';
	$replace[] = '<a href="mailto:$1">$1</a>';
	$replace[] = '<a href="mailto:$1">$2</a>';

	// This thing takes a while! :)
	$text = preg_replace($pattern, $replace, $text);

	return $text;
}


//
// Make hyperlinks clickable
//
function do_clickable($text)
{
	$text = ' '.$text;

	$text = ucp_preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(https?|ftp|news){1}://([\p{L}\p{N}\-]+\.([\p{L}\p{N}\-]+\.)*[\p{L}\p{N}]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#uie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5://$6\', \'$5://$6\', true).stripslashes(\'$4$10$11$12\')', $text);
	$text = ucp_preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(www|ftp)\.(([\p{L}\p{N}\-]+\.)*[\p{L}\p{N}]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#uie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5.$6\', \'$5.$6\', true).stripslashes(\'$4$10$11$12\')', $text);

	return substr($text, 1);
}


//
// Convert a series of smilies to images
//
function do_smilies($text)
{
    global $pun_config, $smilies;

    $text = ' '.$text.' ';

    foreach ($smilies as $smiley_text => $smiley_img)
    {
        if (strpos($text, $smiley_text) !== false)
            $text = ucp_preg_replace(
                '#(?<=[>\s])'.preg_quote($smiley_text, '#').'(?=[^\p{L}\p{N}])#um', 
                '<img src="' . pun_htmlspecialchars(get_base_url(true) . '/img/smilies/'.$smiley_img) . 
                '" width="15" height="15" alt="' . substr($smiley_img, 0, strrpos($smiley_img, '.')) . '" />', 
                $text);
    }

    return substr($text, 1, -1);
}


//
// Parse message text
//
function parse_message($text, $hide_smilies)
{
	global $pun_config, $lang_common, $pun_user;

	if ($pun_config['o_censoring'] == '1')
		$text = censor_words($text);

	// Convert applicable characters to HTML entities
	$text = pun_htmlspecialchars($text);

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($text, '[code]', '[/code]', $errors);
		$text = implode("\1", $outside);
	}

	if ($pun_config['p_message_bbcode'] == '1' && strpos($text, '[') !== false && strpos($text, ']') !== false)
		$text = do_bbcode($text);

	if ($pun_config['o_smilies'] == '1' && $pun_user['show_smilies'] == '1' && $hide_smilies == '0')
		$text = do_smilies($text);

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br />', '&#160; &#160; ', '&#160; ', ' &#160;');
	$text = str_replace($pattern, $replace, $text);

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode("\1", $text);
		$text = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
			//	$text .= '</p><div class="codebox"><pre><code>'.pun_trim($inside[$i], "\n\r").'</code></pre></div><p>';
			{
				$num_lines = (substr_count($inside[$i], "\n"));
				$text .= '</p><div class="codebox"><pre'.(($num_lines > 28) ? ' class="vscroll"' : '').'><code>'.pun_trim($inside[$i], "\n\r").'</code></pre></div><p>';
			}
		}
	}

	// Add paragraph tag around post, but make sure there are no empty paragraphs
	$text = preg_replace('#<br />\s*?<br />((\s*<br />)*)#i', "</p>$1<p>", $text);
	$text = str_replace('<p><br />', '<p>', $text);
	$text = str_replace('<p></p>', '', '<p>'.$text.'</p>');

	return $text;
}


//
// Parse signature text
//
function parse_signature($text)
{
	global $pun_config, $lang_common, $pun_user;

	if ($pun_config['o_censoring'] == '1')
		$text = censor_words($text);

	// Convert applicable characters to HTML entities
	$text = pun_htmlspecialchars($text);

	if ($pun_config['p_sig_bbcode'] == '1' && strpos($text, '[') !== false && strpos($text, ']') !== false)
		$text = do_bbcode($text, true);

	if ($pun_config['o_smilies_sig'] == '1' && $pun_user['show_smilies'] == '1')
		$text = do_smilies($text);


	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br />', '&#160; &#160; ', '&#160; ', ' &#160;');
	$text = str_replace($pattern, $replace, $text);

	// Add paragraph tag around post, but make sure there are no empty paragraphs
	$text = preg_replace('#<br />\s*?<br />((\s*<br />)*)#i', "</p>$1<p>", $text);
	$text = str_replace('<p><br />', '<p>', $text);
	$text = str_replace('<p></p>', '', '<p>'.$text.'</p>');

	return $text;
}
