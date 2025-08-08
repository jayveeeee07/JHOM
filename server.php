<?php
// server.php
header('Content-Type: application/json; charset=utf-8');

$dataFile = __DIR__ . '/data.json';

// Ensure data.json exists
if(!file_exists($dataFile)){
    file_put_contents($dataFile, json_encode([
        "updates"=>[], "news"=>[], "events"=>[], "promos"=>[]
    ], JSON_PRETTY_PRINT));
}

// Read current data
$raw = file_get_contents($dataFile);
$data = json_decode($raw, true);
if($data === null) $data = ["updates"=>[], "news"=>[], "events"=>[], "promos"=>[]];

// Simple router: GET => return data, POST => modify
$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET'){
    // Return JSON for the client (home/admin)
    echo json_encode($data);
    exit;
}

// POST handling: expects fields: section, action (add|edit|delete), title, description, date, index (for edit/delete)
$section = isset($_POST['section']) ? $_POST['section'] : null;
$action  = isset($_POST['action']) ? $_POST['action'] : 'add';

// basic validation
$validSections = ['updates','news','events','promos'];
if(!in_array($section, $validSections)){
    echo json_encode(["status"=>"error","message"=>"invalid section"]);
    exit;
}

if($action === 'add'){
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';

    $item = [
        "title" => $title,
        "description" => $description,
        "date" => $date
    ];
    $data[$section][] = $item;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(["status"=>"ok","message"=>"added","item"=>$item]);
    exit;
}

if($action === 'edit'){
    if(!isset($_POST['index'])){
        echo json_encode(["status"=>"error","message"=>"missing index"]);
        exit;
    }
    $index = intval($_POST['index']);
    if(!isset($data[$section][$index])){
        echo json_encode(["status"=>"error","message"=>"index not found"]);
        exit;
    }
    $title = isset($_POST['title']) ? trim($_POST['title']) : $data[$section][$index]['title'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : $data[$section][$index]['description'];
    $date = isset($_POST['date']) ? trim($_POST['date']) : $data[$section][$index]['date'];

    $data[$section][$index] = [
        "title"=>$title,
        "description"=>$description,
        "date"=>$date
    ];
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(["status"=>"ok","message"=>"edited","index"=>$index]);
    exit;
}

if($action === 'delete'){
    if(!isset($_POST['index'])){
        echo json_encode(["status"=>"error","message"=>"missing index"]);
        exit;
    }
    $index = intval($_POST['index']);
    if(!isset($data[$section][$index])){
        echo json_encode(["status"=>"error","message"=>"index not found"]);
        exit;
    }
    array_splice($data[$section], $index, 1);
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(["status"=>"ok","message"=>"deleted","index"=>$index]);
    exit;
}

// fallback
echo json_encode(["status"=>"error","message"=>"unknown action"]);
