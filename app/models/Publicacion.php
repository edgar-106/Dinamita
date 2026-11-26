<?php

/** Gestión privada: toda consulta exige el identificador del propietario. */
class Publicacion extends Model {
    public function owned(int $ownerId, ?int $id = null): array {
        if ($this->db === null) {
            throw new RuntimeException('La base de datos no está disponible.');
        }
        $sql = "SELECT p.*, e.nombre AS estado, c.nombre AS categoria,
                       (SELECT url_imagen FROM imagen_propiedad i WHERE i.id_propiedad=p.id_propiedad
                        ORDER BY orden_visualizacion, id_imagen LIMIT 1) AS imagen
                FROM propiedad p JOIN estado_propiedad e ON e.id_estado=p.id_estado
                JOIN categoria c ON c.id_categoria=p.id_categoria WHERE p.id_propietario=?";
        $params = [$ownerId];
        if ($id !== null) {
            $sql .= ' AND p.id_propiedad=?';
            $params[] = $id;
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY p.fecha_publicacion DESC, p.id_propiedad DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene las propiedades favoritas del usuario autenticado.
     */
    public function favorites(int $ownerId): array {
        if ($this->db === null) {
            throw new RuntimeException('La base de datos no está disponible.');
        }
        $ids = [];
        $stmt = $this->db->prepare('SELECT id_propiedad FROM favorito WHERE id_usuario = ? ORDER BY fecha_registro DESC');
        $stmt->execute([$ownerId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fid) {
            $ids[] = (int) $fid;
        }
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT p.*, e.nombre AS estado, c.nombre AS categoria,
                       (SELECT url_imagen FROM imagen_propiedad i WHERE i.id_propiedad=p.id_propiedad
                        ORDER BY orden_visualizacion, i.id_imagen LIMIT 1) AS imagen
                FROM propiedad p JOIN estado_propiedad e ON e.id_estado=p.id_estado
                JOIN categoria c ON c.id_categoria=p.id_categoria WHERE p.id_propiedad IN ($placeholders)";
        $stmt = $this->db->prepare($sql . ' ORDER BY p.fecha_publicacion DESC, p.id_propiedad DESC');
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function change(int $ownerId, int $id, array $data, bool $withdraw = false): array {
        if ($this->db === null) {
            return ['success' => false, 'message' => 'La base de datos no está disponible.'];
        }
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT p.*, e.nombre AS estado FROM propiedad p
                JOIN estado_propiedad e ON e.id_estado=p.id_estado
                WHERE p.id_propiedad=? AND p.id_propietario=? FOR UPDATE');
            $stmt->execute([$id, $ownerId]);
            $property = $stmt->fetch();
            if (!$property) {
                throw new RuntimeException('La publicación no existe o no pertenece a tu cuenta.');
            }
            if ($property['fecha_retiro'] !== null || $property['estado'] !== 'DISPONIBLE') {
                throw new RuntimeException('Esta publicación ya no está disponible para cambios.');
            }
            // Una operación en curso debe resolverse con administración antes de cambiar el anuncio.
            $busy = $this->db->prepare("SELECT 1 FROM transaccion WHERE id_propiedad=? AND estado <> 'CANCELADA' LIMIT 1");
            $busy->execute([$id]);
            if ($busy->fetchColumn()) {
                throw new RuntimeException('Hay una operación asociada. Contacta a administración antes de modificar o retirar el anuncio.');
            }
            if ($withdraw) {
                $this->db->prepare('UPDATE propiedad SET fecha_retiro=NOW() WHERE id_propiedad=? AND id_propietario=?')
                    ->execute([$id, $ownerId]);
            } else {
                $title = trim((string) ($data['titulo'] ?? ''));
                $description = trim((string) ($data['descripcion'] ?? ''));
                $location = trim((string) ($data['ubicacion'] ?? ''));
                $price = filter_var($data['precio'] ?? null, FILTER_VALIDATE_FLOAT);
                $area = filter_var($data['superficie'] ?? null, FILTER_VALIDATE_FLOAT);
                $operation = (string) ($data['operacion'] ?? '');
                if (mb_strlen($title) < 5 || mb_strlen($title) > 180 || mb_strlen($description) < 20
                    || mb_strlen($location) < 4 || mb_strlen($location) > 255
                    || $price === false || $price <= 0 || !is_finite((float) $price)
                    || $area === false || $area <= 0 || !is_finite((float) $area)
                    || !in_array($operation, ['VENTA', 'RENTA'], true)) {
                    throw new RuntimeException('Revisa el título, descripción, ubicación, precio, superficie y operación.');
                }
                $rooms = null;
                $baths = null;
                if ($property['habitaciones'] !== null || $property['banos'] !== null) {
                    $rooms = filter_var($data['habitaciones'] ?? null, FILTER_VALIDATE_INT);
                    $baths = filter_var($data['banos'] ?? null, FILTER_VALIDATE_INT);
                    if ($rooms === false || $baths === false || $rooms < 0 || $rooms > 50 || $baths < 0 || $baths > 50) {
                        throw new RuntimeException('Habitaciones y baños deben ser números entre 0 y 50.');
                    }
                }
                $this->db->prepare('UPDATE propiedad SET titulo=?, descripcion=?, ubicacion=?, precio=?, superficie=?,
                    operacion=?, habitaciones=?, banos=? WHERE id_propiedad=? AND id_propietario=?')
                    ->execute([$title, $description, $location, $price, $area, $operation, $rooms, $baths, $id, $ownerId]);
            }
            $this->db->commit();
            $this->notifyChanges((int) $id, $withdraw);
            return ['success' => true, 'message' => $withdraw ? 'Publicación retirada del catálogo. Se conserva en tu historial.' : 'Cambios guardados.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if (!($e instanceof RuntimeException) || $e instanceof PDOException) {
                error_log('Error gestionando publicación: ' . $e->getMessage());
                return ['success' => false, 'message' => 'No fue posible guardar el cambio. Inténtalo nuevamente.'];
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Avisa a los usuarios que guardaron la propiedad como favorito sobre
     * los cambios realizados (edición o retiro del catálogo).
     */
    private function notifyChanges(int $propertyId, bool $withdraw): void {
        try {
            (new Notificacion())->notifyFavoriteChanges(
                $propertyId,
                $withdraw ? 'PROPIEDAD_RETIRADA' : 'PROPIEDAD_ACTUALIZADA',
                $withdraw
                    ? 'El anuncio que guardaste en favoritos fue retirado del catálogo.'
                    : 'El anuncio que guardaste en favoritos fue actualizado por su propietario.'
            );
        } catch (Throwable $e) {
            error_log('Error creando notificaciones de favoritos: ' . $e->getMessage());
        }
    }
}
