<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/console.php';
new yii\console\Application($config);

echo "=== TESTANDO ISOLAMENTO E ACESSO PRESTANISTA ===\n\n";

// 1. Testa helpers no model Usuario com mocks de Colaborador
echo "1. Testes de flags no Colaborador e métodos de Usuario:\n";

$colabVendedor = new \app\modules\vendas\models\Colaborador([
    'id' => 'colab-vendedor-1',
    'nome_completo' => 'Vendedor Teste',
    'eh_vendedor' => true,
    'eh_cobrador' => false,
    'eh_administrador' => false,
]);

$colabCobrador = new \app\modules\vendas\models\Colaborador([
    'id' => 'colab-cobrador-2',
    'nome_completo' => 'Cobrador Teste',
    'eh_vendedor' => false,
    'eh_cobrador' => true,
    'eh_administrador' => false,
]);

$colabHibrido = new \app\modules\vendas\models\Colaborador([
    'id' => 'colab-hibrido-3',
    'nome_completo' => 'Híbrido Teste',
    'eh_vendedor' => true,
    'eh_cobrador' => true,
    'eh_administrador' => false,
]);

assert($colabVendedor->eh_vendedor === true && !$colabVendedor->eh_cobrador);
assert($colabCobrador->eh_cobrador === true && !$colabCobrador->eh_vendedor);
assert($colabHibrido->eh_vendedor === true && $colabHibrido->eh_cobrador === true);
echo "✓ Flags de colaborador validadas com sucesso.\n";

// Mock de Usuário para testar métodos de checagem de perfil
$userVendedor = new class extends \app\models\Usuario {
    public $colabMock;
    public function getColaborador() {
        return $this->colabMock;
    }
};
$userVendedor->colabMock = $colabVendedor;
$userVendedor->eh_dono_loja = false;
$userVendedor->is_admin = false;

$userCobrador = new class extends \app\models\Usuario {
    public $colabMock;
    public function getColaborador() {
        return $this->colabMock;
    }
};
$userCobrador->colabMock = $colabCobrador;
$userCobrador->eh_dono_loja = false;
$userCobrador->is_admin = false;

$userHibrido = new class extends \app\models\Usuario {
    public $colabMock;
    public function getColaborador() {
        return $this->colabMock;
    }
};
$userHibrido->colabMock = $colabHibrido;
$userHibrido->eh_dono_loja = false;
$userHibrido->is_admin = false;

$userDono = new \app\models\Usuario([
    'eh_dono_loja' => true,
    'is_admin' => false,
]);

assert($userVendedor->isVendedorAmbulante() === true, "userVendedor deve ser vendedor ambulante");
assert($userVendedor->isCobradorRua() === false, "userVendedor NÃO deve ser cobrador de rua");
assert($userVendedor->isColaboradorHibrido() === false, "userVendedor NÃO deve ser híbrido");

assert($userCobrador->isCobradorRua() === true, "userCobrador deve ser cobrador de rua");
assert($userCobrador->isVendedorAmbulante() === false, "userCobrador NÃO deve ser vendedor ambulante");
assert($userCobrador->isColaboradorHibrido() === false, "userCobrador NÃO deve ser híbrido");

assert($userHibrido->isVendedorAmbulante() === true, "userHibrido deve ser vendedor");
assert($userHibrido->isCobradorRua() === true, "userHibrido deve ser cobrador");
assert($userHibrido->isColaboradorHibrido() === true, "userHibrido DEVE ser híbrido");

assert($userDono->isGestorPrestanista() === true, "Dono deve ser gestor prestanista");
assert($userDono->isVendedorAmbulante() === false, "Dono sem vínculo colab não é vendedor ambulante");
echo "✓ Métodos de validação de papéis em Usuario validados com sucesso.\n\n";

// 2. Testa existência de controllers e views
echo "2. Testes de integridade estrutural:\n";
assert(class_exists('\app\modules\prestanista\controllers\CampoController'), "CampoController deve existir");
assert(class_exists('\app\modules\prestanista\controllers\VendedorController'), "VendedorController deve existir");
assert(class_exists('\app\modules\prestanista\controllers\CobradorController'), "CobradorController deve existir");
assert(file_exists(__DIR__ . '/../modules/prestanista/views/campo/index.php'), "View campo/index.php deve existir");
assert(file_exists(__DIR__ . '/../modules/prestanista/views/vendedor/index.php'), "View vendedor/index.php deve existir");
assert(file_exists(__DIR__ . '/../modules/prestanista/views/cobrador/index.php'), "View cobrador/index.php deve existir");
assert(file_exists(__DIR__ . '/../modules/prestanista/views/equipe/index.php'), "View equipe/index.php deve existir");
echo "✓ Todos os Controllers e Views existem e estão acessíveis.\n\n";

// 3. Validação do código de redirecionamento no AuthController
echo "3. Validação de redirecionamento pós-login no AuthController:\n";
$authContent = file_get_contents(__DIR__ . '/../controllers/AuthController.php');
assert(strpos($authContent, 'isColaboradorHibrido') !== false, "AuthController deve verificar isColaboradorHibrido");
assert(strpos($authContent, '/prestanista/campo/index') !== false, "AuthController deve redirecionar híbrido para /prestanista/campo/index");
assert(strpos($authContent, '/prestanista/vendedor/index') !== false, "AuthController deve redirecionar vendedor para /prestanista/vendedor/index");
assert(strpos($authContent, '/prestanista/cobrador/index') !== false, "AuthController deve redirecionar cobrador para /prestanista/cobrador/index");
echo "✓ Redirecionamentos pós-login verificados no AuthController.\n\n";

// 4. Validação de regras de isolamento no Module.php
echo "4. Validação de regras de isolamento no Module.php:\n";
$moduleContent = file_get_contents(__DIR__ . '/../modules/prestanista/Module.php');
assert(strpos($moduleContent, 'isVendedorAmbulante') !== false, "Module deve verificar isVendedorAmbulante");
assert(strpos($moduleContent, 'isCobradorRua') !== false, "Module deve verificar isCobradorRua");
assert(strpos($moduleContent, "Seu perfil de acesso é exclusivo para Cobrança de Rua.") !== false, "Bloqueio de vendedor em cobrador presente");
assert(strpos($moduleContent, "Seu perfil de acesso é exclusivo para Vendas Ambulantes.") !== false, "Bloqueio de cobrador em vendedor presente");
assert(strpos($moduleContent, "Acesso restrito ao administrador da loja.") !== false, "Bloqueio de painel administrativo presente");
echo "✓ Regras de isolamento no Module.php verificadas com sucesso.\n\n";

echo "========================================================\n";
echo "✓ TODOS OS TESTES PASSARAM COM SUCESSO!\n";
echo "========================================================\n";
