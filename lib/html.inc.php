<?php
/**
 * HTML Helpers
 * @package php-tool-suite
 * @subpackage HTML helpers
 */
require_once('hook.inc.php');
require_once('sanitize.inc.php');
require_once('vendor/simple_html_dom.php');

/**
 * Parse an HTML string into DOM.
 * It's just a wrapper around the <a href="http://simplehtmldom.sourceforge.net/" target="_blank">simplehtmldom</a> string parser.
 * @param string $html The HTML string to parse.
 * @return a DOM representation of the HTML data
 * @see http://simplehtmldom.sourceforge.net/
 */
function dom($html) {
	return @str_get_html($html, true, false, DEFAULT_TARGET_CHARSET, false);
}


/**
 * Returns an HTML stylesheet link tag
 * @param array $attrs The link tag attributes ('src', 'media'...)
 * @return an HTML stylesheet link
 * 		<link rel="stylesheet" type="text/css" src="jquery.min.css" />
 */
function stylesheet($attrs){
	return '<link rel="stylesheet" type="text/css" ' . attrs($attrs) . ' />';
}


/**
 * Returns an HTML external javascript tag
 * @param array $attrs The script tag attributes ('src', 'defer', 'async'...)
 * @return an HTML external javascript tag
 * 		<script type="text/javascript" src="jquery.min.js"></script>
 */
function javascript($attrs) {
	return '<script type="text/javascript" ' . attrs($attrs) . '></script>';
}


function html5($args) {

	$stylesheets_str = '';
	if( isset($args['stylesheets']) && is_array($args['stylesheets'])) {
		foreach ($args['stylesheets'] as $stylesheet) {
			$stylesheets_str .= stylesheet(array('href' => $stylesheet));
		}
		unset($args['stylesheets']);
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
		$head .= tag('meta', '', array($key => $value), true);
	}

	$head .= hook_do('html_stylesheets');

	$head .= $stylesheets_str;
	$head .= $scripts_str;

	$head .= hook_do('html_head');

	$page = tag('head', $head);

	$page .= tag('body', $args['body']);

	$page .= hook_do('html_scripts');

	return '<!DOCTYPE html>' . tag('html', $page, array('lang' => $args['lang'], 'xml:lang' => $args['lang'] ));
}


function block($str = '', $callback = null) {
	if( $callback ) {
		var_set('site/page/blocks/' . slug($str), $callback);
	}
	//return LOG_ERROR('Vous devez indiquer un callback d\'affichage pour le blog "' . $str . '")');
}

function code($content, $language) {
	return '<pre><code data-language="' . $language . '">' . $content . '</code></pre>';
}

function image($attrs) {
	$attrs = array_merge(array(
		'alt' => '',
		'src' => ''), $attrs);
	return '<img ' . attrs($attrs) . ' />';
}

function block_load($block, $args = array()) {
	$cb = var_get('site/page/blocks/' . slug($block));
	if( is_callable($cb) ) {
		return $cb($args);	
	}
	return $cb;
}

function text_vars($text, $vars) {
	foreach ($vars as $key => $value) {
		if( is_string($value) ){
			$text = str_replace('{#' . $key . '}', $value, $text);
		}
	}
	return $text; 
}

function menu($items = array(), $attrs = array()) {

	$attrs = array_merge(array('class' => 'menu', 'callback' => null), $attrs);

	$cb = null;
	if( is_callable($attrs['callback']) ){
		$cb = $attrs['callback'];
		unset($attrs['callback']);
	}

	$html = '<nav role="navigation"><ul ' . attrs($attrs) . '>';
	foreach ($items as $key => $value) {
		if( is_string($value) ){
			$html .= '<li class="item item-link">';
			if( $cb ){
				$html .= $cb($key, $value);
			}elseif( is_integer($key) ){
				$html .= $value;
			}else {
				$html .= hyperlink($key, $value);
			}
			$html .= '</li>';
		}
	}
	$html .= '</ul></nav>';
	return $html;
}

function hidden($name, $value, $attrs = array()) {
	$attrs = array_merge(array('type' => 'hidden', 'name' => $name, 'value' => $value), $attrs);
	return tag('input', '', $attrs);
}


function format_date($d = 'now', $format = '%d %B %Y, %H:%M') {
	$datetime = strtotime($d);
	return strftime($format, $datetime);
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
	return '<br />'."\r\n";
}

function hr() {
	return '<hr />' . "\r\n";
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

function search($options = array()) {
	$options = array_merge(array(
		'title' => t('Search for'),
		'form' => array('role' => 'search', 'id' => 'search'),
		'searchField' => array('name' => 'search', 'type' => 'search', 'placeholder' => ''),
		'button.label' => t('Search'),
		'button.field' => array('name' => 'search_submit')
		), $options);

	return form(
		fieldset($options['title'], field($options['searchField']) . button_submit($options['button.label'], $options['button.field'])),
		array($options['form'])
	);
}

function title($label, $level = 1){
	return '<h' . $level . '>' . $label . '</h' . $level . '>';
}

function text($content) {
	return nl2br(htmlspecialchars($content));
}

function paragraph($content) {
	return tag('p', $content);
}

function span($attrs = array()) {
	if( is_string($attrs) ){
		$attrs = array('class' => $attrs);
	}
	return '<span ' . attrs($attrs) . '></span>';
}

function button($content = '', $attrs = array()) {
	$attrs = array_merge(array('class'=> 'btn'), $attrs);
	return tag('a', $content, $attrs);
}

function button_submit($label = 'Submit', $attrs = array()) {
	//static $ids = 0;
	if( !isset($attrs['name']) ){
		$attrs['name'] = slug($label);
	}
	$attrs = array_merge(array('type' => 'submit', 'value' => $label), $attrs);
	//++$ids;
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
		LOG_ARRAY($attrs);
		print 'ERROR';
		return $html;	
	}
	$attributes = array();
	foreach ($attrs as $key => $value) {
		if( is_string($value) && is_string($key) ){
			$attributes[] = $key . '="' . $value . '"';
		}
	}
	return implode(' ', $attributes);
}

function fieldset($name = '', $content = ''){
	$html = '<fieldset><legend>' . htmlspecialchars($name) . '</legend>';
	$html .= $content;
	$html .= '</fieldset>';
	return $html;
}


?>