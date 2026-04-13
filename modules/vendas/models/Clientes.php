<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "prest_clientes".
 *
 * @property string $id
 * @property string $usuario_id Chave estrangeira para a tabela de usuários, garantindo o isolamento dos dados.
 * @property string $nome_completo
 * @property string|null $cpf
 * @property string|null $telefone
 * @property string|null $email
 * @property string|null $endereco_logradouro
 * @property string|null $endereco_numero
 * @property string|null $endereco_complemento
 * @property string|null $endereco_bairro
 * @property string|null $endereco_cidade
 * @property string|null $endereco_estado
 * @property string|null $endereco_cep
 * @property string|null $ponto_referencia Informações adicionais para localizar o endereço do cliente.
 * @property string|null $observacoes
 * @property bool $ativo Indica se o cliente está ativo no sistema (para exclusão lógica).
 * @property string $data_criacao
 * @property string $data_atualizacao
 * @property string|null $regiao_id
 * @property string|null $senha_hash Hash da senha do cliente para autenticação na PWA
 *
 * @property PrestPeriodosCobranca[] $periodos
 * @property PrestCarteiraCobranca[] $prestCarteiraCobrancas
 * @property PrestHistoricoCobranca[] $prestHistoricoCobrancas
 * @property PrestOrcamentos[] $prestOrcamentos
 * @property PrestVendas[] $prestVendas
 * @property PrestRegioes $regiao
 * @property PrestUsuarios $usuario
 */
