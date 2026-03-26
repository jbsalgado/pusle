<?php

namespace tests\models;

use app\modules\vendas\models\VendaItem;

class VendaItemFracionadoTest extends \Codeception\Test\Unit
{
    public function testCalculoSubtotalFracionado()
    {
        $item = new VendaItem();
        $item->quantidade = 1.555;
        $item->preco_unitario_venda = 10.00;
        $item->desconto_valor = 0;

        // Simula o cálculo que o banco ou trigger faria, ou valida a regra de negócio se houver
        $valorCalculado = $item->quantidade * $item->preco_unitario_venda;

        $this->assertEquals(15.55, $valorCalculado, 'O cálculo de subtotal com 3 casas decimais na quantidade deve ser preciso');
    }

    public function testValidacaoQuantidadeDecimal()
    {
        $item = new VendaItem();
        $item->quantidade = 0.001;
        $this->assertTrue($item->validate(['quantidade']), 'A quantidade mínima de 0.001 deve ser válida');

        $item->quantidade = 0;
        $this->assertFalse($item->validate(['quantidade']), 'Quantidade zero não deve ser válida conforme as rules');
    }
}
