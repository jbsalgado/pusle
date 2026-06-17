<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;

/**
 * ============================================================================================================
 * Model: Fornecedor
 * ============================================================================================================
 * Tabela: prest_fornecedores
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $nome_fantasia
 * @property string $razao_social
 * @property string $cnpj
 * @property string $cpf
 * @property string $inscricao_estadual
 * @property string $telefone
 * @property string $email
 * @property string $endereco
 * @property string $numero
 * @property string $complemento
 * @property string $bairro
 * @property string $cidade
 * @property string $estado
 * @property string $cep
 * @property string $observacoes
 * @property boolean $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Usuario $usuario
 * @property Compra[] $compras
 */
class Fornecedor extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_fornecedores';
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
            [['usuario_id', 'nome_fantasia'], 'required'],
            [['usuario_id'], 'string'],
            [['nome_fantasia'], 'string', 'max' => 150],
            [['razao_social'], 'string', 'max' => 255],
            [['cnpj', 'cpf'], 'string', 'max' => 18],
            [['inscricao_estadual'], 'string', 'max' => 50],
            [['telefone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['endereco'], 'string', 'max' => 255],
            [['numero'], 'string', 'max' => 20],
            [['complemento'], 'string', 'max' => 100],
            [['bairro', 'cidade'], 'string', 'max' => 100],
            [['estado'], 'string', 'max' => 2],
            [['estado'], 'match', 'pattern' => '/^[A-Z]{2}$/', 'message' => 'O Estado deve conter 2 letras maiúsculas.'],
            [['cep'], 'string', 'max' => 9],
            [['observacoes'], 'string'],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            
            // Validação: deve ter CPF ou CNPJ (pelo menos um)
            [['cpf', 'cnpj'], 'validateCpfCnpj'],
        ];
    }

    /**
     * Validação customizada: deve ter CPF ou CNPJ
     */
    public function validateCpfCnpj($attribute, $params)
    {
        $cpf = $this->cpf ? preg_replace('/[^0-9]/', '', $this->cpf) : '';
        $cnpj = $this->cnpj ? preg_replace('/[^0-9]/', '', $this->cnpj) : '';
        
        if (empty($cpf) && empty($cnpj)) {
            $this->addError('cpf', 'É necessário informar CPF ou CNPJ.');
            $this->addError('cnpj', 'É necessário informar CPF ou CNPJ.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'nome_fantasia' => 'Nome Fantasia',
            'razao_social' => 'Razão Social',
            'cnpj' => 'CNPJ',
            'cpf' => 'CPF',
            'inscricao_estadual' => 'Inscrição Estadual',
            'telefone' => 'Telefone',
            'email' => 'E-mail',
            'endereco' => 'Endereço',
            'numero' => 'Número',
            'complemento' => 'Complemento',
            'bairro' => 'Bairro',
            'cidade' => 'Cidade',
            'estado' => 'Estado (UF)',
            'cep' => 'CEP',
            'observacoes' => 'Observações',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data de Criação',
            'data_atualizacao' => 'Data de Atualização',
        ];
    }

    /**
     * Relacionamento com Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Relacionamento com Compras
     */
    public function getCompras()
    {
        return $this->hasMany(Compra::class, ['fornecedor_id' => 'id']);
    }

    /**
     * Retorna o nome completo do fornecedor (nome fantasia ou razão social)
     */
    public function getNomeCompleto()
    {
        return $this->nome_fantasia ?: $this->razao_social;
    }

    /**
     * Retorna o documento formatado (CPF ou CNPJ)
     */
    public function getDocumentoFormatado()
    {
        if ($this->cnpj) {
            $cnpj = preg_replace('/[^0-9]/', '', $this->cnpj);
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        } elseif ($this->cpf) {
            $cpf = preg_replace('/[^0-9]/', '', $this->cpf);
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }
        return '';
    }

    /**
     * Busca fornecedores ativos do usuário logado
     */
    public static function getListaDropdown($usuarioId)
    {
        return static::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true])
            ->orderBy('nome_fantasia')
            ->all();
    }

    /**
     * Retorna array para dropdown
     */
    public static function getListaDropdownArray($usuarioId)
    {
        $fornecedores = static::getListaDropdown($usuarioId);
        $lista = [];
        foreach ($fornecedores as $fornecedor) {
            $lista[$fornecedor->id] = $fornecedor->nome_fantasia;
        }
        return $lista;
    }
}

