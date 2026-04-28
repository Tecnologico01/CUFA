<?php
session_start();

/* destruir la sesión completamente */
session_unset();
session_destroy();

/* redireccionar a selección de perfil */
header("Location: index.php");
exit;
?>