<?php
// api/map.php - Gerador de mapa GraphHopper
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$origin_lat = (float)($_GET['olat'] ?? 0);
$origin_lng = (float)($_GET['olng'] ?? 0);
$dest_lat = (float)($_GET['dlat'] ?? 0);
$dest_lng = (float)($_GET['dlng'] ?? 0);

if (!$origin_lat || !$origin_lng || !$dest_lat || !$dest_lng) {
    echo '<div style="padding:20px;text-align:center;color:var(--text-dim);">⚠️ Coordenadas inválidas</div>';
    exit;
}

$key = GRAPHOPPER_API_KEY;
$map_url = "https://graphhopper.com/maps/?point={$origin_lat},{$origin_lng}&point={$dest_lat},{$dest_lng}&vehicle=car&locale=pt&key={$key}&layer=OpenStreetMap";

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { width: 100%; height: 100%; overflow: hidden; }
        iframe { width: 100%; height: 100%; border: none; }
        .error { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100%; 
            background:var(--bg-input); 
            color: #f5b041; 
            font-family: monospace;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <iframe src="<?= htmlspecialchars($map_url) ?>" 
            allow="geolocation">
    </iframe>
</body>
</html>