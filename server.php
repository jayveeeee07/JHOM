<?php
$dataFile = 'data.json';
$data = json_decode(file_get_contents($dataFile), true);

$section = $_POST['section'];
$action = $_POST['action'];

if ($action === 'add') {
    $data[$section][] = [
        "title" => $_POST['title'],
        "description" => $_POST['description'],
        "date" => $_POST['date']
    ];
} elseif ($action === 'edit') {
    $index = $_POST['index'];
    $data[$section][$index] = [
        "title" => $_POST['title'],
        "description" => $_POST['description'],
        "date" => $_POST['date']
    ];
} elseif ($action === 'delete') {
    $index = $_POST['index'];
    array_splice($data[$section], $index, 1);
}

file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
echo "success";
?>
