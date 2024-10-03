<?php
session_start(); // Iniciar la sesión

// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bd_artesanias";

// Crear la conexión
$conexion = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error al conectar con la base de datos: " . $conexion->connect_error);
}

// Verificar si se ha añadido o eliminado un producto al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_carrito'])) {
        $id_producto = $_POST['id_producto'];
        $cantidad = 1; // Se agrega un producto al carrito

        // Reducir el stock en la base de datos
        $sql_update = "UPDATE producto SET stock = stock - ? WHERE id_producto = ? AND stock > 0";
        $stmt = $conexion->prepare($sql_update);
        $stmt->bind_param('ii', $cantidad, $id_producto);
        $stmt->execute();
        $stmt->close();

        // Añadir el producto al carrito (sesión)
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
        $_SESSION['carrito'][$id_producto] = isset($_SESSION['carrito'][$id_producto]) ? $_SESSION['carrito'][$id_producto] + $cantidad : $cantidad;

        // Redirigir a la sección de carrito de compras
        header("Location: Productos.php#cart");
        exit();
    }

    if (isset($_POST['eliminar_carrito'])) {
        $id_producto = $_POST['id_producto'];
        $cantidad = $_SESSION['carrito'][$id_producto];

        // Aumentar el stock en la base de datos
        $sql_update = "UPDATE producto SET stock = stock + ? WHERE id_producto = ?";
        $stmt = $conexion->prepare($sql_update);
        $stmt->bind_param('ii', $cantidad, $id_producto);
        $stmt->execute();
        $stmt->close();

        // Eliminar el producto del carrito
        unset($_SESSION['carrito'][$id_producto]);

        // Redirigir a la sección de carrito de compras
        header("Location: Productos.php#cart");
        exit();
    }
}

// Consulta SQL corregida
$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, a.ID_usuario AS id_artesano, u.nombre AS nombre_artesano, c.nombre AS comunidad
        FROM producto p
        JOIN artesano a ON p.ID_artesano = a.ID_usuario
        JOIN usuario u ON a.ID_usuario = u.id_usuario
        LEFT JOIN pertenece pe ON u.id_usuario = pe.ID_usuario
        LEFT JOIN comunidad c ON pe.ID_comunidad = c.id_comunidad";

// Ejecutar la consulta
$resultado = $conexion->query($sql);

// Verificar si la consulta fue exitosa
if (!$resultado) {
    echo "Error en la consulta SQL: " . $conexion->error;
    exit(); // Detener la ejecución si hay un error
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Tienda en Línea</title>
    <link rel="stylesheet" href="css/Productos.css">
</head>
<body>

<!-- Header -->
<header>
    <div class="logo">
        <img src="logo1.jpg" alt="Logo de Manjares y Artes">
    </div>
    <nav>
        <ul>
            <li><a href="home.php">Inicio</a></li>
            <li><a href="Productos.php">Productos</a></li>
            <li><a href="#">Nosotros</a></li>
            <li><a href="#">Contacto</a></li>
            <?php if (!isset($_SESSION['usuario_id'])): ?>
                <li><a href="login.php">Iniciar Sesión</a></li>
                <li><a href="register.php">Registrarse</a></li>
            <?php else: ?>
                <li><a href="perfil.php">Mi Perfil</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<!-- Sección de Productos -->
<div class="main-container">
    <div class="products-container">
    <div class="spacer-text" style="height: 100px; background-color: #db9e61;"></div>
        <h1>Sección de Productos</h1>
        
        <?php
        // verificar si los productos existen
        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                echo "<div class='product'>";
                $nombre_imagen = str_replace(' ', '_', $fila["nombre"]) . ".webp";  // Nombre de la imagen
                $ruta_imagen = "Imagenes/" . $nombre_imagen; // Ruta a las imágenes
                
                // Verificar si la imagen existe
                if (file_exists($ruta_imagen)) {
                    echo "<img src='" . $ruta_imagen . "' alt='" . $fila["nombre"] . "'>";
                } else {
                    echo "<img src='https://via.placeholder.com/150' alt='Imagen por defecto'>";
                }

                echo "<div class='product-info'>";
                echo "<h2>" . $fila["nombre"] . "</h2>";
                echo "<p>Descripción: " . $fila["descripcion"] . "</p>";
                echo "<p>Precio: Bs" . number_format($fila["precio"], 2) . "</p>";
                echo "<p>Stock: " . $fila["stock"] . "</p>";

                // Botón para añadir al carrito
                if ($fila["stock"] > 0) {
                    echo "<form method='POST' action=''>";
                    echo "<input type='hidden' name='id_producto' value='" . $fila["id_producto"] . "'>";
                    echo "<button type='submit' name='agregar_carrito'>Añadir al carrito</button>";
                    echo "</form>";
                } else {
                    echo "<p class='out-of-stock'>Producto agotado</p>";
                }

                // Botón para ver perfil del artesano
                echo "<p>Artesano: <a href='perfil_artesano.php?id_artesano=" . $fila["id_artesano"] . "'>Ver perfil de " . $fila["nombre_artesano"] . "</a></p>";

                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p>No se encontraron productos.</p>";
        }
        ?>
    </div>

    <!-- Carrito de compras -->
    <div class="cart-container">
    <div class="spacer-text" style="height: 100px; background-color:  #f7f3e9;"></div>
        <h2>Carrito de Compras</h2>
        <div id="cart-items">
            <?php
            if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
                $total = 0;
                echo "<table class='cart-table'>";
                echo "<thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario (Bs)</th><th>Subtotal (Bs)</th><th>Eliminar</th></tr></thead>";
                echo "<tbody>";
                foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
                    $sql = "SELECT nombre, precio FROM producto WHERE id_producto = ?";
                    $stmt = $conexion->prepare($sql);
                    $stmt->bind_param('i', $id_producto);
                    $stmt->execute();
                    $stmt->bind_result($nombre, $precio);
                    $stmt->fetch();
                    $stmt->close();

                    $subtotal = $precio * $cantidad;
                    $total += $subtotal;
                    echo "<tr>";
                    echo "<td>" . $nombre . "</td>";
                    echo "<td><input type='number' value='" . $cantidad . "' min='1' class='quantity-input'></td>";
                    echo "<td>Bs" . number_format($precio, 2) . "</td>";
                    echo "<td>Bs" . number_format($subtotal, 2) . "</td>";
                    echo "<td><form method='POST' action=''><input type='hidden' name='id_producto' value='" . $id_producto . "'><button type='submit' name='eliminar_carrito' class='delete-btn'>Eliminar</button></form></td>";
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
                echo "<p class='cart-total'>Total a pagar: <span>Bs" . number_format($total, 2) . "</span></p>";
                echo "<div class='cart-buttons'><a href='#' class='continue-shopping-btn'>Continuar Comprando</a> <a href='#' class='checkout-btn'>Finalizar Compra</a></div>";
            } else {
                echo "<p>El carrito está vacío.</p>";
            }
            ?>
        </div>
    </div>
</div>

<footer>
    © 2024 Tienda en Línea. Todos los derechos reservados.
</footer>

</body>
</html>

<?php
$conexion->close();
?>
