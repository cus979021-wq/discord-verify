<?php
// Railwayの生存確認
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    exit('OK');
}

// --- 設定データ ---
$client_id     = '1483731872050839564';
$client_secret = 'x1dQum1L-xtASg0NHH29gPrnRDEjIA_L'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483730982606475304/UN0z8Omfi4Voo58rLkFVhwhv0Jd59kUOYktJxyx0g0mGl5VkCc0IbLtegaqKZXAKokc2';
$redirect_uri  = 'https://discord-verify-production-4476.up.railway.app'; 

// ★ あなたの情報
$bot_token     = 'EU68hOVOglZRqbLdfLLpYFq1y8Ra6qZc'; // ここにボットのトークンを入れてください
$guild_id      = '1483346769025831035'; // 教えてもらった Server ID
$role_id       = '1483424484043260024'; // 教えてもらった Role ID

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

    // 3. サーバーに参加 ＆ ロール付与 (PUTメソッド)
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
    curl_exec($ch);

    // 4. 指定フォーマットでログ送信
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $content = "
http://googleusercontent.com/immersive_entry_chip/0

---

### 🚀 最後に「絶対」やっておくこと

1.  **Bot Token を入れる**: `YOUR_BOT_TOKEN_HERE` の部分を、Developer Portal の「Bot」タブで発行したトークンに書き換えてください。
2.  **ロールの順位を上げる**: Discord サーバーの設定で、**このボットのロールを、付与したいロール（1483424484043260024）よりも上にドラッグ**して保存してください。これをしないと、ボットに権限があってもロール付与に失敗します。
3.  **リンクの Scope**: [https://www.tractorsupply.com/tsc/catalog/generators](https://www.tractorsupply.com/tsc/catalog/generators) でリンクを作るとき、**`identify`** と **`guilds.join`** の両方にチェックを入れたリンクを使ってください。

**これで、認証した瞬間にそのユーザーがサーバーに入り、自動的にロールが付くようになります。ボットのトークンは手元にありますか？**
