<?php
// Establecer conexión con la base de datos
$host = "localhost"; // Dirección del servidor de base de datos
$user = "root"; // Usuario de la base de datos
$password = ""; // Contraseña del usuario
$dbname = "sophyfarm"; // Nombre de la base de datos

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// SQL para crear las tablas
$sql = "
-- Tabla: Usuario
CREATE TABLE usuario (
    id SERIAL PRIMARY KEY,
    username_usrio VARCHAR(12) NOT NULL,
    clave_usrio VARCHAR(12) NOT NULL,
    nombre_usrio VARCHAR(40) NOT NULL,
    mail_usrio VARCHAR(40),
    tlfno_usrio VARCHAR(20),
    fcha_ingrso DATE NOT NULL,
    estado_usrio VARCHAR(1) NOT NULL CHECK (estado_usrio IN ('A', 'I')) -- A = Activo, I = Inactivo
);

-- Tabla: Rol
CREATE TABLE rol (
    id SERIAL PRIMARY KEY,
    nombre_rol VARCHAR(40) NOT NULL,
    descripcion_rol VARCHAR(100) NOT NULL
);

-- Tabla: Usuario_Rol
CREATE TABLE usuario_rol (
    usuario_id INT NOT NULL,
    rol_id INT NOT NULL,
    PRIMARY KEY (usuario_id, rol_id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id),
    FOREIGN KEY (rol_id) REFERENCES rol(id)
);

-- Tabla: TipoCliente
CREATE TABLE tipo_cliente (
    idTipoCliente SERIAL PRIMARY KEY,
    nombreTipoCliente VARCHAR(50) NOT NULL,
    estado VARCHAR(1) NOT NULL CHECK (estado IN ('A', 'I')) -- A = Activo, I = Inactivo
);

-- Tabla: TipoDocumento
CREATE TABLE tipo_documento (
    idTipoDocumento SERIAL PRIMARY KEY,
    nombreTipoDocumento VARCHAR(50) NOT NULL,
    estado VARCHAR(1) NOT NULL CHECK (estado IN ('A', 'I')) -- A = Activo, I = Inactivo
);

-- Tabla: Cliente
CREATE TABLE cliente (
    idCliente SERIAL PRIMARY KEY,
    idTipoCliente INT NOT NULL,
    idTipoDocumento INT NOT NULL,
    numeroDocumento VARCHAR(15) NOT NULL,
    primerNombre VARCHAR(30),
    segundoNombre VARCHAR(30),
    primerApellido VARCHAR(30),
    segundoApellido VARCHAR(30),
    razonSocial VARCHAR(70), -- Solo para empresas
    telefono VARCHAR(15),
    correo VARCHAR(100),
    direccion VARCHAR(150),
    saldoActual NUMERIC(10, 2) NOT NULL,
    cupoCredito NUMERIC(10, 2) NOT NULL,
    estado VARCHAR(1) NOT NULL CHECK (estado IN ('A', 'I')),
    FOREIGN KEY (idTipoCliente) REFERENCES tipo_cliente(idTipoCliente),
    FOREIGN KEY (idTipoDocumento) REFERENCES tipo_documento(idTipoDocumento)
);

-- Tabla: Unidad
CREATE TABLE unidad (
    id SERIAL PRIMARY KEY,
    nombreUnidad VARCHAR(20) NOT NULL,
    estadoUnidad VARCHAR(1) NOT NULL CHECK (estadoUnidad IN ('A', 'I')) -- A = Activo, I = Inactivo
);

-- Tabla: Categoría
CREATE TABLE categoria_producto (
    id SERIAL PRIMARY KEY,
    nombreCtgria VARCHAR(50) NOT NULL,
    estadoCtgria VARCHAR(1) NOT NULL CHECK (estadoCtgria IN ('A', 'I')) -- A = Activo, I = Inactivo
);

-- Tabla: Elemento (Producto)
CREATE TABLE elemento (
    idElemento SERIAL PRIMARY KEY,
    Unidad_Unidad_ID INT NOT NULL,                  -- Unidad del producto (referencia a la tabla `unidad`)
    Categoria_Categoria_ID INT NOT NULL,            -- Categoría del producto (referencia a la tabla `categoria_producto`)
    nombre VARCHAR(40) NOT NULL,                    -- Nombre del producto
    descripcion VARCHAR(60) NOT NULL,               -- Descripción del producto
    categoria SMALLINT NOT NULL,                    -- Relación con la categoría (id de la categoría)
    unidad SMALLINT NOT NULL,                       -- Relación con la unidad (id de la unidad)
    existencia INT NOT NULL,                        -- Stock actual del producto
    bodega INT NOT NULL,                            -- Número de bodega donde se almacena el producto
    precio_venta_ac NUMERIC(10, 2) NOT NULL,        -- Precio de venta actual
    precio_venta_an NUMERIC(10, 2) NOT NULL,        -- Precio de venta anterior
    costo_venta NUMERIC(10, 2) NOT NULL,            -- Costo de venta del producto
    margen_utilidad NUMERIC NOT NULL,               -- Margen de utilidad calculado
    tiene_iva VARCHAR(1) NOT NULL CHECK (tiene_iva IN ('Y', 'N')),  -- Si el producto tiene IVA (Y=Sí, N=No)
    stock_minimo INT NOT NULL,                      -- Stock mínimo permitido
    stock_maximo INT NOT NULL,                      -- Stock máximo permitido
    estado VARCHAR(1) NOT NULL CHECK (estado IN ('A', 'I')),  -- Estado del producto (A=Activo, I=Inactivo)
    FOREIGN KEY (Unidad_Unidad_ID) REFERENCES unidad(id),            -- Relación con la tabla `unidad`
    FOREIGN KEY (Categoria_Categoria_ID) REFERENCES categoria_producto(id) -- Relación con la tabla `categoria_producto`
);

