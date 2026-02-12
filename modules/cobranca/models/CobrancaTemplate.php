<?php

namespace app\modules\cobranca\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use app\models\Usuario;

/**
 * Model: CobrancaTemplate
 * 
 * Templates de mensagens para cobranças
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $tipo (ANTES, DIA, APOS)
 * @property string $titulo
 * @property string $mensagem
 * @property boolean $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 */
class CobrancaTemplate extends ActiveRecord
{
    const TIPO_ANTES = 'ANTES';
    const TIPO_DIA = 'DIA';
    const TIPO_APOS = 'APOS';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_cobranca_template';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_criacao',
                'updatedAtAttribute' => 'data_atualizacao',
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
            [['usuario_id', 'tipo', 'titulo', 'mensagem'], 'required'],
            [['usuario_id', 'tipo'], 'string'],
            [['mensagem'], 'string'],
            [['titulo'], 'string', 'max' => 100],
            [['ativo'], 'boolean'],
            [['tipo'], 'in', 'range' => [self::TIPO_ANTES, self::TIPO_DIA, self::TIPO_APOS]],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],

            // Defaults
            [['ativo'], 'default', 'value' => true],
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
            'tipo' => 'Tipo',
            'titulo' => 'Título',
            'mensagem' => 'Mensagem',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relação com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Lista de tipos disponíveis
     */
    public static function getTipos()
    {
        return [
            self::TIPO_ANTES => '3 Dias Antes do Vencimento',
            self::TIPO_DIA => 'Dia do Vencimento',
            self::TIPO_APOS => 'Após Vencimento',
        ];
    }

    /**
     * Retorna o nome do tipo
     */
    public function getTipoNome()
    {
        $tipos = self::getTipos();
        return $tipos[$this->tipo] ?? $this->tipo;
    }

    /**
     * Variáveis disponíveis para substituição
     */
    public static function getVariaveisDisponiveis()
    {
        return [
            '{nome}' => 'Nome do cliente',
            '{valor}' => 'Valor da parcela',
            '{vencimento}' => 'Data de vencimento',
            '{parcela}' => 'Número da parcela',
            '{empresa}' => 'Nome da empresa',
        ];
    }

    /**
     * Substitui variáveis no template
     */
    public function substituirVariaveis($parcela, $cliente)
    {
        $variaveis = [
            '{nome}' => $cliente->nome ?? 'Cliente',
            '{valor}' => number_format($parcela->valor_parcela, 2, ',', '.'),
            '{vencimento}' => Yii::$app->formatter->asDate($parcela->data_vencimento),
            '{parcela}' => $parcela->numero_parcela . '/' . $parcela->venda->numero_parcelas,
            '{empresa}' => Yii::$app->name,
        ];

        return str_replace(array_keys($variaveis), array_values($variaveis), $this->mensagem);
    }
}
