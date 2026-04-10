<?php
include '../conexion.php';

// 📌 Capturar datos
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

// ⚠ Validar documento duplicado
$verificarDoc = $conexion->query("SELECT * FROM Cliente WHERE numeroDocumento = '$numeroDocumento'");
if ($verificarDoc->num_rows > 0) {
    die("<h3>⚠ Error: Ya existe un cliente registrado con este documento o NIT.</h3>
         <br><a href='agregar.php'>⬅ Volver</a>");
}

// ⚠ Validar persona natural: no debe tener razón social vacía, pero sí nombre y apellido
if ($idTipoCliente == 1) { // Persona natural
    if (empty($primerNombre) || empty($primerApellido)) {
        die("<h3>⚠ Error: Para personas naturales, el primer nombre y primer apellido son obligatorios.</h3>
             <br><a href='agregar.php'>⬅ Volver</a>");
    }
    $razonSocial = ""; // No aplica para personas
}

// ⚠ Validar empresa: solo debe tener razón social y NIT
if ($idTipoCliente == 2) { // Empresa
    if (empty($razonSocial)) {
        die("<h3>⚠ Error: Para empresas, la razón social es obligatoria.</h3>
             <br><a href='agregar.php'>⬅ Volver</a>");
    }
    // Empresas no usan nombres
    $primerNombre = $primerApellido = $segundoNombre = $segundoApellido = "";
}

// ✅ Insertar si todo está correcto
$sql = "INSERT INTO Cliente (
            idTipoCliente, idTipoDocumento, numeroDocumento, primerNombre, segundoNombre, 
            primerApellido, segundoApellido, razonSocial, telefono, correo, direccion
        ) VALUES (
            '$idTipoCliente', '$idTipoDocumento', '$numeroDocumento', '$primerNombre', '$segundoNombre',
            '$primerApellido', '$segundoApellido', '$razonSocial', '$telefono', '$correo', '$direccion'
        )";

if ($conexion->query($sql) === TRUE) {
    header("Location: listar.php");
} else {
    echo "❌ Error: " . $conexion->error;
}
?>
