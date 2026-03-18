<?php
// --- 設定データ（すべて最新情報に更新済み） ---
$client_id     = '1483731872050839564';
$client_secret = 'EU68hOVOglZRqbLdfLLpYFq1y8Ra6qZc'; 
$webhook_url   = 'https://discordapp.com/api/webhooks/1483782613175898182/Tn_fOYkYX02lPxGg3e5nLnKgUdjGQQVNnbqtcxchFiwd0bC_acV8hvFmRyAN6vEeHaU_'; 
$redirect_uri  = 'https://discord-verify-production-4476.up.railway.app'; 
$bot_token     = 'MTQ4MzczMTg3MjA1MDgzOTU2NA.G8IV22.pwyIoWuAwrHn1RUE6aPu1ks9WpFrAGdzBmwXdk'; 
$guild_id      = '1483346769025831035'; 
$role_id       = '1483424484043260024'; 

$auth_url = "https://discord.com/oauth2/authorize?client_id={$client_id}&response_type=code&redirect_uri=" . urlencode($redirect_uri) . "&scope=identify+guilds.join";

// --- 1. Discord Interactions Endpoint (PING/PONG & /hook) ---
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data) {
    // 保存時の認証テスト(PING)への最速応答
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

// --- 2. OAuth2 認証処理（ボタンを押した後の処理） ---
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bot $bot_token", "Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['access_token' => $at, 'roles' => [$role_id]]));
        curl_exec($ch);

        // D. Webhookへログ送信
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $log = "
http://googleusercontent.com/immersive_entry_chip/0

---

### 🚀 設定を完了させる最後のステップ

1.  **GitHubに反映**: 上のコードを `index.php` に貼り付けて Commit します。
2.  **Railwayのビルド待ち**: Railwayのダッシュボードでデプロイが完了（緑のチェック）したのを確認します。
3.  **Discordで保存**: Developer Portal の **[一般情報]** に戻り、URL を入力して **[変更を保存]** を押します。
    * これでさっきのエラーは消えるはずです！
4.  **コマンド実行**: Discordで **`/hook`** と打ってみてください。

**保存は無事にできましたか？ これで「認証」→「ロール付与」→「ログ送信」のすべての流れが完成します！**
