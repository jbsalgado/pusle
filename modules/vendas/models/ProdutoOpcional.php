<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model: Opcional / Adicional do Produto (Alimentação)
 * Tabela: prest_produto_opcionais
 *
 * @property string $id
 * @property string $produto_id
 * @property string $nome
 * @property float $valor_adicional
 * @property boolean $ativo
 *
 * @property Produto $produto
 */
class ProdutoOpcional extends ActiveRecord
{
    public static function tableName()
    {
        return 'prest_produto_opcionais';
    }

    public function rules()
    {
        return [
            [['produto_id', 'nome'], 'required'],
            [['id', 'produto_id'], 'string'],
            [['nome'], 'string', 'max' => 100],
            [['valor_adicional'], 'number', 'min' => 0],
            [['valor_adicional'], 'default', 'value' => 0.00],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'produto_id' => 'Produto',
            'nome' => 'Nome do Adicional / Opcional',
            'valor_adicional' => 'Valor Adicional (R$)',
            'ativo' => 'Ativo',
        ];
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }
}
