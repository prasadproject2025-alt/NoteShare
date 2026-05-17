<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$users_file = __DIR__ . '/data/users.json';

function loadUsers() {
    global $users_file;
    if (!file_exists($users_file)) {
        return [];
    }
    return json_decode(file_get_contents($users_file), true) ?: [];
}

function saveUsers($users) {
    global $users_file;
    $data_dir = dirname($users_file);
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
}

try {
    switch ($method) {
        case 'GET':
            // Get all users
            $users = loadUsers();
            $user_list = [];
            foreach ($users as $email => $user) {
                // Exclude sensitive information like passwords
                $safe_user = [
                    'id' => md5($email), // Use hashed email as ID for privacy
                    'email' => $email,
                    'name' => $user['name'] ?? 'N/A',
                    'coins' => $user['coins'] ?? 0,
                    'created_at' => $user['created_at'] ?? null,
                    'status' => $user['status'] ?? 'active',
                    'blocked' => $user['blocked'] ?? false
                ];
                $user_list[] = $safe_user;
            }
            echo json_encode([
                'success' => true,
                'users' => $user_list,
                'total' => count($user_list)
            ]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';

            switch ($action) {
                case 'add_coins':
                    $user_id = $input['user_id'] ?? '';
                    $amount = intval($input['amount'] ?? 0);

                    if (!$user_id || $amount <= 0) {
                        throw new Exception('Invalid user ID or amount');
                    }

                    $users = loadUsers();
                    $found = false;

                    foreach ($users as $email => &$user) {
                        if (md5($email) === $user_id) {
                            $user['coins'] = ($user['coins'] ?? 0) + $amount;
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        throw new Exception('User not found');
                    }

                    saveUsers($users);
                    echo json_encode(['success' => true, 'message' => 'Coins added successfully']);
                    break;

                case 'reset_coins':
                    $user_id = $input['user_id'] ?? '';

                    if (!$user_id) {
                        throw new Exception('Invalid user ID');
                    }

                    $users = loadUsers();
                    $found = false;

                    foreach ($users as $email => &$user) {
                        if (md5($email) === $user_id) {
                            $user['coins'] = 0;
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        throw new Exception('User not found');
                    }

                    saveUsers($users);
                    echo json_encode(['success' => true, 'message' => 'Coins reset successfully']);
                    break;

                case 'delete_user':
                    $user_id = $input['user_id'] ?? '';

                    if (!$user_id) {
                        throw new Exception('Invalid user ID');
                    }

                    $users = loadUsers();
                    $found = false;

                    foreach ($users as $email => $user) {
                        if (md5($email) === $user_id) {
                            unset($users[$email]);
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        throw new Exception('User not found');
                    }

                    saveUsers($users);
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                    break;

                case 'toggle_block':
                    $user_id = $input['user_id'] ?? '';
                    $block = $input['block'] ?? false;

                    if (!$user_id) {
                        throw new Exception('Invalid user ID');
                    }

                    $users = loadUsers();
                    $found = false;

                    foreach ($users as $email => &$user) {
                        if (md5($email) === $user_id) {
                            $user['blocked'] = $block;
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        throw new Exception('User not found');
                    }

                    saveUsers($users);
                    $action_text = $block ? 'blocked' : 'unblocked';
                    echo json_encode(['success' => true, 'message' => "User $action_text successfully"]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>