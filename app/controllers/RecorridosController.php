<?php

class RecorridosController extends Controller {
    private function user(): int {
        $id=(int)(Usuario::current()['id']??0);
        if(!$id) throw new RuntimeException('Inicia sesión para editar tus recorridos.',401);
        return $id;
    }
    private function failure(Throwable $e): void {
        $status=(int)$e->getCode();
        if(!in_array($status,[400,401,404,409,503],true)) { error_log('Recorrido: '.$e->getMessage()); $status=500; }
        $this->json(['success'=>false,'message'=>$status===500?'No fue posible procesar el recorrido. Inténtalo de nuevo.':$e->getMessage()],$status);
    }
    public function editor(string $id): void {
        try {
            $model=new RecorridoFoto(); $user=$this->user(); $p=$model->owner((int)$id,$user);
            $data=$model->load((int)$id,$user);
            $this->renderView('recorridos/fotos',['pageTitle'=>'Editor de recorrido | INFONATEC','activePage'=>'mis-propiedades','editor'=>true,'propertyId'=>(int)$id,'tourData'=>$data,'propertyTitle'=>$p['titulo']]);
        } catch(Throwable $e) { $this->failure($e); }
    }
    public function viewer(string $id): void {
        try {
            $preview=($_GET['preview']??'')==='1';
            $data=(new RecorridoFoto())->load((int)$id,$preview?$this->user():null);
            $this->renderView('recorridos/fotos',['pageTitle'=>'Recorrido por fotografías | INFONATEC','activePage'=>'recorrido','editor'=>false,'propertyId'=>(int)$id,'tourData'=>$data,'propertyTitle'=>$data['titulo']??'Vista previa'], false);
        } catch(Throwable $e) { $this->failure($e); }
    }
    public function upload(string $id): void {
        try {
            $user=$this->user(); Security::requireCsrf();
            $scene=(new RecorridoFoto())->upload((int)$id,$user,$_FILES['foto']??[]);
            $this->json(['success'=>true,'scene'=>$scene]);
        } catch(Throwable $e) { $this->failure($e); }
    }
    public function save(string $id): void {
        try {
            $user=$this->user();
            $input=json_decode(file_get_contents('php://input'),true);
            if(!is_array($input)) throw new RuntimeException('Solicitud inválida.',400);
            Security::requireCsrf($input);
            $result=(new RecorridoFoto())->save((int)$id,$user,$input);
            $this->json(['success'=>true]+$result);
        } catch(Throwable $e) { $this->failure($e); }
    }
    public function image(string $id): void {
        try {
            $a=(new RecorridoFoto())->image((int)$id,isset(Usuario::current()['id'])?(int)Usuario::current()['id']:null);
            $root=dirname(__DIR__,2).'/storage/recorridos/';
            $path=$root.basename($a['ruta']);
            if(!is_file($path)) throw new RuntimeException('Imagen no disponible.',404);
            header('Content-Type: '.$a['mime']); header('Cache-Control: private, no-store');
            header('Content-Length: '.filesize($path));
            readfile($path); exit;
        } catch(Throwable $e) { $this->failure($e); }
    }
    public function cleanup(string $id): void {
        try {
            $user=$this->user(); $input=json_decode(file_get_contents('php://input'),true);
            if(!is_array($input)||!isset($input['version'])) throw new RuntimeException('Solicitud inválida.',400);
            Security::requireCsrf($input);
            $deleted=(new RecorridoFoto())->cleanup((int)$id,$user,(int)$input['version']);
            $this->json(['success'=>true,'deleted'=>$deleted]);
        } catch(Throwable $e) { $this->failure($e); }
    }
}
