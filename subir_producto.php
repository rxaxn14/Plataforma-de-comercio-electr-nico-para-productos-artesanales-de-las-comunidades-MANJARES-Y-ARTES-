<?php
session_start();
include('db.php'); // Conectar con la base de datos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_producto = mysqli_real_escape_string($conexion, $_POST['nombre_producto']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $precio = mysqli_real_escape_string($conexion, $_POST['precio']);
    $id_usuario = $_SESSION['usuario_id'];

    // Manejar la carga de la imagen
    $imagen = $_FILES['imagen']['name'];
    $ruta_temporal = $_FILES['imagen']['tmp_name'];
    $carpeta_destino = 'uploads/' . basename($imagen);

    if (move_uploaded_file($ruta_temporal, $carpeta_destino)) {
        // Insertar los datos del producto en la base de datos
        $sql = "INSERT INTO Producto (Nombre, Descripcion, Precio, Imagen, ID_Usuario) VALUES ('$nombre_producto', '$descripcion', '$precio', '$imagen', '$id_usuario')";
        if (mysqli_query($conexion, $sql)) {
            echo "Producto subido exitosamente.";
        } else {
            echo "Error al subir el producto: " . mysqli_error($conexion);
        }
    } else {
        echo "Error al subir la imagen.";
    }
}
?>
