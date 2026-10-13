<?php
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tta_repuestos");
if ($conexion->connect_error) {
    echo json_encode([]);
    exit;
}

// 1. Búsqueda por coincidencia en la descripción o código (para el menú desplegable)
if (isset($_GET['term'])) {
    $term = "%" . trim($_GET['term']) . "%";
    $stmt = $conexion->prepare("SELECT codigo, descripcion FROM productos WHERE descripcion LIKE ? OR codigo LIKE ? LIMIT 10");
    $stmt->bind_param("ss", $term, $term);
    $stmt->execute();
    $res = $stmt->get_result();

    $productos = [];
    while ($row = $res->fetch_assoc()) {
        $productos[] = [
            'codigo' => $row['codigo'],
            'descripcion' => $row['descripcion']
        ];
    }
    echo json_encode($productos);
    $stmt->close();
    $conexion->close();
    exit;
}

// 2. Búsqueda exacta por código
if (isset($_GET['codigo'])) {
    $codigo = trim($_GET['codigo']);
    $stmt = $conexion->prepare("SELECT descripcion FROM productos WHERE codigo = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        echo json_encode([
            'encontrado' => true,
            'descripcion' => $row['descripcion']
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
    $stmt->close();
    $conexion->close();
    exit;
}

echo json_encode([]);
$conexion->close();
?>