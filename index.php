<?php
/**
 * Entrada de compatibilidad para instalaciones XAMPP que todavía apuntan a la
 * carpeta del proyecto. La aplicación se ejecuta exclusivamente desde public/.
 */
header('Location: public/', true, 302);
exit;
