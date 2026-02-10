<?php
header("Content-Type: text/plain; charset=utf-8");

function getTOTP($s): string {
  $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $s = strtoupper(str_replace('=', '', $s));
  
  $b = '';
  foreach (str_split($s) as $c) {
    $b .= str_pad(decbin(strpos($a, $c)), 5, '0', STR_PAD_LEFT);
  }
  
  $k = '';
  foreach (str_split($b, 8) as $bin) {
    if (strlen($bin) === 8) $k .= chr(bindec($bin));
  }

  $t = floor(time() / 30);
  $msg = pack('N', 0) . pack('N', $t);
  $h = hash_hmac('sha1', $msg, $k, true);

  $o = ord($h[19]) & 0xf;
  $v = (
    ((ord($h[$o]) & 0x7f) << 24) |
    ((ord($h[$o + 1]) & 0xff) << 16) |
    ((ord($h[$o + 2]) & 0xff) << 8) |
    (ord($h[$o + 3]) & 0xff)
  ) % 1000000;

  return str_pad($v, 6, '0', STR_PAD_LEFT);
}






$key = $_POST["key"]??"";
if (getTOTP(getenv("OTP_TOKEN")) !== $key) die("Unauthorized");

$chat_id = getenv("CHAT_ID");
$token = getenv("BOT_TOKEN");
$text = $_POST["text"]??"_\[Empty\]_";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://api.telegram.org/bot$token/sendMessage");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
  "chat_id"    => $chat_id,
  "text"       => $text,
  "parse_mode" => "markdownv2"
]));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($curl);
$res = json_decode($res, true);


die($res["ok"] ? "Sent to @{$res["result"]["chat"]["username"]}" : $res["description"]);
?>
