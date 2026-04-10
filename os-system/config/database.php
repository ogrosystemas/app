<?php
class Database {
    // Configuração para servidor de produção (Locaweb)
    private $host = "";
    private $db_name = "";  // ALTERE PARA O NOME DO SEU BANCO
    private $username = "m";        // ALTERE PARA SEU USUÁRIO
    private $password = "";          // ALTERE PARA SUA SENHA
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", 
                                  $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Em produção, não exibir erro detalhado
            error_log("Erro de conexão: " . $exception->getMessage());
            die("Erro ao conectar ao banco de dados. Contate o administrador.");
        }
        return $this->conn;
    }
}
?>