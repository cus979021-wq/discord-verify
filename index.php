<?php
// --- Discord Endpoint 認証専用コード ---
$input = file_get_contents('php://input');
if ($input) {
    $data = json_decode($input, true);
    
    // Discordからの「PING」に対して「PONG」を返す（最優先）
    if (isset($data['type']) && $data['type'] === 1) {
        header('Content-Type: application/json');
        echo json_encode(['type' => 1]);
        exit;
    }

    // スラッシュコマンド /hook への応答
    if (isset($data['type']) && $data['type'] === 2 && $data['data']['name'] === 'hook') {
        $role_id = '1483424484043260024';
        $auth_url = "https://discord.com/oauth2/authorize?client_id=1483731872050839564&response_type=code&redirect_uri=https%3A%2F%2Fdiscord-verify-production-4476.up.railway.app&scope=identify+guilds.join";
        
        header('Content-Type: application/json');
        echo json_encode([
            "type" => 4,
            "data" => [
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
            ]
        ]);
        exit;
    }
}

// --- 通常のOAuth処理（ブラウザ経由） ---
if (isset($_GET['code'])) {
    // ここにトークン取得・ロール付与の処理（前回の後半部分）を入れる
    // 今はまず保存を優先するため、空でもOKです
    header("Location: https://discord.com/channels/@me");
    exit;
}

echo "Endpoint is Ready.";
