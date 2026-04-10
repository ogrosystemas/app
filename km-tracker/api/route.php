<?php
/**
 * api/route.php - API de rotas com GraphHopper + Cache
 */

ob_start();

// Bootstrap
$bootstrapPaths = [
    __DIR__ . '/../includes/bootstrap.php',
    __DIR__ . '/includes/bootstrap.php',
    __DIR__ . '/bootstrap.php'
];

$bootstrapLoaded = false;
foreach ($bootstrapPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $bootstrapLoaded = true;
        break;
    }
}

if (!$bootstrapLoaded) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Bootstrap não encontrado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar chave GraphHopper
if (!defined('GRAPHOPPER_API_KEY') || empty(GRAPHOPPER_API_KEY)) {
    echo json_encode(['error' => 'Chave da API GraphHopper não configurada. Registre em https://www.graphhopper.com/']);
    exit;
}

// Criar pasta de cache
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Limpar cache antigo (mais de 30 dias)
if (rand(1, 100) === 1) {
    $files = glob($cacheDir . '/*.json');
    foreach ($files as $file) {
        if (filemtime($file) < time() - (30 * 86400)) {
            unlink($file);
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - Autocomplete
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'autocomplete') {
    $query = trim($_GET['q'] ?? '');
    
    if (strlen($query) < 3) {
        echo json_encode([]);
        exit;
    }
    
    $cacheFile = $cacheDir . '/autocomplete_' . md5($query) . '.json';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
        echo file_get_contents($cacheFile);
        exit;
    }
    
    // Modificado para buscar apenas cidades e estados
    $url = "https://nominatim.openstreetmap.org/search"
         . "?q=" . urlencode($query)
         . "&format=json"
         . "&addressdetails=1"
         . "&limit=6"
         . "&countrycodes=br"
         . "&accept-language=pt-BR"
         . "&featuretype=city"  // Limita a cidades
         . "&class=place";       // Apenas lugares
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MutantesKMTracker/2.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        $results = [];
        foreach ($data as $item) {
            // Extrair apenas cidade e estado
            $address = $item['address'] ?? [];
            $cidade = $address['city'] ?? $address['town'] ?? $address['municipality'] ?? '';
            $estado = $address['state'] ?? '';
            
            // Formatar como "Cidade, UF"
            $label = $cidade;
            if ($estado) {
                $label .= ', ' . $estado;
            }
            
            // Se não encontrou cidade, usa o nome do lugar
            if (empty($label) || $label == ', ') {
                $label = $item['display_name'] ?? '';
                // Simplifica ainda mais
                $label = preg_replace('/, Brasil$/', '', $label);
                $label = explode(',', $label)[0];
            }
            
            $results[] = [
                'label' => $label,
                'lat' => (float)$item['lat'],
                'lon' => (float)$item['lon']
            ];
        }
        
        // Remover duplicatas (mesma cidade)
        $unique = [];
        foreach ($results as $r) {
            $key = $r['label'];
            if (!isset($unique[$key])) {
                $unique[$key] = $r;
            }
        }
        $results = array_values($unique);
        
        $output = json_encode($results);
        file_put_contents($cacheFile, $output);
        echo $output;
    } else {
        echo json_encode([]);
    }
    exit;
}

