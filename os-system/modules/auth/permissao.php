<?php
class Permissao {
    private $conn;
    private $perfil;
    
    public function __construct($db, $perfil) {
        $this->conn = $db;
        $this->perfil = $perfil;
    }
    
    public function temPermissao($modulo, $acao = 'ver') {
        if($this->perfil == 'admin') {
            return true;
        }
        
        $query = "SELECT COUNT(*) FROM permissoes WHERE perfil = :perfil AND modulo = :modulo AND acao = :acao";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':perfil' => $this->perfil,
            ':modulo' => $modulo,
            ':acao' => $acao
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function getMenusPermitidos() {
        // Caminho base sem barra no final
        $baseUrl = '';
        
        $menus = [
            'dashboard' => ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'url' => $baseUrl . '/index.php'],
            'pdv' => ['icon' => 'bi-cash-stack', 'label' => 'PDV / Caixa', 'url' => $baseUrl . '/modules/pdv/pdv.php'],
            'os' => ['icon' => 'bi-wrench', 'label' => 'Ordens de Serviço', 'url' => $baseUrl . '/modules/os/os.php'],
            'orcamentos' => ['icon' => 'bi-file-text', 'label' => 'Orçamentos', 'url' => $baseUrl . '/modules/orcamentos/orcamentos.php'],
            'produtos' => ['icon' => 'bi-box', 'label' => 'Produtos', 'url' => $baseUrl . '/modules/produtos/produtos.php'],
            'clientes' => ['icon' => 'bi-people', 'label' => 'Clientes', 'url' => $baseUrl . '/modules/clientes/clientes.php'],
            'servicos' => ['icon' => 'bi-tools', 'label' => 'Serviços', 'url' => $baseUrl . '/modules/servicos/servicos.php'],
            'estoque' => ['icon' => 'bi-database', 'label' => 'Estoque', 'url' => $baseUrl . '/modules/estoque/estoque.php'],
            'relatorios' => ['icon' => 'bi-graph-up', 'label' => 'Relatórios', 'url' => $baseUrl . '/modules/relatorios/relatorios.php'],
            'usuarios' => ['icon' => 'bi-person-badge', 'label' => 'Usuários', 'url' => $baseUrl . '/modules/usuarios/usuarios.php']
        ];
        
        $menusPermitidos = [];
        foreach($menus as $key => $menu) {
            if($this->temPermissao($key, 'ver')) {
                $menusPermitidos[] = $menu;
            }
        }
        
        return $menusPermitidos;
    }
}
?>