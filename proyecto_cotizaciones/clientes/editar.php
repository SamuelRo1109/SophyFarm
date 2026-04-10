<?php
include '../conexion.php';

$id = $_GET['id'];
$sql = "SELECT * FROM Cliente WHERE idCliente = $id";
$cliente = $conexion->query($sql)->fetch_assoc();

$tiposCliente = $conexion->query("SELECT * FROM TipoCliente WHERE estado='A'");
$tiposDocumento = $conexion->query("SELECT * FROM TipoDocumento WHERE estado='A'");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
    <script>
    function mostrarCampos() {
        let tipo = document.getElementById("tipoCliente").value;

        if (tipo == "2") { // Empresa
            document.getElementById("nombrePersona").style.display = "none";
            document.getElementById("razonSocialDiv").style.display = "block";

        } else { // Persona natural
            document.getElementById("nombrePersona").style.display = "block";
            document.getElementById("razonSocialDiv").style.display = "none";
        }
    }
    </script>
</head>
<body>
    <h2>✏ Editar Cliente</h2>
    <form action="actualizar.php" method="POST">
        <input type="hidden" name="idCliente" value="<?= $cliente['idCliente']; ?>">

        <!-- TIPO CLIENTE -->
        <label>Tipo de Cliente:</label><br>
        <select name="idTipoCliente" id="tipoCliente" onchange="mostrarCampos()" required>
            <?php while ($row = $tiposCliente->fetch_assoc()) { ?>
                <option value="<?= $row['idTipoCliente']; ?>" <?= $cliente['idTipoCliente'] == $row['idTipoCliente'] ? 'selected' : '' ?>>
                    <?= $row['nombreTipoCliente']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        <!-- TIPO DOCUMENTO -->
        <label>Tipo de Documento:</label><br>
        <select name="idTipoDocumento" required>
            <?php while ($row = $tiposDocumento->fetch_assoc()) { ?>
                <option value="<?= $row['idTipoDocumento']; ?>" <?= $cliente['idTipoDocumento'] == $row['idTipoDocumento'] ? 'selected' : '' ?>>
                    <?= $row['nombreTipoDocumento']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        <!-- NUMERO DOCUMENTO / NIT -->
        <label>Número de Documento / NIT:</label><br>
        <input type="text" name="numeroDocumento" value="<?= $cliente['numeroDocumento']; ?>" required><br><br>

        <!-- SOLO PERSONA NATURAL -->
        <div id="nombrePersona">
            <label>Primer Nombre:</label><br>
            <input type="text" name="primerNombre" value="<?= $cliente['primerNombre']; ?>"><br><br>

            <label>Segundo Nombre:</label><br>
            <input type="text" name="segundoNombre" value="<?= $cliente['segundoNombre']; ?>"><br><br>

            <label>Primer Apellido:</label><br>
            <input type="text" name="primerApellido" value="<?= $cliente['primerApellido']; ?>"><br><br>

            <label>Segundo Apellido:</label><br>
            <input type="text" name="segundoApellido" value="<?= $cliente['segundoApellido']; ?>"><br><br>
        </div>

        <!-- SOLO EMPRESA -->
        <div id="razonSocialDiv" style="display:none;">
            <label>Razón Social:</label><br>
            <input type="text" name="razonSocial" value="<?= $cliente['razonSocial']; ?>"><br><br>
        </div>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono" value="<?= $cliente['telefono']; ?>"><br><br>

        <label>Correo:</label><br>
        <input type="email" name="correo" value="<?= $cliente['correo']; ?>"><br><br>

        <label>Dirección:</label><br>
        <input type="text" name="direccion" value="<?= $cliente['direccion']; ?>"><br><br>

        <button type="submit">Actualizar</button>
        <a href="listar.php">Cancelar</a>
    </form>

    <script>
        // Mostrar campos correctos al cargar página
        mostrarCampos();
    </script>
</body>
</html>