// POST - Calcular rota com GraphHopper
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'calculate_route') {
        $origin = trim($input['origin'] ?? '');
        $destination = trim($input['destination'] ?? '');
        $waypoints = $input['waypoints'] ?? [];
        
        if (empty($origin) || empty($destination)) {
            echo json_encode(['error' => 'Informe origem e destino']);
            exit;
        }
        
        // Criar chave única para cache
        $cacheKey = md5($origin . '|' . $destination . '|' . implode('|', $waypoints));
        $cacheFile = $cacheDir . '/route_' . $cacheKey . '.json';
        
        // Cache por 30 dias
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < (30 * 86400)) {
            echo file_get_contents($cacheFile);
            exit;
        }
        
        // Geocodificar origem
        $oriCoords = geocodeAddress($origin);
        if (!$oriCoords) {
            echo json_encode(['error' => "Origem não encontrada: " . htmlspecialchars($origin)]);
            exit;
        }
        
        // Geocodificar destino
        $dstCoords = geocodeAddress($destination);
        if (!$dstCoords) {
            echo json_encode(['error' => "Destino não encontrado: " . htmlspecialchars($destination)]);
            exit;
        }
        
        // Construir URL da GraphHopper
        $url = "https://graphhopper.com/api/1/route"
             . "?point=" . $oriCoords['lat'] . "," . $oriCoords['lon']
             . "&point=" . $dstCoords['lat'] . "," . $dstCoords['lon'];
        
        // Adicionar waypoints
        $waypointsCoords = [];
        foreach ($waypoints as $wp) {
            $wp = trim($wp);
            if (!empty($wp)) {
                $coords = geocodeAddress($wp);
                if ($coords) {
                    $waypointsCoords[] = $coords;
                    $url .= "&point=" . $coords['lat'] . "," . $coords['lon'];
                }
            }
        }
        
        $url .= "&vehicle=car"
              . "&locale=pt"
              . "&points_encoded=false"
              . "&elevation=false"
              . "&key=" . GRAPHOPPER_API_KEY;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo json_encode(['error' => 'Erro na conexão: ' . $curlError]);
            exit;
        }
        
        if ($httpCode !== 200) {
            $errorMsg = 'Erro ao calcular rota';
            if ($httpCode === 429) {
                $errorMsg = 'Limite de requisições excedido. Tente novamente mais tarde.';
            } elseif ($httpCode === 401) {
                $errorMsg = 'Chave da API GraphHopper inválida.';
            }
            echo json_encode(['error' => $errorMsg]);
            exit;
        }
        
        $data = json_decode($response, true);
        
        if (empty($data['paths'])) {
            echo json_encode(['error' => 'Rota não encontrada']);
            exit;
        }
        
        $path = $data['paths'][0];
        $distanceMeters = $path['distance'] ?? 0;
        $distanceKm = round($distanceMeters / 1000, 1);
        
        $path = $data['paths'][0];
$distanceMeters = $path['distance'] ?? 0;
$distanceKm = round($distanceMeters / 1000, 1);

// GraphHopper retorna time em MILISSEGUNDOS
// Teste: se for 60km, deve retornar ~3.600.000 ms
$durationRaw = $path['time'] ?? 0;

// Se o valor for muito alto (> 1 milhão), está em ms, senão em segundos
if ($durationRaw > 1000000) {
    $timeMinutes = round($durationRaw / 60000);
} else {
    $timeMinutes = round($durationRaw / 60);
}

// Limitar a um valor razoável (max 24h = 1440 minutos)
if ($timeMinutes > 1440 && $distanceKm < 200) {
    // Se deu mais de 24h para menos de 200km, algo está errado
    $timeMinutes = round($distanceKm / 60 * 60); // estimativa: 60km/h
}
        
        // Extrair pontos da rota
        $routePoints = [];
        if (isset($path['points']['coordinates'])) {
            foreach ($path['points']['coordinates'] as $coord) {
                $routePoints[] = [$coord[1], $coord[0]];
            }
        }
        
        $result = [
            'ok' => true,
            'km' => $distanceKm,
            'duration_min' => $timeMinutes,
            'duration_text' => formatDuration($timeMinutes),
            'origin' => [
                'lat' => $oriCoords['lat'],
                'lon' => $oriCoords['lon'],
                'label' => $oriCoords['label']
            ],
            'destination' => [
                'lat' => $dstCoords['lat'],
                'lon' => $dstCoords['lon'],
                'label' => $dstCoords['label']
            ],
            'waypoints' => $waypointsCoords,
            'points' => $routePoints
        ];
        
        file_put_contents($cacheFile, json_encode($result));
        echo json_encode($result);
        exit;
    }
    
    echo json_encode(['error' => 'Ação inválida']);
    exit;
}

function geocodeAddress($address) {
    $address = trim($address);
    if (empty($address)) return null;
    
    if (preg_match('/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/', $address, $matches)) {
        return [
            'lat' => (float)$matches[1],
            'lon' => (float)$matches[2],
            'label' => $address,
            'text' => $address
        ];
    }
    
    global $cacheDir;
    $cacheFile = $cacheDir . '/geocode_' . md5($address) . '.json';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < (30 * 86400)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }
    
    $url = "https://nominatim.openstreetmap.org/search"
         . "?q=" . urlencode($address)
         . "&format=json"
         . "&limit=1"
         . "&addressdetails=1"
         . "&countrycodes=br"
         . "&accept-language=pt-BR";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MutantesKMTracker/2.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) return null;
    
    $data = json_decode($response, true);
    if (empty($data)) return null;
    
    $item = $data[0];
    $result = [
        'lat' => (float)$item['lat'],
        'lon' => (float)$item['lon'],
        'label' => $item['display_name'] ?? $address,
        'text' => $address
    ];
    
    file_put_contents($cacheFile, json_encode($result));
    
    return $result;
}

function formatDuration($minutes) {
    if ($minutes < 60) return $minutes . ' min';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $mins == 0 ? $hours . ' h' : $hours . ' h ' . $mins . ' min';
}
?>