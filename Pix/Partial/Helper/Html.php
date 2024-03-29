<?php

/**
 * Pix_Partial_Helper_Html 
 * 
 * @uses Pix
 * @uses _Partial_Helper
 * @package Pix_Partial
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Partial_Helper_Html extends Pix_Partial_Helper
{
    public function getFuncs()
    {
	return array('link_to', 'image_tag', 'urlArg');
    }

    public function link_to($partial, $href, $text, $html_options = array())
    {
	$optionHtml = '';
	foreach ($html_options as $key => $val) {
	    $optionHtml .= sprintf(' %s="%s"', $key, $partial->escape($val));
	}
	return sprintf('<a href="%s"%s>%s</a>', $partial->escape($href), $optionHtml, $text);
    }

    public function image_tag($partial, $src, $html_options = array(), $extra_options = array())
    {
	$optionHtml = '';
	if ($maxsize = intval($extra_options['maxsize'])) {
	    if (($h = intval($extra_options['height'])) and ($w = intval($extra_options['width']))) {
		$bigger_side = max($h, $w);
		if ($bigger_side > $maxsize) {
		    $html_options['width'] = intval(ceil($w * $maxsize) / $bigger_side);
		    $html_options['height'] = intval(ceil($h * $maxsize) / $bigger_side);
                }
	    } else {
		$html_options['width'] = $html_options['height'] = $maxsize;
	    }
	}
	foreach ($html_options as $key => $val) {
	    $optionHtml .= sprintf(' %s="%s"', $key, $partial->escape($val));
	}
	return sprintf('<img src="%s"%s>', $partial->escape($src), $optionHtml);
    }

    public function urlArg($partial, $args)
    {
	$urlarg = new Pix_UrlArg;
	foreach ($args as $k => $v) {
	    $urlarg->$k = $v;
	}

	return strval($urlarg);
    }
}
