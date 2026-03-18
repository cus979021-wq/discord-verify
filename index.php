<?php
/**
 * Discord Verification System - Final Stable Version
 * URL: https://discord-verify-production-1337.up.railway.app
 */

// エラー内容を表示するように設定（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. 設定データ（最新情報） ---
$client_id     = '1483731872050839564';
$client_secret = 'qzy6uNqoSeLYccMvwxQUB94HdZqp_pHg';
$bot_token     = 'MTQ4MzczMTg3MjA1MDgzOTU2NA.G8IV22.pwyIoWuAwrHn1RUE6aPu1ks9WpFrAGdzBmwXdk';
$guild_id      = '1483346769025831035';
$role_id       = '1483424484043260024';
$webhook_url   = 'https://discordapp.com/api/webhooks/1483782613175898182/Tn_fOYkYX02lPxGg3e5nLnKgUdjGQQVNnbqtcxchFiwd0bC_acV8hvFmRyAN6vEeHaU_';
$redirect_uri  = 'https://discord-verify-production-1337.up.railway.app';

// 認証URLの作成
$auth_params = http_build_query([
    'client_id'     => $client_id,
    'response_type' => 'code',
    'redirect_uri'  => $redirect_uri,
    'scope'         => 'identify guilds.join'
]);
$auth_url = "https://discord.com/api/oauth2/authorize?" . $auth_params;

// --- 2. Discord Interaction Endpoint (PING/PONG & /hook) ---
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if ($data) {
    // Discordからの生存確認(PING)への応答
    if (isset($data['type']) && $data['type'] === 1) {
        header('Content-Type: application/json');
        echo json_encode(['type' => 1]);
        exit;
    }

    // スラッシュコマンド /hook への応答
    if (isset($data['type']) && $data['type'] === 2 && isset($data['data']['name']) && $data['data']['name'] === 'hook') {
        header('Content-Type: application/json');
        echo json_encode([
            "type" => 4,
            "data" => [
                "embeds" => [[
                    "title" => "認証システム",
                    "description" => "サーバーに参加いただきありがとうございます。\n下のボタンを押して認証を完了させてください。\n\n認証後、自動的に <@&{$role_id}> が付与されます。\n\n**Powered by Unify_BOT**",
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

// --- 3. OAuth2 認証処理 (ボタン押下後の戻り先) ---
if (isset($_GET['code'])) {
    // トークン取得
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

        // ユーザー情報の取得
        $ch = curl_init('https://discord.com/api/users/@me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $at"]);
        $user = json_decode(curl_exec($ch), true);

        if (isset($user['id'])) {
            // サーバーにユーザーを追加 ＆ ロール付与
            $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/members/{$user['id']}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bot $bot_token", "Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['access_token' => $at, 'roles' => [$role_id]]));
            curl_exec($ch);

            // Webhookへログ送信
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $log_content = "
http://googleusercontent.com/immersive_entry_chip/0

---

### 🚀 貼り付けた後の手順

1.  GitHubで **Commit changes** ボタンを押して保存します。
2.  **Railwayのダッシュボード** を開き、ビルドが完了（緑色のチェック）するのを待ちます。
3.  ブラウザで `https://discord-verify-production-1337.up.railway.app` を開き、**「Verification System is Online.」** と出れば準備完了です！
4.  Discord Developer Portal の **Interactions Endpoint URL** にこのURLを貼り付けて保存してください。

今度はコードが見えましたか？反映できたら教えてくださいね。
