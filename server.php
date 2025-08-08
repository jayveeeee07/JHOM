<?php
$dataFile = 'data.json';
$jsonData = json_decode(file_get_contents($dataFile), true);

// Handle delete
if (isset($_GET['delete'])) {
    $section = $_GET['section'];
    $index = intval($_GET['index']);
    array_splice($jsonData[$section], $index, 1);
    file_put_contents($dataFile, json_encode($jsonData, JSON_PRETTY_PRINT));
    echo "✅ Item deleted successfully";
    exit;
}

// Handle Add
$section = $_POST['section'];

if ($section === 'rooms') {
    // Upload image
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) mkdir($targetDir);
    $imagePath = $targetDir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);

    $jsonData['rooms'][] = [
        "name" => $_POST['roomName'],
        "price" => $_POST['price'],
        "roomNumber" => $_POST['roomNumber'],
        "description" => $_POST['description'],
        "image" => $imagePath
    ];
} else {
    $jsonData[$section][] = [
        "title" => $_POST['title'],
        "description" => $_POST['description'],
        "date" => $_POST['date']
    ];
}

file_put_contents($dataFile, json_encode($jsonData, JSON_PRETTY_PRINT));
header("Location: adminpanel.html");
?>
