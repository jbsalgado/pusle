<?php

namespace tests\unit\models;

use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;

class ProdutoTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $categoriaId;
    private $usuarioId = '5e449fee-4486-4536-a64f-74aed38a6987'; // ID do log

    protected function _before()
    {
        // Encontra uma categoria qualquer para os testes
        $cat = Categoria::find()->one();
        if (!$cat) {
            $cat = new Categoria(['nome' => 'Teste', 'usuario_id' => $this->usuarioId]);
            $cat->save(false);
        }
        $this->categoriaId = $cat->id;
    }

    /**
     * Testa se o produto com valores padrão passa na validação (Bug fix de ponto_corte >= estoque_minimo)
     */
    public function testValidationStockDefaults()
    {
        $produto = new Produto();
        $produto->usuario_id = $this->usuarioId;
        $produto->categoria_id = $this->categoriaId;
        $produto->nome = 'Produto Teste Unitário';
        $produto->unidade_medida = 'UN';
        $produto->preco_custo = 10.00;
        $produto->preco_venda_sugerido = 20.00;
        $produto->codigo_referencia = 'UNIT-TEST-' . uniqid();

        // O salvamento deve ser bem sucedido agora que os defaults são 0 e 0
        $this->assertTrue($produto->validate(), 'O produto com valores padrão deve ser válido. Erros: ' . json_encode($produto->getErrors()));
    }

    /**
     * Testa se o somatório das variações atualiza corretamente o mestre
     */
    public function testRecalculateStockSum()
    {
        // 1. Criar Mestre
        $mestre = new Produto();
        $mestre->usuario_id = $this->usuarioId;
        $mestre->categoria_id = $this->categoriaId;
        $mestre->nome = 'Mestre Teste Sum';
        $mestre->preco_custo = 10.00;
        $mestre->preco_venda_sugerido = 20.00;
        $mestre->codigo_referencia = 'SUM-MASTER-' . uniqid();
        $mestre->estoque_atual = 0;
        $this->assertTrue($mestre->save(), 'Falha ao salvar mestre');

        // 2. Criar Variação A (Estoque 5)
        $varA = new Produto();
        $varA->parent_id = $mestre->id;
        $varA->usuario_id = $mestre->usuario_id;
        $varA->categoria_id = $mestre->categoria_id;
        $varA->nome = 'Var A';
        $varA->cor = 'Azul';
        $varA->estoque_atual = 5;
        $varA->preco_custo = 10;
        $varA->preco_venda_sugerido = 20;
        $varA->codigo_referencia = $mestre->codigo_referencia . '-A';
        $this->assertTrue($varA->save(), 'Falha ao salvar Var A: ' . json_encode($varA->getErrors()));

        // 3. Criar Variação B (Estoque 10)
        $varB = new Produto();
        $varB->parent_id = $mestre->id;
        $varB->usuario_id = $mestre->usuario_id;
        $varB->categoria_id = $mestre->categoria_id;
        $varB->nome = 'Var B';
        $varB->cor = 'Verde';
        $varB->estoque_atual = 10;
        $varB->preco_custo = 10;
        $varB->preco_venda_sugerido = 20;
        $varB->codigo_referencia = $mestre->codigo_referencia . '-B';
        $this->assertTrue($varB->save(), 'Falha ao salvar Var B');

        // 4. Recalcular
        $this->assertTrue($mestre->recalculateStockSum(), 'Falha ao executar recalculateStockSum');
        
        // 5. Validar total (5 + 10 = 15)
        $mestre->refresh();
        $this->assertEquals(15.0, (float)$mestre->estoque_atual, 'O estoque do mestre deve ser a soma das variações (15)');
        
        // Cleanup
        $varA->delete();
        $varB->delete();
        $mestre->delete();
    }
}
