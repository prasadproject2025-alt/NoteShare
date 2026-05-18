# Convert PHP pages from reference repo to static HTML for Vercel
$root = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$src = Join-Path $root "_php_ref"
$dst = $root

$pages = @{
    "index.php" = "index.html"
    "login.php" = "login.html"
    "create_account.php" = "create-account.html"
    "buy_notes.php" = "buy-notes.html"
    "sell_notes.php" = "sell-notes.html"
    "share_notes.php" = "share-notes.html"
    "rent_notes.php" = "rent-notes.html"
    "coins.php" = "coins.html"
    "messages.php" = "messages.html"
    "profile.php" = "profile.html"
    "edit_profile.php" = "edit-profile.html"
    "upload_progress.php" = "upload-progress.html"
    "admin_login.php" = "admin-login.html"
    "admin_dashboard.php" = "admin-dashboard.html"
}

$htmlShellStart = @'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoteShare - VIT Note Sharing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/nav.js" defer></script>
</head>
<body>
<div id="app-header"></div>
'@

$htmlShellEnd = @'
<div id="app-footer"></motion.div>
</body>
</html>
'@.Replace('</motion.div>', '')

function Convert-Content($content) {
    # Remove PHP blocks (non-greedy)
    $content = [regex]::Replace($content, '(?s)<\?php.*?\?>\s*', '')

    # Remove duplicate DOCTYPE/html from old header include
    $content = [regex]::Replace($content, '(?s)<!DOCTYPE html>.*?</nav>\s*', '', 1)

    # .php -> .html in links
    $content = $content -replace 'href="([^"]+)\.php"', 'href="$1.html"'
    $content = $content -replace "href='([^']+)\.php'", "href='`$1.html'"
    $content = $content -replace 'window\.location\.href\s*=\s*''([^'']+)\.php''', "window.location.href = '`$1.html'"
    $content = $content -replace 'window\.location\.href\s*=\s*"([^"]+)\.php"', 'window.location.href = "$1.html"'
    $content = $content -replace "fetch\('([^']+)\.php", "fetch('`$1.html"
    $content = $content -replace 'fetch\("([^"]+)\.php', 'fetch("$1.html'

    # API endpoints
    $content = $content -replace "fetch\('auth/send_otp\.php'", "fetch('/api/send-otp'"
    $content = $content -replace "fetch\('auth/verify_otp\.php'", "fetch('/api/verify-otp'"
    $content = $content -replace "fetch\('auth/login\.php'", "// removed - use NoteShareAuth.login"
    $content = $content -replace "fetch\('auth/create_account\.php'", "// removed - use NoteShareAuth.register"
    $content = $content -replace "fetch\('auth/get_user_data\.php'", "// removed"
    $content = $content -replace "fetch\('get_user_coins\.php[^']*'", "NoteShareCoins.getBalanceInfo().then(data => ({ json: () => Promise.resolve(data) }))"
    $content = $content -replace "fetch\('update_user_coins\.php'", "// use NoteShareCoins.updateCoins"
    $content = $content -replace "fetch\('clear_session\.php[^']*'", "Promise.resolve({ ok: true, json: () => ({ success: true }) })"
    $content = $content -replace "fetch\('admin_api\.php[^']*'", "fetch('/api/admin'"
    $content = $content -replace 'Location: login\.php', 'login.html'
    $content = $content -replace 'action="sell_notes\.php"', 'action="#" id="sell-notes-form"'

    # Session PHP echoes -> JS globals (filled by auth)
    $content = $content -replace "const userId = '';", "let userId = '';"
    $content = $content -replace "const userId = '<\?php echo \$_SESSION\['user_id'\]; \?>';", 'let userId = "";'
    $content = $content -replace "const userEmail = '[^']*';", "let userEmail = window.NoteShareAuth ? window.NoteShareAuth.getUserEmail() : '';"
    $content = $content -replace "const userName = '[^']*';", "let userName = window.NoteShareAuth ? window.NoteShareAuth.getUserName() : '';"
    $content = $content -replace '\$chat_id = [^;]+;', ''
    $content = $content -replace "const chatId = '[^']*';", "const chatId = new URLSearchParams(window.location.search).get('chat_id') || '';"

    # Remove duplicate firebase script blocks (nav loads them)
    $content = [regex]::Replace($content, '(?s)<script src="https://www\.gstatic\.com/firebasejs[^<]+</script>\s*<script src="js/firebase-config\.js"></script>\s*', '')

    return $content.Trim()
}

foreach ($entry in $pages.GetEnumerator()) {
    $inFile = Join-Path $src $entry.Key
  if (-not (Test-Path $inFile)) {
        Write-Warning "Skip missing: $($entry.Key)"
        continue
    }
    $raw = Get-Content $inFile -Raw -Encoding UTF8
    $body = Convert-Content $raw
    $out = $htmlShellStart + "`n" + $body + "`n" + $htmlShellEnd
    $outFile = Join-Path $dst $entry.Value
    [System.IO.File]::WriteAllText($outFile, $out, [System.Text.UTF8Encoding]::new($false))
    Write-Host "Converted $($entry.Key) -> $($entry.Value)"
}

# Copy css
Copy-Item (Join-Path $src "css\style.css") (Join-Path $dst "css\style.css") -Force
Copy-Item (Join-Path $src "js\firebase-config.js") (Join-Path $dst "js\firebase-config.js") -Force

# Root redirect
@"
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=login.html">
    <title>NoteShare</title>
    <script>window.location.replace('login.html');</script>
</head>
<body><p><a href="login.html">Go to NoteShare</a></p></body>
</html>
"@ | Set-Content (Join-Path $dst "index-redirect.html") -Encoding UTF8

Write-Host "Done."
