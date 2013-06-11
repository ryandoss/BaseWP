<?php
/*
 * Debugging & logging
 */

class WPBDP_Debugging {

	private static $debug = false;
	private static $messages = array();

	public static function debug_on() {
		self::$debug = true;

		error_reporting(E_ALL | E_DEPRECATED);
		// @ini_set('display_errors', '1');
		set_error_handler(array('WPBDP_Debugging', '_php_error_handler'));

		add_action('wp_head', array('WPBDP_Debugging', '_print_styles'));
		add_action('admin_print_styles', array('WPBDP_Debugging', '_print_styles'));
		add_action('admin_notices', array('WPBDP_Debugging', '_debug_bar_head'));
		add_action('admin_footer', array('WPBDP_Debugging', '_debug_bar_footer'));
		add_action('wp_footer', array('WPBDP_Debugging', '_debug_bar_footer'));
	}

	public static function _php_error_handler($errno, $errstr, $file, $line, $context) {
		self::add_debug_msg( $errstr, 'php', array('file' => $file, 'line' => $line) );
	}

	public static function debug_off() {
		self::$debug = false;

		remove_action('admin_print_styles', array('WPBDP_Debugging', '_print_styles'));
		remove_action('admin_notices', array('WPBDP_Debugging', '_debug_bar_head'));
		remove_action('wp_footer', array('WPBDP_Debugging', '_debug_bar_footer'));
	}

	public static function _print_styles() {
		echo '<style type="text/css">';
		echo 'div#wpbdp-debugging { color: #000; background: #fff; width: 100%; margin: 2px 0; color: #333; clear:both; }';
		echo 'div#wpbdp-debugging table { border-collapse: collapse; }';
		echo 'div#wpbdp-debugging table tr { border-bottom: dotted 1px #666; }';
		echo 'div#wpbdp-debugging table tr.log-deprecated { background: #ddd; }';
		echo 'div#wpbdp-debugging table td { font-size: 11px; font-family: monospace; padding: 0 5px; }';
		echo 'div#wpbdp-debugging table td.timestamp { width: 50px; }';
		echo 'div#wpbdp-debugging table td.type { width: 100px; }';
		echo 'div#wpbdp-debugging table td.message { max-width: 450px; }';
		echo 'div#wpbdp-debugging table td.context { width: 200px; }';
		echo 'div#wpbdp-debugging table td.file { width: 200px; }';

		if (!is_admin())
			echo 'div#wpbdp-debugging { display: block !important; }';

		echo '</style>';
	}

	public static function _debug_bar_head() {
		if (!self::$debug)
			return;

		echo '<div id="wpbdp-debugging-placeholder"></div>';
	}

	public static function _debug_bar_footer() {
		if (!self::$debug)
			return;

		if (!self::$messages)
			return;

		echo '<div id="wpbdp-debugging" style="display: none;">';
		echo '<table>';

		foreach (self::$messages as $item) {
			echo '<tr class="' . $item['type'] . '">';
			echo '<td class="handle">&raquo;</td>';
			echo '<td class="timestamp">' . date('H:i:s', $item['timestamp']) . '</td>';
			echo '<td class="type">' . $item['type'] . '</td>';
			echo '<td class="message">' . $item['message'] . '</td>';

			if ($item['context']) {
				echo '<td class="context">' . $item['context']['function'] . '</td>';
				echo '<td class="file">' . basename($item['context']['file']) . ':' . $item['context']['line'] . '</td>';
			} else {
				echo '<td class="context"></td><td class="file"></td>';
			}

			// . print_r($item['context'], 1) . '</td>';
			echo '</tr>';
			// echo '<div class="item">';
			// echo '<pre class="message">' . $item['message'] . '</pre>';
			// echo '<dl class="details">';
			// echo '<dt>Timestamp:</dt><dd>' . $item['timestamp'] . '</dd>';
			// echo '<dt>Called from:</dt><dd>' . $item['context'] . '</dd>';
			// echo '</dl>';
			// echo '</div>';
		}

		echo '</table>';
		echo '</div>';

		echo '<script type="text/javascript">jQuery("#wpbdp-debugging-placeholder").replaceWith(jQuery("#wpbdp-debugging").show());</script>';
	}

