<?php
include '../conexion.php';

$sql = "SELECT c.idCliente, 
               CONCAT(c.primerNombre, ' ', c.primerApellido, ' ', c.razonSocial) AS nombreCompleto,
               c.numeroDocumento,
               tc.nombreTipoCliente,
               td.nombreTipoDocumento,
               c.telefono,
               c.correo
        FROM Cliente c
        INNER JOIN TipoCliente tc ON c.idTipoCliente = tc.idTipoCliente
        INNER JOIN TipoDocumento td ON c.idTipoDocumento = td.idTipoDocumento";

$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
</head>
<body>
    <h2>📋 Listado de Clientes</h2>
    <a href="agregar.php">➕ Agregar Cliente</a>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nombre / Razón Social</th>
            <th>Documento</th>
            <th>Tipo Cliente</th>
            <th>Tipo Documento</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Acciones</th>
        </tr>
        <?php while ($fila = $resultado->fetch_assoc()) { ?>
        <tr>
            <td><?= $fila['idCliente']; ?></td>
            <td><?= $fila['nombreCompleto']; ?></td>
            <td><?= $fila['numeroDocumento']; ?></td>
            <td><?= $fila['nombreTipoCliente']; ?></td>
            <td><?= $fila['nombreTipoDocumento']; ?></td>
            <td><?= $fila['telefono']; ?></td>
            <td><?= $fila['correo']; ?></td>
            <td>
                <a href="editar.php?id=<?= $fila['idCliente']; ?>">✏ Editar</a> | 
                <a href="eliminar.php?id=<?= $fila['idCliente']; ?>" onclick="return confirm('¿Seguro de eliminar?')">🗑 Eliminar</a>
            </td>
        </tr>
        <?php } ?>
    </table>
    <br>
    <a href="../index.php">⬅ Volver al inicio</a>
</body>
</html>
