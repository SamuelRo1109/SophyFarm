<?php
include '../conexion.php';

// 📌 Recibir datos del formulario
$idCliente        = $_POST['idCliente'];
$idTipoCliente    = $_POST['idTipoCliente'];
$idTipoDocumento  = $_POST['idTipoDocumento'];
$numeroDocumento  = trim($_POST['numeroDocumento']);
$primerNombre     = trim($_POST['primerNombre']);
$segundoNombre    = trim($_POST['segundoNombre']);
$primerApellido   = trim($_POST['primerApellido']);
$segundoApellido  = trim($_POST['segundoApellido']);
$razonSocial      = trim($_POST['razonSocial']);
$telefono         = trim($_POST['telefono']);
$correo           = trim($_POST['correo']);
$direccion        = trim($_POST['direccion']);

// ⚠ Validar documento duplicado (pero ignorando el del mismo cliente)
$verDoc = $conexion->query("SELECT * FROM Cliente WHERE numeroDocumento = '$numeroDocumento' AND idCliente != $idCliente");
if ($verDoc->num_rows > 0) {
    die("<h3>⚠ Error: ya existe otro cliente con este documento o NIT.</h3>
         <br><a href='editar.php?id=$idCliente'>⬅ Volver</a>");
}

// ⚠ Validar persona natural
if ($idTipoCliente == 1) { // 1 = Persona Natural
    if (empty($primerNombre) || empty($primerApellido)) {
        die("<h3>⚠ Error: Para personas, el primer nombre y primer apellido son obligatorios.</h3>
             <br><a href='editar.php?id=$idCliente'>⬅ Volver</a>");
    }
    $razonSocial = ""; // No aplica en personas
}

// ⚠ Validar empresa
if ($idTipoCliente == 2) { // 2 = Empresa
    if (empty($razonSocial)) {
        die("<h3>⚠ Error: Para empresas, la razón social es obligatoria.</h3>
             <br><a href='editar.php?id=$idCliente'>⬅ Volver</a>");
    }
    // Limpiar nombres si es empresa
    $primerNombre = $segundoNombre = $primerApellido = $segundoApellido = "";
}

// ✅ Ejecutar actualización
$sql = "UPDATE Cliente SET
            idTipoCliente    = '$idTipoCliente',
            idTipoDocumento  = '$idTipoDocumento',
            numeroDocumento  = '$numeroDocumento',
            primerNombre     = '$primerNombre',
            segundoNombre    = '$segundoNombre',
            primerApellido   = '$primerApellido',
            segundoApellido  = '$segundoApellido',
            razonSocial      = '$razonSocial',
            telefono         = '$telefono',
            correo           = '$correo',
            direccion        = '$direccion'
        WHERE idCliente = $idCliente";

if ($conexion->query($sql) === TRUE) {
    header("Location: listar.php");
} else {
    echo "❌ Error: " . $conexion->error;
}
?>
