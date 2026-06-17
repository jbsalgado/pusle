<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Venda;

/**
 * Model para a tabela prest_cupons_fiscais
 * 
 * @property string $id
 * @property string $venda_id
 * @property string $usuario_id
 * @property integer $numero
 * @property integer $serie
 * @property string $modelo
 * @property string $chave_acesso
 * @property string $xml_path
 * @property string $pdf_path
 * @property string $status
 * @property integer $ambiente
 * @property string $mensagem_retorno
 * @property string $data_emissao
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Venda $venda
 * @property Usuario $usuario
 */
class CupomFiscal extends ActiveRecord
{
    const STATUS_PENDENTE = 'PENDENTE';
    const STATUS_AUTORIZADA = 'AUTORIZADA';
    const STATUS_CANCELADA = 'CANCELADA';
    const STATUS_ERRO = 'ERRO';

    const AMBIENTE_PRODUCAO = 1;
    const AMBIENTE_HOMOLOGACAO = 2;

    const MODELO_NFE = '55';
    const MODELO_NFCE = '65';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_cupons_fiscais';
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
            [['venda_id', 'usuario_id'], 'required'],
            [['numero', 'serie', 'ambiente'], 'integer'],
            [['venda_id', 'usuario_id', 'status', 'modelo'], 'string'],
            [['chave_acesso'], 'string', 'max' => 44],
            [['xml_path', 'pdf_path', 'mensagem_retorno'], 'string'],
            [['data_emissao'], 'safe'],
            [['venda_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venda::class, 'targetAttribute' => ['venda_id' => 'id']],
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
            'venda_id' => 'Venda',
            'usuario_id' => 'Usuário',
            'numero' => 'Número',
            'serie' => 'Série',
            'modelo' => 'Modelo',
            'chave_acesso' => 'Chave de Acesso',
            'xml_path' => 'Caminho XML',
            'pdf_path' => 'Caminho PDF',
            'status' => 'Status',
            'ambiente' => 'Ambiente',
            'mensagem_retorno' => 'Mensagem de Retorno',
            'data_emissao' => 'Data de Emissão',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVenda()
    {
        return $this->hasOne(Venda::class, ['id' => 'venda_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
