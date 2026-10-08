<?php
/**
 * Controlador de Autenticación (Login, Registro, Logout)
 */

class AuthController extends Controller {

    public function login(): void {
        // Devuelve JSON al formulario asíncrono y evita procesar GET como login.
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
        // El modelo valida campos, guarda la sesión y responde el resultado.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuarioModel = new Usuario();
            $result = $usuarioModel->register($_POST);

            $this->json($result);
        }
        $this->redirect('');
    }

    public function logout(): void {
        // La sesión es el único estado de autenticación del lado del servidor.
        Usuario::logout();
        $this->redirect('');
    }
}
