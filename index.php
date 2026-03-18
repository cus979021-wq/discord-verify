<?php
// Discordの認証（PING/PONG）を最優先で処理するコード
$raw_post = file_get_contents('php://input');
$data = json_decode($raw_post, true);

// 1. Discordからの認証信号（PING）を受け取ったら即座にPONGを返す
if (isset($data['type']) && $data['type'] === 1) {
    header('Content-Type: application/json');
    echo json_encode(['type' => 1]);
    exit;
}

// 2. /hook コマンドへの応答
if (isset($data['type']) && $data['type'] === 2 && $data['data']['name'] === 'hook') {
    header('Content-Type: application/json');
    echo json_encode([
        "type" => 4,
        "data" => [
            "embeds" => [[
                "title" => "認証",
                "description" => "サーバーに参加してくれてありがとうございます。最初は認証からお願いします。\n\n認証後 <@&1483424484043260024> が付与されます。",
                "color" => 5814783
            ]],
            "components" => [[
                "type" => 1,
                "components" => [[
                    "type" => 2, "label" => "認証", "style" => 5, "url" => "https://discord.com/api/oauth2/authorize?client_id=1483731872050839564&response_type=code&redirect_uri=https%3A%2F%2Fdiscord-verify-production-4476.up.railway.app&scope=identify+guilds.join"
                ]]
            ]]
        ]
    ]);
    exit;
}

// ブラウザ確認用
echo "Endpoint validation ready.";
