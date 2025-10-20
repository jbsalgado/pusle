<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\modules\vendas\models\Venda;

use yii\behaviors\TimestampBehavior;
use app\modules\vendas\models\Regiao;
use app\models\Usuario;
use app\modules\vendas\models\CarteiraCobranca;


/**
 * ============================================================================================================
 * Model: Cliente
 * ============================================================================================================
 * Tabela: prest_clientes
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $regiao_id
 * @property string $nome_completo
 * @property string $cpf
 * @property string $telefone
 * @property string $email
 * @property string $endereco_logradouro
 * @property string $endereco_numero
 * @property string $endereco_complemento
 * @property string $endereco_bairro
 * @property string $endereco_cidade
 * @property string $endereco_estado
 * @property string $endereco_cep
 * @property string $ponto_referencia
 * @property string $observacoes
 * @property boolean $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 * @property Regiao $regiao
 * @property Venda[] $vendas
 * @property CarteiraCobranca[] $carteirasCobranca
 */
class Cliente extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_clientes';
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
            [['usuario_id', 'nome_completo'], 'required'],
            [['usuario_id', 'regiao_id'], 'string'],
            [['observacoes', 'ponto_referencia'], 'string'],
            [['ativo'], 'boolean'],
            [['nome_completo'], 'string', 'max' => 150],
            [['cpf'], 'string', 'max' => 11],
            [['cpf'], 'match', 'pattern' => '/^[0-9]{11}$/'],
            [['cpf'], 'unique'],
            [['telefone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['endereco_logradouro'], 'string', 'max' => 255],
            [['endereco_numero'], 'string', 'max' => 20],
            [['endereco_complemento', 'endereco_bairro', 'endereco_cidade'], 'string', 'max' => 100],
            [['endereco_estado'], 'string', 'max' => 2],
            [['endereco_cep'], 'string', 'max' => 8],
            [['endereco_cep'], 'match', 'pattern' => '/^[0-9]{8}$/'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['regiao_id'], 'exist', 'skipOnError' => true, 'targetClass' => Regiao::class, 'targetAttribute' => ['regiao_id' => 'id']],
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
            'regiao_id' => 'Região',
            'nome_completo' => 'Nome Completo',
            'cpf' => 'CPF',
            'telefone' => 'Telefone',
            'email' => 'E-mail',
            'endereco_logradouro' => 'Logradouro',
            'endereco_numero' => 'Número',
            'endereco_complemento' => 'Complemento',
            'endereco_bairro' => 'Bairro',
            'endereco_cidade' => 'Cidade',
            'endereco_estado' => 'Estado',
            'endereco_cep' => 'CEP',
            'ponto_referencia' => 'Ponto de Referência',
            'observacoes' => 'Observações',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data de Cadastro',
            'data_atualizacao' => 'Última Atualização',
        ];
    }

    /**
     * Retorna endereço completo formatado
     */
    public function getEnderecoCompleto()
    {
        $partes = array_filter([
            trim($this->endereco_logradouro . ' ' . $this->endereco_numero),
            $this->endereco_complemento,
            $this->endereco_bairro,
            $this->endereco_cidade,
            $this->endereco_estado,
            $this->endereco_cep ? $this->formatarCep($this->endereco_cep) : null
        ]);
        
        return implode(', ', $partes);
    }

    /**
     * Formata CEP
     */
    public function formatarCep($cep)
    {
        return preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $cep);
    }

    /**
     * Formata CPF
     */
    public function getCpfFormatado()
    {
        if (!$this->cpf) return '';
        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $this->cpf);
    }

    /**
     * Formata telefone
     */
    public function getTelefoneFormatado()
    {
        if (!$this->telefone) return '';
        $telefone = preg_replace('/[^0-9]/', '', $this->telefone);
        
        if (strlen($telefone) == 11) {
            return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) == 10) {
            return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $telefone);
        }
        
        return $this->telefone;
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getRegiao()
    {
        return $this->hasOne(Regiao::class, ['id' => 'regiao_id']);
    }

    public function getVendas()
    {
        return $this->hasMany(Venda::class, ['cliente_id' => 'id']);
    }

    public function getCarteirasCobranca()
    {
        return $this->hasMany(CarteiraCobranca::class, ['cliente_id' => 'id']);
    }

    /**
     * Retorna clientes ativos para dropdown
     */
    public static function getListaDropdown($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        return self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->select(['nome_completo', 'id'])
            ->indexBy('id')
            ->orderBy(['nome_completo' => SORT_ASC])
            ->column();
    }
}