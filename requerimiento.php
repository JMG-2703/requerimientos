<?php
// Conexión a la base de datos 'tta_repuestos'
$conexion = new mysqli("localhost", "root", "", "tta_repuestos");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Variables para búsqueda
$buscando_boleta = isset($_GET['no_boleta']) ? intval($_GET['no_boleta']) : null;
$buscando_fecha  = isset($_GET['fecha_filtro']) ?$_GET['fecha_filtro'] : null;

$boleta_data = null;
$detalles_data = [];

// 1. Buscar por número exacto de documento
if ($buscando_boleta) {
    $stmt =$conexion->prepare("SELECT * FROM boletas WHERE no_boleta = ?");
    $stmt->bind_param("i", $buscando_boleta);$stmt->execute();
    $res_boleta =$stmt->get_result();

    if ($res_boleta->num_rows > 0) {
        $boleta_data =$res_boleta->fetch_assoc();
        
        $stmt_det =$conexion->prepare("SELECT * FROM detalles_boleta WHERE boleta_id = ?");
        $stmt_det->bind_param("i", $boleta_data['id']);$stmt_det->execute();
        $res_det =$stmt_det->get_result();
        
        while ($row_det =$res_det->fetch_assoc()) {
            $detalles_data[] =$row_det;
        }
        $stmt_det->close();
    }
    $stmt->close();
}

