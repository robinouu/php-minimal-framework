<?php
/**
 * Emailing
 * @package php-tool-suite
 * @subpackage Emailing
 */

/**
 * Sends an email to one or multiple targets
 * @param array $options
 * <ul>
 * 	<li>to : string|array An email list to receive the mail</li>
 * 	<li>from : string|array An email list from which the mail was sent</li>
 * 	<li>subjet : string The email subject</li>
 * 	<li>charset : string The email charset (utf-8 by default)</li>
 * 	<li>content : string The message to send</li>
 * 	<li>html : bool Is the content an HTML template ? TRUE by default.</li>
 * </ul>
 * @subpackage Emailing
 * @return type
 */
function email($options) {
	$options = array_merge(array(
		'to' => array(),
		'from' => '',
		'subject' => '',
		'charset' => 'utf-8',
		'content' => '',
		'html' => true
	), $options);

	$mime_boundary = md5(time());

	if( is_array($options['from']) ){
		$from = implode(', ', $options['from']);
	}else{
		$from = $options['from'];
	}

	if( is_array($options['to']) ){
		$to = implode(', ', $options['to']);
	}else{
		$to = $options['to'];
	}

	$msg = 'From: ' . $from . "\n";
	$msg .= 'MIME-Version: 1.0'. "\n";
	$msg .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"". "\n"; 
	 
	if( $options['html'] ){
		if( is_string($options['content']) ){
			$msg .= '--'.$mime_boundary. "\n";
			$msg .= 'Content-Type: text/html; charset=' . $options['charset'] . "\n";
			$msg .= 'Content-Transfer-Encoding: 7bit'. "\n\n";
			$msg .= $options['content'] . "\n\n";
		}elseif ( is_array($options['content']) ) {
			foreach ($options['content'] as $key => $value) {
				$msg .= '--'.$mime_boundary. "\n";
				$msg .= 'Content-Type: ' . $value['type'] . ';';
				if( $value['filename'] ){
					$msg .= 'name=\"'.$value['filename'].'\";'. "\n";
					$msg .= "Content-Transfer-Encoding: base64". "\n";
					$msg .= "Content-Disposition: attachment; filename=\"".$value['filename']."\"". "\n\n";
					$msg .= chunk_split(base64_encode($value['data'])) . "\n\n";
				}
			}
		}
	}else{
		if( is_string($options['content']) ){
			$msg .= '--'.$mime_boundary. "\n";
			$msg .= 'Content-Type: text/plain; charset=' . $options['charset'] . "\n";
			$msg .= $options['content'] . "\n\n";
		}
	}

	return mail($to, $options['subject'], '', $msg);
}

?>