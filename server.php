<?php
// server.php
// Simple JSON-backed endpoint for data.json
// - GET: returns data.json
// - POST:
//    * If body is raw JSON and matches expected structure => overwrite data.json
//    * Else handle form POST fields: section, action(add|edit|delete), title, description, date, index

header('Content-Type: application/json; charset=utf-8');

$DATA_FILE = __DIR__ . '/data.json';
$DEFAULT = [
    "updates" => [],
    "news"    => [],
    "events"  => [],
    "promos"  => []
];

// ensure data.json exists and is valid
if (!file_exists($DATA_FILE)) {
    file_put_contents($DATA_FILE, json_encode($DEFAULT, JSON_PRETTY_PRINT));
}

$raw = file_get_contents($DATA_FILE);
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $DEFAULT;
}

// Helper to save and respond
function save_and_json($data, $msg = "ok") {
    global $DATA_FILE;
    file_put_contents($DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(["status" => "ok", "message" => $msg, "data" => $data]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode($data);
    exit;
}

// POST handling
$input = file_get_contents('php://input');
$inputJson = json_decode($input, true);

// If raw JSON supplied (from admin JS that does JSON.stringify(data))
if ($inputJson !== null && is_array($inputJson)) {
    // validate shape: should contain the top-level keys we expect (at least)
    $allowedKeys = ['updates','news','events','promos'];
    $hasKey = false;
    foreach ($allowedKeys as $k) {
        if (array_key_exists($k, $inputJson)) { $hasKey = true; break; }
    }
    if ($hasKey) {
        // Overwrite data.json safely
        // Normalize: ensure each key is an array of objects with title/description/date
        foreach ($allowedKeys as $k) {
            if (!isset($inputJson[$k]) || !is_array($inputJson[$k])) {
                $inputJson[$k] = [];
            } else {
                // ensure each item has title/description/date keys
                foreach ($inputJson[$k] as $idx => $it) {
                    if (!is_array($it)) {
                        $inputJson[$k][$idx] = ["title" => (string)$it, "description"=>"", "date"=>""];
                    } else {
                        $t = isset($it['title']) ? (string)$it['title'] : (isset($it[0]) ? (string)$it[0] : "");
                        $d = isset($it['description']) ? (string)$it['description'] : (isset($it[1]) ? (string)$it[1] : "");
                        $dt = isset($it['date']) ? (string)$it['date'] : "";
                        $inputJson[$k][$idx] = ["title"=>$t, "description"=>$d, "date"=>$dt];
                    }
                }
            }
        }
        save_and_json($inputJson, "data overwritten via JSON payload");
    }
    // If JSON but not our shape, continue to try form handling below
}

// If not raw JSON overwrite, handle form-style POST (section/action/title/...)
$post = $_POST;
$section = isset($post['section']) ? $post['section'] : null;
$action  = isset($post['action']) ? $post['action'] : 'add';
$validSections = ['updates','news','events','promos'];

if (!$section || !in_array($section, $validSections)) {
    echo json_encode(["status"=>"error","message"=>"invalid or missing section"]);
    exit;
}

if ($action === 'add') {
    $title = isset($post['title']) ? trim($post['title']) : '';
    $description = isset($post['description']) ? trim($post['description']) : '';
    $date = isset($post['date']) ? trim($post['date']) : '';

    $item = ["title"=>$title, "description"=>$description, "date"=>$date];
    $data[$section][] = $item;
    save_and_json($data, "item added");
}

if ($action === 'edit') {
    if (!isset($post['index'])) {
        echo json_encode(["status"=>"error","message"=>"missing index for edit"]);
        exit;
    }
    $index = intval($post['index']);
    if (!isset($data[$section][$index])) {
        echo json_encode(["status"=>"error","message"=>"index not found"]);
        exit;
    }
    $title = isset($post['title']) ? trim($post['title']) : $data[$section][$index]['title'];
    $description = isset($post['description']) ? trim($post['description']) : $data[$section][$index]['description'];
    $date = isset($post['date']) ? trim($post['date']) : $data[$section][$index]['date'];

    $data[$section][$index] = ["title"=>$title, "description"=>$description, "date"=>$date];
    save_and_json($data, "item edited");
}

if ($action === 'delete') {
    if (!isset($post['index'])) {
        echo json_encode(["status"=>"error","message"=>"missing index for delete"]);
        exit;
    }
    $index = intval($post['index']);
    if (!isset($data[$section][$index])) {
        echo json_encode(["status"=>"error","message"=>"index not found"]);
        exit;
    }
    array_splice($data[$section], $index, 1);
    save_and_json($data, "item deleted");
}

// fallback
echo json_encode(["status"=>"error","message"=>"unknown request"]);
exit;
