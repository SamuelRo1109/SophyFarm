<?php
include '../conexion.php';

$idPedido = $_GET['id'];

// Verificar si el pedido está confirmado
$pedido = $conexion->query("SELECT estadoPedido FROM Pedido WHERE idPedido = $idPedido")->fetch_assoc();

if ($pedido['estadoPedido'] !== 'CONFIRMADO') {
    echo "<script>
        alert('⚠ El pedido no está confirmado, no es necesario cancelarlo.');
        window.location.href = 'listar.php';
    </script>";
    exit;
}

// Obtener detalles del pedido
$detalles = $conexion->query("
    SELECT idProducto, cantidad 
    FROM DetallePedido 
    WHERE idPedido = $idPedido
");

// Restaurar stock de cada producto
while ($d = $detalles->fetch_assoc()) {
    $idProd = $d['idProducto'];
    $cant   = $d['cantidad'];
    $conexion->query("UPDATE Producto SET stockActual = stockActual + $cant WHERE idProducto = $idProd");
}

// Cambiar estado del pedido
$conexion->query("UPDATE Pedido SET estadoPedido = 'CANCELADO' WHERE idPedido = $idPedido");

// Mensaje y redirección automática
echo "<script>
    alert('✅ Pedido cancelado correctamente. El stock ha sido restaurado.');
    window.location.href = 'listar.php';
</script>";
exit;
?>
