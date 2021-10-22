<?php

/*
htmLawed 1.1.14, 8 August 2012
Copyright Santosh Patnaik
Dual licensed with LGPL 3 and GPL 2+
A PHP Labware internal utility; www.bioinformatics.org/phplabware/internal_utilities/htmLawed

See htmLawed_README.txt/htm
*/

function htmLawed($t, $C = 1, $S = [])
{
    $C = is_array($C) ? $C : [];
    if (!empty($C['valid_xhtml'])) {
        $C['elements'] = empty($C['elements']) ? '*-center-dir-font-isindex-menu-s-strike-u' : $C['elements'];
        $C['make_tag_strict'] = isset($C['make_tag_strict']) ? $C['make_tag_strict'] : 2;
        $C['xml:lang'] = isset($C['xml:lang']) ? $C['xml:lang'] : 2;
    }
    // config eles
$e = ['a' => 1, 'abbr' => 1, 'acronym' => 1, 'address' => 1, 'applet' => 1, 'area' => 1, 'b' => 1, 'bdo' => 1, 'big' => 1, 'blockquote' => 1, 'br' => 1, 'button' => 1, 'caption' => 1, 'center' => 1, 'cite' => 1, 'code' => 1, 'col' => 1, 'colgroup' => 1, 'dd' => 1, 'del' => 1, 'dfn' => 1, 'dir' => 1, 'div' => 1, 'dl' => 1, 'dt' => 1, 'em' => 1, 'embed' => 1, 'fieldset' => 1, 'font' => 1, 'form' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1, 'hr' => 1, 'i' => 1, 'iframe' => 1, 'img' => 1, 'input' => 1, 'ins' => 1, 'isindex' => 1, 'kbd' => 1, 'label' => 1, 'legend' => 1, 'li' => 1, 'map' => 1, 'menu' => 1, 'noscript' => 1, 'object' => 1, 'ol' => 1, 'optgroup' => 1, 'option' => 1, 'p' => 1, 'param' => 1, 'pre' => 1, 'q' => 1, 'rb' => 1, 'rbc' => 1, 'rp' => 1, 'rt' => 1, 'rtc' => 1, 'ruby' => 1, 's' => 1, 'samp' => 1, 'script' => 1, 'select' => 1, 'small' => 1, 'span' => 1, 'strike' => 1, 'strong' => 1, 'sub' => 1, 'sup' => 1, 'table' => 1, 'tbody' => 1, 'td' => 1, 'textarea' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'tr' => 1, 'tt' => 1, 'u' => 1, 'ul' => 1, 'var' => 1]; // 86/deprecated+embed+ruby
if (!empty($C['safe'])) {
    unset($e['applet'], $e['embed'], $e['iframe'], $e['object'], $e['script']);
}
    $x = !empty($C['elements']) ? str_replace(["\n", "\r", "\t", ' '], '', $C['elements']) : '*';
    if ('-*' == $x) {
        $e = [];
    } elseif (false === strpos($x, '*')) {
        $e = array_flip(explode(',', $x));
    } else {
        if (isset($x[1])) {
            preg_match_all('`(?:^|-|\+)[^\-+]+?(?=-|\+|$)`', $x, $m, \PREG_SET_ORDER);
            for ($i = count($m); --$i >= 0;) {
                $m[$i] = $m[$i][0];
            }
            foreach ($m as $v) {
                if ('+' == $v[0]) {
                    $e[substr($v, 1)] = 1;
                }
                if ('-' == $v[0] && isset($e[($v = substr($v, 1))]) && !in_array('+'.$v, $m, true)) {
                    unset($e[$v]);
                }
            }
        }
    }
    $C['elements'] = &$e;
    // config attrs
    $x = !empty($C['deny_attribute']) ? str_replace(["\n", "\r", "\t", ' '], '', $C['deny_attribute']) : '';
    $x = array_flip((isset($x[0]) && '*' == $x[0]) ? explode('-', $x) : explode(',', $x.(!empty($C['safe']) ? ',on*' : '')));
    if (isset($x['on*'])) {
        unset($x['on*']);
        $x += ['onblur' => 1, 'onchange' => 1, 'onclick' => 1, 'ondblclick' => 1, 'onfocus' => 1, 'onkeydown' => 1, 'onkeypress' => 1, 'onkeyup' => 1, 'onmousedown' => 1, 'onmousemove' => 1, 'onmouseout' => 1, 'onmouseover' => 1, 'onmouseup' => 1, 'onreset' => 1, 'onselect' => 1, 'onsubmit' => 1];
    }
    $C['deny_attribute'] = $x;
    // config URL
    $x = (isset($C['schemes'][2]) && strpos($C['schemes'], ':')) ? strtolower($C['schemes']) : 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https';
    $C['schemes'] = [];
    foreach (explode(';', str_replace([' ', "\t", "\r", "\n"], '', $x)) as $v) {
        $x = $x2 = null;
        list($x, $x2) = explode(':', $v, 2);
        if ($x2) {
            $C['schemes'][$x] = array_flip(explode(',', $x2));
        }
    }
    if (!isset($C['schemes']['*'])) {
        $C['schemes']['*'] = ['file' => 1, 'http' => 1, 'https' => 1];
    }
    if (!empty($C['safe']) && empty($C['schemes']['style'])) {
        $C['schemes']['style'] = ['!' => 1];
    }
    $C['abs_url'] = isset($C['abs_url']) ? $C['abs_url'] : 0;
    if (!isset($C['base_url']) || !preg_match('`^[a-zA-Z\d.+\-]+://[^/]+/(.+?/)?$`', $C['base_url'])) {
        $C['base_url'] = $C['abs_url'] = 0;
    }
    // config rest
    $C['and_mark'] = empty($C['and_mark']) ? 0 : 1;
    $C['anti_link_spam'] = (isset($C['anti_link_spam']) && is_array($C['anti_link_spam']) && 2 == count($C['anti_link_spam']) && (empty($C['anti_link_spam'][0]) || hl_regex($C['anti_link_spam'][0])) && (empty($C['anti_link_spam'][1]) || hl_regex($C['anti_link_spam'][1]))) ? $C['anti_link_spam'] : 0;
    $C['anti_mail_spam'] = isset($C['anti_mail_spam']) ? $C['anti_mail_spam'] : 0;
    $C['balance'] = isset($C['balance']) ? (bool) $C['balance'] : 1;
    $C['cdata'] = isset($C['cdata']) ? $C['cdata'] : (empty($C['safe']) ? 3 : 0);
    $C['clean_ms_char'] = empty($C['clean_ms_char']) ? 0 : $C['clean_ms_char'];
    $C['comment'] = isset($C['comment']) ? $C['comment'] : (empty($C['safe']) ? 3 : 0);
    $C['css_expression'] = empty($C['css_expression']) ? 0 : 1;
    $C['direct_list_nest'] = empty($C['direct_list_nest']) ? 0 : 1;
    $C['hexdec_entity'] = isset($C['hexdec_entity']) ? $C['hexdec_entity'] : 1;
    $C['hook'] = (!empty($C['hook']) && function_exists($C['hook'])) ? $C['hook'] : 0;
    $C['hook_tag'] = (!empty($C['hook_tag']) && function_exists($C['hook_tag'])) ? $C['hook_tag'] : 0;
    $C['keep_bad'] = isset($C['keep_bad']) ? $C['keep_bad'] : 6;
    $C['lc_std_val'] = isset($C['lc_std_val']) ? (bool) $C['lc_std_val'] : 1;
    $C['make_tag_strict'] = isset($C['make_tag_strict']) ? $C['make_tag_strict'] : 1;
    $C['named_entity'] = isset($C['named_entity']) ? (bool) $C['named_entity'] : 1;
    $C['no_deprecated_attr'] = isset($C['no_deprecated_attr']) ? $C['no_deprecated_attr'] : 1;
    $C['parent'] = isset($C['parent'][0]) ? strtolower($C['parent']) : 'body';
    $C['show_setting'] = !empty($C['show_setting']) ? $C['show_setting'] : 0;
    $C['style_pass'] = empty($C['style_pass']) ? 0 : 1;
    $C['tidy'] = empty($C['tidy']) ? 0 : $C['tidy'];
    $C['unique_ids'] = isset($C['unique_ids']) ? $C['unique_ids'] : 1;
    $C['xml:lang'] = isset($C['xml:lang']) ? $C['xml:lang'] : 0;

    if (isset($GLOBALS['C'])) {
        $reC = $GLOBALS['C'];
    }
    $GLOBALS['C'] = $C;
    $S = is_array($S) ? $S : hl_spec($S);
    if (isset($GLOBALS['S'])) {
        $reS = $GLOBALS['S'];
    }
    $GLOBALS['S'] = $S;

    $t = preg_replace('`[\x00-\x08\x0b-\x0c\x0e-\x1f]`', '', $t);
    if ($C['clean_ms_char']) {
        $x = ["\x7f" => '', "\x80" => '&#8364;', "\x81" => '', "\x83" => '&#402;', "\x85" => '&#8230;', "\x86" => '&#8224;', "\x87" => '&#8225;', "\x88" => '&#710;', "\x89" => '&#8240;', "\x8a" => '&#352;', "\x8b" => '&#8249;', "\x8c" => '&#338;', "\x8d" => '', "\x8e" => '&#381;', "\x8f" => '', "\x90" => '', "\x95" => '&#8226;', "\x96" => '&#8211;', "\x97" => '&#8212;', "\x98" => '&#732;', "\x99" => '&#8482;', "\x9a" => '&#353;', "\x9b" => '&#8250;', "\x9c" => '&#339;', "\x9d" => '', "\x9e" => '&#382;', "\x9f" => '&#376;'];
        $x = $x + (1 == $C['clean_ms_char'] ? ["\x82" => '&#8218;', "\x84" => '&#8222;', "\x91" => '&#8216;', "\x92" => '&#8217;', "\x93" => '&#8220;', "\x94" => '&#8221;'] : ["\x82" => '\'', "\x84" => '"', "\x91" => '\'', "\x92" => '\'', "\x93" => '"', "\x94" => '"']);
        $t = strtr($t, $x);
    }
    if ($C['cdata'] || $C['comment']) {
        $t = preg_replace_callback('`<!(?:(?:--.*?--)|(?:\[CDATA\[.*?\]\]))>`sm', 'hl_cmtcd', $t);
    }
    $t = preg_replace_callback('`&amp;([A-Za-z][A-Za-z0-9]{1,30}|#(?:[0-9]{1,8}|[Xx][0-9A-Fa-f]{1,7}));`', 'hl_ent', str_replace('&', '&amp;', $t));
    if ($C['unique_ids'] && !isset($GLOBALS['hl_Ids'])) {
        $GLOBALS['hl_Ids'] = [];
    }
    if ($C['hook']) {
        $t = $C['hook']($t, $C, $S);
    }
    if ($C['show_setting'] && preg_match('`^[a-z][a-z0-9_]*$`i', $C['show_setting'])) {
        $GLOBALS[$C['show_setting']] = ['config' => $C, 'spec' => $S, 'time' => microtime()];
    }
    // main
    $t = preg_replace_callback('`<(?:(?:\s|$)|(?:[^>]*(?:>|$)))|>`m', 'hl_tag', $t);
    $t = $C['balance'] ? hl_bal($t, $C['keep_bad'], $C['parent']) : $t;
    $t = (($C['cdata'] || $C['comment']) && false !== strpos($t, "\x01")) ? str_replace(["\x01", "\x02", "\x03", "\x04", "\x05"], ['', '', '&', '<', '>'], $t) : $t;
    $t = $C['tidy'] ? hl_tidy($t, $C['tidy'], $C['parent']) : $t;
    unset($C, $e);
    if (isset($reC)) {
        $GLOBALS['C'] = $reC;
    }
    if (isset($reS)) {
        $GLOBALS['S'] = $reS;
    }

    return $t;
    // eof
}

