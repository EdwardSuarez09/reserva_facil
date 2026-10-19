<?php
include __DIR__ . '/../../config/conexion.php';

// Para esta funcionalidad, asumiremos que el cliente está logueado o proporciona su información
// Por ahora simularemos un cliente_id, pero esto debería venir de la sesión

$restaurante = $_POST['restaurante'] ?? '';
$mesa_id = $_POST['id_mesa'] ?? null;
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$personas = $_POST['personas'] ?? 1;
$duracion = $_POST['duracion'] ?? 120;
$nombre_cliente = $_POST['nombre_cliente'] ?? '';
$telefono_cliente = $_POST['telefono_cliente'] ?? '';
$email_cliente = $_POST['email_cliente'] ?? '';
$comentarios = $_POST['comentarios'] ?? '';

// Validar campos requeridos
if (empty($restaurante) || empty($fecha) || empty($hora) || empty($nombre_cliente) || empty($telefono_cliente) || empty($email_cliente)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados']);
    exit;
}

try {
    // Obtener ID del restaurante y horarios
    $stmt_rest = $conn->prepare("SELECT id, horario_apertura, horario_cierre FROM restaurantes WHERE nombre = ?");
    $stmt_rest->bind_param("s", $restaurante);
    $stmt_rest->execute();
    $resultado_rest = $stmt_rest->get_result();
    $restaurante_data = $resultado_rest->fetch_assoc();
    
    if (!$restaurante_data) {
        echo json_encode(['success' => false, 'message' => 'Restaurante no encontrado']);
        exit;
    }
    
    $id_restaurante = $restaurante_data['id'];
    
    // VALIDACIÓN DE HORARIOS DEL RESTAURANTE
    if (isset($restaurante_data['horario_apertura']) && isset($restaurante_data['horario_cierre'])) {
        $horario_apertura = $restaurante_data['horario_apertura'];
        $horario_cierre = $restaurante_data['horario_cierre'];
        $hora_solicitada = date('H:i:s', strtotime($hora));
        
        // Verificar si la hora está dentro del rango de funcionamiento
        if ($hora_solicitada < $horario_apertura || $hora_solicitada > $horario_cierre) {
            $apertura_12h = date('g:i A', strtotime($horario_apertura));
            $cierre_12h = date('g:i A', strtotime($horario_cierre));
            
            echo json_encode([
                'success' => false, 
                'message' => "Lo sentimos, {$restaurante} solo atiende de {$apertura_12h} a {$cierre_12h}. Por favor selecciona un horario dentro de nuestras horas de servicio.",
                'tipo_error' => 'horario_invalido',
                'horario_apertura' => $apertura_12h,
                'horario_cierre' => $cierre_12h
            ]);
            exit;
        }
    }
    
    // Verificar si existe la tabla usuarios_cliente
    $check_table = "SHOW TABLES LIKE 'usuarios_cliente'";
    $table_exists = $conn->query($check_table)->num_rows > 0;
    
    $cliente_id = null;
    
    if ($table_exists) {
        // Buscar si el cliente ya existe por email
        $stmt_cliente = $conn->prepare("SELECT id FROM usuarios_cliente WHERE email = ?");
        $stmt_cliente->bind_param("s", $email_cliente);
        $stmt_cliente->execute();
        $resultado_cliente = $stmt_cliente->get_result();
        
        if ($resultado_cliente->num_rows > 0) {
            // Cliente existe, usar su ID
            $cliente_data = $resultado_cliente->fetch_assoc();
            $cliente_id = $cliente_data['id'];
        } else {
            // Cliente no existe, crear uno nuevo
            $password_temp = password_hash('temp123', PASSWORD_DEFAULT); // Password temporal
            $stmt_crear = $conn->prepare("INSERT INTO usuarios_cliente (nombre, email, telefono, password) VALUES (?, ?, ?, ?)");
            $stmt_crear->bind_param("ssss", $nombre_cliente, $email_cliente, $telefono_cliente, $password_temp);
            
            if ($stmt_crear->execute()) {
                $cliente_id = $conn->insert_id;
            }
        }
    }
    
    // Verificar que la mesa siga disponible
    if ($mesa_id) {
        $hora_inicio = date('H:i', strtotime($hora . ' -2 hours'));
        $hora_fin = date('H:i', strtotime($hora . ' +2 hours'));
        
        $stmt_check = $conn->prepare("
            SELECT id FROM reservas 
            WHERE mesa_id = ? 
            AND fecha_reserva = ? 
            AND hora_reserva BETWEEN ? AND ?
            AND estado IN ('Pendiente', 'Confirmada')
        ");
        $stmt_check->bind_param("isss", $mesa_id, $fecha, $hora_inicio, $hora_fin);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Lo sentimos, esta mesa ya ha sido reservada por otro cliente']);
            exit;
        }
    }
    
    // Insertar la reserva - usar ambos campos de restaurante según la estructura real de la tabla
    $sql = "INSERT INTO reservas (cliente_id, id_restaurante, restaurante_id, mesa_id, nombre_cliente, telefono_cliente, 
            email_cliente, fecha_reserva, hora_reserva, numero_personas, duracion, comentarios, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')";
    
    $stmt = $conn->prepare($sql);
    
    if ($cliente_id) {
        $stmt->bind_param("iiiisssssiis", 
            $cliente_id,
            $id_restaurante,
            $id_restaurante, // Llenar ambos campos de restaurante
            $mesa_id, 
            $nombre_cliente, 
            $telefono_cliente, 
            $email_cliente, 
            $fecha, 
            $hora, 
            $personas, 
            $duracion,
            $comentarios
        );
    } else {
        // Si no hay tabla usuarios_cliente, insertar con cliente_id NULL
        $cliente_id_null = null;
        $stmt->bind_param("iiiisssssiis", 
            $cliente_id_null,
            $id_restaurante,
            $id_restaurante, // Llenar ambos campos de restaurante
            $mesa_id, 
            $nombre_cliente, 
            $telefono_cliente, 
            $email_cliente, 
            $fecha, 
            $hora, 
            $personas, 
            $duracion,
            $comentarios
        );
    }
    
    if ($stmt->execute()) {
        $reserva_id = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => '¡Reserva realizada exitosamente!',
            'reserva_id' => $reserva_id,
            'detalles' => [
                'cliente' => $nombre_cliente,
                'restaurante' => $restaurante,
                'fecha' => $fecha,
                'hora' => $hora,
                'personas' => $personas,
                'mesa' => $mesa_id
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al procesar la reserva: ' . $stmt->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?> 