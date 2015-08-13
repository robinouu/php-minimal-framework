<?php
/**
 * HTML Helpers
 * @package php-tool-suite
 * @subpackage HTML helpers
 */

plugin_require(array('hook', 'sanitize'));

/**
 * Parse an HTML string into DOM.
 * It's just a wrapper around the <a href="http://simplehtmldom.sourceforge.net/" target="_blank">simplehtmldom</a> string parser.
 * @param string $html The HTML string to parse.
 * @return a DOM representation of the HTML data
 * @see http://simplehtmldom.sourceforge.net/
 */
function dom($html) {
	require_once(dirname(__FILE__).'/vendor/simple_html_dom.php');
	return @str_get_html($html, true, false, DEFAULT_TARGET_CHARSET, false);
}


/**
 * Returns an HTML stylesheet link tag
 * @param array $attrs The link tag attributes ('src', 'media'...)
 * @return an HTML stylesheet link
 * 		<link rel="stylesheet" type="text/css" src="jquery.min.css" />
 */
function stylesheet($attrs){
	$attrs = array_merge(array(
		'media' => 'screen,projection,tv',
		'rel' => 'stylesheet',
		'href' => ''
	), $attrs);
	return tag('link', '', $attrs, true);
}


/**
 * Returns an HTML external javascript tag
 * @param array $attrs The script tag attributes ('src', 'defer', 'async'...)
 * @return an HTML external javascript tag
 * 		<script type="text/javascript" src="jquery.min.js"></script>
 */
function javascript($attrs, $content = '') {
	$attrs = array_merge(array(
		'type' => 'text/javascript',
		'src' => '',
	), $attrs);
	return tag('script', $content, $attrs);
}

/**
 * Returns a minimal template of an HTML 5 valid page
 * @param array $options The page options
 * <ul>
 *	<li>title string The page title</li>
 *	<li>meta array An array of meta key/value pairs</li>
 *	<li>lang string The page lang. Default to current_lang()</li>
 *	<li>stylesheets string Appends stylesheets tags to the head</li>
 *	<li>scripts string Appends scripts tags to the end of the body</li>
 *	<li>body string The page content</li>
 * </ul>
 * @return string The HTML 5 template.
 */
function html5($args) {

	$stylesheets_str = '';
	if( isset($args['stylesheets']) ){
		if( is_array($args['stylesheets']) ) {
			foreach ($args['stylesheets'] as $stylesheet) {
				$stylesheets_str .= stylesheet(array('href' => $stylesheet));
			}
			unset($args['stylesheets']);
		}else{
			$stylesheets_str = (string)$args['stylesheets'];
		}
	}
	$stylesheets_str .= hook_do('html/stylesheets');


	$scripts_str = '';
	if( isset($args['scripts']) && is_array($args['scripts']) ){
		foreach ($args['scripts'] as $script) {
			$scripts_str .= javascript(array('src' => $script));
		}
		unset($args['scripts']);
	}
	$scripts_str .= hook_do('html/scripts');

	$args = array_merge(array(
		'title' => '',
		'meta' => array(
			'charset' => 'UTF-8',
			'description' => '',
			'keywords' => '',
			'viewport' => 'width=device-width,initial-scale=1.0',
		),
		'lang' => current_lang(),
		'body' => '',
		'stylesheets' => $stylesheets_str,
		'scripts' => $scripts_str
	), $args);

	$head = tag('title', $args['title']);

	foreach ($args['meta'] as $key => $value) {
		$head .= tag('meta', '', array('name' => $key, 'content' => $value), true);
	}

	$head .= hook_do('html_stylesheets', '');

	$head .= $stylesheets_str;

	$head .= hook_do('html_head', '');

	$page = tag('head', $head);

	$page .= tag('body', $args['body'] . $scripts_str);

	return '<!DOCTYPE html>' . tag('html', $page, array('lang' => $args['lang'], 'xml:lang' => $args['lang'] ));
}


function block($block = '', $callback = null) {
	hook_register('html/blocks/' . object_hash($block), $callback);
}

function code($content, $language) {
	return tag('pre', tag('code', $content, array('data-language' => $language)));
}

function image($attrs) {
	$attrs = array_merge(array(
		'alt' => '',
		'src' => ''), $attrs);
	return tag('img', '', $attrs, true);
}

function block_load($block, $args = array()) {
	return hook_do('html/blocks/' . object_hash($block), $args);
}

function text_vars($text, $vars) {
	foreach ($vars as $key => $value) {
		if( is_string($value) ){
			$text = str_replace('{#' . $key . '}', $value, $text);
		}
	}
	return $text; 
}

/**
 * Returns an HTML representation of a menu.
 * @param array $items The menu items. If the array is associative, creates hyperlinks for values.
 * @param array $attrs The ul attributes.
 * @param callable $callback An optional callback to use to print the items content.
 * @param boolean $isNav If set to TRUE, wraps the menu with a navigation ARIA role on a nav tag.
 * @return An HTML representation of a menu
 */
