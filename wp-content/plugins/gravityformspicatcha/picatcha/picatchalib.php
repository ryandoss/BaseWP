<?php
/*
 * @file
 * This is a PHP library that handles calling Picatcha.
 *
 * Copyright (c) 2011 Picatca -- http://picatcha.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The Picatcha server URL
 */
define("PICATCHA_API_SERVER", "api.picatcha.com");

/**
 * in case json functions do not exist
 */
if (!function_exists('json_encode')) {
  require_once('JSON.php');
  function json_encode($value = FALSE) {
    $json = new Services_JSON();
    return $json->encode($value);
  }
}

if (!function_exists('json_decode')) {
  require_once('JSON.php');
  function json_decode($value) {
    $json = new Services_JSON();
    return $json->decode($value);
  }
}


/**
 * Submits an HTTP POST to a Picatcha server
 *
 * @param $host
 *   Host to send the request (string)
 * @param $path
 *   Path to send the request (string)
 * @param $data
 *   Data to send with the request (array)
 * @param $port
 *   Port to send the request (integer, default: 80)
 *
 * @return
 *   response (array)
 */
function _picatcha_http_post($host, $path, $data, $port = 80) {
  $http_request  = "POST $path HTTP/1.0\r\n";
  $http_request .= "Host: $host\r\n";
  $http_request .= "User-Agent: Picatcha/PHP\r\n";
  $http_request .= "Content-Length: " . strlen($data) . "\r\n";
  $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
  $http_request .= "\r\n";
  $http_request .= $data;

  $response = '';
  if ( FALSE == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
    die('Could not open socket');
  }

  fwrite($fs, $http_request);

  // 1160: One TCP-IP packet
  while ( !feof($fs) )
    $response .= fgets($fs, 1160);
  fclose($fs);
  $response = explode("\r\n\r\n", $response, 2);
  return $response;
}


/**
 * Gets the challenge HTML (javascript and non-javascript version).
 *
 * This is called from the browser, and the resulting Picatcha HTML widget
 * is embedded within the HTML form it was called from.
 *
 * @param $pubkey
 *   A public key for Picatcha (string)
 * @param $error
 *   The error given by Picatcha (string, default: null)
 *
 * @return
 *   The HTML to be embedded in the user's form (string)
 */
function picatcha_get_html($form_id, $pubkey, $error = NULL, $format='2', $style='#2a1f19', $link = '1', $IMG_SIZE = '75', $NOISE_LEVEL = 0, $NOISE_TYPE = 0, $lang = 'en', $langOverride = '0') {

      $elm_id = 'picatcha';

      $script = 'Picatcha.PUBLIC_KEY="'.$pubkey.'";' .
            'Picatcha.setCustomization({"format":"'.$format.'","color":"'.$style.'","link":"'.$link.'","image_size":"'.$IMG_SIZE.'","lang":"'.$lang.'","langOverride":"'.$langOverride.'","noise_level":"'.$NOISE_LEVEL.'","noise_type":"'.$NOISE_TYPE.'"});'.
            'Picatcha.create("'.$elm_id.'",{});';

      GFFormDisplay::add_init_script($form_id, "picatcha", GFFormDisplay::ON_PAGE_RENDER, $script);

      $html = '';
      if ( $error != NULL ) {
        $html .= '<div id="' . $elm_id . '_error">' . $error . '</div>';
      }
      $html .= '<div id="' . $elm_id . '"></div>';
      return $html;
}


/**
 * A PicatchaResponse is returned from picatcha_check_answer()
 */
class PicatchaResponse {
        var $is_valid;
        var $error;
}


/**
  * Calls an HTTP POST function to verify if the user's choices were correct
  *
  * @param $privkey
  *   Private key (string)
  * @param $remoteip
  *   Remote IP (string)
  * @param $challenge
  *   Challenge token (string)
  * @param $response
  *   Response (array)
  * @param $extra_params
  *   Extra variables to post to the server (array)
  *
  * @return
  *   An instance of PicatchaResponse
  */
function picatcha_check_answer($privkey, $remoteip, $user_agent, $challenge, $response, $extra_params = array()) {
  if ($privkey == NULL || $privkey == '') {
    die("To use Picatcha you must get an API key from <a href='http://picatcha.com'>http://picatcha.com</a>");
  }

  if ($remoteip == NULL || $remoteip == '') {
    die("For security reasons, you must pass the remote ip to Picatcha");
  }
  if ($user_agent == NULL || $user_agent == '') {
    die("You must pass the user agent to Picatcha");
  }

  // discard spam submissions
  if ($challenge == NULL || strlen($challenge) == 0 || $response == NULL || count($response) == 0) {
    $picatcha_response = new PicatchaResponse();
    $picatcha_response->is_valid = FALSE;
    $picatcha_response->error = 'incorrect-answer';
    return $picatcha_response;
  }

  $params = array(
    'k' => $privkey,
    'ip' => $remoteip,
    'ua' => $user_agent,
    't' => $challenge,
    'r' => $response,
  ) + $extra_params;
  $data = json_encode($params);
  $response = _picatcha_http_post(PICATCHA_API_SERVER, "/v", $data);
  $res = json_decode($response[1], FALSE);

  $picatcha_response = new PicatchaResponse();
  if ($res->s) {
    $picatcha_response->is_valid = TRUE;
  }
  else {
    $picatcha_response->is_valid = FALSE;
    $picatcha_response->error = $res->e;
  }
  return $picatcha_response;
}


/**
 * Gets a URL where the user can sign up for Picatcha.
 *
 * If your application has a configuration page where you enter a key,
 * you should provide a link using this function.
 *
 * @param $domain
 *   The domain where the page is hosted (string)
 * @param $appname
 *   The name of your application (string)
 */
function picatcha_get_signup_url($domain = NULL, $appname = NULL) {
  return "http://picatcha.com/";
}
