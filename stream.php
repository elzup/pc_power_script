<?php
require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/keys.php');

/**
keys.phpを設置して twitter tokenなどを用意しておく
define('CONSUMER_KEY'       , '*********');
define('CONSUMER_SECRET'    , '*********');
define('OAUTH_TOKEN'        , '***-******');
define('OAUTH_TOKEN_SECRET' , '*********');
define('EM_CONSUMER_KEY'       , '*********');
define('EM_CONSUMER_SECRET'    , '*********');
define('EM_OAUTH_TOKEN'        , '***-******');
define('EM_OAUTH_TOKEN_SECRET' , '*********');
 *
 */

$to = new TwistOAuth(EM_CONSUMER_KEY, EM_CONSUMER_SECRET, EM_OAUTH_TOKEN, EM_OAUTH_TOKEN_SECRET);

define('YOUR_SCREEN_NAME', 'arzzup');
define('MATCH_PATTERN_EJECT', '#(eject|[起お]き[ろ|て])#u');
define('MATCH_PATTERN_BG', '#(bg|(壁|かべ)(紙|[がか]み)|アッシェンテ|ｱｯｼｪﾝﾃ)#u');

define('DIR_IMG_SAVE', '~/Pictures/bg/');

load_last_bg();
//

$url = 'https://userstream.twitter.com/1.1/user.json';
$method = 'GET';

// パラメータ
$oauth_parameters = array(
    'oauth_consumer_key' => CONSUMER_KEY,
    'oauth_nonce' => microtime(),
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => time(),
    'oauth_token' => OAUTH_TOKEN,
    'oauth_version' => '1.0',
);
 
$a = $oauth_parameters;
ksort($a);
$base_string = implode('&', array(
    rawurlencode($method),
    rawurlencode($url),
    rawurlencode(http_build_query($a, '', '&', PHP_QUERY_RFC3986))
));
$key = implode('&', array(rawurlencode(CONSUMER_SECRET), rawurlencode(OAUTH_TOKEN_SECRET)));
$oauth_parameters['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $key, true));
 
 
$fp = fsockopen("ssl://userstream.twitter.com", 443);
if ($fp) {
    fwrite($fp, "GET " . $url . " HTTP/1.1\r\n"
                . "Host: userstream.twitter.com\r\n"
                . 'Authorization: OAuth ' . http_build_query($oauth_parameters, '', ',', PHP_QUERY_RFC3986) . "\r\n"
                . "\r\n");
    while (!feof($fp)) {
        $res = fgets($fp);
        $res = json_decode($res, true);
        // テキスト判定部分
        if (isset($res['in_reply_to_screen_name']) && preg_match('#' . YOUR_SCREEN_NAME . '#i', $res['in_reply_to_screen_name'])) {
            if (preg_match(MATCH_PATTERN_EJECT, $res['text'])) {
                `eject`;
                $text = '@' . $res['user']['screen_name'] . ' えるざっぷ叩き起こしたよ！ありがとう！';
                post_elmane($res['id'], $text);
            }
            if (preg_match(MATCH_PATTERN_BG, $res['text'])) {
                if (!$url = $res['entities']['media'][0]['media_url']) {
                    continue;
                }
                $words = explode('/', $url);
                $hash = $words[4];
                $f = file_get_contents($url);
                if (!file_exists(DIR_IMG_SAVE . $hash)) {
                    exec("wget $url -P " . DIR_IMG_SAVE);
                }
                exec("display -window root -resize 1366x768 " . DIR_IMG_SAVE . $hash);
                $text = '@' . $res['user']['screen_name'] . ' えるざっぷのPCの壁紙変更に成功したよ！かわいい壁紙をありがとう！';
                post_elmane($res['id'], $text);
            }
        }
    }
    fclose($fp);
}

function post_elmane($rep_id, $text) {
    global $to;
    $query = 'statuses/update';
    $params = array(
        'in_reply_to_status_id' => $rep_id,
        'status' => $text,
    );
    $to->post($query, $params);
}

function load_last_bg() {
    global $to;
    $query = 'search/tweets';
    $params = array(
        'q' => '@' . YOUR_SCREEN_NAME,
        'result_type' => 'recent',
        'include_entities' => 1,
        'count' => 3
    );
    $res = $to->get($query, $params);
    foreach ($res->statuses as $st) {
        if (!preg_match(MATCH_PATTERN_BG, $st->text) || (!$url = @$st->entities->media[0]->media_url)) {
            continue;
        }
        $words = explode('/', $url);
        $hash = array_pop($words);
        $f = file_get_contents($url);
        if (!file_exists(DIR_IMG_SAVE . $hash)) {
            exec("wget $url -P " . DIR_IMG_SAVE);
            $text = '@' . $st->user->screen_name . ' 遅れたけどえるざっぷのPCの壁紙変更に成功したよ！ありがとう！';
            post_elmane($st->id, $text);
        }
        exec("display -window root -resize 1366x768 " . DIR_IMG_SAVE . $hash);
        break;
    }
}

