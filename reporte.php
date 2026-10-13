<?php
// Conexión a la base de datos 'tta_repuestos'
$conexion = new mysqli("localhost", "root", "", "tta_repuestos");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Variables del Rango de Fechas
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes por defecto
$fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-d');    // Fecha actual por defecto

$resultados = [];
$total_filas = 0;

if ($fecha_inicio && $fecha_fin) {
    // Consulta agrupada: suma la cantidad de cada producto dentro del rango de fechas
    $sql = "SELECT 
                d.codigo, 
                d.descripcion, 
                SUM(CAST(NULLIF(d.unidades, '') AS DECIMAL(10,2))) as total_cantidad,
                COUNT(DISTINCT b.id) as total_boletas
            FROM detalles_boleta d
            INNER JOIN boletas b ON d.boleta_id = b.id
            WHERE b.fecha BETWEEN ? AND ?
              AND d.descripcion != ''
            GROUP BY d.descripcion, d.codigo
            ORDER BY total_cantidad DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $resultados[] = $row;
    }
    $total_filas = count($resultados);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado por Fechas - TTA</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; padding: 20px; color: #333; }
        .container { max-width: 950px; margin: 0 auto; background: #ffffff; padding: 25px 30px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        
        /* FILTRO DE RANGO DE FECHAS */
        .filter-card { background: #f8f9fa; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; margin-bottom: 25px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; justify-content: space-between; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 13px; font-weight: bold; color: #444; }
        .form-group input { padding: 7px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-box { font-size: 24px; font-weight: bold; background: #222; color: #fff; padding: 8px 12px; border-radius: 4px; }
        .logo-text span { display: block; font-size: 11px; font-weight: bold; text-transform: uppercase; line-height: 1.1; }
        .company-title { text-align: center; }
        .company-title h2 { font-size: 16px; margin-bottom: 4px; text-transform: uppercase; }
        .company-title h3 { font-size: 13px; color: #555; font-weight: normal; }

        /* TABLA DE RESULTADOS */
        .report-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .report-table th { background-color: #343a40; color: #fff; border: 1px solid #212529; padding: 10px; font-size: 13px; text-align: left; }
        .report-table td { border: 1px solid #dee2e6; padding: 8px 10px; font-size: 13px; }
        .report-table tr:nth-child(even) { background-color: #f8f9fa; }
        
        .badge-qty { background: #007bff; color: #fff; font-weight: bold; padding: 4px 10px; border-radius: 12px; font-size: 13px; display: inline-block; }

        .btn { padding: 8px 16px; border: none; border-radius: 4px; font-size: 13px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-search { background: #28a745; color: white; }
        .btn-print { background: #ffc107; color: #212529; }
        .btn-menu { background: #343a40; color: white; }

        @media print {
            body { background: #fff; padding: 0; }
            .filter-card, .btn-action { display: none !important; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Encabezado -->
    <div class="header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="logo-box">TTA</div>
            <div class="logo-text">
                <span>Tecnología</span>
                <span>Textil</span>
                <span>Avanzada</span>
            </div>
        </div>

        <div class="company-title">
            <h2>Consolidado de Productos Solicitados</h2>
            <h3>Reporte por Rango de Fechas</h3>
        </div>

        <div class="btn-action">
            <a href="index.php" class="btn btn-menu">🏠 Menú</a>
        </div>
    </div>

    <!-- Filtro por Rango de Fechas -->
    <div class="filter-card">
        <form method="GET" action="reporte.php" class="filter-form">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="form-group">
                    <label>Fecha Inicial:</label>
                    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                </div>

                <div class="form-group">
                    <label>Fecha Final:</label>
                    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-search">🔍 Generar Reporte</button>
                <button type="button" class="btn btn-print" onclick="window.print();">🖨️ Imprimir</button>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <div style="margin-bottom: 10px; font-size: 13px; color: #555;">
        Mostrando resultados del <strong><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></strong> al <strong><?php echo date('d/m/Y', strtotime($fecha_fin)); ?></strong> (Total de productos distintos: <strong><?php echo $total_filas; ?></strong>)
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 20%;">Código</th>
                <th style="width: 50%;">Descripción del Producto</th>
                <th style="width: 10%; text-align: center;">Solicitudes</th>
                <th style="width: 15%; text-align: center;">Suma Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resultados)): ?>
                <?php $i = 1; foreach ($resultados as $row): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold; color: #666;"><?php echo $i++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['codigo'] ?: 'N/A'); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                        <td style="text-align: center;"><?php echo $row['total_boletas']; ?> boleta(s)</td>
                        <td style="text-align: center;">
                            <span class="badge-qty"><?php echo (float)$row['total_cantidad']; ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #888;">
                        No se encontraron registros de productos en el rango de fechas seleccionado.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>