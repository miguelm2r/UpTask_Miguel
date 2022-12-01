<?php

namespace Model;

class Usuario extends ActiveRecord{
    protected static $tabla = 'usuarios';
    protected static $columnasDB = ['id', 'nombre', 'email', 'password', 
    'token', 'confirmado'];
    

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->password = $args['password'] ?? '';
        $this->password2 = $args['password2'] ?? '';
        $this->password_actual = $args['password_actual'] ?? '';
        $this->password_nuevo = $args['password_nuevo'] ?? '';
        $this->token = $args['token'] ?? '0';
        $this->confirmado = $args['confirmado'] ?? 0;
    }

    // Validar el login de usuarios
    public function validarLogin(){
        if(!$this->email){
            self::$alertas['error'][] = 'El email del usuario es obligatorio';
        }

        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)){
            self::$alertas['error'][] = 'El email no es valido';
        }

        if(!$this->password){
            self::$alertas['error'][] = 'La contraseña del usuario es obligatorio';
        }

        return self::$alertas;
    }

    // Validación para cuentas nuevas
    public function validarNuevaCuenta(){
        if(!$this->nombre){
            self::$alertas['error'][] = 'El nombre del usuario es obligatorio';
        }

        if(!$this->email){
            self::$alertas['error'][] = 'El email del usuario es obligatorio';
        }

        if(!$this->password){
            self::$alertas['error'][] = 'La contraseña del usuario es obligatorio';
        }
        
        if(strlen($this->password)<6){
            self::$alertas['error'][] = 'La contraseña del usuario debe contener al menos 6 caracteres';
        }
        
        if($this->password !== $this->password2){
            self::$alertas['error'][] = 'Las contraseñas deben ser iguales';
        }

        return self::$alertas;
    }

    // Valida un email
    public function validarEmail(){
        if (!$this->email){
            self::$alertas['error'][] = 'El email es obligatorio';
        }

        if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)){
            self::$alertas['error'][] = 'El email no es valido';
        }

        return self::$alertas;
    }

    // Valida el password
    public function validarPassword(){
        if(!$this->password){
            self::$alertas['error'][] = 'La contraseña del usuario es obligatorio';
        }
        if(strlen($this->password)<6){
            self::$alertas['error'][] = 'La contraseña del usuario debe contener al menos 6 caracteres';
        }
        return self::$alertas;
    }

    public function validar_perfil() {
        if(!$this->nombre){
            self::$alertas['error'][] = 'El nombre es obligatorio';
        }
        if(!$this->email){
            self::$alertas['error'][] = 'El email es obligatorio';
        }
        return self::$alertas;
    }

    // Comprobar la contraseña
    public function comprobar_password() : bool{
        return password_verify($this->password_actual, $this->password);
    }

    public function hashPassword() : void{
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    // Generar un token
    public function crearToken() : void{
        $this->token = uniqid();
    }

    public function nuevo_password() : array{
        if(!$this->password_actual){
            self::$alertas['error'][] = 'La contraseña actual no puede ir vacia';
        }
        if(!$this->password_nuevo){
            self::$alertas['error'][] = 'La contraseña nueva no puede ir vacia';
        }
        if(strlen($this->password_nuevo) < 6){
            self::$alertas['error'][] = 'La contraseña nueva debe contener al menos 6 caracteres';
        }

        return self::$alertas;
    }
}