<?php
class Auth {

    public static function iniciarSesion(array $usuario): void {
        session_regenerate_id(true);
        $_SESSION['user_id']     = $usuario['id'];
        $_SESSION['user_email']  = $usuario['email'];
        $_SESSION['user_rol']    = $usuario['rol'];
        $_SESSION['estudio_id']  = $usuario['estudio_id'] ?? null;
        $_SESSION['empresa_id']  = $usuario['empresa_id'] ?? null;
        $_SESSION['primer_login']= $usuario['primer_login'];
        $_SESSION['user_nombre'] = $usuario['nombre'] ?? '';
    }

    public static function cerrarSesion(): void {
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    public static function estaLogueado(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function usuario(): ?array {
        if (!self::estaLogueado()) return null;
        return [
            'id'          => $_SESSION['user_id'],
            'email'       => $_SESSION['user_email'],
            'rol'         => $_SESSION['user_rol'],
            'estudio_id'  => $_SESSION['estudio_id'],
            'empresa_id'  => $_SESSION['empresa_id'],
            'primer_login'=> $_SESSION['primer_login'],
            'nombre'      => $_SESSION['user_nombre'],
        ];
    }

    public static function rol(): ?string {
        return $_SESSION['user_rol'] ?? null;
    }

    public static function requerirRol(string ...$roles): void {
        if (!self::estaLogueado()) {
            header('Location: /login.php');
            exit;
        }
        if (!in_array(self::rol(), $roles)) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verificarPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function generarPasswordTemporal(): string {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $pass = '';
        for ($i = 0; $i < 10; $i++) {
            $pass .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $pass;
    }
}
