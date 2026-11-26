<?php

class PublicacionesController extends Controller {
    private function user(): array {
        $user = Usuario::current();
        if (empty($user['id'])) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $this->json(['success' => false, 'message' => 'Inicia sesión para administrar tus publicaciones.'], 401);
            }
            $this->renderView('cuenta/propiedades', ['pageTitle' => 'Mis propiedades | INFONATEC', 'activePage' => 'mis-propiedades', 'owner' => null, 'listings' => []]);
            exit;
        }
        return $user;
    }

    public function index(): void {
        $user = $this->user();
        $error = '';
        $listings = [];
        $favorites = [];
        try {
            $ownerId = (int) $user['id'];
            $listings = (new Publicacion())->owned($ownerId);
            $favorites = (new Publicacion())->favorites($ownerId);
        } catch (Throwable $e) {
            error_log('Error cargando publicaciones: ' . $e->getMessage());
            $error = 'No fue posible cargar tus propiedades. Inténtalo de nuevo más tarde.';
        }
        $flash = $_SESSION['publication_flash'] ?? null;
        unset($_SESSION['publication_flash']);
        $this->renderView('cuenta/propiedades', ['pageTitle' => 'Mis propiedades | INFONATEC', 'activePage' => 'mis-propiedades', 'owner' => $user, 'listings' => $listings, 'favorites' => $favorites, 'error' => $error, 'flash' => $flash]);
    }

    public function edit(string $id): void {
        $user = $this->user();
        $items = (new Publicacion())->owned((int) $user['id'], (int) $id);
        if (!ctype_digit($id) || empty($items)) {
            http_response_code(404);
            $this->renderView('cuenta/propiedades', ['pageTitle' => 'Publicación no encontrada', 'activePage' => 'mis-propiedades', 'owner' => $user, 'listings' => [], 'error' => 'La publicación no existe o no pertenece a tu cuenta.']);
            return;
        }
        $flash = $_SESSION['publication_flash'] ?? null;
        unset($_SESSION['publication_flash']);
        $this->renderView('cuenta/editar', ['pageTitle' => 'Editar propiedad | INFONATEC', 'activePage' => 'mis-propiedades', 'listing' => $items[0], 'flash' => $flash]);
    }

    public function update(string $id): void { $this->save($id, false); }
    public function withdraw(string $id): void { $this->save($id, true); }

    private function save(string $id, bool $withdraw): void {
        $user = $this->user();
        Security::requireCsrf();
        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->json(['success' => false, 'message' => 'Publicación inválida.'], 400);
        }
        $result = (new Publicacion())->change((int) $user['id'], (int) $id, $_POST, $withdraw);
        if (str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            $this->json($result, $result['success'] ? 200 : 400);
        }
        $_SESSION['publication_flash'] = $result;
        // Conservar el formulario ante un error de validación, sin guardar tokens en sesión.
        if (!$withdraw && !$result['success']) {
            $_SESSION['publication_old_input'] = array_intersect_key($_POST, array_flip(['titulo','descripcion','ubicacion','precio','superficie','operacion','habitaciones','banos']));
        }
        $this->redirect(!$withdraw && !$result['success'] ? 'mis-propiedades/' . (int) $id . '/editar' : 'mis-propiedades');
    }
}
