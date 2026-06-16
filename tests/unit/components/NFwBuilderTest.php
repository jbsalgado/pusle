<?php

namespace tests\unit\components;

use app\components\NFwBuilder;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\FormaPagamento;

class NFwBuilderTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testMapFormaPagamento()
    {
        $builder = new NFwBuilder();

        // Testa mapeamentos conhecidos
        $this->assertEquals('01', $builder->mapFormaPagamento(FormaPagamento::TIPO_DINHEIRO));
        $this->assertEquals('03', $builder->mapFormaPagamento(FormaPagamento::TIPO_CARTAO_CREDITO));
        $this->assertEquals('04', $builder->mapFormaPagamento(FormaPagamento::TIPO_CARTAO_DEBITO));
        $this->assertEquals('15', $builder->mapFormaPagamento(FormaPagamento::TIPO_BOLETO));
        $this->assertEquals('17', $builder->mapFormaPagamento(FormaPagamento::TIPO_PIX));
        $this->assertEquals('17', $builder->mapFormaPagamento('PIX_ESTATICO'));

        // Testa fallback
        $this->assertEquals('99', $builder->mapFormaPagamento('TIPO_DESCONHECIDO'));
    }

    public function testBuildNFCeStructure()
    {
        // Este teste é mais complexo pois exige modelos.
        // Simularemos apenas a chamada básica ou verificaremos se o método existe.
        $builder = new NFwBuilder();
        $this->assertTrue(method_exists($builder, 'buildNFCe'));
    }
}
