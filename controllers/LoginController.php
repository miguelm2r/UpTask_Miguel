<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController{
    public static function login(Router $router) {
        $alertas = [];
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $auth = new Usuario($_POST);
            $alertas = $auth->validarLogin();

            if(empty($alertas)){
                // Verificar que el usuario existe
                $usuario = Usuario::where('email', $auth->email);
                if(!$usuario || !$usuario->confirmado) {
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmados');
                } else {
                    // El usuario existe
                    if (password_verify($_POST['password'], $usuario->password)){
                        // Iniciar la sesion
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        // Redireccionar
                        header('Location: /dashboard');

                    } else {
                        Usuario::setAlerta('error', 'La contraseña es incorrecta');
                    }
                }
            }
        }

        $alertas = Usuario::getAlertas();
        // Render a la vista
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesion',
            'alertas' => $alertas
        ]);
    }

    public static function logout() {
        session_start();
        $_SESSION = [];
        header('Location: /');
    }

    public static function crear(Router $router) {

        $usuario = new Usuario();
        $alertas = [];
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            if(empty($alertas)){
                $existeUsuario = Usuario::where('email', $usuario->email);
                if($existeUsuario){
                    Usuario::setAlerta('error', 'El usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                } else {
                    // Hasear el password
                    $usuario->hashPassword();

                    // Eliminar password2
                    unset($usuario->password2);

                    // Generar el token
                    $usuario->crearToken();

                    // Crear un nuevo usuario
                    $resultado = $usuario->guardar();

                    // Enviar email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarConfirmacion();
                    
                    if($resultado ?? false){
                        header('Location: /mensaje');
                    }
                }
            }
        }
        // Render a la vista
        $router->render('auth/crear', [
            'titulo' => 'Crea tu cuenta en UpTask',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router) {
        $alertas = [];
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if(empty($alertas)){
                // Buscar el usuario
                $usuario = Usuario::where('email', $usuario->email);
                if($usuario && $usuario->confirmado === "1"){
                    // Encontre el usuario
                    // Generar un nuevo token
                    $usuario->crearToken();
                    unset($usuario->password2);
                    // Actualizar el usuario
                    $usuario->guardar();
                    // Enviar el email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();
                    // Imprimir la alerta
                    $usuario::setAlerta('exito','Hemos enviado las instrucciones a tu email');
                } else {
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
                }
            }
        }

        $alertas = Usuario::getAlertas();

        // Render a la vista
        $router->render('auth/olvide', [
            'titulo' => 'Olvide mi contraseña',
            'alertas' => $alertas
        ]);

    }

    public static function reestablecer(Router $router) {
        $token = s($_GET['token']);
        $mostrar = true;
        if(!$token) header ('Location: /');

        // Identificar el usuario con este token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
            Usuario::setAlerta('error','Token no valido');
            $mostrar = false;
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            
            // Añadir el nuevo password 
            $usuario->sincronizar($_POST);

            // Validar el password
            $alertas = $usuario->validarPassword();

            if(empty($alertas)){
                // Hashear el nuevo password
                $usuario->hashPassword();
                // Eliminar el token
                $usuario->token = null;
                // Guardar el usuario
                $resultado = $usuario->guardar();
                // Redireccionar
                if($resultado) header ('Location: /');
            }
        }

        $alertas = Usuario::getAlertas();
        // Render a la vista
        $router->render('auth/reestablecer', [
            'titulo' => 'Reestablece Tu Contraseña',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }

    public static function mensaje(Router $router) {
        // Render a la vista
        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Correctamente'
        ]);
    }

    public static function confirmar(Router $router) {
        $token = s($_GET['token']);

        if(!$token) header('Location: /');

        // Encontrar al usuario
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
            // No existe el usuario con ese token
            Usuario::setAlerta('error','Token no valido');
        } else{
            // Confirmar la cuenta
            $usuario->confirmado = 1;
            unset($usuario->password2);
            $usuario->token = null;
            $usuario->guardar();

            Usuario::setAlerta('exito','Cuenta confirmada correctamente');
        }

        $alertas = Usuario::getAlertas();

        // Render a la vista
        $router->render('auth/confirmar', [
            'titulo' => 'Confirme tu cuenta UpTask',
            'alertas' => $alertas
        ]);
    }

    
}