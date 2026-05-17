<?php
$users = json_decode(file_get_contents('data/users.json'), true);
echo 'Users in system: ' . count($users) . PHP_EOL;
foreach ($users as $email => $user) {
    echo '- ' . $user['name'] . ' (' . $user['email'] . ') - ' . ($user['coins'] ?? 0) . ' coins' . PHP_EOL;
}
?>