function hl_attrval($t, $p)
{
    // check attr val against $S
    $o = 1;
    $l = strlen($t);
    foreach ($p as $k => $v) {
        switch ($k) {
  case 'maxlen': if ($l > $v) {
      $o = 0;
  }
  break; case 'minlen': if ($l < $v) {
      $o = 0;
  }
  break; case 'maxval': if ((float) ($t) > $v) {
      $o = 0;
  }
  break; case 'minval': if ((float) ($t) < $v) {
      $o = 0;
  }
  break; case 'match': if (!preg_match($v, $t)) {
      $o = 0;
  }
  break; case 'nomatch': if (preg_match($v, $t)) {
      $o = 0;
  }
  break; case 'oneof':
   $m = 0;
   foreach (explode('|', $v) as $n) {
       if ($t == $n) {
           $m = 1;
           break;
       }
   }
   $o = $m;
  break; case 'noneof':
   $m = 1;
   foreach (explode('|', $v) as $n) {
       if ($t == $n) {
           $m = 0;
           break;
       }
   }
   $o = $m;
  break; default:
  break;
 }
        if (!$o) {
            break;
        }
    }

    return $o ? $t : (isset($p['default']) ? $p['default'] : 0);
    // eof
}

function hl_bal($t, $do = 1, $in = 'div')
{
    // balance tags
// by content
$cB = ['blockquote' => 1, 'form' => 1, 'map' => 1, 'noscript' => 1]; // Block
$cE = ['area' => 1, 'br' => 1, 'col' => 1, 'embed' => 1, 'hr' => 1, 'img' => 1, 'input' => 1, 'isindex' => 1, 'param' => 1]; // Empty
$cF = ['button' => 1, 'del' => 1, 'div' => 1, 'dd' => 1, 'fieldset' => 1, 'iframe' => 1, 'ins' => 1, 'li' => 1, 'noscript' => 1, 'object' => 1, 'td' => 1, 'th' => 1]; // Flow; later context-wise dynamic move of ins & del to $cI
$cI = ['a' => 1, 'abbr' => 1, 'acronym' => 1, 'address' => 1, 'b' => 1, 'bdo' => 1, 'big' => 1, 'caption' => 1, 'cite' => 1, 'code' => 1, 'dfn' => 1, 'dt' => 1, 'em' => 1, 'font' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1, 'i' => 1, 'kbd' => 1, 'label' => 1, 'legend' => 1, 'p' => 1, 'pre' => 1, 'q' => 1, 'rb' => 1, 'rt' => 1, 's' => 1, 'samp' => 1, 'small' => 1, 'span' => 1, 'strike' => 1, 'strong' => 1, 'sub' => 1, 'sup' => 1, 'tt' => 1, 'u' => 1, 'var' => 1]; // Inline
$cN = ['a' => ['a' => 1], 'button' => ['a' => 1, 'button' => 1, 'fieldset' => 1, 'form' => 1, 'iframe' => 1, 'input' => 1, 'label' => 1, 'select' => 1, 'textarea' => 1], 'fieldset' => ['fieldset' => 1], 'form' => ['form' => 1], 'label' => ['label' => 1], 'noscript' => ['script' => 1], 'pre' => ['big' => 1, 'font' => 1, 'img' => 1, 'object' => 1, 'script' => 1, 'small' => 1, 'sub' => 1, 'sup' => 1], 'rb' => ['ruby' => 1], 'rt' => ['ruby' => 1]]; // Illegal
$cN2 = array_keys($cN);
    $cR = ['blockquote' => 1, 'dir' => 1, 'dl' => 1, 'form' => 1, 'map' => 1, 'menu' => 1, 'noscript' => 1, 'ol' => 1, 'optgroup' => 1, 'rbc' => 1, 'rtc' => 1, 'ruby' => 1, 'select' => 1, 'table' => 1, 'tbody' => 1, 'tfoot' => 1, 'thead' => 1, 'tr' => 1, 'ul' => 1];
    $cS = ['colgroup' => ['col' => 1], 'dir' => ['li' => 1], 'dl' => ['dd' => 1, 'dt' => 1], 'menu' => ['li' => 1], 'ol' => ['li' => 1], 'optgroup' => ['option' => 1], 'option' => ['#pcdata' => 1], 'rbc' => ['rb' => 1], 'rp' => ['#pcdata' => 1], 'rtc' => ['rt' => 1], 'ruby' => ['rb' => 1, 'rbc' => 1, 'rp' => 1, 'rt' => 1, 'rtc' => 1], 'select' => ['optgroup' => 1, 'option' => 1], 'script' => ['#pcdata' => 1], 'table' => ['caption' => 1, 'col' => 1, 'colgroup' => 1, 'tfoot' => 1, 'tbody' => 1, 'tr' => 1, 'thead' => 1], 'tbody' => ['tr' => 1], 'tfoot' => ['tr' => 1], 'textarea' => ['#pcdata' => 1], 'thead' => ['tr' => 1], 'tr' => ['td' => 1, 'th' => 1], 'ul' => ['li' => 1]]; // Specific - immediate parent-child
    if ($GLOBALS['C']['direct_list_nest']) {
        $cS['ol'] = $cS['ul'] += ['ol' => 1, 'ul' => 1];
    }
    $cO = ['address' => ['p' => 1], 'applet' => ['param' => 1], 'blockquote' => ['script' => 1], 'fieldset' => ['legend' => 1, '#pcdata' => 1], 'form' => ['script' => 1], 'map' => ['area' => 1], 'object' => ['param' => 1, 'embed' => 1]]; // Other
$cT = ['colgroup' => 1, 'dd' => 1, 'dt' => 1, 'li' => 1, 'option' => 1, 'p' => 1, 'td' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'tr' => 1]; // Omitable closing
// block/inline type; ins & del both type; #pcdata: text
$eB = ['address' => 1, 'blockquote' => 1, 'center' => 1, 'del' => 1, 'dir' => 1, 'dl' => 1, 'div' => 1, 'fieldset' => 1, 'form' => 1, 'ins' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1, 'hr' => 1, 'isindex' => 1, 'menu' => 1, 'noscript' => 1, 'ol' => 1, 'p' => 1, 'pre' => 1, 'table' => 1, 'ul' => 1];
    $eI = ['#pcdata' => 1, 'a' => 1, 'abbr' => 1, 'acronym' => 1, 'applet' => 1, 'b' => 1, 'bdo' => 1, 'big' => 1, 'br' => 1, 'button' => 1, 'cite' => 1, 'code' => 1, 'del' => 1, 'dfn' => 1, 'em' => 1, 'embed' => 1, 'font' => 1, 'i' => 1, 'iframe' => 1, 'img' => 1, 'input' => 1, 'ins' => 1, 'kbd' => 1, 'label' => 1, 'map' => 1, 'object' => 1, 'q' => 1, 'ruby' => 1, 's' => 1, 'samp' => 1, 'select' => 1, 'script' => 1, 'small' => 1, 'span' => 1, 'strike' => 1, 'strong' => 1, 'sub' => 1, 'sup' => 1, 'textarea' => 1, 'tt' => 1, 'u' => 1, 'var' => 1];
    $eN = ['a' => 1, 'big' => 1, 'button' => 1, 'fieldset' => 1, 'font' => 1, 'form' => 1, 'iframe' => 1, 'img' => 1, 'input' => 1, 'label' => 1, 'object' => 1, 'ruby' => 1, 'script' => 1, 'select' => 1, 'small' => 1, 'sub' => 1, 'sup' => 1, 'textarea' => 1]; // Exclude from specific ele; $cN values
$eO = ['area' => 1, 'caption' => 1, 'col' => 1, 'colgroup' => 1, 'dd' => 1, 'dt' => 1, 'legend' => 1, 'li' => 1, 'optgroup' => 1, 'option' => 1, 'param' => 1, 'rb' => 1, 'rbc' => 1, 'rp' => 1, 'rt' => 1, 'rtc' => 1, 'script' => 1, 'tbody' => 1, 'td' => 1, 'tfoot' => 1, 'thead' => 1, 'th' => 1, 'tr' => 1]; // Missing in $eB & $eI
$eF = $eB + $eI;

    // $in sets allowed child
    $in = ((isset($eF[$in]) && '#pcdata' != $in) || isset($eO[$in])) ? $in : 'div';
    if (isset($cE[$in])) {
        return !$do ? '' : str_replace(['<', '>'], ['&lt;', '&gt;'], $t);
    }
    if (isset($cS[$in])) {
        $inOk = $cS[$in];
    } elseif (isset($cI[$in])) {
        $inOk = $eI;
        $cI['del'] = 1;
        $cI['ins'] = 1;
    } elseif (isset($cF[$in])) {
        $inOk = $eF;
        unset($cI['del'], $cI['ins']);
    } elseif (isset($cB[$in])) {
        $inOk = $eB;
        unset($cI['del'], $cI['ins']);
    }
    if (isset($cO[$in])) {
        $inOk = $inOk + $cO[$in];
    }
    if (isset($cN[$in])) {
        $inOk = array_diff_assoc($inOk, $cN[$in]);
    }

    $t = explode('<', $t);
    $ok = $q = []; // $q seq list of open non-empty ele
    ob_start();

    for ($i = -1, $ci = count($t); ++$i < $ci;) {
        // allowed $ok in parent $p
        if ($ql = count($q)) {
            $p = array_pop($q);
            $q[] = $p;
            if (isset($cS[$p])) {
                $ok = $cS[$p];
            } elseif (isset($cI[$p])) {
                $ok = $eI;
                $cI['del'] = 1;
                $cI['ins'] = 1;
            } elseif (isset($cF[$p])) {
                $ok = $eF;
                unset($cI['del'], $cI['ins']);
            } elseif (isset($cB[$p])) {
                $ok = $eB;
                unset($cI['del'], $cI['ins']);
            }
            if (isset($cO[$p])) {
                $ok = $ok + $cO[$p];
            }
            if (isset($cN[$p])) {
                $ok = array_diff_assoc($ok, $cN[$p]);
            }
        } else {
            $ok = $inOk;
            unset($cI['del'], $cI['ins']);
        }
        // bad tags, & ele content
        if (isset($e) && (1 == $do || (isset($ok['#pcdata']) && (3 == $do || 5 == $do)))) {
            echo '&lt;', $s, $e, $a, '&gt;';
        }
        if (isset($x[0])) {
            if (strlen(trim($x)) && (($ql && isset($cB[$p])) || (isset($cB[$in]) && !$ql))) {
                echo '<div>', $x, '</div>';
            } elseif ($do < 3 || isset($ok['#pcdata'])) {
                echo $x;
            } elseif (strpos($x, "\x02\x04")) {
                foreach (preg_split('`(\x01\x02[^\x01\x02]+\x02\x01)`', $x, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
                    echo "\x01\x02" == substr($v, 0, 2) ? $v : ($do > 4 ? preg_replace('`\S`', '', $v) : '');
                }
            } elseif ($do > 4) {
                echo preg_replace('`\S`', '', $x);
            }
        }
        // get markup
        if (!preg_match('`^(/?)([a-z1-6]+)([^>]*)>(.*)`sm', $t[$i], $r)) {
            $x = $t[$i];
            continue;
        }
        $s = null;
        $e = null;
        $a = null;
        $x = null;
        list($all, $s, $e, $a, $x) = $r;
        // close tag
        if ($s) {
            if (isset($cE[$e]) || !in_array($e, $q, true)) {
                continue;
            } // Empty/unopen
            if ($p == $e) {
                array_pop($q);
                echo '</', $e, '>';
                unset($e);
                continue;
            } // Last open
  $add = ''; // Nesting - close open tags that need to be
  for ($j = -1, $cj = count($q); ++$j < $cj;) {
      if (($d = array_pop($q)) == $e) {
          break;
      }
      $add .= "</{$d}>";
  }
            echo $add, '</', $e, '>';
            unset($e);
            continue;
        }
        // open tag
        // $cB ele needs $eB ele as child
        if (isset($cB[$e]) && strlen(trim($x))) {
            $t[$i] = "{$e}{$a}>";
            array_splice($t, $i + 1, 0, 'div>'.$x);
            unset($e, $x);
            ++$ci;
            --$i;
            continue;
        }
        if ((($ql && isset($cB[$p])) || (isset($cB[$in]) && !$ql)) && !isset($eB[$e]) && !isset($ok[$e])) {
            array_splice($t, $i, 0, 'div>');
            unset($e, $x);
            ++$ci;
            --$i;
            continue;
        }
        // if no open ele, $in = parent; mostly immediate parent-child relation should hold
        if (!$ql || !isset($eN[$e]) || !array_intersect($q, $cN2)) {
            if (!isset($ok[$e])) {
                if ($ql && isset($cT[$p])) {
                    echo '</', array_pop($q), '>';
                    unset($e, $x);
                    --$i;
                }
                continue;
            }
            if (!isset($cE[$e])) {
                $q[] = $e;
            }
            echo '<', $e, $a, '>';
            unset($e);
            continue;
        }
        // specific parent-child
        if (isset($cS[$p][$e])) {
            if (!isset($cE[$e])) {
                $q[] = $e;
            }
            echo '<', $e, $a, '>';
            unset($e);
            continue;
        }
        // nesting
        $add = '';
        $q2 = [];
        for ($k = -1, $kc = count($q); ++$k < $kc;) {
            $d = $q[$k];
            $ok2 = [];
            if (isset($cS[$d])) {
                $q2[] = $d;
                continue;
            }
            $ok2 = isset($cI[$d]) ? $eI : $eF;
            if (isset($cO[$d])) {
                $ok2 = $ok2 + $cO[$d];
            }
            if (isset($cN[$d])) {
                $ok2 = array_diff_assoc($ok2, $cN[$d]);
            }
            if (!isset($ok2[$e])) {
                if (!$k && !isset($inOk[$e])) {
                    continue 2;
                }
                $add = "</{$d}>";
                for (; ++$k < $kc;) {
                    $add = "</{$q[$k]}>{$add}";
                }
                break;
            }
            $q2[] = $d;
        }
        $q = $q2;
        if (!isset($cE[$e])) {
            $q[] = $e;
        }
        echo $add, '<', $e, $a, '>';
        unset($e);
        continue;
    }

    // end
    if ($ql = count($q)) {
        $p = array_pop($q);
        $q[] = $p;
        if (isset($cS[$p])) {
            $ok = $cS[$p];
        } elseif (isset($cI[$p])) {
            $ok = $eI;
            $cI['del'] = 1;
            $cI['ins'] = 1;
        } elseif (isset($cF[$p])) {
            $ok = $eF;
            unset($cI['del'], $cI['ins']);
        } elseif (isset($cB[$p])) {
            $ok = $eB;
            unset($cI['del'], $cI['ins']);
        }
        if (isset($cO[$p])) {
            $ok = $ok + $cO[$p];
        }
        if (isset($cN[$p])) {
            $ok = array_diff_assoc($ok, $cN[$p]);
        }
    } else {
        $ok = $inOk;
        unset($cI['del'], $cI['ins']);
    }
    if (isset($e) && (1 == $do || (isset($ok['#pcdata']) && (3 == $do || 5 == $do)))) {
        echo '&lt;', $s, $e, $a, '&gt;';
    }
    if (isset($x[0])) {
        if (strlen(trim($x)) && (($ql && isset($cB[$p])) || (isset($cB[$in]) && !$ql))) {
            echo '<div>', $x, '</div>';
        } elseif ($do < 3 || isset($ok['#pcdata'])) {
            echo $x;
        } elseif (strpos($x, "\x02\x04")) {
            foreach (preg_split('`(\x01\x02[^\x01\x02]+\x02\x01)`', $x, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
                echo "\x01\x02" == substr($v, 0, 2) ? $v : ($do > 4 ? preg_replace('`\S`', '', $v) : '');
            }
        } elseif ($do > 4) {
            echo preg_replace('`\S`', '', $x);
        }
    }
    while (!empty($q) && ($e = array_pop($q))) {
        echo '</', $e, '>';
    }
    $o = ob_get_contents();
    ob_end_clean();

    return $o;
    // eof
}

function hl_cmtcd($t)
{
    // comment/CDATA sec handler
    $t = $t[0];
    global $C;
    if (!($v = $C[$n = '-' == $t[3] ? 'comment' : 'cdata'])) {
        return $t;
    }
    if (1 == $v) {
        return '';
    }
    if ('comment' == $n) {
        if (' ' != substr(($t = preg_replace('`--+`', '-', substr($t, 4, -3))), -1)) {
            $t .= ' ';
        }
    } else {
        $t = substr($t, 1, -1);
    }
    $t = 2 == $v ? str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $t) : $t;

    return str_replace(['&', '<', '>'], ["\x03", "\x04", "\x05"], ('comment' == $n ? "\x01\x02\x04!--$t--\x05\x02\x01" : "\x01\x01\x04$t\x05\x01\x01"));
    // eof
}

function hl_ent($t)
{
    // entitity handler
    global $C;
    $t = $t[1];
    static $U = ['quot' => 1, 'amp' => 1, 'lt' => 1, 'gt' => 1];
    static $N = ['fnof' => '402', 'Alpha' => '913', 'Beta' => '914', 'Gamma' => '915', 'Delta' => '916', 'Epsilon' => '917', 'Zeta' => '918', 'Eta' => '919', 'Theta' => '920', 'Iota' => '921', 'Kappa' => '922', 'Lambda' => '923', 'Mu' => '924', 'Nu' => '925', 'Xi' => '926', 'Omicron' => '927', 'Pi' => '928', 'Rho' => '929', 'Sigma' => '931', 'Tau' => '932', 'Upsilon' => '933', 'Phi' => '934', 'Chi' => '935', 'Psi' => '936', 'Omega' => '937', 'alpha' => '945', 'beta' => '946', 'gamma' => '947', 'delta' => '948', 'epsilon' => '949', 'zeta' => '950', 'eta' => '951', 'theta' => '952', 'iota' => '953', 'kappa' => '954', 'lambda' => '955', 'mu' => '956', 'nu' => '957', 'xi' => '958', 'omicron' => '959', 'pi' => '960', 'rho' => '961', 'sigmaf' => '962', 'sigma' => '963', 'tau' => '964', 'upsilon' => '965', 'phi' => '966', 'chi' => '967', 'psi' => '968', 'omega' => '969', 'thetasym' => '977', 'upsih' => '978', 'piv' => '982', 'bull' => '8226', 'hellip' => '8230', 'prime' => '8242', 'Prime' => '8243', 'oline' => '8254', 'frasl' => '8260', 'weierp' => '8472', 'image' => '8465', 'real' => '8476', 'trade' => '8482', 'alefsym' => '8501', 'larr' => '8592', 'uarr' => '8593', 'rarr' => '8594', 'darr' => '8595', 'harr' => '8596', 'crarr' => '8629', 'lArr' => '8656', 'uArr' => '8657', 'rArr' => '8658', 'dArr' => '8659', 'hArr' => '8660', 'forall' => '8704', 'part' => '8706', 'exist' => '8707', 'empty' => '8709', 'nabla' => '8711', 'isin' => '8712', 'notin' => '8713', 'ni' => '8715', 'prod' => '8719', 'sum' => '8721', 'minus' => '8722', 'lowast' => '8727', 'radic' => '8730', 'prop' => '8733', 'infin' => '8734', 'ang' => '8736', 'and' => '8743', 'or' => '8744', 'cap' => '8745', 'cup' => '8746', 'int' => '8747', 'there4' => '8756', 'sim' => '8764', 'cong' => '8773', 'asymp' => '8776', 'ne' => '8800', 'equiv' => '8801', 'le' => '8804', 'ge' => '8805', 'sub' => '8834', 'sup' => '8835', 'nsub' => '8836', 'sube' => '8838', 'supe' => '8839', 'oplus' => '8853', 'otimes' => '8855', 'perp' => '8869', 'sdot' => '8901', 'lceil' => '8968', 'rceil' => '8969', 'lfloor' => '8970', 'rfloor' => '8971', 'lang' => '9001', 'rang' => '9002', 'loz' => '9674', 'spades' => '9824', 'clubs' => '9827', 'hearts' => '9829', 'diams' => '9830', 'apos' => '39',  'OElig' => '338', 'oelig' => '339', 'Scaron' => '352', 'scaron' => '353', 'Yuml' => '376', 'circ' => '710', 'tilde' => '732', 'ensp' => '8194', 'emsp' => '8195', 'thinsp' => '8201', 'zwnj' => '8204', 'zwj' => '8205', 'lrm' => '8206', 'rlm' => '8207', 'ndash' => '8211', 'mdash' => '8212', 'lsquo' => '8216', 'rsquo' => '8217', 'sbquo' => '8218', 'ldquo' => '8220', 'rdquo' => '8221', 'bdquo' => '8222', 'dagger' => '8224', 'Dagger' => '8225', 'permil' => '8240', 'lsaquo' => '8249', 'rsaquo' => '8250', 'euro' => '8364', 'nbsp' => '160', 'iexcl' => '161', 'cent' => '162', 'pound' => '163', 'curren' => '164', 'yen' => '165', 'brvbar' => '166', 'sect' => '167', 'uml' => '168', 'copy' => '169', 'ordf' => '170', 'laquo' => '171', 'not' => '172', 'shy' => '173', 'reg' => '174', 'macr' => '175', 'deg' => '176', 'plusmn' => '177', 'sup2' => '178', 'sup3' => '179', 'acute' => '180', 'micro' => '181', 'para' => '182', 'middot' => '183', 'cedil' => '184', 'sup1' => '185', 'ordm' => '186', 'raquo' => '187', 'frac14' => '188', 'frac12' => '189', 'frac34' => '190', 'iquest' => '191', 'Agrave' => '192', 'Aacute' => '193', 'Acirc' => '194', 'Atilde' => '195', 'Auml' => '196', 'Aring' => '197', 'AElig' => '198', 'Ccedil' => '199', 'Egrave' => '200', 'Eacute' => '201', 'Ecirc' => '202', 'Euml' => '203', 'Igrave' => '204', 'Iacute' => '205', 'Icirc' => '206', 'Iuml' => '207', 'ETH' => '208', 'Ntilde' => '209', 'Ograve' => '210', 'Oacute' => '211', 'Ocirc' => '212', 'Otilde' => '213', 'Ouml' => '214', 'times' => '215', 'Oslash' => '216', 'Ugrave' => '217', 'Uacute' => '218', 'Ucirc' => '219', 'Uuml' => '220', 'Yacute' => '221', 'THORN' => '222', 'szlig' => '223', 'agrave' => '224', 'aacute' => '225', 'acirc' => '226', 'atilde' => '227', 'auml' => '228', 'aring' => '229', 'aelig' => '230', 'ccedil' => '231', 'egrave' => '232', 'eacute' => '233', 'ecirc' => '234', 'euml' => '235', 'igrave' => '236', 'iacute' => '237', 'icirc' => '238', 'iuml' => '239', 'eth' => '240', 'ntilde' => '241', 'ograve' => '242', 'oacute' => '243', 'ocirc' => '244', 'otilde' => '245', 'ouml' => '246', 'divide' => '247', 'oslash' => '248', 'ugrave' => '249', 'uacute' => '250', 'ucirc' => '251', 'uuml' => '252', 'yacute' => '253', 'thorn' => '254', 'yuml' => '255'];
    if ('#' != $t[0]) {
        return ($C['and_mark'] ? "\x06" : '&').(isset($U[$t]) ? $t : (isset($N[$t]) ? (!$C['named_entity'] ? '#'.($C['hexdec_entity'] > 1 ? 'x'.dechex($N[$t]) : $N[$t]) : $t) : 'amp;'.$t)).';';
    }
    if (($n = ctype_digit($t = substr($t, 1)) ? (int) $t : hexdec(substr($t, 1))) < 9 || ($n > 13 && $n < 32) || 11 == $n || 12 == $n || ($n > 126 && $n < 160 && 133 != $n) || ($n > 55295 && ($n < 57344 || ($n > 64975 && $n < 64992) || 65534 == $n || 65535 == $n || $n > 1114111))) {
        return ($C['and_mark'] ? "\x06" : '&')."amp;#{$t};";
    }

    return ($C['and_mark'] ? "\x06" : '&').'#'.(((ctype_digit($t) && $C['hexdec_entity'] < 2) || !$C['hexdec_entity']) ? $n : 'x'.dechex($n)).';';
    // eof
}

function hl_prot($p, $c = null)
{
    // check URL scheme
    global $C;
    $b = $a = '';
    if (null == $c) {
        $c = 'style';
        $b = $p[1];
        $a = $p[3];
        $p = trim($p[2]);
    }
    $c = isset($C['schemes'][$c]) ? $C['schemes'][$c] : $C['schemes']['*'];
    static $d = 'denied:';
    if (isset($c['!']) && substr($p, 0, 7) != $d) {
        $p = "$d$p";
    }
    if (isset($c['*']) || !strcspn($p, '#?;') || (substr($p, 0, 7) == $d)) {
        return "{$b}{$p}{$a}";
    } // All ok, frag, query, param
if (preg_match('`^([a-z\d\-+.&#; ]+?)(:|&#(58|x3a);|%3a|\\\\0{0,4}3a).`i', $p, $m) && !isset($c[strtolower($m[1])])) { // Denied prot
 return "{$b}{$d}{$p}{$a}";
}
    if ($C['abs_url']) {
        if (-1 == $C['abs_url'] && 0 === strpos($p, $C['base_url'])) { // Make url rel
            $p = substr($p, strlen($C['base_url']));
        } elseif (empty($m[1])) { // Make URL abs
            if ('//' == substr($p, 0, 2)) {
                $p = substr($C['base_url'], 0, strpos($C['base_url'], ':') + 1).$p;
            } elseif ('/' == $p[0]) {
                $p = preg_replace('`(^.+?://[^/]+)(.*)`', '$1', $C['base_url']).$p;
            } elseif (strcspn($p, './')) {
                $p = $C['base_url'].$p;
            } else {
                preg_match('`^([a-zA-Z\d\-+.]+://[^/]+)(.*)`', $C['base_url'], $m);
                $p = preg_replace('`(?<=/)\./`', '', $m[2].$p);
                while (preg_match('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', $p)) {
                    $p = preg_replace('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', '', $p);
                }
                $p = $m[1].$p;
            }
        }
    }

    return "{$b}{$p}{$a}";
    // eof
}

function hl_regex($p)
{
    // ?regex
    if (empty($p)) {
        return 0;
    }
    if ($t = ini_get('track_errors')) {
        $o = isset($php_errormsg) ? $php_errormsg : null;
    } else {
        ini_set('track_errors', 1);
    }
    unset($php_errormsg);
    if (($d = ini_get('display_errors'))) {
        ini_set('display_errors', 0);
    }
    preg_match($p, '');
    if ($d) {
        ini_set('display_errors', 1);
    }
    $r = isset($php_errormsg) ? 0 : 1;
    if ($t) {
        $php_errormsg = isset($o) ? $o : null;
    } else {
        ini_set('track_errors', 0);
    }

    return $r;
    // eof
}

function hl_spec($t)
{
    // final $spec
    $s = [];
    $t = str_replace(["\t", "\r", "\n", ' '], '', preg_replace('/"(?>(`.|[^"])*)"/sme', 'substr(str_replace(array(";", "|", "~", " ", ",", "/", "(", ")", \'`"\'), array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\""), "$0"), 1, -1)', trim($t)));
    for ($i = count(($t = explode(';', $t))); --$i >= 0;) {
        $w = $t[$i];
        if (empty($w) || ($e = strpos($w, '=')) === false || !strlen(($a = substr($w, $e + 1)))) {
            continue;
        }
        $y = $n = [];
        foreach (explode(',', $a) as $v) {
            if (!preg_match('`^([a-z:\-\*]+)(?:\((.*?)\))?`i', $v, $m)) {
                continue;
            }
            if (($x = strtolower($m[1])) == '-*') {
                $n['*'] = 1;
                continue;
            }
            if ('-' == $x[0]) {
                $n[substr($x, 1)] = 1;
                continue;
            }
            if (!isset($m[2])) {
                $y[$x] = 1;
                continue;
            }
            foreach (explode('/', $m[2]) as $m) {
                if (empty($m) || ($p = strpos($m, '=')) == 0 || $p < 5) {
                    $y[$x] = 1;
                    continue;
                }
                $y[$x][strtolower(substr($m, 0, $p))] = str_replace(["\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08"], [';', '|', '~', ' ', ',', '/', '(', ')'], substr($m, $p + 1));
            }
            if (isset($y[$x]['match']) && !hl_regex($y[$x]['match'])) {
                unset($y[$x]['match']);
            }
            if (isset($y[$x]['nomatch']) && !hl_regex($y[$x]['nomatch'])) {
                unset($y[$x]['nomatch']);
            }
        }
        if (!count($y) && !count($n)) {
            continue;
        }
        foreach (explode(',', substr($w, 0, $e)) as $v) {
            if (!strlen(($v = strtolower($v)))) {
                continue;
            }
            if (count($y)) {
                $s[$v] = $y;
            }
            if (count($n)) {
                $s[$v]['n'] = $n;
            }
        }
    }

    return $s;
    // eof
}

function hl_tag($t)
{
    // tag/attribute handler
    global $C;
    $t = $t[0];
    // invalid < >
    if ('< ' == $t) {
        return '&lt; ';
    }
    if ('>' == $t) {
        return '&gt;';
    }
    if (!preg_match('`^<(/?)([a-zA-Z][a-zA-Z1-6]*)([^>]*?)\s?>$`m', $t, $m)) {
        return str_replace(['<', '>'], ['&lt;', '&gt;'], $t);
    }
    if (!isset($C['elements'][($e = strtolower($m[2]))])) {
        return ($C['keep_bad'] % 2) ? str_replace(['<', '>'], ['&lt;', '&gt;'], $t) : '';
    }
    // attr string
    $a = str_replace(["\n", "\r", "\t"], ' ', trim($m[3]));
    // tag transform
static $eD = ['applet' => 1, 'center' => 1, 'dir' => 1, 'embed' => 1, 'font' => 1, 'isindex' => 1, 'menu' => 1, 's' => 1, 'strike' => 1, 'u' => 1]; // Deprecated
if ($C['make_tag_strict'] && isset($eD[$e])) {
    $trt = hl_tag2($e, $a, $C['make_tag_strict']);
    if (!$e) {
        return ($C['keep_bad'] % 2) ? str_replace(['<', '>'], ['&lt;', '&gt;'], $t) : '';
    }
}
    // close tag
static $eE = ['area' => 1, 'br' => 1, 'col' => 1, 'embed' => 1, 'hr' => 1, 'img' => 1, 'input' => 1, 'isindex' => 1, 'param' => 1]; // Empty ele
if (!empty($m[1])) {
    return !isset($eE[$e]) ? (empty($C['hook_tag']) ? "</$e>" : $C['hook_tag']($e)) : (($C['keep_bad']) % 2 ? str_replace(['<', '>'], ['&lt;', '&gt;'], $t) : '');
}

    // open tag & attr
static $aN = ['abbr' => ['td' => 1, 'th' => 1], 'accept-charset' => ['form' => 1], 'accept' => ['form' => 1, 'input' => 1], 'accesskey' => ['a' => 1, 'area' => 1, 'button' => 1, 'input' => 1, 'label' => 1, 'legend' => 1, 'textarea' => 1], 'action' => ['form' => 1], 'align' => ['caption' => 1, 'embed' => 1, 'applet' => 1, 'iframe' => 1, 'img' => 1, 'input' => 1, 'object' => 1, 'legend' => 1, 'table' => 1, 'hr' => 1, 'div' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1, 'p' => 1, 'col' => 1, 'colgroup' => 1, 'tbody' => 1, 'td' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'tr' => 1], 'alt' => ['applet' => 1, 'area' => 1, 'img' => 1, 'input' => 1], 'archive' => ['applet' => 1, 'object' => 1], 'axis' => ['td' => 1, 'th' => 1], 'bgcolor' => ['embed' => 1, 'table' => 1, 'tr' => 1, 'td' => 1, 'th' => 1], 'border' => ['table' => 1, 'img' => 1, 'object' => 1], 'bordercolor' => ['table' => 1, 'td' => 1, 'tr' => 1], 'cellpadding' => ['table' => 1], 'cellspacing' => ['table' => 1], 'char' => ['col' => 1, 'colgroup' => 1, 'tbody' => 1, 'td' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'tr' => 1], 'charoff' => ['col' => 1, 'colgroup' => 1, 'tbody' => 1, 'td' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'tr' => 1], 'charset' => ['a' => 1, 'script' => 1], 'checked' => ['input' => 1], 'cite' => ['blockquote' => 1, 'q' => 1, 'del' => 1, 'ins' => 1], 'classid' => ['object' => 1], 'clear' => ['br' => 1], 'code' => ['applet' => 1], 'codebase' => ['object' => 1, 'applet' => 1], 'codetype' => ['object' => 1], 'color' => ['font' => 1], 'cols' => ['textarea' => 1], 'colspan' => ['td' => 1, 'th' => 1], 'compact' => ['dir' => 1, 'dl' => 1, 'menu' => 1, 'ol' => 1, 'ul' => 1], 'coords' => ['area' => 1, 'a' => 1], 'data' => ['object' => 1], 'datetime' => ['del' => 1, 'ins' => 1], 'declare' => ['object' => 1], 'defer' => ['script' => 1], 'dir' => ['bdo' => 1], 'disabled' => ['button' => 1, 'input' => 1, 'optgroup' => 1, 'option' => 1, 'select' => 1, 'textarea' => 1], 'enctype' => ['form' => 1], 'face' => ['font' => 1], 'flashvars' => ['embed' => 1], 'for' => ['label' => 1], 'frame' => ['table' => 1], 'frameborder' => ['iframe' => 1], 'headers' => ['td' => 1, 'th' => 1], 'height' => ['embed' => 1, 'iframe' => 1, 'td' => 1, 'th' => 1, 'img' => 1, 'object' => 1, 'applet' => 1], 'href' => ['a' => 1, 'area' => 1], 'hreflang' => ['a' => 1], 'hspace' => ['applet' => 1, 'img' => 1, 'object' => 1], 'ismap' => ['img' => 1, 'input' => 1], 'label' => ['option' => 1, 'optgroup' => 1], 'language' => ['script' => 1], 'longdesc' => ['img' => 1, 'iframe' => 1], 'marginheight' => ['iframe' => 1], 'marginwidth' => ['iframe' => 1], 'maxlength' => ['input' => 1], 'method' => ['form' => 1], 'model' => ['embed' => 1], 'multiple' => ['select' => 1], 'name' => ['button' => 1, 'embed' => 1, 'textarea' => 1, 'applet' => 1, 'select' => 1, 'form' => 1, 'iframe' => 1, 'img' => 1, 'a' => 1, 'input' => 1, 'object' => 1, 'map' => 1, 'param' => 1], 'nohref' => ['area' => 1], 'noshade' => ['hr' => 1], 'nowrap' => ['td' => 1, 'th' => 1], 'object' => ['applet' => 1], 'onblur' => ['a' => 1, 'area' => 1, 'button' => 1, 'input' => 1, 'label' => 1, 'select' => 1, 'textarea' => 1], 'onchange' => ['input' => 1, 'select' => 1, 'textarea' => 1], 'onfocus' => ['a' => 1, 'area' => 1, 'button' => 1, 'input' => 1, 'label' => 1, 'select' => 1, 'textarea' => 1], 'onreset' => ['form' => 1], 'onselect' => ['input' => 1, 'textarea' => 1], 'onsubmit' => ['form' => 1], 'pluginspage' => ['embed' => 1], 'pluginurl' => ['embed' => 1], 'prompt' => ['isindex' => 1], 'readonly' => ['textarea' => 1, 'input' => 1], 'rel' => ['a' => 1], 'rev' => ['a' => 1], 'rows' => ['textarea' => 1], 'rowspan' => ['td' => 1, 'th' => 1], 'rules' => ['table' => 1], 'scope' => ['td' => 1, 'th' => 1], 'scrolling' => ['iframe' => 1], 'selected' => ['option' => 1], 'shape' => ['area' => 1, 'a' => 1], 'size' => ['hr' => 1, 'font' => 1, 'input' => 1, 'select' => 1], 'span' => ['col' => 1, 'colgroup' => 1], 'src' => ['embed' => 1, 'script' => 1, 'input' => 1, 'iframe' => 1, 'img' => 1], 'standby' => ['object' => 1], 'start' => ['ol' => 1], 'summary' => ['table' => 1], 'tabindex' => ['a' => 1, 'area' => 1, 'button' => 1, 'input' => 1, 'object' => 1, 'select' => 1, 'textarea' => 1], 'target' => ['a' => 1, 'area' => 1, 'form' => 1], 'type' => ['a' => 1, 'embed' => 1, 'object' => 1, 'param' => 1, 'script' => 1, 'input' => 1, 'li' => 1, 'ol' => 1, 'ul' => 1, 'button' => 1], 'usemap' => ['img' => 1, 'input' => 1, 'object' => 1], 'valign' => ['col' => 1, 'colgroup' => 1, 'tbody' => 1, 'td' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'tr' => 1], 'value' => ['input' => 1, 'option' => 1, 'param' => 1, 'button' => 1, 'li' => 1], 'valuetype' => ['param' => 1], 'vspace' => ['applet' => 1, 'img' => 1, 'object' => 1], 'width' => ['embed' => 1, 'hr' => 1, 'iframe' => 1, 'img' => 1, 'object' => 1, 'table' => 1, 'td' => 1, 'th' => 1, 'applet' => 1, 'col' => 1, 'colgroup' => 1, 'pre' => 1], 'wmode' => ['embed' => 1], 'xml:space' => ['pre' => 1, 'script' => 1, 'style' => 1]]; // Ele-specific
static $aNE = ['checked' => 1, 'compact' => 1, 'declare' => 1, 'defer' => 1, 'disabled' => 1, 'ismap' => 1, 'multiple' => 1, 'nohref' => 1, 'noresize' => 1, 'noshade' => 1, 'nowrap' => 1, 'readonly' => 1, 'selected' => 1]; // Empty
static $aNP = ['action' => 1, 'cite' => 1, 'classid' => 1, 'codebase' => 1, 'data' => 1, 'href' => 1, 'longdesc' => 1, 'model' => 1, 'pluginspage' => 1, 'pluginurl' => 1, 'usemap' => 1]; // Need scheme check; excludes style, on* & src
static $aNU = ['class' => ['param' => 1, 'script' => 1], 'dir' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'iframe' => 1, 'param' => 1, 'script' => 1], 'id' => ['script' => 1], 'lang' => ['applet' => 1, 'br' => 1, 'iframe' => 1, 'param' => 1, 'script' => 1], 'xml:lang' => ['applet' => 1, 'br' => 1, 'iframe' => 1, 'param' => 1, 'script' => 1], 'onclick' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'ondblclick' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'onkeydown' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'onkeypress' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'onkeyup' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'onmousedown' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'onmousemove' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'onmouseout' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'onmouseover' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'onmouseup' => ['applet' => 1, 'bdo' => 1, 'br' => 1, 'font' => 1, 'iframe' => 1, 'isindex' => 1, 'param' => 1, 'script' => 1], 'style' => ['param' => 1, 'script' => 1], 'title' => ['param' => 1, 'script' => 1]]; // Univ & exceptions

if ($C['lc_std_val']) {
    // predef attr vals for $eAL & $aNE ele
    static $aNL = ['all' => 1, 'baseline' => 1, 'bottom' => 1, 'button' => 1, 'center' => 1, 'char' => 1, 'checkbox' => 1, 'circle' => 1, 'col' => 1, 'colgroup' => 1, 'cols' => 1, 'data' => 1, 'default' => 1, 'file' => 1, 'get' => 1, 'groups' => 1, 'hidden' => 1, 'image' => 1, 'justify' => 1, 'left' => 1, 'ltr' => 1, 'middle' => 1, 'none' => 1, 'object' => 1, 'password' => 1, 'poly' => 1, 'post' => 1, 'preserve' => 1, 'radio' => 1, 'rect' => 1, 'ref' => 1, 'reset' => 1, 'right' => 1, 'row' => 1, 'rowgroup' => 1, 'rows' => 1, 'rtl' => 1, 'submit' => 1, 'text' => 1, 'top' => 1];
    static $eAL = ['a' => 1, 'area' => 1, 'bdo' => 1, 'button' => 1, 'col' => 1, 'form' => 1, 'img' => 1, 'input' => 1, 'object' => 1, 'optgroup' => 1, 'option' => 1, 'param' => 1, 'script' => 1, 'select' => 1, 'table' => 1, 'td' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'tr' => 1, 'xml:space' => 1];
    $lcase = isset($eAL[$e]) ? 1 : 0;
}

    $depTr = 0;
    if ($C['no_deprecated_attr']) {
        // dep attr:applicable ele
        static $aND = ['align' => ['caption' => 1, 'div' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1, 'hr' => 1, 'img' => 1, 'input' => 1, 'legend' => 1, 'object' => 1, 'p' => 1, 'table' => 1], 'bgcolor' => ['table' => 1, 'td' => 1, 'th' => 1, 'tr' => 1], 'border' => ['img' => 1, 'object' => 1], 'bordercolor' => ['table' => 1, 'td' => 1, 'tr' => 1], 'clear' => ['br' => 1], 'compact' => ['dl' => 1, 'ol' => 1, 'ul' => 1], 'height' => ['td' => 1, 'th' => 1], 'hspace' => ['img' => 1, 'object' => 1], 'language' => ['script' => 1], 'name' => ['a' => 1, 'form' => 1, 'iframe' => 1, 'img' => 1, 'map' => 1], 'noshade' => ['hr' => 1], 'nowrap' => ['td' => 1, 'th' => 1], 'size' => ['hr' => 1], 'start' => ['ol' => 1], 'type' => ['li' => 1, 'ol' => 1, 'ul' => 1], 'value' => ['li' => 1], 'vspace' => ['img' => 1, 'object' => 1], 'width' => ['hr' => 1, 'pre' => 1, 'td' => 1, 'th' => 1]];
        static $eAD = ['a' => 1, 'br' => 1, 'caption' => 1, 'div' => 1, 'dl' => 1, 'form' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1, 'hr' => 1, 'iframe' => 1, 'img' => 1, 'input' => 1, 'legend' => 1, 'li' => 1, 'map' => 1, 'object' => 1, 'ol' => 1, 'p' => 1, 'pre' => 1, 'script' => 1, 'table' => 1, 'td' => 1, 'th' => 1, 'tr' => 1, 'ul' => 1];
        $depTr = isset($eAD[$e]) ? 1 : 0;
    }

    // attr name-vals
    if (false !== strpos($a, "\x01")) {
        $a = preg_replace('`\x01[^\x01]*\x01`', '', $a);
    } // No comment/CDATA sec
    $mode = 0;
    $a = trim($a, ' /');
    $aA = [];
    while (strlen($a)) {
        $w = 0;
        switch ($mode) {
  case 0: // Name
   if (preg_match('`^[a-zA-Z][\-a-zA-Z:]+`', $a, $m)) {
       $nm = strtolower($m[0]);
       $w = $mode = 1;
       $a = ltrim(substr_replace($a, '', 0, strlen($m[0])));
   }
  break; case 1:
   if ('=' == $a[0]) { // =
       $w = 1;
       $mode = 2;
       $a = ltrim($a, '= ');
   } else { // No val
       $w = 1;
       $mode = 0;
       $a = ltrim($a);
       $aA[$nm] = '';
   }
  break; case 2: // Val
   if (preg_match('`^((?:"[^"]*")|(?:\'[^\']*\')|(?:\s*[^\s"\']+))(.*)`', $a, $m)) {
       $a = ltrim($m[2]);
       $m = $m[1];
       $w = 1;
       $mode = 0;
       $aA[$nm] = trim(('"' == $m[0] || '\'' == $m[0]) ? substr($m, 1, -1) : $m);
   }
  break;
 }
        if (0 == $w) { // Parse errs, deal with space, " & '
            $a = preg_replace('`^(?:"[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*`', '', $a);
            $mode = 0;
        }
    }
    if (1 == $mode) {
        $aA[$nm] = '';
    }

    // clean attrs
    global $S;
    $rl = isset($S[$e]) ? $S[$e] : [];
    $a = [];
    $nfr = 0;
    foreach ($aA as $k => $v) {
        if (((isset($C['deny_attribute']['*']) ? isset($C['deny_attribute'][$k]) : !isset($C['deny_attribute'][$k])) && (isset($aN[$k][$e]) || (isset($aNU[$k]) && !isset($aNU[$k][$e]))) && !isset($rl['n'][$k]) && !isset($rl['n']['*'])) || isset($rl[$k])) {
            if (isset($aNE[$k])) {
                $v = $k;
            } elseif (!empty($lcase) && (('button' != $e || 'input' != $e) || 'type' == $k)) { // Rather loose but ?not cause issues
                $v = (isset($aNL[($v2 = strtolower($v))])) ? $v2 : $v;
            }
            if ('style' == $k && !$C['style_pass']) {
                if (false !== strpos($v, '&#')) {
                    static $sC = ['&#x20;' => ' ', '&#32;' => ' ', '&#x45;' => 'e', '&#69;' => 'e', '&#x65;' => 'e', '&#101;' => 'e', '&#x58;' => 'x', '&#88;' => 'x', '&#x78;' => 'x', '&#120;' => 'x', '&#x50;' => 'p', '&#80;' => 'p', '&#x70;' => 'p', '&#112;' => 'p', '&#x53;' => 's', '&#83;' => 's', '&#x73;' => 's', '&#115;' => 's', '&#x49;' => 'i', '&#73;' => 'i', '&#x69;' => 'i', '&#105;' => 'i', '&#x4f;' => 'o', '&#79;' => 'o', '&#x6f;' => 'o', '&#111;' => 'o', '&#x4e;' => 'n', '&#78;' => 'n', '&#x6e;' => 'n', '&#110;' => 'n', '&#x55;' => 'u', '&#85;' => 'u', '&#x75;' => 'u', '&#117;' => 'u', '&#x52;' => 'r', '&#82;' => 'r', '&#x72;' => 'r', '&#114;' => 'r', '&#x4c;' => 'l', '&#76;' => 'l', '&#x6c;' => 'l', '&#108;' => 'l', '&#x28;' => '(', '&#40;' => '(', '&#x29;' => ')', '&#41;' => ')', '&#x20;' => ':', '&#32;' => ':', '&#x22;' => '"', '&#34;' => '"', '&#x27;' => "'", '&#39;' => "'", '&#x2f;' => '/', '&#47;' => '/', '&#x2a;' => '*', '&#42;' => '*', '&#x5c;' => '\\', '&#92;' => '\\'];
                    $v = strtr($v, $sC);
                }
                $v = preg_replace_callback('`(url(?:\()(?: )*(?:\'|"|&(?:quot|apos);)?)(.+?)((?:\'|"|&(?:quot|apos);)?(?: )*(?:\)))`iS', 'hl_prot', $v);
                $v = !$C['css_expression'] ? preg_replace('`expression`i', ' ', preg_replace('`\\\\\S|(/|(%2f))(\*|(%2a))`i', ' ', $v)) : $v;
            } elseif (isset($aNP[$k]) || false !== strpos($k, 'src') || 'o' == $k[0]) {
                $v = str_replace("\xad", ' ', (false !== strpos($v, '&') ? str_replace(['&#xad;', '&#173;', '&shy;'], ' ', $v) : $v));
                $v = hl_prot($v, $k);
                if ('href' == $k) { // X-spam
                    if ($C['anti_mail_spam'] && 0 === strpos($v, 'mailto:')) {
                        $v = str_replace('@', htmlspecialchars($C['anti_mail_spam']), $v);
                    } elseif ($C['anti_link_spam']) {
                        $r1 = $C['anti_link_spam'][1];
                        if (!empty($r1) && preg_match($r1, $v)) {
                            continue;
                        }
                        $r0 = $C['anti_link_spam'][0];
                        if (!empty($r0) && preg_match($r0, $v)) {
                            if (isset($a['rel'])) {
                                if (!preg_match('`\bnofollow\b`i', $a['rel'])) {
                                    $a['rel'] .= ' nofollow';
                                }
                            } elseif (isset($aA['rel'])) {
                                if (!preg_match('`\bnofollow\b`i', $aA['rel'])) {
                                    $nfr = 1;
                                }
                            } else {
                                $a['rel'] = 'nofollow';
                            }
                        }
                    }
                }
            }
            if (isset($rl[$k]) && is_array($rl[$k]) && ($v = hl_attrval($v, $rl[$k])) === 0) {
                continue;
            }
            $a[$k] = str_replace('"', '&quot;', $v);
        }
    }
    if ($nfr) {
        $a['rel'] = isset($a['rel']) ? $a['rel'].' nofollow' : 'nofollow';
    }

    // rqd attr
    static $eAR = ['area' => ['alt' => 'area'], 'bdo' => ['dir' => 'ltr'], 'form' => ['action' => ''], 'img' => ['src' => '', 'alt' => 'image'], 'map' => ['name' => ''], 'optgroup' => ['label' => ''], 'param' => ['name' => ''], 'script' => ['type' => 'text/javascript'], 'textarea' => ['rows' => '10', 'cols' => '50']];
    if (isset($eAR[$e])) {
        foreach ($eAR[$e] as $k => $v) {
            if (!isset($a[$k])) {
                $a[$k] = isset($v[0]) ? $v : $k;
            }
        }
    }

    // depr attrs
    if ($depTr) {
        $c = [];
        foreach ($a as $k => $v) {
            if ('style' == $k || !isset($aND[$k][$e])) {
                continue;
            }
            if ('align' == $k) {
                unset($a['align']);
                if ('img' == $e && ('left' == $v || 'right' == $v)) {
                    $c[] = 'float: '.$v;
                } elseif (('div' == $e || 'table' == $e) && 'center' == $v) {
                    $c[] = 'margin: auto';
                } else {
                    $c[] = 'text-align: '.$v;
                }
            } elseif ('bgcolor' == $k) {
                unset($a['bgcolor']);
                $c[] = 'background-color: '.$v;
            } elseif ('border' == $k) {
                unset($a['border']);
                $c[] = "border: {$v}px";
            } elseif ('bordercolor' == $k) {
                unset($a['bordercolor']);
                $c[] = 'border-color: '.$v;
            } elseif ('clear' == $k) {
                unset($a['clear']);
                $c[] = 'clear: '.('all' != $v ? $v : 'both');
            } elseif ('compact' == $k) {
                unset($a['compact']);
                $c[] = 'font-size: 85%';
            } elseif ('height' == $k || 'width' == $k) {
                unset($a[$k]);
                $c[] = $k.': '.('*' != $v[0] ? $v.(ctype_digit($v) ? 'px' : '') : 'auto');
            } elseif ('hspace' == $k) {
                unset($a['hspace']);
                $c[] = "margin-left: {$v}px; margin-right: {$v}px";
            } elseif ('language' == $k && !isset($a['type'])) {
                unset($a['language']);
                $a['type'] = 'text/'.strtolower($v);
            } elseif ('name' == $k) {
                if (2 == $C['no_deprecated_attr'] || ('a' != $e && 'map' != $e)) {
                    unset($a['name']);
                }
                if (!isset($a['id']) && preg_match('`[a-zA-Z][a-zA-Z\d.:_\-]*`', $v)) {
                    $a['id'] = $v;
                }
            } elseif ('noshade' == $k) {
                unset($a['noshade']);
                $c[] = 'border-style: none; border: 0; background-color: gray; color: gray';
            } elseif ('nowrap' == $k) {
                unset($a['nowrap']);
                $c[] = 'white-space: nowrap';
            } elseif ('size' == $k) {
                unset($a['size']);
                $c[] = 'size: '.$v.'px';
            } elseif ('start' == $k || 'value' == $k) {
                unset($a[$k]);
            } elseif ('type' == $k) {
                unset($a['type']);
                static $ol_type = ['i' => 'lower-roman', 'I' => 'upper-roman', 'a' => 'lower-latin', 'A' => 'upper-latin', '1' => 'decimal'];
                $c[] = 'list-style-type: '.(isset($ol_type[$v]) ? $ol_type[$v] : 'decimal');
            } elseif ('vspace' == $k) {
                unset($a['vspace']);
                $c[] = "margin-top: {$v}px; margin-bottom: {$v}px";
            }
        }
        if (count($c)) {
            $c = implode('; ', $c);
            $a['style'] = isset($a['style']) ? rtrim($a['style'], ' ;').'; '.$c.';' : $c.';';
        }
    }
    // unique ID
    if ($C['unique_ids'] && isset($a['id'])) {
        if (!preg_match('`^[A-Za-z][A-Za-z0-9_\-.:]*$`', ($id = $a['id'])) || (isset($GLOBALS['hl_Ids'][$id]) && 1 == $C['unique_ids'])) {
            unset($a['id']);
        } else {
            while (isset($GLOBALS['hl_Ids'][$id])) {
                $id = $C['unique_ids'].$id;
            }
            $GLOBALS['hl_Ids'][($a['id'] = $id)] = 1;
        }
    }
    // xml:lang
    if ($C['xml:lang'] && isset($a['lang'])) {
        $a['xml:lang'] = isset($a['xml:lang']) ? $a['xml:lang'] : $a['lang'];
        if (2 == $C['xml:lang']) {
            unset($a['lang']);
        }
    }
    // for transformed tag
    if (!empty($trt)) {
        $a['style'] = isset($a['style']) ? rtrim($a['style'], ' ;').'; '.$trt : $trt;
    }
    // return with empty ele /
    if (empty($C['hook_tag'])) {
        $aA = '';
        foreach ($a as $k => $v) {
            $aA .= " {$k}=\"{$v}\"";
        }

        return "<{$e}{$aA}".(isset($eE[$e]) ? ' /' : '').'>';
    }

    return $C['hook_tag']($e, $a);
    // eof
}

