<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal - Sistema TTA</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .menu-container {
            background: #ffffff;
            width: 100%;
            max-width: 850px;
            padding: 40px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .logo-header {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .logo-box {
            font-size: 28px;
            font-weight: 900;
            background: #222;
            color: #fff;
            padding: 10px 16px;
            border-radius: 5px;
            letter-spacing: 1px;
        }

        .logo-text span {
            display: block;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
            text-align: left;
        }

        h1 {
            font-size: 20px;
            color: #333;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        p.subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 35px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 25px 20px;
            text-decoration: none;
            color: #333;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .menu-card:hover {
            border-color: #007bff;
            transform: translateY(-4px);
            box-shadow: 0 6px 18px rgba(0, 123, 255, 0.15);
        }

        .card-icon {
            font-size: 38px;
            margin-bottom: 12px;
        }

        .card-title {
            font-size: 15px;
            font-weight: bold;
            color: #111;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .card-desc {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }
    </style>
</head>
<body>

<div class="menu-container">
    <div class="logo-header">
        <div class="logo-box">TTA</div>
        <div class="logo-text">
            <span>Tecnología</span>
            <span>Textil</span>
            <span>Avanzada</span>
        </div>
    </div>

    <h1>Sistema de Control de Solicitudes</h1>
    <p class="subtitle">Seleccione el módulo al que desea ingresar:</p>

    <div class="menu-grid">
        <!-- Opción 1: Solicitud de Consumibles -->
        <a href="boleta_consumibles.php" class="menu-card">
            <div class="card-icon">📦</div>
            <div class="card-title">Solicitud de Consumibles</div>
            <div class="card-desc">Control de repuestos, consumibles y materia prima.</div>
        </a>

        <!-- Opción 2: Requerimiento de Compra -->
        <a href="requerimiento.php" class="menu-card">
            <div class="card-icon">📄</div>
            <div class="card-title">Requerimiento de Compra</div>
            <div class="card-desc">Formato con persona solicitante, unidad de medida y área de uso.</div>
        </a>

        <!-- Opción 3: Reporte Consolidado por Fechas -->
        <a href="reporte.php" class="menu-card">
            <div class="card-icon">📊</div>
            <div class="card-title">Reportes por Fechas</div>
            <div class="card-desc">Consolidado con la suma total acumulada por producto en un rango de fechas.</div>
        </a>
    </div>
</div>

</body>
</html>