<?php

/** Escenas planas y panorámicas horizontales. Coordenadas normalizadas [0,1]. */
class RecorridoFoto extends Model {
    private function db(): PDO {
        if (!$this->db) throw new RuntimeException('Base de datos no disponible.', 503);
        return $this->db;
    }

    public function owner(int $property, int $user): array {
        $s = $this->db()->prepare("SELECT p.* FROM propiedad p JOIN usuario u ON u.id_usuario=p.id_propietario
            WHERE p.id_propiedad=? AND p.id_propietario=? AND u.activo=1");
        $s->execute([$property, $user]);
        $row = $s->fetch();
        if (!$row) throw new RuntimeException('La propiedad no existe o no pertenece a tu cuenta.', 404);
        return $row;
    }

    public function load(int $property, ?int $owner = null): array {
        if ($owner !== null) $this->owner($property, $owner);
        $s = $this->db()->prepare("SELECT r.*, p.titulo, p.fecha_retiro, e.nombre AS estado FROM recorrido_foto r
            JOIN propiedad p ON p.id_propiedad=r.id_propiedad JOIN estado_propiedad e ON e.id_estado=p.id_estado
            WHERE r.id_propiedad=?");
        $s->execute([$property]);
        $r = $s->fetch();
        $empty = ['schema_version'=>1, 'escena_inicial_id'=>null, 'escenas'=>[], 'conexiones'=>[]];
        if (!$r && $owner !== null) return ['version'=>0, 'graph'=>$empty, 'published'=>false];
        if (!$r || ($owner === null && (!$r['publicado'] || $r['fecha_retiro'] !== null || $r['estado'] !== 'DISPONIBLE'))) {
            throw new RuntimeException('Recorrido no disponible.', 404);
        }
        return ['version'=>(int)$r['version'], 'graph'=>json_decode($owner !== null ? $r['borrador'] : $r['publicado'], true),
            'published'=>(bool)$r['publicado'], 'titulo'=>$r['titulo']];
    }

    private function ensureTour(int $property): int {
        $empty = json_encode(['schema_version'=>1,'escena_inicial_id'=>null,'escenas'=>[],'conexiones'=>[]]);
        $this->db()->prepare('INSERT IGNORE INTO recorrido_foto (id_propiedad,borrador) VALUES (?,?)')->execute([$property,$empty]);
        $s = $this->db()->prepare('SELECT id_recorrido FROM recorrido_foto WHERE id_propiedad=? FOR UPDATE');
        $s->execute([$property]);
        return (int)$s->fetchColumn();
    }

    public function upload(int $property, int $user, array $file): array {
        $this->owner($property,$user);
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Selecciona una imagen válida. Si supera el límite del servidor, reduce su tamaño.',400);
        }
        if ($file['size'] > 8*1024*1024 || $file['size'] <= 0) throw new RuntimeException('Cada fotografía debe pesar como máximo 8 MB.',400);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? null;
        $size = @getimagesize($file['tmp_name']);
        if (!$ext || !$size || $size[0]<100 || $size[1]<100 || $size[0]>24000 || $size[1]>24000 || $size[0]*$size[1]>64000000) {
            throw new RuntimeException('Usa una imagen JPG, PNG o WEBP de al menos 100 × 100 y como máximo 64 megapíxeles (24000 píxeles por lado).',400);
        }
        // Los navegadores orientan el JPEG según EXIF. Guardar las mismas
        // dimensiones visuales para validar panorámicas y colocar los puntos.
        if ($mime==='image/jpeg' && function_exists('exif_read_data')) {
            $exif=@exif_read_data($file['tmp_name'],'IFD0');
            if (in_array((int)($exif['Orientation']??1),[5,6,7,8],true)) [$size[0],$size[1]]=[$size[1],$size[0]];
        }
        $folder = dirname(__DIR__,2).'/storage/recorridos';
        if (!is_dir($folder) && !mkdir($folder,0755,true) && !is_dir($folder)) throw new RuntimeException('No se pudo guardar la imagen.',503);
        $name = bin2hex(random_bytes(20)).'.'.$ext;
        $moved = false;
        try {
            $this->db()->beginTransaction();
            $tour = $this->ensureTour($property);
            $s = $this->db()->prepare('SELECT COUNT(*) FROM recorrido_archivo WHERE id_recorrido=?');
            $s->execute([$tour]);
            if ((int)$s->fetchColumn() >= 60) throw new RuntimeException('Guarda o publica los cambios para liberar imágenes sin usar. Límite: 60 archivos por recorrido.',400);
            $moved = move_uploaded_file($file['tmp_name'],$folder.'/'.$name);
            if (!$moved) throw new RuntimeException('No se pudo guardar la imagen.',503);
            $this->db()->prepare('INSERT INTO recorrido_archivo (id_recorrido,ruta,mime,ancho,alto) VALUES (?,?,?,?,?)')->execute([$tour,$name,$mime,$size[0],$size[1]]);
            $id = (int)$this->db()->lastInsertId();
            $this->db()->commit();
            return ['id'=>$id,'nombre'=>'Nuevo espacio','tipo_visual'=>$size[0]/$size[1]>=2.5?'panorama':'imagen','imagen_url'=>BASE_URL.'recorridos/imagen/'.$id,
                'ancho'=>$size[0],'alto'=>$size[1],'video_id'=>null,'inicio_segundos'=>null,'fin_segundos'=>null];
        } catch (Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            if ($moved) @unlink($folder.'/'.$name);
            throw $e;
        }
    }

    public function save(int $property, int $user, array $input): array {
        $p = $this->owner($property,$user);
        $action = $input['accion'] ?? 'guardar';
        if (!in_array($action,['guardar','publicar','ocultar'],true)) throw new RuntimeException('Acción inválida.',400);
        if ($action==='publicar' && $p['fecha_retiro']!==null) throw new RuntimeException('No puedes publicar un recorrido de un anuncio retirado.',400);
        $this->db()->beginTransaction();
        try {
            $tour = $this->ensureTour($property);
            $q = $this->db()->prepare('SELECT * FROM recorrido_foto WHERE id_recorrido=?'); $q->execute([$tour]); $r=$q->fetch();
            if (!isset($input['version']) || (int)$input['version'] !== (int)$r['version']) throw new RuntimeException('Otra pestaña guardó cambios. Recarga el editor antes de continuar.',409);
            $graph = $this->validate($tour,$input['graph'] ?? [],$action==='publicar');
            $encoded = json_encode($graph,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $published = $action==='publicar' ? $encoded : ($action==='ocultar' ? null : $r['publicado']);
            $this->db()->prepare('UPDATE recorrido_foto SET borrador=?,publicado=?,version=version+1 WHERE id_recorrido=?')->execute([$encoded,$published,$tour]);
            $this->db()->commit();
            // No borrar archivos aquí: otra pestaña podría tener una carga aún no guardada.
            return ['version'=>(int)$r['version']+1,'graph'=>$graph,'published'=>$published!==null];
        } catch (Throwable $e) {
            if ($this->db()->inTransaction()) $this->db()->rollBack();
            throw $e;
        }
    }

    private function validate(int $tour, $graph, bool $publish): array {
        if(!is_array($graph)) throw new RuntimeException('El formato del recorrido es inválido.',400);
        $scenes=$graph['escenas']??[]; $links=$graph['conexiones']??[];
        if (!is_array($scenes)||!is_array($links)||count($scenes)>30||count($links)>150) throw new RuntimeException('Máximo 30 escenas y 150 conexiones.',400);
        $q=$this->db()->prepare('SELECT * FROM recorrido_archivo WHERE id_recorrido=?'); $q->execute([$tour]);
        $assets=[]; foreach($q->fetchAll() as $a) $assets[(int)$a['id_archivo']]=$a;
        $clean=[]; $ids=[];
        foreach($scenes as $i=>$s) {
            $id=(int)($s['id']??0); $name=trim((string)($s['nombre']??'')); $type=$s['tipo_visual']??'imagen';
            if(!isset($assets[$id])||isset($ids[$id])||mb_strlen($name)<1||mb_strlen($name)>80||!in_array($type,['imagen','panorama'],true)) throw new RuntimeException('Revisa los nombres, tipos y fotografías de las escenas.',400);
            $a=$assets[$id];
            $angle=(int)($s['angulo_horizontal']??180);
            if(!in_array($angle,[180,270,360],true)) throw new RuntimeException('Selecciona una cobertura de 180, 270 o 360 grados.',400);
            if($type==='panorama' && $a['ancho']/$a['alto']<1.5) throw new RuntimeException('Una panorámica horizontal debe ser al menos 1.5 veces más ancha que alta.',400);
            // Los campos de video están reservados; este módulo no acepta enlaces de video arbitrarios.
            if(!empty($s['video_id'])) throw new RuntimeException('La integración de escenas de video aún no está habilitada.',400);
            $ids[$id]=true;
            $clean[]=['id'=>$id,'nombre'=>$name,'tipo_visual'=>$type,'imagen_url'=>BASE_URL.'recorridos/imagen/'.$id,
                'ancho'=>(int)$a['ancho'],'alto'=>(int)$a['alto'],'angulo_horizontal'=>$angle,'orden'=>$i,'video_id'=>null,'inicio_segundos'=>null,'fin_segundos'=>null];
        }
        $start=(int)($graph['escena_inicial_id']??0);
        if($clean && !isset($ids[$start])) throw new RuntimeException('Selecciona la escena inicial.',400);
        $connections=[];
        foreach($links as $l) {
            $from=(int)($l['escena_origen_id']??0); $to=(int)($l['escena_destino_id']??0); $text=trim((string)($l['etiqueta']??''));
            $x=filter_var($l['x']??null,FILTER_VALIDATE_FLOAT); $y=filter_var($l['y']??null,FILTER_VALIDATE_FLOAT);
            if(!isset($ids[$from],$ids[$to])||$from===$to||mb_strlen($text)<1||mb_strlen($text)>60||$x===false||$y===false||!is_finite((float)$x)||!is_finite((float)$y)||$x<0||$x>1||$y<0||$y>1) throw new RuntimeException('Hay una conexión inválida. Elige otra escena y una posición dentro de la foto.',400);
            $connections[]=['id'=>count($connections)+1,'escena_origen_id'=>$from,'escena_destino_id'=>$to,'etiqueta'=>$text,'coordenadas'=>'imagen_normalizada','x'=>(float)$x,'y'=>(float)$y];
        }
        if($publish) {
            if(!$clean) throw new RuntimeException('Agrega al menos una fotografía.',400);
            $seen=[$start=>true]; do {$before=count($seen); foreach($connections as $l) if(isset($seen[$l['escena_origen_id']])) $seen[$l['escena_destino_id']]=true;} while(count($seen)>$before);
            if(count($seen)!==count($ids)) throw new RuntimeException('Conecta todos los espacios desde la escena inicial antes de publicar.',400);
        }
        return ['schema_version'=>1,'escena_inicial_id'=>$clean?$start:null,'escenas'=>$clean,'conexiones'=>$connections];
    }

    public function image(int $id, ?int $user): array {
        $q=$this->db()->prepare('SELECT a.*,r.id_propiedad,p.id_propietario FROM recorrido_archivo a JOIN recorrido_foto r ON r.id_recorrido=a.id_recorrido JOIN propiedad p ON p.id_propiedad=r.id_propiedad WHERE a.id_archivo=?');
        $q->execute([$id]); $a=$q->fetch();
        if(!$a) throw new RuntimeException('Imagen no disponible.',404);
        if((int)$a['id_propietario']===$user) $this->owner((int)$a['id_propiedad'],$user);
        if((int)$a['id_propietario']!==$user) {
            $r=$this->load((int)$a['id_propiedad']);
            if(!in_array($id,array_column($r['graph']['escenas'],'id'),true)) throw new RuntimeException('Imagen no disponible.',404);
        }
        return $a;
    }

    public function cleanup(int $property, int $user, int $version): int {
        $this->owner($property,$user);
        $this->db()->beginTransaction();
        $paths=[];
        try {
            $tour=$this->ensureTour($property);
            $q=$this->db()->prepare('SELECT * FROM recorrido_foto WHERE id_recorrido=?'); $q->execute([$tour]); $r=$q->fetch();
            if((int)$r['version']!==$version) throw new RuntimeException('Otra pestaña guardó cambios. Recarga antes de limpiar.',409);
            $used=[];
            foreach(['borrador','publicado'] as $field) foreach((json_decode($r[$field]??'null',true)['escenas']??[]) as $s) $used[(int)$s['id']]=true;
            $q=$this->db()->prepare('SELECT * FROM recorrido_archivo WHERE id_recorrido=?'); $q->execute([$tour]);
            foreach($q->fetchAll() as $a) if(!isset($used[(int)$a['id_archivo']])) {
                $this->db()->prepare('DELETE FROM recorrido_archivo WHERE id_archivo=?')->execute([$a['id_archivo']]); $paths[]=$a['ruta'];
            }
            $this->db()->commit();
        } catch(Throwable $e) { if($this->db()->inTransaction()) $this->db()->rollBack(); throw $e; }
        foreach($paths as $path) @unlink(dirname(__DIR__,2).'/storage/recorridos/'.basename($path));
        return count($paths);
    }
}
