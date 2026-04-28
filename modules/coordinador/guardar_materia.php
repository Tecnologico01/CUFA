<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre         = $_POST['nombre'] ?? '';
    $nombre_corto   = !empty($_POST['nombre_corto']) ? $_POST['nombre_corto'] : null;
    $clave          = !empty($_POST['clave']) ? $_POST['clave'] : null;
    // ... (El resto de tus variables se mantienen igual) ...
    $grado          = $_POST['grado'] ?? null;
    $carrera_id     = $_POST['carrera_id'] ?? null;
    $aula           = !empty($_POST['aula']) ? $_POST['aula'] : null;
    $tipo           = $_POST['tipo'] ?? 'Obligatoria';
    $area_formacion = $_POST['area_formacion'] ?? null;
    $seriacion_id   = !empty($_POST['seriacion_id']) ? $_POST['seriacion_id'] : null;
    $es_opcional    = isset($_POST['es_opcional']) ? 1 : 0;
    $maneja_niveles = isset($_POST['maneja_niveles']) ? 1 : 0;
    $horas_docente        = (int)($_POST['horas_docente'] ?? 0);
    $horas_independientes = (int)($_POST['horas_independientes'] ?? 0);
    $creditos             = (int)($_POST['creditos'] ?? 0);

    try {
        // Novedad: VALIDACIÓN DE CLAVE DUPLICADA
        if ($clave !== null) {
            $stmtCheck = $pdo->prepare("SELECT id FROM materias WHERE clave = ?");
            $stmtCheck->execute([$clave]);
            if ($stmtCheck->rowCount() > 0) {
                // Si ya existe, lo regresamos con un error
                header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=materias&error=clave_duplicada");
                exit;
            }
        }

        // Si no está duplicada, procedemos con el INSERT
        $sql = "INSERT INTO materias (
                    nombre, nombre_corto, clave, grado, carrera_id, 
                    aula, tipo, seriacion_id, es_opcional, maneja_niveles, 
                    area_formacion, horas_docente, horas_independientes, creditos
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre, $nombre_corto, $clave, $grado, $carrera_id,
            $aula, $tipo, $seriacion_id, $es_opcional, $maneja_niveles,
            $area_formacion, $horas_docente, $horas_independientes, $creditos
        ]);

        header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=materias&status=success");
        exit;

    } catch (PDOException $e) {
        die("Error crítico al guardar la materia: " . $e->getMessage());
    }
}