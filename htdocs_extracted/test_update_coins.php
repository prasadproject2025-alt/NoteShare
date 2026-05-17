<?php
session_start();

// Simulate login for testing
$_SESSION['user_id'] = md5('durgaprasad.s2022a@vitstudent.ac.in');
$_SESSION['user_email'] = 'durgaprasad.s2022a@vitstudent.ac.in';

// Simulate JSON input by overriding the input stream
$testJson = json_encode([
    'action' => 'deduct',
    'coins' => 1,
    'description' => 'Test API deduction'
]);

// Create a temporary file and redirect php://input to it
$tempFile = tempnam(sys_get_temp_dir(), 'php_input');
file_put_contents($tempFile, $testJson);

// Redirect stdin to our temp file
$stdin = fopen('php://stdin', 'r');
$tempHandle = fopen($tempFile, 'r');

// This is a bit hacky, but let's try a different approach
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Override the file_get_contents call by creating a wrapper
class TestInputWrapper {
    public static $testData = '';
}

TestInputWrapper::$testData = $testJson;

// Temporarily replace file_get_contents
$original_file_get_contents = 'file_get_contents';
if (!function_exists('test_file_get_contents')) {
    function test_file_get_contents($filename) {
        if ($filename === 'php://input') {
            return TestInputWrapper::$testData;
        }
        return call_user_func('\\' . $original_file_get_contents, $filename);
    }
    $original_file_get_contents = 'file_get_contents';
    rename_function('file_get_contents', 'original_file_get_contents');
    rename_function('test_file_get_contents', 'file_get_contents');
}

header('Content-Type: application/json');

// Include the API logic
include 'update_user_coins.php';

// Clean up
unlink($tempFile);
?>