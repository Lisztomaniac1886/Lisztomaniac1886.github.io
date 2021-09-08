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

class JavascriptSlideshowHooks {
	/**
	 * Sets up this extensions parser functions.
	 *
	 * @access public
	 * @param  object	Parser object passed as a reference.
	 * @return boolean	true
	 */
	public static function wfSlideshowExtension(Parser &$parser) {
		$output = RequestContext::getMain()->getOutput();
		$output->addModules('ext.slideshow.main');

		$parser->setHook('slideshow', 'JavascriptSlideshowHooks::renderSlideshowTag');
		$parser->setFunctionHook('slideshow', 'JavascriptSlideshowHooks::renderSlideshowParserFunction');

		return true;
	}

	/**
	 * Explodes a string that contains a space delimited array of associative key value pairs.
	 *
	 * @access private
	 * @param  string	String to explode arguments from.
	 * @return array	Constructed array of associative key value pairs.
	 */
	private static function explodeArguments($string) {
		$pairDelimiter = ' ';
		$kvDelimiter = '=';

		$_pieces = explode($pairDelimiter, $string);
		if (count($_pieces)) {
			foreach ($_pieces as $value) {
				// We only want the information if it is a valid key value pair.
				if (strpos($value, $kvDelimiter)) {
					list($key, $value) = explode($kvDelimiter, $value);
					$arguments[trim($key)] = trim($value);
				}
			}
		}
		return $arguments;
	}

	/**
	 * Initiates some needed classes.
	 *
	 * @access public
	 * @param  object	Parser object passed as a reference.
	 * @param  string	First argument passed to function tag, HTML input of <div> tags.
	 * @param  string	Second argument passed to function tag, delimited list of options.
	 * @return string	HTML output of self::renderSlideshow()
	 */
	public static function renderSlideshowParserFunction(&$parser, $input = '', $options = '') {
		return self::renderSlideshow($input, self::explodeArguments($options));
	}

	/**
	 * The callback function for converting the input text to HTML output.
	 *
	 * @access public
	 * @return void
	 */
	public static function renderSlideshowTag($input, $argv, $parser, $frame) {
		$wikitext = self::renderSlideshow($input, $argv);
		return $parser->recursiveTagParse($wikitext, $frame);
	}

	/**
	 * Renders the slideshow information into output for the calling tag or function.
	 *
	 * @access public
	 * @param  string	Combined raw HTML and wiki text.
	 * @param  array	Options that have been parsed by self::explodeArguments()
	 * @return string	Rendered output
	 */
	private static function renderSlideshow($wikitext, $options = []) {
		// check if HTML5 is true
		global $wgHtml5;
		if (!$wgHtml5) {
			return '<span class="error">JavascriptSlideshow: ' . wfMessage('javascriptslideshow-error-html5')->inContentLanguage() . '</span>';
		}

		$validSequences = ['forward', 'backward', 'random'];
		$validTransitions = ['cut', 'fade', 'blindDown'];

		$id = (isset($options['id']) ? $options['id'] : 'slideshow_' . rand());

		// set default if not set
		$refresh = (isset($options['refresh']) ? $options['refresh'] : '1000');
		$sequence = (isset($options['sequence']) ? $options['sequence'] : 'forward');
		$transition = (isset($options['transition']) ? $options['transition'] : 'cut');
		$transitiontime = (isset($options['transitiontime']) ? $options['transitiontime'] : '400');
		$center = (isset($options['center']) ? $options['center'] : 'false');

		// validate input
		if (!in_array($sequence, $validSequences)) {
			return '<span class="error">JavascriptSlideshow: ' . wfMessage('javascriptslideshow-invalid-parameter', 'sequence', $sequence, implode(', ', $validSequences))->inContentLanguage() . '</span>';
		} elseif (!in_array($transition, $validTransitions)) {
			return '<span class="error">JavascriptSlideshow: ' . wfMessage('javascriptslideshow-invalid-parameter', 'transition', $transition, implode(', ', $validTransitions))->inContentLanguage() . '</span>';
		} elseif (!is_numeric($refresh)) {
			return '<span class="error">JavascriptSlideshow: ' . wfMessage('javascriptslideshow-invalid-num-parameter', 'refresh')->inContentLanguage() . '</span>';
		} elseif (!is_numeric($transitiontime)) {
			return '<span class="error">JavascriptSlideshow: ' . wfMessage('javascriptslideshow-invalid-num-parameter', 'transitiontime')->inContentLanguage() . '</span>';
		} else {
			$output = '';
			$dataAttrs = "data-transition='$transition' data-refresh='$refresh' data-sequence='$sequence' data-transitiontime='$transitiontime'";
			$styleAttrs = ($center == 'true' ? "style='margin-left:auto;margin-right:auto'" : "");
			$output .= "<div id='$id' class='slideshow' $dataAttrs $styleAttrs >$wikitext</div> ";
			$output .= "<div id='$id-spacer' class='slideshowspacer'></div>";
			return $output;
		}
		return '<span class="error">JavascriptSlideshow: ' . wfMessage('javascriptslideshow-error-unknown')->inContentLanguage() . '</span>';
	}

	/**
	 * Add a CSS module with addModuleStyles to ensure it's loaded
	 * even if there is no Javascript support
	 *
	 * @return [type] [description]
	 */
	public static function extensionHook() {
		global $wgOut;
		$wgOut->addModuleStyles('ext.slideshow.css');
		return true;
	}
}
