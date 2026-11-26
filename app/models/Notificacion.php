<?php

/**
 * Modelo de Notificaciones In-App.
 * Guarda los avisos de cambios (edición, retiro, compra) en las propiedades
 * que los usuarios guardaron como favoritos.
 */
class Notificacion extends Model {

    /**
     * Crea una notificación para cada usuario que tiene la propiedad como favorito.
     */
    public function notifyFavoriteChanges(int $propertyId, string $type, string $message): int {
        if ($this->db === null || $propertyId <= 0) {
            return 0;
        }
        try {
            $userIds = [];
            $stmt = $this->db->prepare('SELECT DISTINCT id_usuario FROM favorito WHERE id_propiedad = ? ORDER BY id_usuario');
            $stmt->execute([$propertyId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fid) {
                $userIds[] = (int) $fid;
            }
            if (empty($userIds)) {
                return 0;
            }

            $insert = $this->db->prepare(
                'INSERT INTO notificacion (id_usuario, id_propiedad, tipo, mensaje, leida) VALUES (?, ?, ?, ?, 0)'
            );
            $creadas = 0;
            foreach ($userIds as $uid) {
                $insert->execute([$uid, $propertyId, $type, mb_substr($message, 0, 300)]);
                $creadas++;
            }
            return $creadas;
        } catch (Exception $e) {
            error_log('Error creando notificaciones: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lista las notificaciones más recientes de un usuario autenticado,
     * uniéndolas con el título de la propiedad afectada.
     */
    public function forUser(int $userId, int $limit = 20): array {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT n.id_notificacion, n.id_usuario, n.id_propiedad, n.tipo, n.mensaje, n.leida, n.fecha_creacion,
                        p.titulo AS propiedad_titulo
                 FROM notificacion n
                 LEFT JOIN propiedad p ON p.id_propiedad = n.id_propiedad
                 WHERE n.id_usuario = ?
                 ORDER BY n.fecha_creacion DESC, n.id_notificacion DESC
                 LIMIT ?'
            );
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error consultando notificaciones: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta las notificaciones sin leer del usuario autenticado.
     */
    public function unreadCount(int $userId): int {
        if ($this->db === null || $userId <= 0) {
            return 0;
        }
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM notificacion WHERE id_usuario = ? AND leida = 0');
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Error consultando conteo de notificaciones: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Marca una notificación específica como leída.
     */
    public function markRead(int $userId, int $id): bool {
        if ($this->db === null || $userId <= 0 || $id <= 0) {
            return false;
        }
        try {
            $stmt = $this->db->prepare('UPDATE notificacion SET leida = 1 WHERE id_notificacion = ? AND id_usuario = ?');
            $stmt->execute([$id, $userId]);
            return (bool) $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Error marcando notificación como leída: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function markAllRead(int $userId): bool {
        if ($this->db === null || $userId <= 0) {
            return false;
        }
        try {
            $stmt = $this->db->prepare('UPDATE notificacion SET leida = 1 WHERE id_usuario = ? AND leida = 0');
            $stmt->execute([$userId]);
            return (bool) $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Error marcando notificaciones como leídas: ' . $e->getMessage());
            return false;
        }
    }
}
