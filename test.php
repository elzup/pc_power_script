<?php

$text = '@Arzzup 壁紙 榛名 可愛い http://hoge';
define('MATCH_PATTERN_BG', '#(?:bg|(?:壁|かべ)(?:紙|[がか]み)|アッシェンテ|ｱｯｼｪﾝﾃ)(?<q>.*?)(?:http.*)?$#u');
if (preg_match(MATCH_PATTERN_BG, $text, $m)) {
    var_dump($m);
}
/*
var_dump(@exif_imagetype('http://top1walls.com/walls/anime-manga/akaza-akari-funami-yui-toshinou-1667326-1920x1080.jpg'));
var_dump(@exif_imagetype('http://elzup.com/i/co01.png'));
*/

/*
$str = '@arzzup bg ヨーロッパ 夜景';
define('MATCH_PATTERN_BG', '#(?:bg|(?:壁|かべ)(?:紙|[がか]み)|アッシェンテ|ｱｯｼｪﾝﾃ)(?<q>.*?)(?:http.*)?$#u');
preg_match(MATCH_PATTERN_BG, $str, $m);
var_dump($m);

 */