	private static function _extract_context($context) {
		// print_r($context);
		// exit;
		if (is_array($context) && !empty($context)) {
			foreach ($context as $item) {
				if (isset($item['class']) && $item['class'] == 'WPBDP_Debugging')
					continue;

				if (isset($item['file']) && $item['file'] == __FILE__)
					continue;

				// advance 1 frame if is a deprecated() call to obtain the calling function
				if (isset($item['function']) && $item['function'] == 'wpbdp_log_deprecated')
					continue;

				return $item;
			}
		}

		return array();
	}

	private static function add_debug_msg($msg, $type='debug', $context=null) {
		self::$messages[] = array('timestamp' => time(),
								  'message' => $msg,
								  'type' => $type,
								  'context' => $type == 'php' ? $context : self::_extract_context($context),
								 );
	}

	private static function _var_dump($var) {
		return var_export($var, 1);
	}

	/* API */

	public static function debug() {
		if (self::$debug) {
			foreach (func_get_args() as $var)
				self::add_debug_msg(self::_var_dump($var), 'debug', debug_backtrace());
		}
	}

	public static function debug_e() {
		$ret = '';

		foreach (func_get_args() as $arg)
			$ret .= self::_var_dump($arg) . "\n";

		wp_die(sprintf('<pre>%s</pre>', $ret), '');
	}

	public static function log($msg, $type='info') {
		self::add_debug_msg($msg, sprintf('log-%s', $type), debug_backtrace());
	}

}

function wpbdp_log($msg, $type='info') {
	call_user_func(array('WPBDP_Debugging', 'log'), $msg, $type);
}

function wpbdp_log_deprecated() {
	wpbdp_log('Deprecated function called.', 'deprecated');
}

function wpbdp_debug() {
	$args = func_get_args();
	call_user_func_array(array('WPBDP_Debugging', 'debug'), $args);
}

function wpbdp_debug_e() {
	$args = func_get_args();
	call_user_func_array(array('WPBDP_Debugging', 'debug_e'), $args);
}

/**
 * E-mail handling class.
 * @since 2.1
 */
class WPBDP_Email {

	public function __construct() {
		$this->headers = array();

		$this->subject = '';
		$this->from = null;
		$this->to = array();
		$this->cc = array();

		$this->body = '';
		$this->plain = '';
		$this->html = '';
	}

	private function prepare_html() {
		if (!$this->html) {
			$text = $this->body ? $this->body : $this->plain;
			$text = str_ireplace(array("<br>", "<br/>", "<br />"), "\n", $text);
			$this->html = nl2br($text);
		}
	}

	private function prepare_plain() {
		if (!$this->plain) {
			$text = $this->body ? $this->body : $this->html;
			$this->plain = strip_tags($text); // FIXME: this removes 'valid' plain text like <whatever>
		}
	}

	/**
	 * Sends the email.
	 * @param string $format allowed values are 'html', 'plain' or 'both'
	 * @return boolean true on success, false otherwise
	 */
	public function send($format='both') {
		// TODO: implement 'plain' and 'both'
		$this->prepare_html();
		$this->prepare_plain();

		$from = $this->from ? $this->from : sprintf('%s <%s>', get_option('blogname'), get_option('admin_email'));
		$headers = array_merge(array(
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/html; charset="' . get_option('blog_charset') . '"',
			'From' => $from
		), $this->headers);

		$email_headers = '';
		foreach ($headers as $k => $v) {
			$email_headers .= sprintf("%s: %s\r\n", $k, $v);
		}

		return wp_mail($this->to, $this->subject, $this->html, $email_headers);
	}

}

/*
 * Misc.
 */

function wpbdp_getv($dict, $key, $default=false) {
	$_dict = is_object($dict) ? (array) $dict : $dict;

	if (is_array($_dict) && isset($_dict[$key]))
		return $_dict[$key];

	return $default;
}

function wpbdp_render_page($template, $vars=array(), $echo_output=false) {
	if ($vars) {
		extract($vars);
	}

	ob_start();
	include($template);
	$html = ob_get_contents();
	ob_end_clean();

	if ($echo_output)
		echo $html;

	return $html;
}

