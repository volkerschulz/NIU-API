<?php

if (php_sapi_name() != "cli")
    echo '<pre>';
require_once('niuapi.php');

$username = 'email@example.com';
$password = 'your_password';
$country_code = '49';

echo PHP_EOL . 'Getting token...' . PHP_EOL;
if (file_exists('saved_token.txt')) {
    $token = file_get_contents('saved_token.txt');
    echo 'Token loaded from file: ' . $token . PHP_EOL;
} else {
    $token = NiuApi::get_token($username, $password, $country_code);
    if ($token === false || empty($token)) {
        die('Failed to get token from API!' . PHP_EOL);
    }
    echo 'Got token from API: ' . $token . PHP_EOL;
    file_put_contents('saved_token.txt', $token);
}
NiuApi::$token = $token;

echo PHP_EOL;
echo 'Loading vehicles...' . PHP_EOL;
$vehicles = NiuApi::get_vehicles();
if ($vehicles === false || !is_array($vehicles)) {
    die('Failed to get vehicles from API!' . PHP_EOL);
}
echo count($vehicles) . ' vehicle(s) found:' . PHP_EOL;
$serial_number = null;
foreach ($vehicles as $key => $vehicle) {
    echo "\t{$key}: {$vehicle->type} named {$vehicle->name} w/ serial no {$vehicle->sn}" . PHP_EOL;
    echo "\tThe following properties are available for this vehicle: " . implode(', ', array_keys((array) $vehicle)) . PHP_EOL;
    if ($serial_number === null) {
        $serial_number = $vehicle->sn;
    }
}
if ($serial_number === null) {
    die('Failed to extract serial no from vehicles!' . PHP_EOL);
}

echo PHP_EOL;
echo 'Loading motor info for first listed vehicle...' . PHP_EOL;
NiuApi::$serial_no = $serial_number;
$data = NiuApi::get_motor_info();
echo "\tVehicle is " . ($data->isConnected ? "online" : "offline") . PHP_EOL;
echo "\tLast known position is: {$data->postion->lat}, {$data->postion->lng} at " . date('m/d/Y H:i:s', ($data->gpsTimestamp / 1000)) . PHP_EOL;
echo "\tThe following properties are available for motor info: " . implode(', ', array_keys((array) $data)) . PHP_EOL;

echo PHP_EOL;
echo 'Loading overall tally for first listed vehicle...' . PHP_EOL;
$data = NiuApi::get_overall_tally();
echo "\tVehicle ran {$data->totalMileage} km in {$data->bindDaysCount} days" . PHP_EOL;
echo "\tThe following properties are available for overall tally: " . implode(', ', array_keys((array) $data)) . PHP_EOL;

echo PHP_EOL;
echo 'Loading battery info for first listed vehicle...' . PHP_EOL;
$data = NiuApi::get_battery_info();
echo "\tRemaining est. mileage {$data->estimatedMileage} km; battery is " . ($data->isCharging ? '' : 'not ') . "charging" . PHP_EOL;
echo "\tThe following properties are available for battery info: " . implode(', ', array_keys((array) $data)) . PHP_EOL;

echo PHP_EOL;
echo 'Loading battery health info for first listed vehicle...' . PHP_EOL;
$data = NiuApi::get_battery_health();
if (property_exists($data, 'batteries')) {
    if (property_exists($data->batteries, 'compartmentA'))
        echo "\tBattery A has been charged {$data->batteries->compartmentA->healthRecords[0]->chargeCount} times which results in a grade of {$data->batteries->compartmentA->gradeBattery}%" . PHP_EOL;
    if (property_exists($data->batteries, 'compartmentB'))
        echo "\tBattery B has been charged {$data->batteries->compartmentB->healthRecords[0]->chargeCount} times which results in a grade of {$data->batteries->compartmentB->gradeBattery}%" . PHP_EOL;
}
echo "\tThe following properties are available for battery health: " . implode(', ', array_keys((array) $data)) . PHP_EOL;

echo PHP_EOL;
echo 'Loading latest available tracks (max 10) for first listed vehicle...' . PHP_EOL;
$tracks = NiuApi::get_tracks_available(10, 0);
$track_id = null;
if (is_array($tracks)) {
    echo count($tracks) . ' track(s) found:' . PHP_EOL;
    foreach ($tracks as $key => $track) {
        echo "\t{$key}: Track {$track->trackId} started at " . date('m/d/Y H:i:s', ($track->startTime / 1000)) . " over {$track->distance} meters w/ an avg. speed of {$track->avespeed} km/h; ";
        echo ($track->startPoint->battery - $track->lastPoint->battery) . '% of battery(s) used' . PHP_EOL;
        if ($track_id === null) {
            $track_id = $track->trackId;
            $track_date = $track->date;
        }
        echo "\tThe following properties are available for this track: " . implode(', ', array_keys((array) $track)) . PHP_EOL;
    }
}

if ($track_id !== null && isset($track_date)) {
    echo PHP_EOL;
    echo "Loading track details for first track found ({$track_id})..." . PHP_EOL;
    $data = NiuApi::get_tracks_details($track_id, $track_date);
    if (@is_array($data->trackItems)) {
        echo count($data->trackItems) . ' waypoints found' . PHP_EOL;
        echo "\tThe following properties are available for this track: " . implode(', ', array_keys((array) $data)) . PHP_EOL;
    } else {
        echo "\tFAILED" . PHP_EOL;
    }
}
