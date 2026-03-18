<?php
// --- 最新の設定データ ---
$client_id     = '1483731872050839564';
$client_secret = 'EU68hOVOglZRqbLdfLLpYFq1y8Ra6qZc'; 
$guild_id      = '1483346769025831035'; 
$role_id       = '1483424484043260024'; 
$bot_token     = 'MTQ4MzczMTg3MjA1MDgzOTU2NA.G8IV22.pwyIoWuAwrHn1RUE6aPu1ks9WpFrAGdzBmwXdk'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483782613175898182/Tn_fOYkYX02lPxGg3e5nLnKgUdjGQQVNnbqtcxchFiwd0bC_acV8hvFmRyAN6vEeHaU_'; 
$redirect_uri  = 'https://discord-verify-production-4476.up.railway.app'; 

$auth_url = "https://discord.com/oauth2/authorize?client_id={$client_id}&response_type=code&redirect_uri=" . urlencode($redirect_uri) . "&scope=identify+guilds.join";

// --- 1. Discord Interactions Endpoint (保存エラー回避用) ---
$raw_post = file_get_contents('php://input');
$data = json_decode($raw_post, true);

if ($data) {
    // PINGへの応答 (これが無いと保存できません)
    if (isset($data['type']) && $data['type'] === 1) {
        header('Content-Type: application/json');
        exit(json_encode(['type' => 1]));
    }
    // /hook コマンドへの応答
    if (isset($data['type']) && $data['type'] === 2 && $data['data']['name'] === 'hook') {
        header('Content-Type: application/json');
        echo json_encode([
            "type" => 4,
            "data" => [
                "embeds" => [[
                    "title" => "認証システム",
                    "description" => "サーバーに参加いただきありがとうございます。\n下のボタンを押して認証を完了させてください。\n\n認証後、自動的に <@&{$role_id}> が付与されます。",
                    "color" => 3447003
                ]],
                "components" => [[
                    "type" => 1,
                    "components" => [[
                        "type" => 2, "label" => "認証を開始する", "style" => 5, "url" => $auth_url
                    ]]
                ]]
            ]
        ]);
        exit;
    }
}

// --- 2. OAuth2 認証・ロール付与・ログ送信 ---
if (isset($_GET['code'])) {
    // A. アクセストークンの取得
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

        // B. ユーザー情報の取得
        $ch = curl_init('https://discord.com/api/users/@me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $at"]);
        $user = json_decode(curl_exec($ch), true);

        if (isset($user['id'])) {
            // C. サーバーにユーザーを追加 ＆ ロール付与
            $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/members/{$user['id']}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bot $bot_token", "Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['access_token' => $at, 'roles' => [$role_id]]));
            curl_exec($ch);

            // D. Webhookへログ送信
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $log = "
http://googleusercontent.com/immersive_entry_chip/0

---

### 🚀 修正したポイント
1.  **保存エラー対策**: 冒頭に `type === 1` (PING) への応答を追加しました。これで Developer Portal での保存が通ります。
2.  **最新ID反映**: `Secret`、`Guild ID`、`Role ID`、`Webhook` をあなたが最後に提示したものにすべて書き換えました。
3.  **URL修正**: ロール付与のURLにあった余計なエンコード（`%7B` など）を削除し、正しく通信できるようにしました。

### 📌 手順
1.  GitHubの `index.php` をこのコードで**全上書き**して保存。
2.  Railwayのデプロイが終わったら、Discord Developer Portalの「一般情報」で **Interactions Endpoint URL** を保存。
3.  Discordで **`/hook`** を打ってボタンを出し、テストする。

**これで保存は成功しましたか？ また、`/hook` は出てくるようになりましたか？**