class Clientes extends \yii\db\ActiveRecord
{
    public $senha; // Campo virtual para receber senha em texto plano

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
    public static function tableName()
    {
        return 'prest_clientes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['regiao_id', 'id', 'usuario_id'], 'filter', 'filter' => function($value) { return $value === '' ? null : $value; }],
            [['id', 'usuario_id', 'tipo_pessoa', 'cnpj', 'razao_social', 'inscricao_estadual', 'indicador_ie', 'nome_responsavel', 'inscricao_municipal', 'ponto_referencia', 'observacoes', 'senha_hash', 'senha'], 'string'],
            [['tipo_pessoa'], 'default', 'value' => 'F'],
            [['indicador_ie'], 'default', 'value' => '9'],
            [['ativo'], 'default', 'value' => 1],
            [['usuario_id', 'nome_completo', 'tipo_pessoa'], 'required'],
            
            // Validações condicionais
            [['cpf'], 'required', 'when' => function($model) { return $model->tipo_pessoa === 'F'; }, 'whenClient' => "function (attribute, value) { return $('#clientes-tipo_pessoa').val() === 'F'; }"],
            [['cnpj', 'razao_social'], 'required', 'when' => function($model) { return $model->tipo_pessoa === 'J'; }, 'whenClient' => "function (attribute, value) { return $('#clientes-tipo_pessoa').val() === 'J'; }"],
            
            [['ativo'], 'boolean'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['nome_completo', 'razao_social', 'nome_responsavel'], 'string', 'max' => 150],
            [['cpf'], 'string', 'max' => 14], // Permite máscara 000.000.000-00
            [['cnpj'], 'string', 'max' => 18], // Permite máscara 00.000.000/0000-00
            [['telefone', 'endereco_numero', 'inscricao_estadual', 'inscricao_municipal'], 'string', 'max' => 20],
            [['email', 'endereco_complemento', 'endereco_bairro', 'endereco_cidade'], 'string', 'max' => 100],
            [['endereco_logradouro', 'senha_hash'], 'string', 'max' => 255],
            [['endereco_estado', 'tipo_pessoa', 'indicador_ie'], 'string', 'max' => 2],
            [['endereco_cep'], 'string', 'max' => 10], // Permite máscara 00000-000
            
            // Unicidade
            [['cpf'], 'unique', 'targetAttribute' => ['cpf', 'usuario_id'], 'message' => 'Este CPF já está cadastrado para esta loja.', 'when' => function($model) { return !empty($model->cpf); }],
            [['cnpj'], 'unique', 'targetAttribute' => ['cnpj', 'usuario_id'], 'message' => 'Este CNPJ já está cadastrado para esta loja.', 'when' => function($model) { return !empty($model->cnpj); }],
            
            [['id'], 'unique'],
            [['regiao_id'], 'exist', 'skipOnError' => true, 'skipOnEmpty' => true, 'targetClass' => Regioes::class, 'targetAttribute' => ['regiao_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuarios::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuario ID',
            'nome_completo' => 'Nome Completo',
            'cpf' => 'Cpf',
            'telefone' => 'Telefone',
            'email' => 'Email',
            'endereco_logradouro' => 'Endereco Logradouro',
            'endereco_numero' => 'Endereco Numero',
            'endereco_complemento' => 'Endereco Complemento',
            'endereco_bairro' => 'Endereco Bairro',
            'endereco_cidade' => 'Endereco Cidade',
            'endereco_estado' => 'Endereco Estado',
            'endereco_cep' => 'Endereco Cep',
            'ponto_referencia' => 'Ponto Referencia',
            'observacoes' => 'Observacoes',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
            'regiao_id' => 'Regiao ID',
            'senha' => 'Senha (acesso PWA)',
            'senha_hash' => 'Senha Hash',
            'tipo_pessoa' => 'Tipo de Pessoa',
            'cnpj' => 'CNPJ',
            'razao_social' => 'Razão Social',
            'inscricao_estadual' => 'Inscrição Estadual',
            'indicador_ie' => 'Contribuinte ICMS',
            'nome_responsavel' => 'Nome do Responsável',
            'inscricao_municipal' => 'Inscrição Municipal',
        ];
    }

    /**
     * Antes de salvar, criptografa a senha se fornecida e limpa documentos
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Limpar documentos
            if ($this->cpf) {
                $this->cpf = preg_replace('/[^0-9]/', '', $this->cpf);
            }
            if ($this->cnpj) {
                $this->cnpj = preg_replace('/[^0-9]/', '', $this->cnpj);
            }
            
            // Se senha foi fornecida, gera hash
            if (!empty($this->senha)) {
                $this->senha_hash = Yii::$app->security->generatePasswordHash($this->senha);
            }
            return true;
        }
        return false;
    }

    /**
     * Gets query for [[Periodos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestPeriodosCobrancaQuery
     */
    public function getPeriodos()
    {
        return $this->hasMany(PeriodosCobranca::class, ['id' => 'periodo_id'])->viaTable('prest_carteira_cobranca', ['cliente_id' => 'id']);
    }

    /**
     * Gets query for [[PrestCarteiraCobrancas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestCarteiraCobrancaQuery
     */
    public function getPrestCarteiraCobrancas()
    {
        return $this->hasMany(CarteiraCobranca::class, ['cliente_id' => 'id']);
    }

    /**
     * Gets query for [[PrestHistoricoCobrancas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestHistoricoCobrancaQuery
     */
    public function getPrestHistoricoCobrancas()
    {
        return $this->hasMany(HistoricoCobranca::class, ['cliente_id' => 'id']);
    }

    /**
     * Gets query for [[PrestOrcamentos]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestOrcamentosQuery
     */
    public function getPrestOrcamentos()
    {
        return $this->hasMany(Orcamento::class, ['cliente_id' => 'id']);
    }

    /**
     * Gets query for [[PrestVendas]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestVendasQuery
     */
    public function getPrestVendas()
    {
        return $this->hasMany(Venda::class, ['cliente_id' => 'id']);
    }

    /**
     * Gets query for [[Regiao]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestRegioesQuery
     */
    public function getRegiao()
    {
        return $this->hasOne(Regioes::class, ['id' => 'regiao_id']);
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery|\app\modules\vendas\query\PrestUsuariosQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuarios::class, ['id' => 'usuario_id']);
    }

    /**
     * {@inheritdoc}
     * @return \app\modules\vendas\query\PrestClientesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\vendas\query\PrestClientesQuery(get_called_class());
    }
}
