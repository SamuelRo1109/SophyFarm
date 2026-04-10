<?php
include '../conexion.php';

$tiposCliente = $conexion->query("SELECT * FROM TipoCliente WHERE estado='A'");
$tiposDocumento = $conexion->query("SELECT * FROM TipoDocumento WHERE estado='A'");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Cliente</title>
    <script>
    function mostrarCampos() {
        let tipo = document.getElementById("tipoCliente").value;

        if (tipo == "2") { // Empresa
            document.getElementById("nombrePersona").style.display = "none";
            document.getElementById("razonSocialDiv").style.display = "block";

            // Borrar nombres si cambian de natural a empresa
            document.getElementById("primerNombre").value = "";
            document.getElementById("segundoNombre").value = "";
            document.getElementById("primerApellido").value = "";
            document.getElementById("segundoApellido").value = "";
        } else { // Persona Natural
            document.getElementById("nombrePersona").style.display = "block";
            document.getElementById("razonSocialDiv").style.display = "none";

            // Borrar razón social si cambian a persona
            document.getElementById("razonSocial").value = "";
        }
    }
    </script>
</head>
<body>
    <h2>➕ Agregar Cliente</h2>
    <form action="guardar.php" method="POST">

        <label>Tipo de Cliente:</label><br>
        <select name="idTipoCliente" id="tipoCliente" onchange="mostrarCampos()" required>
            <?php while ($row = $tiposCliente->fetch_assoc()) { ?>
                <option value="<?= $row['idTipoCliente'] ?>"><?= $row['nombreTipoCliente'] ?></option>
            <?php } ?>
        </select><br><br>

        <label>Tipo de Documento:</label><br>
        <select name="idTipoDocumento" required>
            <?php while ($row = $tiposDocumento->fetch_assoc()) { ?>
                <option value="<?= $row['idTipoDocumento'] ?>"><?= $row['nombreTipoDocumento'] ?></option>
            <?php } ?>
        </select><br><br>

        <label>Número Documento / NIT:</label><br>
        <input type="text" name="numeroDocumento" required><br><br>

        <!-- ✅ Campos solo para Persona Natural -->
        <div id="nombrePersona">
            <label>Primer Nombre:</label><br>
            <input type="text" name="primerNombre" id="primerNombre"><br><br>

            <label>Segundo Nombre:</label><br>
            <input type="text" name="segundoNombre" id="segundoNombre"><br><br>

            <label>Primer Apellido:</label><br>
            <input type="text" name="primerApellido" id="primerApellido"><br><br>

            <label>Segundo Apellido:</label><br>
            <input type="text" name="segundoApellido" id="segundoApellido"><br><br>
        </div>

        <!-- ✅ Campo solo para Empresas -->
        <div id="razonSocialDiv" style="display:none;">
            <label>Razón Social:</label><br>
            <input type="text" name="razonSocial" id="razonSocial"><br><br>
        </div>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono"><br><br>

        <label>Correo:</label><br>
        <input type="email" name="correo"><br><br>

        <label>Dirección:</label><br>
        <input type="text" name="direccion"><br><br>

        <button type="submit">Guardar</button>
        <a href="listar.php">Cancelar</a>
    </form>

    <script>
        // Al cargar la página, mostrar lo correcto según tipo
        mostrarCampos();
    </script>
</body>
</html>
