<?php
// Configuración de la base de datos (XAMPP en puerto 3307; MySQL 9.7 usa 3306)
$host = '127.0.0.1';
$port = 3307;
$dbname = 'restaurantes_db';
$username = 'root';
$password = '';

// Conexión PDO (nueva)
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8 COLLATE utf8_general_ci");
    $pdo->exec("USE $dbname");
    
} catch (PDOException $e) {
    die("Error de conexión PDO: " . $e->getMessage());
}

// Conexión MySQLi (compatibilidad con archivos existentes)
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Verificar conexión MySQLi
if ($conn->connect_error) {
    die("Conexión MySQLi fallida: " . $conn->connect_error);
}

// Establecer charset para MySQLi
$conn->set_charset("utf8");

// Crear tablas necesarias usando PDO
try {
    // Crear tabla restaurantes si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS restaurantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        direccion VARCHAR(200),
        telefono VARCHAR(20),
        email VARCHAR(100),
        tipo_cocina VARCHAR(50),
        imagen VARCHAR(255),
        precio_promedio DECIMAL(10,2),
        horario_apertura TIME,
        horario_cierre TIME,
        capacidad INT DEFAULT 50,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Crear tabla usuarios_cliente si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_cliente (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        telefono VARCHAR(20),
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Crear tabla reservas si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS reservas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT,
        restaurante_id INT,
        fecha_reserva DATE NOT NULL,
        hora_reserva TIME NOT NULL,
        numero_personas INT NOT NULL,
        estado ENUM('pendiente', 'confirmada', 'cancelada') DEFAULT 'pendiente',
        comentarios TEXT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios_cliente(id),
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
    )");
    
    // Crear tabla admin si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        correo VARCHAR(100) UNIQUE NOT NULL,
        contraseña VARCHAR(255) NOT NULL,
        rol ENUM('admin', 'superadmin') DEFAULT 'admin',
        nombre VARCHAR(100),
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insertar restaurantes de ejemplo si la tabla está vacía
    $stmt = $pdo->query("SELECT COUNT(*) FROM restaurantes");
    if ($stmt->fetchColumn() == 0) {
        $restaurantes_ejemplo = [
            [
                'nombre' => 'La Terraza Gourmet',
                'descripcion' => 'Exquisita cocina mediterránea con vistas panorámicas de la ciudad.',
                'direccion' => 'Av. Principal 123, Centro',
                'telefono' => '+1-234-567-8901',
                'email' => 'info@terrazagourmet.com',
                'tipo_cocina' => 'mediterránea',
                'imagen' => 'public/assets/img/Imagenes/DALL·E 2024-11-06 21.54.50 - An elegant logo for a restaurant, featuring a stylized fork and knife crossed over a plate with subtle decorative elements. The background should be s.webp',
                'precio_promedio' => 45.00,
                'horario_apertura' => '12:00:00',
                'horario_cierre' => '23:00:00'
            ],
            [
                'nombre' => 'Sabores del Mar',
                'descripcion' => 'Los mejores mariscos frescos en un ambiente elegante y acogedor.',
                'direccion' => 'Calle del Puerto 456, Zona Costera',
                'telefono' => '+1-234-567-8902',
                'email' => 'reservas@saboresdelmar.com',
                'tipo_cocina' => 'mariscos',
                'imagen' => 'public/assets/img/Imagenes/DALL·E 2024-11-06 22.01.47 - A high-quality image of a gourmet restaurant dish with elegant plating, suitable for use in a restaurant website. The dish should be visually appealin.webp',
                'precio_promedio' => 38.00,
                'horario_apertura' => '11:00:00',
                'horario_cierre' => '22:30:00'
            ],
            [
                'nombre' => 'El Jardín Secreto',
                'descripcion' => 'Cocina vegetariana creativa en un hermoso jardín al aire libre.',
                'direccion' => 'Plaza Verde 789, Distrito Ecológico',
                'telefono' => '+1-234-567-8903',
                'email' => 'contacto@jardinecreto.com',
                'tipo_cocina' => 'vegetariana',
                'imagen' => 'public/assets/img/Imagenes/DALL·E 2024-11-06 22.04.13 - A high-quality image of a traditional homemade meal served in a cozy setting. The dish should include comfor.webp',
                'precio_promedio' => 32.00,
                'horario_apertura' => '10:00:00',
                'horario_cierre' => '21:00:00'
            ]
        ];
        
        foreach ($restaurantes_ejemplo as $rest) {
            $stmt = $pdo->prepare("INSERT INTO restaurantes (nombre, descripcion, direccion, telefono, email, tipo_cocina, imagen, precio_promedio, horario_apertura, horario_cierre) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $rest['nombre'], $rest['descripcion'], $rest['direccion'], $rest['telefono'],
                $rest['email'], $rest['tipo_cocina'], $rest['imagen'], $rest['precio_promedio'],
                $rest['horario_apertura'], $rest['horario_cierre']
            ]);
        }
    }
    
    // Insertar un admin por defecto si no existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO admin (correo, contraseña, rol, nombre) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'admin@reservafacil.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'superadmin',
            'Administrador Principal'
        ]);
    }
    
} catch (PDOException $e) {
    // Error silencioso para no interrumpir la carga de archivos existentes
    error_log("Error creando tablas: " . $e->getMessage());
}
?>
