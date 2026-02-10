<?php
header("Content-Type: text/plain; charset=utf-8");

function getTOTP(string $s): string {
  $b = "";
  foreach (str_split(strtoupper(rtrim($s, '='))) as $c) {
    if (($v = strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', $c)) !== false) {
      $b .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
    }
  }
  $r = "";
  foreach (str_split($b, 8) as $k) if (strlen($k) == 8) $r .= chr(bindec($k));
  $t = floor(time() / 30) |> (fn($x) => pack('N2', 0, $x));
  $h = hash_hmac('sha1', $t, $r, true);
  $o = ord($h[19]) & 0xf;
  $i = unpack('N', substr($h, $o, 4))[1] & 0x7fffffff;
  return str_pad($i % 1e6, 6, '0', STR_PAD_LEFT);
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