// Configuración de valores por defecto
if ($boleta_data) {
    $siguiente_boleta  =$boleta_data['no_boleta'];
    $operador_val      =$boleta_data['operador']; // Persona quien solicita
    $autorizacion_val  =$boleta_data['autorizacion'];
    $observaciones_val =$boleta_data['observaciones']; // Para: Texturizado / notas
    $fecha_val         =$boleta_data['fecha'];
} else {
    $sql_ultimo = "SELECT MAX(no_boleta) as ultimo FROM boletas";
    $resultado  =$conexion->query($sql_ultimo);$fila       = $resultado->fetch_assoc();$ultimo     = $fila['ultimo'] ? intval($fila['ultimo']) : 0;
    
    $siguiente_boleta = ($ultimo >= 250500) ?$ultimo + 1 : 250506;
    $operador_val     = "";
    $autorizacion_val = "";
    $observaciones_val = "";
    $fecha_val        = date('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requerimiento de Compra - TTA</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; background: #ffffff; padding: 25px 30px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .search-bar-container { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #ddd; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between; }
        .search-group { display: flex; gap: 8px; align-items: center; }
        .search-group label { font-weight: bold; font-size: 13px; }
        
        /* HEADER FIEL AL FORMATO FÍSICO */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 2px solid #000; }
        .header-table td, .header-table th { border: 1px solid #000; padding: 6px 10px; font-size: 13px; }
        .header-title { font-size: 18px; font-weight: bold; text-align: center; }
        .logo-box { font-size: 24px; font-weight: 900; background: #222; color: #fff; padding: 6px 10px; border-radius: 4px; display: inline-block; }
        
        .form-input-clean { width: 100%; border: none; outline: none; font-size: 13px; background: transparent; }

        /* TABLA PRINCIPAL DE PRODUCTOS */
        #reqTable { width: 100%; border-collapse: collapse; margin: 15px 0; border: 2px solid #000; }
        #reqTable th { background-color: #f1f3f5; color: #000; border: 1px solid #000; padding: 8px; font-size: 11px; text-transform: uppercase; text-align: center; }
        #reqTable td { border: 1px solid #000; padding: 3px; vertical-align: middle; }
        #reqTable td input { width: 100%; padding: 5px; border: none; outline: none; font-size: 12px; }

        .btn-delete-row { background: transparent; border: none; color: #dc3545; font-size: 16px; cursor: pointer; display: block; margin: 0 auto; }
        
        /* FIRMAS Y PIE */
        .signatures-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 30px; text-align: center; font-size: 12px; font-weight: bold; }
        .signature-line { border-top: 1px solid #000; padding-top: 5px; margin-top: 40px; }

        .actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; font-size: 13px; cursor: pointer; font-weight: bold; }
        .btn-add { background: #17a2b8; color: white; }
        .btn-reset { background: #6c757d; color: white; }
        .btn-print { background: #ffc107; color: #212529; }
        .btn-save { background: #28a745; color: white; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .search-bar-container, .actions, .col-action, .btn-delete-row { display: none !important; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Barra superior de navegación -->
    <div class="search-bar-container">
        <div class="search-group">
            <label>No. Documento:</label>
            <input type="text" id="inputBuscarNo" placeholder="Ej. 250506-02" style="padding: 5px; width: 120px;">
            <button type="button" onclick="buscarPorNumero()" class="btn" style="background: #007bff; color: white;">Ir</button>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="index.php" style="background: #343a40; color: white; padding: 7px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;">🏠 Menú</a>
            <a href="requerimiento.php" style="background: #6c757d; color: white; padding: 7px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;">+ Nuevo Requerimiento</a>
        </div>
    </div>

    <?php if (isset($_GET['guardado'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb;">
            <strong>¡Éxito!</strong> Requerimiento guardado correctamente.
        </div>
    <?php endif; ?>

    <form id="solicitudForm" action="guardar_requerimiento.php" method="POST">
        <input type="hidden" name="origen" value="requerimiento.php">

        <!-- Encabezado igual al papel físico -->
        <table class="header-table">
            <tr>
                <td style="width: 20%; text-align: center;">
                    <div class="logo-box">TTA</div>
                </td>
                <td style="width: 50%;" class="header-title">REQUERIMIENTO DE COMPRA</td>
                <td style="width: 30%;">
                    <strong>No. DOCUMENTO:</strong><br>
                    <input type="text" name="noBoleta" value="<?php echo htmlspecialchars($siguiente_boleta); ?>" class="form-input-clean" style="font-weight: bold; font-size: 14px; color: #c00;" required>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <strong>PERSONA QUIEN SOLICITA:</strong>
                    <input type="text" name="operador" value="<?php echo htmlspecialchars($operador_val); ?>" class="form-input-clean" placeholder="Nombre del solicitante" required>
                </td>
                <td>
                    <strong>FECHA SOLICITUD:</strong>
                    <input type="date" name="fecha" value="<?php echo htmlspecialchars($fecha_val); ?>" class="form-input-clean" required>
                </td>
            </tr>
        </table>

        <!-- Tabla con las 5 columnas exactas de la foto -->
        <table id="reqTable">
            <thead>
                <tr>
                    <th style="width: 10%;">CANTIDAD</th>
                    <th style="width: 45%;">DESCRIPCIÓN</th>
                    <th style="width: 13%;">UNIDAD MEDIDA</th>
                    <th style="width: 14%;">ÁREA DE USO</th>
                    <th style="width: 14%;">PROVEEDOR SUGERIDO</th>
                    <th class="col-action" style="width: 4%;">🗑️</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($detalles_data)): ?>
                    <?php foreach ($detalles_data as$detalle): ?>
                        <tr>
                            <td><input type="text" name="unidades[]" value="<?php echo htmlspecialchars($detalle['unidades']); ?>" style="text-align: center;"></td>
                            <td><input type="text" name="descripcion[]" list="listaDescripciones" value="<?php echo htmlspecialchars($detalle['descripcion']); ?>" autocomplete="off"></td>
                            <td><input type="text" name="unidad_medida[]" value="<?php echo htmlspecialchars($detalle['unidad_medida'] ?? ''); ?>" placeholder="unidad/mts"></td>
                            <td><input type="text" name="area_uso[]" value="<?php echo htmlspecialchars($detalle['area_uso'] ?? ''); ?>" placeholder="Texturizado"></td>
                            <td><input type="text" name="proveedor[]" value="<?php echo htmlspecialchars($detalle['proveedor'] ?? ''); ?>"></td>
                            <td class="col-action"><button type="button" class="btn-delete-row">🗑️</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for($i=0; $i<6; $i++): ?>
                        <tr>
                            <td><input type="text" name="unidades[]" style="text-align: center;"></td>
                            <td><input type="text" name="descripcion[]" list="listaDescripciones" autocomplete="off"></td>
                            <td><input type="text" name="unidad_medida[]" placeholder="ej. unidad/mts"></td>
                            <td><input type="text" name="area_uso[]" placeholder="ej. Texturizado"></td>
                            <td><input type="text" name="proveedor[]"></td>
                            <td class="col-action"><button type="button" class="btn-delete-row">🗑️</button></td>
                        </tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Observaciones / PARA: -->
        <div style="margin-top: 10px;">
            <label style="font-weight: bold; font-size: 13px;">PARA / OBSERVACIONES:</label>
            <input type="text" name="observaciones" value="<?php echo htmlspecialchars($observaciones_val); ?>" class="form-input-clean" style="border-bottom: 1px solid #000; padding: 4px;" placeholder="Ej. PARA: Texturizado">
        </div>

        <!-- Sección de firmas -->
        <div class="signatures-grid">
            <div>
                <div class="signature-line">Firma Solicitante</div>
            </div>
            <div>
                <div class="signature-line">Firma Autorización</div>
            </div>
            <div>
                <div class="signature-line">F. RECIBIDO</div>
            </div>
        </div>

        <div class="actions">
            <button type="button" class="btn btn-add" id="btnAddRow">+ Agregar Fila</button>
            <button type="reset" class="btn btn-reset">Limpiar</button>
            <button type="button" class="btn btn-print" onclick="window.print();">Imprimir</button>
            <button type="submit" class="btn btn-save">Guardar</button>
        </div>
    </form>
</div>

<datalist id="listaDescripciones"></datalist>

<script>
    function buscarPorNumero() {
        const num = document.getElementById('inputBuscarNo').value;
        if (num) {
            window.location.href = 'requerimiento.php?no_boleta=' + encodeURIComponent(num);
        }
    }

    // Agregar Fila
    document.getElementById('btnAddRow').addEventListener('click', function() {
        const tableBody = document.querySelector('#reqTable tbody');
        const newRow = document.createElement('tr');
        
        newRow.innerHTML = `
            <td><input type="text" name="unidades[]" style="text-align: center;"></td>
            <td><input type="text" name="descripcion[]" list="listaDescripciones" autocomplete="off"></td>
            <td><input type="text" name="unidad_medida[]" placeholder="ej. unidad/mts"></td>
            <td><input type="text" name="area_uso[]" placeholder="ej. Texturizado"></td>
            <td><input type="text" name="proveedor[]"></td>
            <td class="col-action"><button type="button" class="btn-delete-row">🗑️</button></td>
        `;
        
        tableBody.appendChild(newRow);
    });

    // Eliminar fila
    document.getElementById('reqTable').addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('btn-delete-row')) {
            const row = event.target.closest('tr');
            const tbody = row.parentElement;
            if (tbody.querySelectorAll('tr').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(input => input.value = '');
            }
        }
    });

    // Búsqueda AJAX de productos al escribir en Descripción
    document.getElementById('reqTable').addEventListener('input', async function(event) {
        if (event.target && event.target.name === 'descripcion[]') {
            const inputDesc = event.target;
            const texto = inputDesc.value.trim();

            if (texto.length >= 1) {
                try {
                    const response = await fetch('./buscar_producto.php?term=' + encodeURIComponent(texto));
                    const productos = await response.json();

                    const dataList = document.getElementById('listaDescripciones');
                    dataList.innerHTML = '';

                    productos.forEach(prod => {
                        const option = document.createElement('option');
                        option.value = prod.descripcion;
                        dataList.appendChild(option);
                    });
                } catch (error) {
                    console.error('Error buscando productos:', error);
                }
            }
        }
    });
</script>
</body>
</html>