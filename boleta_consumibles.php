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
$detalles_data = [];$lista_por_fecha = [];

// 1. Buscar por número exacto de boleta
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

// 2. Buscar por fecha
if ($buscando_fecha) {
    $stmt_f =$conexion->prepare("SELECT * FROM boletas WHERE fecha = ? ORDER BY no_boleta DESC");
    $stmt_f->bind_param("s", $buscando_fecha);$stmt_f->execute();
    $res_f =$stmt_f->get_result();
    
    while ($row_f =$res_f->fetch_assoc()) {
        $lista_por_fecha[] =$row_f;
    }
    $stmt_f->close();
}

// Configuración de valores
if ($boleta_data) {
    $siguiente_boleta  =$boleta_data['no_boleta'];
    $lugar_val         =$boleta_data['lugar'];
    $direccion_val     =$boleta_data['direccion'];
    $observaciones_val =$boleta_data['observaciones'];
    $operador_val      =$boleta_data['operador'];
    $autorizacion_val  =$boleta_data['autorizacion'];

    $partes_fecha = explode('-',$boleta_data['fecha']);
    $anio_actual  =$partes_fecha[0];
    $mes_num      =$partes_fecha[1];
    $dia_actual   =$partes_fecha[2];

    $meses_inv = [         '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',         '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',         '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'     ];$mes_actual = $meses_inv[$mes_num] ?? 'Enero';
} else {
    $sql_ultimo = "SELECT MAX(no_boleta) as ultimo FROM boletas";
    $resultado  =$conexion->query($sql_ultimo);$fila       = $resultado->fetch_assoc();$ultimo     = $fila['ultimo'] ? intval($fila['ultimo']) : 0;
    
    $siguiente_boleta = ($ultimo >= 4050) ?$ultimo + 1 : 4050;

    $lugar_val        = "Guatemala";
    $direccion_val    = "Km 21.5 Antigua Carretera a Amatitlán";
    $observaciones_val = "";
    $operador_val     = "";
    $autorizacion_val  = "";
    $dia_actual       = date('d');
    $meses = [         '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',         '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',         '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'     ];$mes_actual  = $meses[date('m')];$anio_actual = date('Y');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Consumibles - TTA</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; padding: 20px; color: #333; }
        .container { max-width: 950px; margin: 0 auto; background: #ffffff; padding: 25px 30px; border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .search-bar-container { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #ddd; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between; }
        .search-group { display: flex; gap: 8px; align-items: center; }
        .search-group label { font-weight: bold; font-size: 13px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-section { display: flex; align-items: center; gap: 10px; }
        .logo-box { font-size: 24px; font-weight: bold; background: #222; color: #fff; padding: 8px 12px; border-radius: 4px; }
        .logo-text span { display: block; font-size: 11px; font-weight: bold; text-transform: uppercase; line-height: 1.1; }
        .company-title { text-align: center; }
        .company-title h2 { font-size: 16px; margin-bottom: 4px; text-transform: uppercase; }
        .company-title h3 { font-size: 14px; color: #555; font-weight: normal; }
        .ticket-box { border: 1px solid #333; padding: 8px 12px; text-align: center; border-radius: 4px; background: #fafafa; }
        .ticket-box .title { font-size: 12px; font-weight: bold; margin-bottom: 4px; }
        .ticket-box input { width: 80px; text-align: center; font-weight: bold; font-size: 14px; border: 1px solid #ccc; padding: 3px; }
        .form-row { display: flex; gap: 15px; align-items: center; margin-bottom: 12px; }
        .form-group { display: flex; align-items: center; gap: 8px; }
        .form-group.flex-grow { flex-grow: 1; }
        .form-group.width-full { width: 100%; }
        .form-group label { font-weight: bold; font-size: 13px; white-space: nowrap; }
        .form-group input[type="text"], .form-group input[type="number"] { width: 100%; padding: 6px 8px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .date-inputs { display: flex; align-items: center; gap: 6px; }
        .date-inputs input[type="number"] { width: 60px; }
        .date-inputs input[type="text"] { width: 110px; }
        #itemsTable { width: 100%; border-collapse: collapse; margin: 20px 0; }
        #itemsTable th { background-color: #f1f3f5; color: #333; border: 1px solid #ccc; padding: 8px; font-size: 13px; text-align: left; }
        #itemsTable td { border: 1px solid #ccc; padding: 4px; vertical-align: middle; }
        #itemsTable td input { width: 100%; padding: 6px; border: none; outline: none; font-size: 13px; box-sizing: border-box; }
        #itemsTable td input:focus { background-color: #eef5ff; }
        .btn-delete-row { background: transparent; border: none; color: #dc3545; font-size: 16px; cursor: pointer; padding: 4px 8px; border-radius: 3px; display: block; margin: 0 auto; }
        .btn-delete-row:hover { background: #f8d7da; color: #842029; }
        .footer-section { display: flex; flex-direction: column; gap: 10px; margin-top: 15px; }
        .field-line { display: flex; align-items: center; gap: 10px; }
        .field-line label { width: 100px; font-weight: bold; font-size: 13px; }
        .field-line input { flex: 1; padding: 6px 8px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; font-size: 13px; cursor: pointer; font-weight: bold; }
        .btn-add { background: #17a2b8; color: white; }
        .btn-reset { background: #6c757d; color: white; }
        .btn-print { background: #ffc107; color: #212529; }
        .btn-save { background: #28a745; color: white; }
        .btn:hover { opacity: 0.9; }
        @media print {
            body { background: #fff; padding: 0; }
            .search-bar-container, .actions, .results-box, .col-action, .btn-delete-row { display: none !important; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Barra superior de navegación y búsqueda -->
    <div class="search-bar-container">
        <div class="search-group">
            <label>No. Boleta:</label>
            <input type="number" id="inputBuscarNo" placeholder="Ej. 4050" style="padding: 5px; width: 90px;">
            <button type="button" onclick="buscarPorNumero()" class="btn" style="background: #007bff; color: white;">Ir</button>
        </div>

        <div class="search-group">
            <label>Fecha:</label>
            <input type="date" id="inputBuscarFecha" value="<?php echo htmlspecialchars($buscando_fecha ?? ''); ?>" style="padding: 5px;">
            <button type="button" onclick="buscarPorFecha()" class="btn" style="background: #28a745; color: white;">Buscar por Fecha</button>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="index.php" style="background: #343a40; color: white; padding: 7px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;">🏠 Menú</a>
            <a href="boleta_consumibles.php" style="background: #6c757d; color: white; padding: 7px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;">+ Nueva Boleta</a>
        </div>
    </div>

    <?php if (isset($_GET['guardado'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb;">
            <strong>¡Éxito!</strong> La boleta No. <?php echo htmlspecialchars($_GET['no_boleta'] ?? ''); ?> fue guardada correctamente.
        </div>
    <?php endif; ?>

    <form id="solicitudForm" action="guardar_requerimiento.php" method="POST">
        <!-- Campo oculto para asegurar la redirección a este mismo archivo -->
        <input type="hidden" name="origen" value="boleta_consumibles.php">

        <div class="header">
            <div class="logo-section">
                <div class="logo-box">TTA</div>
                <div class="logo-text">
                    <span>Tecnología</span>
                    <span>Textil</span>
                    <span>Avanzada</span>
                </div>
            </div>

            <div class="company-title">
                <h2>Control de Solicitud de Consumibles, Materia Prima y Repuestos.</h2>
                <h3>Tecnología Textil Avanzada, S.A.</h3>
            </div>

            <div class="ticket-box">
                <div class="title">Boleta de Solicitud</div>
                <div class="number">
                    <input type="text" id="noBoleta" name="noBoleta" value="<?php echo $siguiente_boleta; ?>" readonly required>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group flex-grow">
                <label>Lugar:</label>
                <input type="text" id="lugar" name="lugar" value="<?php echo htmlspecialchars($lugar_val); ?>">
            </div>

            <div class="form-group date-inputs">
                <input type="number" id="dia" name="dia" value="<?php echo $dia_actual; ?>" placeholder="Día" required>
                <label>de</label>
                <input type="text" id="mes" name="mes" value="<?php echo htmlspecialchars($mes_actual); ?>" placeholder="Mes" required>
                <label>de</label>
                <input type="number" id="anio" name="anio" value="<?php echo $anio_actual; ?>" placeholder="Año" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group width-full">
                <label>Dirección:</label>
                <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($direccion_val); ?>">
            </div>
        </div>

        <!-- Tabla de ítems -->
        <table id="itemsTable">
            <thead>
                <tr>
                    <th style="width: 15%;">Unidades</th>
                    <th style="width: 25%;">Código</th>
                    <th style="width: 55%;">Descripción del producto</th>
                    <th class="col-action" style="width: 5%; text-align: center;">🗑️</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($detalles_data)): ?>
                    <?php foreach ($detalles_data as$detalle): ?>
                        <tr>
                            <td><input type="text" name="unidades[]" value="<?php echo htmlspecialchars($detalle['unidades']); ?>"></td>
                            <td><input type="text" name="codigo[]" value="<?php echo htmlspecialchars($detalle['codigo'] ?? ''); ?>"></td>
                            <td><input type="text" name="descripcion[]" list="listaDescripciones" value="<?php echo htmlspecialchars($detalle['descripcion']); ?>" autocomplete="off"></td>
                            <td class="col-action" style="text-align: center;">
                                <button type="button" class="btn-delete-row" title="Eliminar fila">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for($i=0; $i<5; $i++): ?>
                        <tr>
                            <td><input type="text" name="unidades[]"></td>
                            <td><input type="text" name="codigo[]"></td>
                            <td><input type="text" name="descripcion[]" list="listaDescripciones" autocomplete="off"></td>
                            <td class="col-action" style="text-align: center;">
                                <button type="button" class="btn-delete-row" title="Eliminar fila">🗑️</button>
                            </td>
                        </tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer-section">
            <div class="field-line">
                <label>Observaciones:</label>
                <input type="text" id="observaciones" name="observaciones" value="<?php echo htmlspecialchars($observaciones_val); ?>">
            </div>
            <div class="field-line">
                <label>Operador:</label>
                <input type="text" id="operador" name="operador" value="<?php echo htmlspecialchars($operador_val); ?>">
            </div>
            <div class="field-line">
                <label>Autorización:</label>
                <input type="text" id="autorizacion" name="autorizacion" value="<?php echo htmlspecialchars($autorizacion_val); ?>">
            </div>
        </div>

        <div class="actions">
            <button type="button" class="btn btn-add" id="btnAddRow">+ Agregar Fila</button>
            <button type="reset" class="btn btn-reset" id="btnReset">Limpiar</button>
            <button type="button" class="btn btn-print" id="btnPrint" onclick="window.print();">Imprimir</button>
            <button type="submit" class="btn btn-save" id="btn-save">Guardar</button>
        </div>
    </form>
</div>

<datalist id="listaDescripciones"></datalist>

<script>
    function buscarPorNumero() {
        const numBoleta = document.getElementById('inputBuscarNo').value;
        if (numBoleta) {
            window.location.href = 'boleta_consumibles.php?no_boleta=' + numBoleta;
        }
    }

    function buscarPorFecha() {
        const fecha = document.getElementById('inputBuscarFecha').value;
        if (fecha) {
            window.location.href = 'boleta_consumibles.php?fecha_filtro=' + fecha;
        }
    }

    document.getElementById('btnAddRow').addEventListener('click', function() {
        const tableBody = document.querySelector('#itemsTable tbody');
        const newRow = document.createElement('tr');
        
        newRow.innerHTML = `
            <td><input type="text" name="unidades[]"></td>
            <td><input type="text" name="codigo[]"></td>
            <td><input type="text" name="descripcion[]" list="listaDescripciones" autocomplete="off"></td>
            <td class="col-action" style="text-align: center;">
                <button type="button" class="btn-delete-row" title="Eliminar fila">🗑️</button>
            </td>
        `;
        
        tableBody.appendChild(newRow);
    });

    document.getElementById('itemsTable').addEventListener('click', function(event) {
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

    document.getElementById('itemsTable').addEventListener('input', async function(event) {
        if (event.target && event.target.name === 'descripcion[]') {
            const inputDesc = event.target;
            const row = inputDesc.closest('tr');
            const inputCodigo = row.querySelector('input[name="codigo[]"]');
            const texto = inputDesc.value.trim();

            const match = texto.match(/^\[(.*?)\]\s*(.*)$/);

            if (match) {
                inputCodigo.value = match[1];
                inputDesc.value = match[2];
                return;
            }

            if (texto.length >= 1) {
                try {
                    const response = await fetch('./buscar_producto.php?term=' + encodeURIComponent(texto));
                    const productos = await response.json();

                    const dataList = document.getElementById('listaDescripciones');
                    dataList.innerHTML = '';

                    productos.forEach(prod => {
                        const option = document.createElement('option');
                        option.value = `[${prod.codigo}] ${prod.descripcion}`;
                        dataList.appendChild(option);
                    });
                } catch (error) {
                    console.error('Error al cargar sugerencias:', error);
                }
            }
        }
    });

    document.getElementById('itemsTable').addEventListener('change', async function(event) {
        if (event.target && event.target.name === 'codigo[]') {
            const inputCodigo = event.target;
            const row = inputCodigo.closest('tr');
            const inputDesc = row.querySelector('input[name="descripcion[]"]');
            const codigo = inputCodigo.value.trim();

            if (codigo !== '') {
                try {
                    const response = await fetch('./buscar_producto.php?codigo=' + encodeURIComponent(codigo));
                    const data = await response.json();

                    if (data.encontrado) {
                        inputDesc.value = data.descripcion;
                    }
                } catch (error) {
                    console.error('Error buscando por código:', error);
                }
            }
        }
    });
</script>
</body>
</html>