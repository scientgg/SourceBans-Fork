<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/config.php';

// Read raw POST data
$values = [];
$raw = file_get_contents('php://input');
mb_parse_str($raw, $values);

if (!isset($values['identkey']) || !isset($values['command'])) {
    echo http_build_query(['status' => 'invalid_inputparameters']);
    return;
}

if ($values['identkey'] !== $identkey) {
    echo http_build_query(['status' => 'invalid_identkey']);
    return;
}

if ($identkey === 'ChangeMe') {
    echo http_build_query(['status' => 'change_default_identkey']);
    return;
}

if ($values['command'] === 'HELO') {
    if (empty($values['serverport']) || empty($values['gamename']) || empty($values['gamedir']) || empty($values['rcon'])) {
        echo http_build_query(['status' => 'invalid_command']);
    } else {
        echo http_build_query(['status' => 'okay', 'options' => '0']);
    }
    return;
}

if ($values['command'] !== 'submitshot' || empty($values['serverport']) || empty($values['data'])) {
    echo http_build_query(['status' => 'invalid_command']);
    return;
}

$img = base64_decode(strtr($values['data'], '-_#', '+/='), true);
if ($img === false) {
    echo http_build_query(['status' => 'failure']);
    return;
}

$playerid = isset($values['playerid']) ? preg_replace('/[^0-9]/', '', $values['playerid']) : '0';
$filename = 'ss_' . $playerid . '_' . time() . '_' . mt_rand(1000,9999) . '.jpg';
$filePath = SB_DEMOS . '/' . $filename;
if (file_put_contents($filePath, $img) === false) {
    echo http_build_query(['status' => 'failure']);
    return;
}

echo http_build_query(['status' => 'success']);

?>
