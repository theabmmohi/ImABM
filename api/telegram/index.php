<?php
header("Content-Type: text/plain; charset=utf-8");
function getTOTP(string $s, int $off = 0): string {
    $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $s = strtoupper(str_replace('=', '', $s));
    $b = '';
    foreach (str_split($s) as $c) {
        $p = strpos($a, $c);
        if ($p !== false) $b .= str_pad(decbin($p), 5, '0', STR_PAD_LEFT);
    }
    $k = '';
    foreach (str_split($b, 8) as $n) {
        if (strlen($n) === 8) $k .= chr(bindec($n));
    }
    $t = floor(time() / 30) + $off;
    $m = pack('N', 0) . pack('N', $t);
    $h = hash_hmac('sha1', $m, $k, true);
    $o = ord($h[19]) & 0xf;
    $v = (((ord($h[$o]) & 0x7f) << 24) | ((ord($h[$o+1]) & 0xff) << 16) | ((ord($h[$o+2]) & 0xff) << 8) | (ord($h[$o+3]) & 0xff)) % 1000000;
    return str_pad((string)$v, 6, '0', STR_PAD_LEFT);
}

function esc(string $t): string {
    return str_replace(['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'], 
                       ['\\_', '\\*', '\\[', '\\]', '\\(', '\\)', '\\~', '\\`', '\\>', '\\#', '\\+', '\\-', '\\=', '\\|', '\\{', '\\}', '\\.', '\\!'], $t);
}

$uk = $_POST["key"] ?? "";
$sec = getenv("OTP_TOKEN");
$auth = false;

for ($i = -1; $i <= 1; $i++) {
    if (getTOTP($sec, $i) === $uk) {
        $auth = true;
        break;
    }
}
if (!$auth) die("Unauthorized");
$cid = getenv("CHAT_ID");
$tok = getenv("BOT_TOKEN");
$txt = esc($_POST["text"] ?? "[Empty]");
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.telegram.org/bot$tok/sendMessage",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(["chat_id" => $cid, "text" => $txt, "parse_mode" => "MarkdownV2"]),
    CURLOPT_RETURNTRANSFER => true
]);
$res = json_decode(curl_exec($ch), true);
die($res["ok"] ? "Sent to @{$res["result"]["chat"]["username"]}" : $res["description"]);
