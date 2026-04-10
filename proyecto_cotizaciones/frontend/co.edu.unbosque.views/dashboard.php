<?php
session_start();  // Asegúrate de que la sesión se haya iniciado
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../co.edu.unbosque.css/style.css">
</head>
<body>
    <h1 id="welcomeMessage">Bienvenido <?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'Invitado'; ?></h1>
    <p id="roleMessage"></p>

    <!-- Los botones deberían dirigir a las páginas correctas -->
    <div id="adminButtons" style="display:none;">
        <button onclick="location.href='cotizacion.html'">Ver Cotizaciones</button>
        <button onclick="location.href='pedido.html'">Ver Pedidos</button>
    </div>

    <!-- Botones para el Vendedor -->
    <div id="vendedorButtons" style="display:none;">
        <button onclick="location.href='createClient.html'">Crear Cliente</button>
        <button onclick="location.href='cotizacion.html'">Ver Cotizaciones</button>
        <button onclick="location.href='pedido.html'">Ver Pedidos</button>
    </div>

    <script src="../co.edu.unbosque.js/app.js"></script>
    <script>
        // Obtener el rol de la sesión desde PHP (lo inyectamos como un valor PHP directamente en el HTML)
        let userRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'no_role'; ?>";  // Inyectar el valor del rol desde PHP
        
        // Actualizar el mensaje de bienvenida según el rol
        document.getElementById('welcomeMessage').innerText = `Bienvenido ${userRole}`;
        
        if (userRole === 'admin') {
            document.getElementById('roleMessage').innerText = 'Accede a las cotizaciones, pedidos y reportes.';
            document.getElementById('adminButtons').style.display = 'block';
        } else if (userRole === 'vendedor') {
            document.getElementById('roleMessage').innerText = 'Accede a los clientes, cotizaciones y pedidos.';
            document.getElementById('vendedorButtons').style.display = 'block';
        } else {
            document.getElementById('roleMessage').innerText = 'No tienes permisos suficientes.';
        }
    </script>
</body>
</html>
