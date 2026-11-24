<?php
/** Uso exclusivo del administrador local, después de verificar al propietario. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (($argv[1] ?? '') !== '--identidad-verificada' || !filter_var($argv[2] ?? '', FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/habilitar_propietario.php --identidad-verificada correo\nVerifica primero la identidad de quien solicita acceso. No basta con conocer su correo.\n");
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';
$db = Database::getConnection();
if (!$db) { fwrite(STDERR, "Base de datos no disponible.\n"); exit(1); }
$stmt = $db->prepare("SELECT u.id_usuario FROM usuario u WHERE u.correo=? AND u.activo=1
    AND EXISTS (SELECT 1 FROM propiedad p WHERE p.id_propietario=u.id_usuario)
    AND NOT EXISTS (SELECT 1 FROM usuario_rol ur JOIN rol r ON r.id_rol=ur.id_rol
                    WHERE ur.id_usuario=u.id_usuario AND r.nombre='ADMIN')");
$stmt->execute([strtolower(trim($argv[2]))]);
$id = $stmt->fetchColumn();
if (!$id) { fwrite(STDERR, "No existe un propietario activo recuperable con ese correo.\n"); exit(1); }
$password = bin2hex(random_bytes(12));
$db->prepare('UPDATE usuario SET contrasena=? WHERE id_usuario=?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
echo "Acceso habilitado para la cuenta existente. Sus propiedades conservan el mismo propietario.\n";
echo "Contraseña nueva: $password\nEntrégala únicamente a la persona cuya identidad verificaste, por un canal privado.\n";