function hl_tag2(&$e, &$a, $t = 1)
{
    // transform tag
    if ('center' == $e) {
        $e = 'div';

        return 'text-align: center;';
    }
    if ('dir' == $e || 'menu' == $e) {
        $e = 'ul';

        return '';
    }
    if ('s' == $e || 'strike' == $e) {
        $e = 'span';

        return 'text-decoration: line-through;';
    }
    if ('u' == $e) {
        $e = 'span';

        return 'text-decoration: underline;';
    }
    static $fs = ['0' => 'xx-small', '1' => 'xx-small', '2' => 'small', '3' => 'medium', '4' => 'large', '5' => 'x-large', '6' => 'xx-large', '7' => '300%', '-1' => 'smaller', '-2' => '60%', '+1' => 'larger', '+2' => '150%', '+3' => '200%', '+4' => '300%'];
    if ('font' == $e) {
        $a2 = '';
        if (preg_match('`face\s*=\s*(\'|")([^=]+?)\\1`i', $a, $m) || preg_match('`face\s*=(\s*)(\S+)`i', $a, $m)) {
            $a2 .= ' font-family: '.str_replace('"', '\'', trim($m[2])).';';
        }
        if (preg_match('`color\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m)) {
            $a2 .= ' color: '.trim($m[2]).';';
        }
        if (preg_match('`size\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m) && isset($fs[($m = trim($m[2]))])) {
            $a2 .= ' font-size: '.$fs[$m].';';
        }
        $e = 'span';

        return ltrim($a2);
    }
    if (2 == $t) {
        $e = 0;

        return 0;
    }

    return '';
    // eof
}

function hl_tidy($t, $w, $p)
{
    // Tidy/compact HTM
    if (strpos(' pre,script,textarea', "$p,")) {
        return $t;
    }
    $t = str_replace(' </', '</', preg_replace(['`(<\w[^>]*(?<!/)>)\s+`', '`\s+`', '`(<\w[^>]*(?<!/)>) `'], [' $1', ' ', '$1'], preg_replace_callback(['`(<(!\[CDATA\[))(.+?)(\]\]>)`sm', '`(<(!--))(.+?)(-->)`sm', '`(<(pre|script|textarea)[^>]*?>)(.+?)(</\2>)`sm'], create_function('$m', 'return $m[1]. str_replace(array("<", ">", "\n", "\r", "\t", " "), array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"), $m[3]). $m[4];'), $t)));
    if (($w = strtolower($w)) == -1) {
        return str_replace(["\x01", "\x02", "\x03", "\x04", "\x05", "\x07"], ['<', '>', "\n", "\r", "\t", ' '], $t);
    }
    $s = strpos(" $w", 't') ? "\t" : ' ';
    $s = preg_match('`\d`', $w, $m) ? str_repeat($s, $m[0]) : str_repeat($s, ("\t" == $s ? 1 : 2));
    $N = preg_match('`[ts]([1-9])`', $w, $m) ? $m[1] : 0;
    $a = ['br' => 1];
    $b = ['button' => 1, 'input' => 1, 'option' => 1];
    $c = ['caption' => 1, 'dd' => 1, 'dt' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1, 'isindex' => 1, 'label' => 1, 'legend' => 1, 'li' => 1, 'object' => 1, 'p' => 1, 'pre' => 1, 'td' => 1, 'textarea' => 1, 'th' => 1];
    $d = ['address' => 1, 'blockquote' => 1, 'center' => 1, 'colgroup' => 1, 'dir' => 1, 'div' => 1, 'dl' => 1, 'fieldset' => 1, 'form' => 1, 'hr' => 1, 'iframe' => 1, 'map' => 1, 'menu' => 1, 'noscript' => 1, 'ol' => 1, 'optgroup' => 1, 'rbc' => 1, 'rtc' => 1, 'ruby' => 1, 'script' => 1, 'select' => 1, 'table' => 1, 'tbody' => 1, 'tfoot' => 1, 'thead' => 1, 'tr' => 1, 'ul' => 1];
    $T = explode('<', $t);
    $X = 1;
    while ($X) {
        $n = $N;
        $t = $T;
        ob_start();
        if (isset($d[$p])) {
            echo str_repeat($s, ++$n);
        }
        echo ltrim(array_shift($t));
        for ($i = -1, $j = count($t); ++$i < $j;) {
            $r = '';
            list($e, $r) = explode('>', $t[$i]);
            $x = '/' == $e[0] ? 0 : ('/' == substr($e, -1) ? 1 : ('!' != $e[0] ? 2 : -1));
            $y = !$x ? ltrim($e, '/') : ($x > 0 ? substr($e, 0, strcspn($e, ' ')) : 0);
            $e = "<$e>";
            if (isset($d[$y])) {
                if (!$x) {
                    if ($n) {
                        echo "\n", str_repeat($s, --$n), "$e\n", str_repeat($s, $n);
                    } else {
                        ++$N;
                        ob_end_clean();
                        continue 2;
                    }
                } else {
                    echo "\n", str_repeat($s, $n), "$e\n", str_repeat($s, (1 != $x ? ++$n : $n));
                }
                echo ltrim($r);
                continue;
            }
            $f = "\n".str_repeat($s, $n);
            if (isset($c[$y])) {
                if (!$x) {
                    echo $e, $f, ltrim($r);
                } else {
                    echo $f, $e, $r;
                }
            } elseif (isset($b[$y])) {
                echo $f, $e, $r;
            } elseif (isset($a[$y])) {
                echo $e, $f, ltrim($r);
            } elseif (!$y) {
                echo $f, $e, $f, ltrim($r);
            } else {
                echo $e, $r;
            }
        }
        $X = 0;
    }
    $t = preg_replace('`[\n]\s*?[\n]+`', "\n", ob_get_contents());
    ob_end_clean();
    if (($l = strpos(" $w", 'r') ? (strpos(" $w", 'n') ? "\r\n" : "\r") : 0)) {
        $t = str_replace("\n", $l, $t);
    }

    return str_replace(["\x01", "\x02", "\x03", "\x04", "\x05", "\x07"], ['<', '>', "\n", "\r", "\t", ' '], $t);
    // eof
}

function hl_version()
{
    // rel
    return '1.1.14';
    // eof
}

function kses($t, $h, $p = ['http', 'https', 'ftp', 'news', 'nntp', 'telnet', 'gopher', 'mailto'])
{
    // kses compat
    foreach ($h as $k => $v) {
        $h[$k]['n']['*'] = 1;
    }
    $C['cdata'] = $C['comment'] = $C['make_tag_strict'] = $C['no_deprecated_attr'] = $C['unique_ids'] = 0;
    $C['keep_bad'] = 1;
    $C['elements'] = count($h) ? strtolower(implode(',', array_keys($h))) : '-*';
    $C['hook'] = 'kses_hook';
    $C['schemes'] = '*:'.implode(',', $p);

    return htmLawed($t, $C, $h);
    // eof
}

function kses_hook($t, &$C, &$S)
{
    // kses compat
    return $t;
    // eof
}
