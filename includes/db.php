<?php
// Activar todos los errores PHP para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Asegurar que tenemos una conexión PDO válida
function getPDO() {
    global $pdo;
    
    if (!$pdo) {
        try {
            $host = 'localhost';
            $db   = 'sistema_ventas';
            $user = 'root';
            $pass = '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new Exception("Error de conexión: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Función para ejecutar consultas SQL (INSERT, UPDATE, DELETE)
function execute($query, $params = []) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        handleAjaxError($e);
        return false;
    }
}

// Función para obtener un solo registro (alias para compatibilidad)
function fetch($query, $params = []) {
    return fetchOne($query, $params);
}

// Función para obtener un registro de la base de datos
function fetchOne($query, $params = []) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        handleAjaxError($e);
        return false;
    }
}

// Función para obtener todos los registros
function fetchAll($query, $params = []) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        handleAjaxError($e);
        return false;
    }
}

// Función para ejecutar una consulta simple
function query($query, $params = []) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        handleAjaxError($e);
        return false;
    }
}

// Función para manejar errores en peticiones AJAX
function handleAjaxError($e) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    throw $e;
}
