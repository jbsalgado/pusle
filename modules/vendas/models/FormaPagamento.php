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
    /**
     * Constantes para tipos de pagamento
     */
    const TIPO_DINHEIRO = 'DINHEIRO';
    const TIPO_PIX = 'PIX'; // PIX Dinâmico (com gateway)
    const TIPO_PIX_ESTATICO = 'PIX_ESTATICO'; // PIX Estático (QR Code fixo)
    const TIPO_CARTAO = 'CARTAO';
    const TIPO_CARTAO_CREDITO = 'CARTAO_CREDITO';
    const TIPO_CARTAO_DEBITO = 'CARTAO_DEBITO';
    const TIPO_BOLETO = 'BOLETO';
    const TIPO_TRANSFERENCIA = 'TRANSFERENCIA';
    const TIPO_CHEQUE = 'CHEQUE';
    const TIPO_OUTRO = 'OUTRO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_formas_pagamento';
    }

    /**
     * ✅ MÉTODO behaviors() ADICIONADO
     * Configuração do TimestampBehavior para data_criacao
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_criacao',
                'updatedAtAttribute' => false, // Não há campo de atualização nesta tabela
                'value' => new Expression('NOW()'),
            ],
        ];
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
            // ✅ VALORES DEFAULT ADICIONADOS (conforme SQL: ativo DEFAULT true, aceita_parcelamento DEFAULT false)
            [['ativo'], 'default', 'value' => true],
            [['aceita_parcelamento'], 'default', 'value' => false],
            [['nome'], 'string', 'max' => 100],
            [['tipo'], 'string', 'max' => 30], // Aumentado para suportar PIX_ESTATICO
            // ✅ TIPOS DE PAGAMENTO EXPANDIDOS
            [['tipo'], 'in', 'range' => [
                self::TIPO_DINHEIRO,
                self::TIPO_PIX,
                self::TIPO_PIX_ESTATICO,
                self::TIPO_CARTAO,
                self::TIPO_CARTAO_CREDITO,
                self::TIPO_CARTAO_DEBITO,
                self::TIPO_BOLETO,
                self::TIPO_TRANSFERENCIA,
                self::TIPO_CHEQUE,
                self::TIPO_OUTRO
            ]],
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

    /**
     * ✅ MÉTODO NOVO: Retorna array com os tipos de pagamento disponíveis
     */
    public static function getTiposList()
    {
        return [
            self::TIPO_DINHEIRO => 'Dinheiro',
            self::TIPO_PIX => 'PIX (Dinâmico)',
            self::TIPO_PIX_ESTATICO => 'PIX Estático',
            self::TIPO_CARTAO => 'Cartão',
            self::TIPO_CARTAO_CREDITO => 'Cartão de Crédito',
            self::TIPO_CARTAO_DEBITO => 'Cartão de Débito',
            self::TIPO_BOLETO => 'Boleto',
            self::TIPO_TRANSFERENCIA => 'Transferência Bancária',
            self::TIPO_CHEQUE => 'Cheque',
            self::TIPO_OUTRO => 'Outro',
        ];
    }

    /**
     * ✅ MÉTODO NOVO: Retorna o nome formatado do tipo
     */
    public function getTipoNome()
    {
        $tipos = self::getTiposList();
        return $tipos[$this->tipo] ?? $this->tipo;
    }

    /**
     * ✅ MÉTODO NOVO: Verifica se a forma de pagamento está ativa
     */
    public function isAtivo()
    {
        return $this->ativo === true;
    }

    /**
     * ✅ MÉTODO NOVO: Verifica se aceita parcelamento
     */
    public function aceitaParcelamento()
    {
        return $this->aceita_parcelamento === true;
    }

    /**
     * Relação com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relação com Parcelas
     */
    public function getParcelas()
    {
        return $this->hasMany(Parcela::class, ['forma_pagamento_id' => 'id']);
    }

    /**
     * ✅ MÉTODO APRIMORADO: Retorna formas ativas para dropdown
     * Agora com opção de filtrar apenas formas que aceitam parcelamento
     */
    public static function getListaDropdown($usuarioId = null, $apenasQueAceitamParcelamento = false)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        $query = self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true]);

        // ✅ NOVO FILTRO: permite filtrar apenas formas que aceitam parcelamento
        if ($apenasQueAceitamParcelamento) {
            $query->andWhere(['aceita_parcelamento' => true]);
        }

        return $query->select(['nome', 'id'])
            ->indexBy('id')
            ->orderBy(['nome' => SORT_ASC])
            ->column();
    }
}