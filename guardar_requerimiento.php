<?php
// Conexión a la base de datos 'tta_repuestos'
$conexion = new mysqli("localhost", "root", "", "tta_repuestos");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Captura de datos generales del formulario
$noBoleta      = intval($_POST['noBoleta']);
$lugar         = $_POST['lugar'] ?? 'Guatemala';
$direccion     = $_POST['direccion'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$operador      = $_POST['operador'] ?? '';
$autorizacion  = $_POST['autorizacion'] ?? '';
$origen        = $_POST['origen'] ?? 'boleta_consumibles.php'; // Redirección según origen

// Procesar fecha
if (isset($_POST['fecha']) && !empty($_POST['fecha'])) {
    $fecha = $_POST['fecha'];
} else {
    $dia   = str_pad($_POST['dia'] ?? date('d'), 2, "0", STR_PAD_LEFT);
    $meses = [
        'Enero'=>'01', 'Febrero'=>'02', 'Marzo'=>'03', 'Abril'=>'04',
        'Mayo'=>'05', 'Junio'=>'06', 'Julio'=>'07', 'Agosto'=>'08',
        'Septiembre'=>'09', 'Octubre'=>'10', 'Noviembre'=>'11', 'Diciembre'=>'12'
    ];
    $mes   = $meses[$_POST['mes'] ?? ''] ?? date('m');
    $anio  = $_POST['anio'] ?? date('Y');
    $fecha = "$anio-$mes-$dia";
}

// 1. Guardar o actualizar la Boleta / Requerimiento
$stmtBoleta = $conexion->prepare("INSERT INTO boletas (no_boleta, fecha, lugar, direccion, observaciones, operador, autorizacion) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE fecha=?, lugar=?, direccion=?, observaciones=?, operador=?, autorizacion=?");
$stmtBoleta->bind_param("issssssssssss", $noBoleta, $fecha, $lugar, $direccion, $observaciones, $operador, $autorizacion,
                                        $fecha, $lugar, $direccion, $observaciones, $operador, $autorizacion);
$stmtBoleta->execute();

$boleta_id = $stmtBoleta->insert_id;
if ($boleta_id == 0) {
    $resB = $conexion->query("SELECT id FROM boletas WHERE no_boleta = $noBoleta");
    $boleta_id = $resB->fetch_assoc()['id'];
    
    // Limpiar filas anteriores para sobreescribir
    $conexion->query("DELETE FROM detalles_boleta WHERE boleta_id = $boleta_id");
}
$stmtBoleta->close();

// 2. Procesar filas de productos
$unidades      = $_POST['unidades'] ?? [];
$codigos       = $_POST['codigo'] ?? [];
$descripciones = $_POST['descripcion'] ?? [];

for ($i = 0; $i < count($descripciones); $i++) {
    $cant = trim($unidades[$i] ?? '');
    $cod  = trim($codigos[$i] ?? '');
    $desc = trim($descripciones[$i] ?? '');

    // Ignorar filas totalmente vacías
    if ($cant === '' && $cod === '' && $desc === '') {
        continue;
    }

    // Registro automático de nuevos productos en la tabla 'productos'
    if (!empty($desc)) {
        if (empty($cod)) {
            $cod = "AUT-" . strtoupper(substr(md5($desc), 0, 6));
        }

        $stmtCheck = $conexion->prepare("SELECT codigo FROM productos WHERE codigo = ? OR descripcion = ?");
        $stmtCheck->bind_param("ss", $cod, $desc);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        if ($resCheck->num_rows == 0) {
            $stmtNewProd = $conexion->prepare("INSERT INTO productos (codigo, descripcion) VALUES (?, ?)");
            $stmtNewProd->bind_param("ss", $cod, $desc);
            $stmtNewProd->execute();
            $stmtNewProd->close();
        } else {
            $rowProd = $resCheck->fetch_assoc();
            if (!empty($rowProd['codigo'])) {
                $cod = $rowProd['codigo'];
            }
        }
        $stmtCheck->close();
    }

    // Insertar detalle de la fila
    $stmtDet = $conexion->prepare("INSERT INTO detalles_boleta (boleta_id, unidades, codigo, descripcion) VALUES (?, ?, ?, ?)");
    $stmtDet->bind_param("isss", $boleta_id, $cant, $cod, $desc);
    $stmtDet->execute();
    $stmtDet->close();
}

$conexion->close();

// Redireccionar al archivo de origen correspondiente
header("Location: $origen?no_boleta=$noBoleta&guardado=1");
exit;
?>