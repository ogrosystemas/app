<?php
// NÃO coloque session_start() aqui, pois já está no config.php

class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($email, $senha) {
        $query = "SELECT * FROM usuarios WHERE email = :email AND ativo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($senha, $usuario['senha'])) {
                session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_perfil'] = $usuario['perfil'];
                $_SESSION['login_time'] = time();
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        // Verificar se a sessão expirou (8 horas)
        if(isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 28800)) {
            $this->logout();
            return false;
        }
        return isset($_SESSION['usuario_id']);
    }
    
    public function hasPermission($perfisPermitidos) {
        if(!$this->isLoggedIn()) return false;
        return in_array($_SESSION['usuario_perfil'], $perfisPermitidos);
    }
    
    public function getCurrentUser() {
        if($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['usuario_id'],
                'nome' => $_SESSION['usuario_nome'],
                'perfil' => $_SESSION['usuario_perfil']
            ];
        }
        return null;
    }
}
?>