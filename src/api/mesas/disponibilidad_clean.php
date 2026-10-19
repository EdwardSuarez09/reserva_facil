<?php
// Limpiar completamente cualquier salida previa
while (ob_get_level()) {
    ob_end_clean();
}

// Iniciar buffer de salida para capturar cualquier error
ob_start();

// CONFIGURAR ZONA HORARIA DE COLOMBIA (sin generar salida)
date_default_timezone_set('America/Bogota');
if (date_default_timezone_get() !== 'America/Bogota') {
    date_default_timezone_set('Etc/GMT+5');
}

// Headers JSON primero
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Desactivar completamente errores y warnings en la salida
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(0);

// Limpiar buffer previo antes de enviar JSON
ob_clean();

try {
    // Conexión directa sin archivo externo para evitar conflictos
    $host = '127.0.0.1';
    $port = 3307;
    $dbname = 'restaurantes_db';
    $username = 'root';
    $password = '';
    
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    
    $restaurante = $_GET['restaurante'] ?? '';
    $fecha = $_GET['fecha'] ?? '';
    $hora = $_GET['hora'] ?? '';
    $personas = (int)($_GET['personas'] ?? 1);
    
    if (empty($restaurante) || empty($fecha) || empty($hora)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Faltan parámetros requeridos',
            'debug' => [
                'restaurante' => $restaurante,
                'fecha' => $fecha,
                'hora' => $hora,
                'personas' => $personas,
                'zona_horaria' => date_default_timezone_get(),
                'hora_colombia' => date('Y-m-d H:i:s')
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Obtener información del restaurante incluyendo horarios
    $stmt_rest = $conn->prepare("SELECT id, nombre, horario_apertura, horario_cierre FROM restaurantes WHERE nombre = ?");
    if (!$stmt_rest) {
        throw new Exception("Error preparando consulta restaurante");
    }
    
    $stmt_rest->bind_param("s", $restaurante);
    $stmt_rest->execute();
    $resultado_rest = $stmt_rest->get_result();
    $restaurante_data = $resultado_rest->fetch_assoc();
    
    if (!$restaurante_data) {
        // Si no encuentra el restaurante, buscar todas las mesas disponibles
        // Esto es útil para testing
        $restaurante_data = ['id' => 1, 'nombre' => $restaurante];
        $restaurante_id = 1;
    } else {
        $restaurante_id = $restaurante_data['id'];
        
        // VALIDACIÓN DE HORARIOS DEL RESTAURANTE
        if (isset($restaurante_data['horario_apertura']) && isset($restaurante_data['horario_cierre'])) {
            $horario_apertura = $restaurante_data['horario_apertura'];
            $horario_cierre = $restaurante_data['horario_cierre'];
            $hora_solicitada = date('H:i:s', strtotime($hora));
            
            // Verificar si la hora está dentro del rango de funcionamiento
            if ($hora_solicitada < $horario_apertura || $hora_solicitada > $horario_cierre) {
                $apertura_12h = date('g:i A', strtotime($horario_apertura));
                $cierre_12h = date('g:i A', strtotime($horario_cierre));
                
                $mensaje_base = "Lo sentimos, {$restaurante_data['nombre']} solo atiende de {$apertura_12h} a {$cierre_12h}.";
                
                // Sugerir horario según si es muy temprano o muy tarde
                if ($hora_solicitada < $horario_apertura) {
                    $sugerencia = " El restaurante aún no ha abierto. La primera reserva disponible es a las {$apertura_12h}.";
                } else {
                    $sugerencia = " El restaurante ya cerró. Puedes reservar mañana desde las {$apertura_12h}.";
                }
                
                http_response_code(400);
                echo json_encode([
                    'error' => $mensaje_base . $sugerencia,
                    'tipo_error' => 'horario_invalido',
                    'horario_apertura' => $apertura_12h,
                    'horario_cierre' => $cierre_12h,
                    'hora_solicitada' => date('g:i A', strtotime($hora)),
                    'sugerencia' => $sugerencia
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }
    
    // Verificar fechas y horas
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i');
    
    if ($fecha < $fecha_actual) {
        echo json_encode([
            'error' => 'No se pueden hacer reservas en fechas pasadas. Por favor selecciona una fecha de hoy en adelante.',
            'debug' => [
                'fecha_recibida' => $fecha,
                'fecha_actual_colombia' => $fecha_actual,
                'hora_actual_colombia' => date('H:i:s'),
                'zona_horaria' => date_default_timezone_get()
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Solo validar hora si es el mismo día
    if ($fecha == $fecha_actual) {
        // Mejorar interpretación de hora - manejar diferentes formatos
        $hora_solicitada_24h = $hora;
        
        // Si contiene AM/PM, convertir (incluyendo formatos con puntos como p.m., a.m.)
        if (stripos($hora, 'am') !== false || stripos($hora, 'pm') !== false || 
            stripos($hora, 'a.m.') !== false || stripos($hora, 'p.m.') !== false) {
            $hora_solicitada_24h = date('H:i', strtotime($hora));
            
            // Debug específico para AM/PM
            error_log("Debug AM/PM - Original: '$hora', Convertida: '$hora_solicitada_24h'");
            
            // Verificar que la conversión funcionó correctamente
            if ($hora_solicitada_24h === false || $hora_solicitada_24h === '00:00') {
                // Intentar parsing manual para casos problemáticos
                $hora_limpia = trim(str_replace(['.', ' '], '', $hora));
                $hora_solicitada_24h = date('H:i', strtotime($hora_limpia));
                error_log("Debug AM/PM manual - Limpia: '$hora_limpia', Resultado: '$hora_solicitada_24h'");
            }
        } else {
            // Verificar si es formato de 12h sin AM/PM
            $hora_parts = explode(':', $hora);
            if (count($hora_parts) == 2) {
                $hora_num = (int)$hora_parts[0];
                $minuto_num = (int)$hora_parts[1];
                
                // Si es hora válida en formato 24h, mantener
                if ($hora_num >= 0 && $hora_num <= 23 && $minuto_num >= 0 && $minuto_num <= 59) {
                    $hora_solicitada_24h = sprintf('%02d:%02d', $hora_num, $minuto_num);
                    
                    // LÓGICA MEJORADA: Si la hora está entre 01:00-11:59 y es de día,
                    // es probable que el usuario quiera PM (debido a frontend sin AM/PM)
                    if ($hora_num >= 1 && $hora_num <= 11) {
                        // Solo aplicar esta corrección si es el mismo día y la hora AM ya pasó
                        $hora_am = sprintf('%02d:%02d', $hora_num, $minuto_num);
                        $hora_pm = sprintf('%02d:%02d', $hora_num + 12, $minuto_num);
                        
                        // Obtener la hora actual en formato numérico para comparación inteligente
                        $hora_actual_num = (int)date('H');
                        
                        // CASO ESPECIAL: Si son las 13:xx (1 PM) y el usuario pone "1:00"
                        // Es muy probable que quiera decir 1:00 PM (13:00), no 1:00 AM
                        if ($hora_num == 1 && $hora_actual_num >= 13 && $hora_actual_num <= 14) {
                            // Si estamos en la 1-2 PM y el usuario pone "1:XX", probablemente quiere 1 PM
                            if (isset($restaurante_data['horario_apertura']) && isset($restaurante_data['horario_cierre'])) {
                                $apertura = $restaurante_data['horario_apertura'];
                                $cierre = $restaurante_data['horario_cierre'];
                                
                                if ($hora_pm >= $apertura && $hora_pm <= $cierre) {
                                    $hora_solicitada_24h = $hora_pm;
                                    error_log("Corrección inteligente aplicada (1 PM): $hora -> $hora_pm");
                                }
                            }
                        }
                        // LÓGICA GENERAL: Si la hora AM ya pasó, pero la PM sería válida
                        else if ($hora_am <= $hora_actual && $hora_pm > $hora_actual) {
                            // Verificar si la hora PM está dentro del horario del restaurante
                            if (isset($restaurante_data['horario_apertura']) && isset($restaurante_data['horario_cierre'])) {
                                $apertura = $restaurante_data['horario_apertura'];
                                $cierre = $restaurante_data['horario_cierre'];
                                
                                if ($hora_pm >= $apertura && $hora_pm <= $cierre) {
                                    $hora_solicitada_24h = $hora_pm;
                                    error_log("Corrección automática aplicada: $hora -> $hora_pm (asumiendo PM)");
                                }
                            }
                        }
                        // CASO ESPECIAL ADICIONAL: Si el usuario pone una hora que coincide con la hora actual ±1
                        // Ejemplo: Son las 13:30 y pone "1:30" - muy probable que quiera 13:30
                        else if (abs($hora_actual_num - ($hora_num + 12)) <= 1) {
                            if (isset($restaurante_data['horario_apertura']) && isset($restaurante_data['horario_cierre'])) {
                                $apertura = $restaurante_data['horario_apertura'];
                                $cierre = $restaurante_data['horario_cierre'];
                                
                                if ($hora_pm >= $apertura && $hora_pm <= $cierre && $hora_pm >= $hora_actual) {
                                    $hora_solicitada_24h = $hora_pm;
                                    error_log("Corrección por proximidad horaria: $hora -> $hora_pm");
                                }
                            }
                        }
                    }
                } else {
                    // Intentar interpretar como 12h
                    $hora_solicitada_24h = date('H:i', strtotime($hora));
                }
            } else {
                $hora_solicitada_24h = date('H:i', strtotime($hora));
            }
        }
        
        // Debug de la hora
        error_log("Debug hora - Original: '$hora', Convertida: '$hora_solicitada_24h', Actual: '$hora_actual' (Colombia)");
        
        if ($hora_solicitada_24h <= $hora_actual) {
            // Verificar si el restaurante aún está abierto para sugerir alternativas
            if (isset($restaurante_data['horario_cierre'])) {
                $cierre = $restaurante_data['horario_cierre'];
                $apertura = $restaurante_data['horario_apertura'];
                $hora_sugerida = date('H:i', strtotime($hora_actual . ' +30 minutes'));
                
                // Mensaje especial mejorado para problemas de formato AM/PM
                $mensaje_especial = "";
                if (preg_match('/^(\d{1,2}):(\d{2})$/', $hora)) {
                    $h = (int)explode(':', $hora)[0];
                    if ($h >= 1 && $h <= 11) {
                        $hora_pm_equivalente = sprintf('%02d:%02d', $h + 12, (int)explode(':', $hora)[1]);
                        
                        // Mensaje específico para el caso de las 1 PM
                        if ($h == 1 && date('H') >= 13) {
                            $mensaje_especial = " (Nota: Si intentabas reservar para la 1:00 PM, esa hora ya pasó. Hora actual: " . date('g:i A') . ")";
                        } else if ($hora_pm_equivalente <= $hora_actual) {
                            $mensaje_especial = " (Nota: Si intentabas reservar para " . date('g:i A', strtotime($hora_pm_equivalente)) . ", esa hora también ya pasó)";
                        } else {
                            $mensaje_especial = " (¿Quisiste decir " . date('g:i A', strtotime($hora_pm_equivalente)) . "? En ese caso, intenta reservar para más tarde)";
                        }
                    }
                }
                
                if ($hora_sugerida <= $cierre) {
                    $sugerencia = "Puedes reservar desde las " . date('g:i A', strtotime($hora_sugerida)) . " hasta las " . date('g:i A', strtotime($cierre)) . " hoy.";
                } else {
                    // Si es muy tarde hoy, sugerir mañana con un ejemplo específico
                    $fecha_manana = date('Y-m-d', strtotime('+1 day'));
                    $hora_ejemplo = date('g:i A', strtotime($hora_pm_equivalente ?? $hora));
                    $sugerencia = "El restaurante ya cerró por hoy. Puedes reservar mañana ($fecha_manana) desde las " . date('g:i A', strtotime($apertura)) . ". Por ejemplo, si querías " . $hora_ejemplo . ", selecciona mañana a esa hora.";
                }
            } else {
                $mensaje_especial = "";
                $sugerencia = "Por favor selecciona un horario futuro.";
            }
            
            echo json_encode([
                'error' => 'La hora seleccionada (' . date('g:i A', strtotime($hora_solicitada_24h)) . ') ya pasó. Hora actual: ' . date('g:i A') . '.' . $mensaje_especial . ' ' . $sugerencia,
                'tipo_error' => 'hora_pasada',
                'hora_actual' => date('g:i A'),
                'hora_solicitada' => date('g:i A', strtotime($hora_solicitada_24h)),
                'sugerencia' => $sugerencia,
                'debug' => [
                    'hora_original' => $hora,
                    'hora_convertida' => $hora_solicitada_24h,
                    'hora_sistema' => $hora_actual
                ]
            ]);
            exit;
        }
    }
    
    // Obtener mesas disponibles - FILTRAR POR RESTAURANTE y capacidad suficiente
    $stmt_mesas = $conn->prepare("SELECT id, numero_mesa, capacidad, imagen FROM mesas WHERE id_restaurante = ? AND capacidad >= ? ORDER BY numero_mesa");
    if (!$stmt_mesas) {
        throw new Exception("Error preparando consulta mesas");
    }
    
    $stmt_mesas->bind_param("ii", $restaurante_id, $personas);
    $stmt_mesas->execute();
    $resultado_mesas = $stmt_mesas->get_result();
    $mesas_totales = [];
    while ($row = $resultado_mesas->fetch_assoc()) {
        // Convertir la imagen a base64 si existe
        if ($row['imagen']) {
            $row['imagen'] = base64_encode($row['imagen']);
        }
        $mesas_totales[] = $row;
    }
    
    // Obtener mesas ocupadas
    $hora_inicio = date('H:i', strtotime($hora . ' -2 hours'));
    $hora_fin = date('H:i', strtotime($hora . ' +2 hours'));
    
    $stmt_ocupadas = $conn->prepare("
        SELECT DISTINCT mesa_id 
        FROM reservas 
        WHERE id_restaurante = ? 
        AND fecha_reserva = ? 
        AND hora_reserva BETWEEN ? AND ?
        AND estado IN ('pendiente', 'confirmada')
        AND mesa_id IS NOT NULL
    ");
    
    $mesas_ocupadas = [];
    if ($stmt_ocupadas) {
        $stmt_ocupadas->bind_param("isss", $restaurante_id, $fecha, $hora_inicio, $hora_fin);
        $stmt_ocupadas->execute();
        $resultado_ocupadas = $stmt_ocupadas->get_result();
        
        while ($row = $resultado_ocupadas->fetch_assoc()) {
            $mesas_ocupadas[] = (int)$row['mesa_id'];
        }
    }
    
    // Filtrar mesas disponibles
    $mesas_disponibles = [];
    foreach ($mesas_totales as $mesa) {
        if (!in_array((int)$mesa['id'], $mesas_ocupadas)) {
            $mesas_disponibles[] = $mesa;
        }
    }
    
    $response = [
        'success' => true,
        'restaurante' => $restaurante_data,
        'mesas_disponibles' => $mesas_disponibles,
        'fecha' => $fecha,
        'hora' => $hora,
        'personas' => $personas,
        'debug' => [
            'total_mesas_encontradas' => count($mesas_totales),
            'mesas_totales_detalles' => $mesas_totales,
            'mesas_ocupadas' => $mesas_ocupadas,
            'mesas_disponibles_count' => count($mesas_disponibles),
            'restaurante_id' => $restaurante_id,
            'hora_inicio_busqueda' => $hora_inicio,
            'hora_fin_busqueda' => $hora_fin,
            'consulta_mesas' => "SELECT id, numero_mesa, capacidad, imagen FROM mesas WHERE id_restaurante = $restaurante_id AND capacidad >= $personas ORDER BY numero_mesa",
            'parametros_busqueda' => [
                'personas_solicitadas' => $personas,
                'fecha_solicitada' => $fecha,
                'hora_solicitada' => $hora,
                'restaurante_nombre' => $restaurante
            ]
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

if (isset($conn)) {
    $conn->close();
}

exit;
?> 