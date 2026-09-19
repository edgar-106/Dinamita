<?php
/**
 * Controlador de Autenticación (Login, Registro, Logout)
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController extends Controller {

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $usuarioModel = new Usuario();
            $result = $usuarioModel->login($email, $password);

            $this->json($result);
        }
        $this->redirect('');
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuarioModel = new Usuario();
            $result = $usuarioModel->register($_POST);

            $this->json($result);
        }
        $this->redirect('');
    }

    public function logout(): void {
        Usuario::logout();
        $this->redirect('');
    }
}
