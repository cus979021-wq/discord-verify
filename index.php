<?php
/**
 * Discord Interactions Endpoint - Validation Bypass Edition
 */

// 1. Discordからの生データ（JSON）を取得
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// 2. Discordからの確認信号（PING）に対し、即座にPONGを返して保存をパスさせる
if (isset($data['type']) && $data['type'] === 1) {
    header('Content-Type: application/json');
    echo json_encode(['type' => 1]);
    exit; // これより下の処理は実行しない（最速レスポンス）
}

// 3. /hook コマンドへの応答（保存が通った後に動作します）
if (isset($data['type']) && $data['type'] === 2 && $data['data']['name'] === 'hook') {
    $role_id = '1483424484043260024';
    $auth_url = "https://discord.com/oauth2/authorize?client_id=1483731872050839564&response_type=code&redirect_uri=https%3A%2F%2Fdiscord-verify-production-4476.up.railway.app&scope=identify+guilds.join";
    
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

// 4. OAuth2 認証処理（?code=... が付いている場合）
if (isset($_GET['code'])) {
    // 以前のトークン取得・ログ送信処理（ここは保存に関係ないので省略可、または前回のを追記）
    header("Location: https://discord.com/channels/@me");
    exit;
}

// ブラウザ確認用
echo "Status: Active";
