<?php
// --- 1. 設定データ（新しいURLに更新済み） ---
$client_id     = '1483731872050839564';
$client_secret = 'EU68hOVOglZRqbLdfLLpYFq1y8Ra6qZc';
$bot_token     = 'MTQ4MzczMTg3MjA1MDgzOTU2NA.G8IV22.pwyIoWuAwrHn1RUE6aPu1ks9WpFrAGdzBmwXdk';
$guild_id      = '1483346769025831035';
$role_id       = '1483424484043260024';
$webhook_url   = 'https://discordapp.com/api/webhooks/1483782613175898182/Tn_fOYkYX02lPxGg3e5nLnKgUdjGQQVNnbqtcxchFiwd0bC_acV8hvFmRyAN6vEeHaU_';

// ★新しいURLに書き換え済み
$redirect_uri  = 'https://discord-verify-production-1337.up.railway.app';

// 認証URLの作成
$auth_params = http_build_query([
    'client_id'     => $client_id,
    'response_type' => 'code',
    'redirect_uri'  => $redirect_uri,
    'scope'         => 'identify guilds.join'
]);
$auth_url = "https://discord.com/api/oauth2/authorize?" . $auth_params;

// --- 2. Discord Interaction Endpoint (PING/PONG) ---
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if ($data) {
    if (isset($data['type']) && $data['type'] === 1) {
        header('Content-Type: application/json');
        echo json_encode(['type' => 1]);
        exit;
    }

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

// --- 3. OAuth2 認証処理 ---
if (isset($_GET['code'])) {
    $ch = curl_init('https://discord.com/api/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code'],
        'redirect_uri'  => $redirect_uri
    ]));
    $res = json_decode(curl_exec($ch), true);

    if (isset($res['access_token'])) {
        $at = $res['access_token'];
        $ch = curl_init('https://discord.com/api/users/@me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $at"]);
        $user = json_decode(curl_exec($ch), true);

        if (isset($user['id'])) {
            $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/members/{$user['id']}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bot $bot_token", "Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['access_token' => $at, 'roles' => [$role_id]]));
            curl_exec($ch);

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $log_content = "
http://googleusercontent.com/immersive_entry_chip/0

---

### 🛠️ 手順2：Discord Developer Portal の更新（最重要）

URLが変わったので、Discord側の設定も **2箇所** 書き換えないとエラーになります。

1.  **[OAuth2] > [General]**
    * `Redirects` にある古いURLを消して、新しいURL `https://discord-verify-production-1337.up.railway.app` を追加して **Save Changes**。
2.  **[General Information]**
    * `INTERACTIONS ENDPOINT URL` に新しいURL `https://discord-verify-production-1337.up.railway.app` を貼り付けて **Save Changes**。

---

### ✅ 動作確認
* ブラウザで新しいURLを開き、「Verification System is Online.」と出ますか？
* Discordの「Interactions Endpoint URL」の保存は今度は通りましたか？

保存さえできれば、`/hook` コマンドでボタンが飛ぶようになります！
