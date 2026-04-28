<?php
ob_start();
ini_set("display_errors","0");
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json');
$alertas = alertasEstoque();
echo json_encode(['count'=>count($alertas),'items'=>$alertas]);
