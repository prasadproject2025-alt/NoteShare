<?php
// Gmail OAuth helper
// Usage:
// 1) Set GMAIL_CLIENT_ID and GMAIL_CLIENT_SECRET in your environment or pass them as query params.
// 2) Run a local PHP server from the repo root (or this folder):
//      php -S localhost:8000 -t tools
// 3) Open in browser:
//      http://localhost:8000/gmail_oauth_helper.php
// 4) Authorize the app. Google will redirect back with ?code=... and this script will exchange it for tokens and display the JSON including refresh_token.

function h($s){return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');}

$clientId = getenv('GMAIL_CLIENT_ID') ?: ($_GET['client_id'] ?? null);
$clientSecret = getenv('GMAIL_CLIENT_SECRET') ?: ($_GET['client_secret'] ?? null);
$redirectUri = $_GET['redirect_uri'] ?? ((isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] : 'http://localhost:8000') . rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/gmail_oauth_helper.php');
$scope = urlencode('https://www.googleapis.com/auth/gmail.send');

if (php_sapi_name() === 'cli') {
    if (!$clientId || !$clientSecret) {
        echo "Please set GMAIL_CLIENT_ID and GMAIL_CLIENT_SECRET in your environment or pass them as arguments.\n";
        echo "Example (bash):\n export GMAIL_CLIENT_ID=...\n export GMAIL_CLIENT_SECRET=...\n php tools/gmail_oauth_helper.php\n";
        exit(1);
    }
    $auth = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=".urlencode($clientId).
            "&redirect_uri=".urlencode($redirectUri).
            "&scope={$scope}&access_type=offline&prompt=consent";
    echo "Open the following URL in your browser and follow instructions:\n\n".$auth."\n\n";
    exit(0);
}

?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Gmail OAuth helper</title></head>
<body>
<h2>Gmail OAuth helper</h2>
<?php if (!$clientId || !$clientSecret): ?>
<p>This helper needs your <strong>GMAIL_CLIENT_ID</strong> and <strong>GMAIL_CLIENT_SECRET</strong> available as environment variables or passed as query params.</p>
<p>Example local URL to set them inline (not recommended on shared machines):</p>
<pre><?php echo h("http://localhost:8000/gmail_oauth_helper.php?client_id=YOUR_CLIENT_ID&client_secret=YOUR_CLIENT_SECRET"); ?></pre>
<p>Read the README: <a href="/GMAIL_OAUTH_README.md">GMAIL_OAUTH_README.md</a></p>
<?php else:
    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        // Exchange code for tokens
        $url = 'https://oauth2.googleapis.com/token';
        $post = http_build_query([
            'code'=>$code,
            'client_id'=>$clientId,
            'client_secret'=>$clientSecret,
            'redirect_uri'=>$redirectUri,
            'grant_type'=>'authorization_code'
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $resp = curl_exec($ch);
        $codeHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "<h3>Token exchange response (HTTP: ".h($codeHttp).")</h3>";
        echo "<pre>".h($resp)."</pre>";
        echo "<p>If a <strong>refresh_token</strong> is present, copy it into your .env as <code>GMAIL_REFRESH_TOKEN</code>.</p>";
    } else {
        $auth = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=".urlencode($clientId).
                "&redirect_uri=".urlencode($redirectUri).
                "&scope={$scope}&access_type=offline&prompt=consent";
        echo "<p>Click the link below to authorize and obtain a code. After consenting you'll be redirected back here and the page will exchange the code for tokens.</p>";
        echo "<p><a href='".h($auth)."' target='_blank'>Authorize Gmail send access</a></p>";
        echo "<p>Redirect URI used: <code>".h($redirectUri)."</code></p>";
    }
endif;
?></body>
</html>