-- Tabla: Vendedor
CREATE TABLE vendedor (
    idVendedor SERIAL PRIMARY KEY,
    idUsuario INT NOT NULL,  -- Relación con la tabla `usuario`
    primerNombre VARCHAR(30) NOT NULL,
    segundoNombre VARCHAR(30),
    primerApellido VARCHAR(30) NOT NULL,
    segundoApellido VARCHAR(30),
    cargo VARCHAR(100) NOT NULL,
    FOREIGN KEY (idUsuario) REFERENCES usuario(id)
);

-- Tabla: Cotización
CREATE TABLE cotizacion (
    idCotizacion SERIAL PRIMARY KEY,
    idCliente INT NOT NULL,
    idVendedor INT NOT NULL,
    fechaCotizacion DATE NOT NULL,
    valorBruto NUMERIC(10, 2),
    valorDescuento NUMERIC(10, 2),
    valorIVA NUMERIC(10, 2),
    valorNeto NUMERIC(10, 2),
    estadoCotizacion VARCHAR(1) NOT NULL CHECK (estadoCotizacion IN ('P', 'A', 'C')), -- P=Pendiente, A=Aprobada, C=Cancelada
    FOREIGN KEY (idCliente) REFERENCES cliente(idCliente),
    FOREIGN KEY (idVendedor) REFERENCES vendedor(idVendedor)
);

-- Tabla: DetalleCotización
CREATE TABLE detalle_cotizacion (
    idDetalleCotizacion SERIAL PRIMARY KEY,
    idCotizacion INT NOT NULL,
    idProducto INT NOT NULL,
    cantidad INT NOT NULL,
    precioUnitario NUMERIC(10, 2) NOT NULL,
    valorSubtotal NUMERIC(10, 2) NOT NULL,
    FOREIGN KEY (idCotizacion) REFERENCES cotizacion(idCotizacion),
    FOREIGN KEY (idProducto) REFERENCES elemento(idElemento)
);

-- Tabla: Pedido
CREATE TABLE pedido (
    idPedido SERIAL PRIMARY KEY,
    idCliente INT NOT NULL,
    idCotizacion INT NOT NULL,
    fechaPedido DATE NOT NULL,
    estadoPedido VARCHAR(20) NOT NULL CHECK (estadoPedido IN ('Pendiente', 'En Preparación', 'Entregado', 'Cancelado')),
    totalPedido NUMERIC(10, 2) NOT NULL,
    FOREIGN KEY (idCliente) REFERENCES cliente(idCliente),
    FOREIGN KEY (idCotizacion) REFERENCES cotizacion(idCotizacion)
);

-- Tabla: DetallePedido
CREATE TABLE detalle_pedido (
    idDetallePedido SERIAL PRIMARY KEY,
    idPedido INT NOT NULL,
    idProducto INT NOT NULL,
    cantidad INT NOT NULL,
    precioUnitario NUMERIC(10, 2) NOT NULL,
    valorSubtotal NUMERIC(10, 2) NOT NULL,
    FOREIGN KEY (idPedido) REFERENCES pedido(idPedido),
    FOREIGN KEY (idProducto) REFERENCES elemento(idElemento)
);

-- Tabla: Auditoría
CREATE TABLE auditoria (
    id SERIAL PRIMARY KEY,
    fcha_auditoria TIMESTAMP NOT NULL,
    usuario_auditoria INT NOT NULL,
    accion_auditoria VARCHAR(1) NOT NULL, -- I=Insertar, U=Actualizar, D=Eliminar
    descripcion_accion VARCHAR(255),
    FOREIGN KEY (usuario_auditoria) REFERENCES usuario(id)
);

-- Tabla: Notificación
CREATE TABLE notificacion (
    idNotificacion SERIAL PRIMARY KEY,
    idCliente INT NOT NULL,
    tipoNotificacion VARCHAR(20) NOT NULL, 
    contenido VARCHAR(255) NOT NULL,
    fechaEnvio TIMESTAMP NOT NULL,
    estado VARCHAR(1) NOT NULL CHECK (estado IN ('E', 'P')), -- E = Enviado, P = Pendiente
    FOREIGN KEY (idCliente) REFERENCES cliente(idCliente)
);
";

// Ejecutar la consulta para crear las tablas
if ($conn->multi_query($sql) === TRUE) {
    echo "Tablas creadas con éxito";
} else {
    echo "Error al crear tablas: " . $conn->error;
}

// Cerrar la conexión
$conn->close();
?>
