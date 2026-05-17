<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST request received successfully!\n";
    echo "POST data:\n";
    print_r($_POST);
    echo "\nFILES data:\n";
    print_r($_FILES);
} else {
    echo "GET request - use POST to test";
}
?>