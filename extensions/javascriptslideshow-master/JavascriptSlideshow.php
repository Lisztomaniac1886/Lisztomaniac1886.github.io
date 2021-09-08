<?php
/**
 * Javascript Slideshow
 * Javascript Slideshow Hooks
 *
 * @author  @See $wgExtensionCredits
 * @license GPL
 * @package Javacsript Slideshow
 * @link    https://gitlab.com/hydrawiki/extensions/javascriptslideshow
 **/

if (function_exists('wfLoadExtension')) {
	wfLoadExtension('JavascriptSlideshow');
	wfWarn(
		'Deprecated PHP entry point used for JavascriptSlideshow extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die('This version of the JavascriptSlideshow extension requires MediaWiki 1.25+');
}
