<!DOCTYPE html>
<html>
<head>
    <title>Test Login Simple</title>
</head>
<body>
    <h2>Test de Login Simplificado</h2>
    
    <div id="auth-status">Verificando...</div>
    
    <div id="login-form" style="display: none;">
        <h3>Login</h3>
        <form onsubmit="doLogin(event)">
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <button type="submit">Login</button>
        </form>
    </div>
    
    <div id="user-info" style="display: none;">
        <h3>Usuario logueado:</h3>
        <div id="user-data"></div>
        <button onclick="doLogout()">Cerrar Sesión</button>
    </div>
    
    <script>
        // Verificar sesión al cargar
        fetch('../src/helpers/verificar_sesion_cliente.php')
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta de verificación:', data);
                if (data.autenticado) {
                    document.getElementById('auth-status').innerHTML = 'Sesión activa';
                    document.getElementById('user-info').style.display = 'block';
                    document.getElementById('user-data').innerHTML = `
                        ID: ${data.cliente.id}<br>
                        Nombre: ${data.cliente.nombre}<br>
                        Email: ${data.cliente.email}
                    `;
                } else {
                    document.getElementById('auth-status').innerHTML = 'No hay sesión activa';
                    document.getElementById('login-form').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('auth-status').innerHTML = 'Error al verificar sesión';
                document.getElementById('login-form').style.display = 'block';
            });
        
        function doLogin(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('../src/api/auth/login_cliente.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta de login:', data);
                if (data.success) {
                    alert('Login exitoso!');
                    location.reload();
                } else {
                    alert('Error: ' + data.mensaje);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        }
        
        function doLogout() {
            fetch('../src/api/auth/cerrar_sesion_cliente.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta de logout:', data);
                alert('Sesión cerrada');
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html> 