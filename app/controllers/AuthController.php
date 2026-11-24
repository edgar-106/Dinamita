<?php
/**
 * Controlador de Autenticación (Login, Registro, Logout)
 */

class AuthController extends Controller {

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::requireCsrf();

            $email = (string) ($_POST['email'] ?? '');
            $password = (string) ($_POST['password'] ?? '');

            $usuarioModel = new Usuario();
            $result = $usuarioModel->login($email, $password);
            $status = (int) ($result['code'] ?? ($result['success'] ? 200 : 401));
            unset($result['code']);

            $this->json($result, $status);
        }
        $this->redirect('');
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::requireCsrf();

            $usuarioModel = new Usuario();
            $result = $usuarioModel->register($_POST);
            $status = (int) ($result['code'] ?? ($result['success'] ? 200 : 400));
            unset($result['code']);

            $this->json($result, $status);
        }
        $this->redirect('');
    }

    public function logout(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::requireCsrf();
            Usuario::logout();

            if (Security::isAjaxRequest()) {
                $this->json(['success' => true]);
            }
            $this->redirect('');
        }

        // Evita cierre de sesión CSRF por petición GET sin validación.
        $this->redirect('');
    }
}
