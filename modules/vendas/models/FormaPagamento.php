<?php
namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Parcela;

/**
 * ============================================================================================================
 * Model: FormaPagamento
 * ============================================================================================================
 * Tabela: prest_formas_pagamento
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $nome
 * @property string $tipo
 * @property boolean $ativo
 * @property boolean $aceita_parcelamento
 * @property string $data_criacao
 * 
 * @property Usuario $usuario
 * @property Parcela[] $parcelas
 */
class FormaPagamento extends ActiveRecord
{
    const TIPO_DINHEIRO = 'DINHEIRO';
    const TIPO_PIX = 'PIX';
    const TIPO_CARTAO = 'CARTAO';
    const TIPO_BOLETO = 'BOLETO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_formas_pagamento';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'nome', 'tipo'], 'required'],
            [['usuario_id'], 'string'],
            [['ativo', 'aceita_parcelamento'], 'boolean'],
            [['nome'], 'string', 'max' => 100],
            [['tipo'], 'string', 'max' => 20],
            [['tipo'], 'in', 'range' => [self::TIPO_DINHEIRO, self::TIPO_PIX, self::TIPO_CARTAO, self::TIPO_BOLETO]],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'nome' => 'Nome',
            'tipo' => 'Tipo',
            'ativo' => 'Ativo',
            'aceita_parcelamento' => 'Aceita Parcelamento',
            'data_criacao' => 'Data de Criação',
        ];
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getParcelas()
    {
        return $this->hasMany(Parcela::class, ['forma_pagamento_id' => 'id']);
    }

    /**
     * Retorna formas ativas para dropdown
     */
    public static function getListaDropdown($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        return self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->select(['nome', 'id'])
            ->indexBy('id')
            ->orderBy(['nome' => SORT_ASC])
            ->column();
    }
}