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

// --- 
define('YOUR_SCREEN_NAME', 'arzzup');
define('ELMANE_SCREEN_NAME', 'elzup_mg');
define('MATCH_PATTERN_EJECT', '#(?:eject|[起お]き[ろ|て])#u');
define('MATCH_PATTERN_BG', '#(?:bg|(?:壁|かべ)(?:紙|[がか]み)|アッシェンテ|ｱｯｼｪﾝﾃ)(?<q>.*?)(?:http.*)?$#u');

define('DIR_IMG_SAVE', '/home/hiro/Pictures/bg/');

post_startup();
//load_last_bg();
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
        if (isset($res['in_reply_to_screen_name']) 
            && preg_match('#^' . YOUR_SCREEN_NAME . '$#i', $res['in_reply_to_screen_name'])
            && !preg_match('#^' . ELMANE_SCREEN_NAME . '$#i', $res['user']['screen_name'])
            && !preg_match('#^akameco$#i', $res['user']['screen_name'])
            ) {
            echo "rep\n";
            // eject
            if (preg_match(MATCH_PATTERN_EJECT, $res['text'])) {
                echo "eject\n";
                `eject`;
                $text = '@' . $res['user']['screen_name'] . ' えるざっぷ叩き起こしたよ！ありがとう！';
                post_elmane($res['id'], $text);
            }
            // bg
            if (preg_match(MATCH_PATTERN_BG, $res['text'], $m)) {
                echo "bg\n";
                $url = get_image_from_tweet($res);
                if (empty($url)) {
                    $q = trim($m['q']);
                    if (empty($q)) {
                        // 対象外
                        return;
                    }
                    // 画像ワードのみ
                    if (substr($q, 0, 1) == '#') {
                        // Twitter ハッシュタグから
                        $sts = get_image_tweets($q);
                        shuffle($sts);
                        foreach ($sts as $st) {
                            if ($url_head = get_image_from_tweet($st)) {
                                break;
                            }
                        }
                        if ($url_head) {
                            $img = new stdclass();
                            $img->title = $q;
                            $img->link = $url_head . ':orig';
                        }
                    } else {
                        // Google 画像検索
                        $img = get_rand_image(get_images($q));
                    }
                    if (!isset($img)) {
                        $text = "@{$res['user']['screen_name']} なぜか壁紙をロードできなかったよ〜ん！残念！";
                        post_elmane($text, $res['id']);
                        continue;
                    }
                    $url = $img->link;
                    $text = '@' . $res['user']['screen_name'] . " えるざっぷの壁紙を「{$img->title}」にしたよ！ありがとう！";
                    post_elmane_image($text, $url, $res['id']);
                } else {
                    // アップロード
                    $url .= ':orig';
                    $message = 'えるざっぷのPCの壁紙変更に成功したよ！かわいい壁紙をありがとう！ (' . date("H:i:s", time()) . ')';
                    $text = '@' . $res['user']['screen_name'] . $message;
                    post_elmane($text, $res['id']);
                }
                exec("display -window root -resize 1366x768 " . $url);
            }
        }
    }
    fclose($fp);
}

function post_startup() {
    $date_str = date('m月d日 H時');
    post_elmane('えるざっぷがPCを起動したよ！[' . $date_str . ']');
}

function post_elmane($text, $rep_id = NULL) {
    try {
        $to = new TwistOAuth(EM_CONSUMER_KEY, EM_CONSUMER_SECRET, EM_OAUTH_TOKEN, EM_OAUTH_TOKEN_SECRET);
        $query = 'statuses/update';
        $params = array(
            'status' => $text,
        );
        if (isset($rep_id)) {
            $params['in_reply_to_status_id' ] = $rep_id;
        }
        $to->post($query, $params);
    } catch (TwistException $e) {
        echo $e->getMessage();
    }
}

function post_elmane_image($text, $url, $rep_id = NULL) {
    try {
        $to = new TwistOAuth(EM_CONSUMER_KEY, EM_CONSUMER_SECRET, EM_OAUTH_TOKEN, EM_OAUTH_TOKEN_SECRET);
        $query = 'statuses/update_with_media';
        $params = array(
            'status' => $text,
            '@media[]' => $url,
        );
        if (isset($rep_id)) {
            $params['in_reply_to_status_id' ] = $rep_id;
        }
        $res = $to->postMultipart($query, $params);
    } catch (TwistException $e) {
        echo $e->getMessage();
    }
}

function get_images($q) {
    $client = new Google_Client();
    $client->setApplicationName(GOOGLE_APP_NAME);
    $client->setDeveloperKey(GOOGLE_API_KEY);
    $service = new Google_Service_Customsearch($client);
    $query = $q;
    $param = array(
        'searchType' => 'image',
        'imgSize' => 'xlarge',
        'cx'         => GOOGLE_CX,
        'num'        => '10',
        'safe'        => 'medium',
        'lr'        => 'lang_ja',
    );
    $results = $service->cse->listCse($query, $param);
    $items = $results->getItems();
//    $img = $items[array_rand($items)];
    return $items;
}

function get_image_tweets($q) {
    $st = NULL;
    try {
        $to = new TwistOAuth(EM_CONSUMER_KEY, EM_CONSUMER_SECRET, EM_OAUTH_TOKEN, EM_OAUTH_TOKEN_SECRET);
        $query = 'search/tweets';
        $params = array(
            'q' => $q . " filter:images",
        );
        $st = $to->get($query, $params);
    } catch (TwistException $e) {
        echo $e->getMessage();
    }
    return @$st->statuses;
}

function get_rand_image($images) {
    shuffle($images);
    foreach ($images as $i) {
        if (is_image($i->link)) {
            return $i;
        }
    }
    return NULL;
}


function is_image($url) {
    return !!@exif_imagetype($url);
}

function get_image_from_tweet($st) {

    if (is_array($st)) {
        $url = @$st['entities']['media'][0]['media_url'];
    } else {
        $url = @$st->entities->media[0]->media_url;
    }
    if (isset($url) && is_image($url)) {
        return $url;
    }
    return FALSE;
}

function load_last_bg() {
    $to = new TwistOAuth(EM_CONSUMER_KEY, EM_CONSUMER_SECRET, EM_OAUTH_TOKEN, EM_OAUTH_TOKEN_SECRET);
    $query = 'search/tweets';
    $params = array(
        'q' => '@' . YOUR_SCREEN_NAME,
        'result_type' => 'recent',
        'include_entities' => 1,
        'count' => 3
    );
    $res = $to->get($query, $params);
    foreach ($res->statuses as $st) {
        if (!preg_match(MATCH_PATTERN_BG, $st->text) || ((!$url = @$st->entities->media[0]->media_url) && (!$url = @$st->entities->urls[0]->expanded_url))) {
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

function collect_img_url($status) {
    if ((!$url = @$st->entities->media[0]->media_url) && (!$url = @$st->entities->urls[0]->expanded_url)) {
        return FALSE;
    }
    return $url;
}