function wpbdp_generate_password($length=6, $level=2) {
   list($usec, $sec) = explode(' ', microtime());
   srand((float) $sec + ((float) $usec * 100000));

   $validchars[1] = "0123456789abcdfghjkmnpqrstvwxyz";
   $validchars[2] = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
   $validchars[3] = "0123456789_!@#$%&*()-=+/abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_!@#$%&*()-=+/";

   $password  = "";
   $counter   = 0;

   while ($counter < $length)
   {
	 $actChar = substr($validchars[$level], rand(0, strlen($validchars[$level])-1), 1);

	 // All character must be different
	 if (!strstr($password, $actChar))
	 {
		$password .= $actChar;
		$counter++;
	 }
   }

   return $password;
}

function wpbdp_capture_action($hook) {
	$output = '';

	$args = func_get_args();
	if (count($args) > 1) {
		$args = array_slice($args, 	1);
	} else {
		$args = array();
	}

	ob_start();
	do_action_ref_array($hook, $args);
	$output = ob_get_contents();
	ob_end_clean();

	return $output;
}

/**
 * @since 2.1.6
 */
function wpbdp_media_upload($file, $use_media_library=true, $check_image=false, $constraints=array(), &$error_msg=null) {
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/image.php');

	// TODO(future): it could be useful to have additional constraints available
	$constraints = array_merge( array(
									'image' => false,
									'max-size' => 0
							  ), $constraints );

	if ($file['error'] == 0) {
		if ($constraints['max-size'] > 0 && $file['size'] > $constraints['max-size'] ) {
			$error_msg = sprintf( _x( 'File size (%s) exceeds maximum file size of %s', 'utils', 'WPBDM' ),
								size_format ($file['size'], 2),
								size_format ($constraints['max-size'], 2)
								);
			return false;
		}

		if ( $upload = wp_handle_upload( $file, array('test_form' => FALSE) ) ) {
			if ( !$use_media_library ) {
				if (!is_array($upload) || isset($upload['error']))
					return false;
				
				return $upload;
			}

			if ( $attachment_id = wp_insert_attachment(array(
				'post_mime_type' => $upload['type'],
				'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
				'post_content' => '',
				'post_status' => 'inherit'
			), $upload['file']) ) {
				$attach_metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
				wp_update_attachment_metadata( $attachment_id, $attach_metadata );

				if ( $check_image && !wp_attachment_is_image( $attachment_id ) ) {
					wp_delete_attachment( $attachment_id, true );

					$error_msg = _x('Uploaded file is not an image', 'utils', 'WPBDM');
					return false;
				}

				return $attachment_id;
			}
		}
	} else {
		$error_msg = _x('Error while uploading file', 'utils', 'WPBDM');
	}

	return false;
}

/**
 * Returns the domain used in the current request, optionally stripping
 * the www part of the domain.
 *
 * @since 2.1.5
 * @param $www  boolean     true to include the 'www' part,
 *                          false to attempt to strip it.
 */
function wpbdp_get_current_domain($www=true, $prefix='') {
    $domain = wpbdp_getv($_SERVER, 'HTTP_HOST', '');
    if (empty($domain)) {
        $domain = wpbdp_getv($_SERVER, 'SERVER_NAME', '');
    }

    if (!$www && substr($domain, 0, 4) === 'www.') {
        $domain = $prefix . substr($domain, 4);
    }

    return $domain;
}

/**
 * Bulds WordPress ajax URL using the same domain used in the current request.
 *
 * @since 2.1.5
 */
function wpbdp_ajaxurl($overwrite=false) {
    static $ajaxurl = false;

    if ($overwrite || $ajaxurl === false) {
        $url = admin_url('admin-ajax.php');
        $parts = parse_url($url);
        $ajaxurl = str_replace($parts['host'], wpbdp_get_current_domain(), $url);
    }

    return $ajaxurl;
}

/**
 * Removes a value from an array.
 * @since 2.3
 */
function wpbdp_array_remove_value( &$array_, &$value_ ) {
	$key = array_search( $value_, $array_ );

	if ( $key !== false ) {
		unset( $array_[$key] );
	}

	return true;
}