function menu($items = array(), $attrs = array(), $callback = array(), $isNav = false) {

	$html = '';
	if( $isNav ){
		$html .= '<nav role="navigation">';
	}
	$html .= '<ul ' . attrs($attrs) . '>';
	foreach ($items as $key => $value) {
		if( is_string($value) ){
			$html .= '<li>';
			if( is_callable($callback) ){
				$html .= $callback($key, $value);
			}elseif( is_integer($key) ){
				$html .= $value;
			}else {
				$html .= hyperlink($key, $value);
			}
			$html .= '</li>';
		}
	}
	$html .= '</ul>';
	if( $isNav ){
		$html .= '</nav>';
	}
	return $html;
}


function timetag($content = '', $attrs = array()) {
	if( is_string($attrs) ){
		$attrs = array('datetime' => $attrs);
	}else{
		$attrs = array_merge(array('datetime' => date('YYYY-MM-DDThh:mm:ssTZD')), $attrs);
	}
	return tag('time', $content, $attrs);
}

// $list_type can be ul, ol, dl
function datalist($name = '', $filters, $list_type = 'ul' ) {
	//return scrud_get($name);

	$data = scrud_list($name, $filters, isset($filters['formatter']) ? array($name => $filters['formatter']) : array());

	$items = '';
	$slug_name = slug($name);
	if( $list_type == 'ul' || $list_type === 'ol' ){
		$html = '<' . $list_type .'>';
		foreach ($data as $value) {
			$html .= '<li class="data data-' . $slug_name . '">';
			$html .= $value['name'];
			$html .= '</li>';
		}
		$html .= '</' . $list_type . '>';
	}else if( $list_type === 'dl') {
		$html = '<' . $list_type .'>';
		foreach ($data as $key => $value) {
			$html .= '<dt class="data data-' . slug($key) . '">' . ucfirst($key) . '</dt><dd>' . $value . '</dd>';
		}
		$html .= '</' . $list_type . '>';
	}
	return $html;
}

function hyperlink($content = 'Link', $attrs = array()){
	if( is_string($attrs) ){
		$attrs = array('href' => $attrs);
	}else{
		$attrs = array_merge(array(
			'href' => '#'), $attrs);
	}
	return tag('a', $content, $attrs);
}

function br() {
	return '<br />';
}

function hr() {
	return '<hr />';
}

/**
 * Returns an HTML tag
 * @param string $tag The tag name.
 * @param string $content The tag content.
 * @param array $attrs The tag attributes
 * @param boolean $inline If TRUE if specified, the HTML inline format will be used. (for tags like link,br,hr...)
 * @return a properly formatted HTML tag
 */
function tag($tag, $content, $attrs = array(), $inline = false) {
	if( $inline ){
		return '<' . $tag . (sizeof($attrs) ? ' ' . attrs($attrs) : '') . ' />';
	}else{
		return '<' . $tag . (sizeof($attrs) ? ' ' . attrs($attrs) : '') . '>' . $content . '</' . $tag . '>';
	}
}

/**
 * Returns an HTML title (hn)
 * @param string $content The content of the hn
 * @param int $level The hn hierarchy level (1-6)
 * @param array $attrs The hn attributes
 * @return The hn title tag
 */
function title($label, $level = 1, $attrs = array()){
	return tag('h' . $level, $label, $attrs);
}

/**
 * Encodes a text to its HTML representation
 * @param string $content The text content
 * @return The HTML representation of a text.
 */
function text($content) {
	return nl2br(htmlspecialchars($content));
}

function paragraph($content, $attrs = array()) {
	return tag('p', $content, $attrs);
}
function p($content, $attrs = array()){
	return paragraph($content, $attrs);
}

function button($content = '', $attrs = array()) {
	$attrs = array_merge(array('class'=> 'btn'), $attrs);
	return tag('a', $content, $attrs);
}

function button_submit($label = 'Submit', $attrs = array()) {
	$attrs = array_merge(array(
		'type' => 'submit',
		'name' => 'btnSubmit',
		'value' => $label
	), $attrs);
	return tag('input', '', $attrs, true);
}


/**
 * Returns an HTML tag attributes.
 * @param array $attrs The attributes with their respective string values.
 * @return string an HTML list of attributes, joined by spaces.
 */
function attrs($attrs = array()) {
	$html = '';
	if( !is_array($attrs) ){
		return '';	
	}
	$attributes = array();
	foreach ($attrs as $key => $value) {
		if( is_string($key) ){
			$attributes[] = $key . '="' . (string)$value . '"';
		}
	}
	return implode(' ', $attributes);
}

function fieldset($name = '', $content = '', $attrs = array()){
	return tag('fieldset', '<legend>' . htmlspecialchars($name) . '</legend>', $attrs);
}


?>