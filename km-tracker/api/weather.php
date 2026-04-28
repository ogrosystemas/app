<?php
/**
 * api/weather.php — Previsão do tempo via Open-Meteo
 * Uso: ?lat=-27.6&lng=-48.5&date=2026-05-01
 */
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$lat  = (float)($_GET['lat']  ?? 0);
$lng  = (float)($_GET['lng']  ?? 0);
$date = $_GET['date'] ?? '';

if (!$lat || !$lng || !$date) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

// Verificar se a data está dentro dos próximos 16 dias
$eventTs = strtotime($date);
$today   = strtotime(date('Y-m-d'));
$diff    = ($eventTs - $today) / 86400;

if ($diff < 0 || $diff > 16) {
    echo json_encode(['error' => 'Fora do alcance', 'diff' => $diff]);
    exit;
}

// Buscar previsão do Open-Meteo
$url = "https://api.open-meteo.com/v1/forecast?" . http_build_query([
    'latitude'   => $lat,
    'longitude'  => $lng,
    'daily'      => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_sum,windspeed_10m_max,precipitation_probability_max',
    'timezone'   => 'America/Sao_Paulo',
    'start_date' => $date,
    'end_date'   => $date,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$res  = curl_exec($ch);
$err  = curl_error($ch);
curl_close($ch);

if ($err || !$res) {
    echo json_encode(['error' => 'Erro na requisição: ' . $err]);
    exit;
}

$data = json_decode($res, true);

if (empty($data['daily'])) {
    echo json_encode(['error' => 'Sem dados']);
    exit;
}

$d = $data['daily'];

// WMO Weather codes → emoji + descrição
$weatherCodes = [
    0  => ['☀️',  'Céu limpo'],
    1  => ['🌤️',  'Predominantemente limpo'],
    2  => ['⛅',  'Parcialmente nublado'],
    3  => ['☁️',  'Nublado'],
    45 => ['🌫️',  'Neblina'],
    48 => ['🌫️',  'Neblina com gelo'],
    51 => ['🌦️',  'Chuvisco leve'],
    53 => ['🌦️',  'Chuvisco moderado'],
    55 => ['🌧️',  'Chuvisco intenso'],
    61 => ['🌧️',  'Chuva leve'],
    63 => ['🌧️',  'Chuva moderada'],
    65 => ['🌧️',  'Chuva intensa'],
    71 => ['🌨️',  'Neve leve'],
    73 => ['🌨️',  'Neve moderada'],
    75 => ['🌨️',  'Neve intensa'],
    80 => ['🌦️',  'Pancadas leves'],
    81 => ['🌧️',  'Pancadas moderadas'],
    82 => ['⛈️',  'Pancadas violentas'],
    95 => ['⛈️',  'Tempestade'],
    96 => ['⛈️',  'Tempestade com granizo'],
    99 => ['⛈️',  'Tempestade com granizo intenso'],
];

$code     = (int)($d['weathercode'][0] ?? 0);
$codeInfo = $weatherCodes[$code] ?? ['🌡️', 'Condição ' . $code];

echo json_encode([
    'ok'          => true,
    'date'        => $date,
    'emoji'       => $codeInfo[0],
    'description' => $codeInfo[1],
    'temp_max'    => round($d['temperature_2m_max'][0] ?? 0),
    'temp_min'    => round($d['temperature_2m_min'][0] ?? 0),
    'rain_mm'     => round($d['precipitation_sum'][0] ?? 0, 1),
    'rain_prob'   => (int)($d['precipitation_probability_max'][0] ?? 0),
    'wind_max'    => round($d['windspeed_10m_max'][0] ?? 0),
    'code'        => $code,
]);
