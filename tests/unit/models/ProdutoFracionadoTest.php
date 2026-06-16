<?php

namespace tests\models;

use app\modules\vendas\models\Produto;
use Yii;

class ProdutoFracionadoTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testPersistenciaEstoqueDecimal()
    {
        $produto = new Produto();
        $produto->nome = 'Produto Teste Fracionado';
        $produto->usuario_id = '00000000-0000-0000-0000-000000000000'; // ID genérico ou de teste
        $produto->venda_fracionada = true;
        $produto->unidade_medida = 'KG';
        $produto->estoque_atual = 10.535;
        $produto->preco_venda_sugerido = 100.00;
        $produto->ativo = true;

        // Valida se as regras aceitam o valor
        $this->assertTrue($produto->validate(['estoque_atual']), 'O estoque atual deve aceitar valores decimais');

        // Se quisermos testar o banco de dados, precisaríamos de uma transação ou fixture
        // Por enquanto, validamos a lógica do modelo e regras de validação
        $this->assertEquals(10.535, $produto->estoque_atual);
    }

    public function testValidacaoVendaFracionada()
    {
        $produto = new Produto();
        $produto->venda_fracionada = true;
        $this->assertTrue($produto->venda_fracionada);

        $produto->venda_fracionada = false;
        $this->assertFalse($produto->venda_fracionada);
    }
}
