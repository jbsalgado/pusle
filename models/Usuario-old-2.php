<?php

namespace app\models;

use app\models\Assinaturas;
use app\models\Modulo;
use app\models\Pagamentos;
use app\models\UsuarioModulo;
use app\modules\vendas\models\CarteiraCobranca;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\Clientes;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\Comissao;
use app\modules\vendas\models\Configuracao;
use app\modules\vendas\models\EstoqueMovimentacoes;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\HistoricoCobranca;
use app\modules\vendas\models\Orcamento;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\PeriodosCobranca;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Regioes;
use app\modules\vendas\models\RegraParcelamento;
use app\modules\vendas\models\RotaCobranca;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\Vendedor;
use Yii;

/**
 * Model class for table "prest_usuarios".
 *
 * @property string $id
 * @property string $nome
 * @property string|null $email
 * @property string $hash_senha
 * @property string $data_criacao
 * @property string $data_atualizacao
 * @property string $cpf
 * @property string $telefone
 * @property string|null $auth_key
 * @property boolean $api_de_pagamento
 * @property string|null $mercadopago_public_key
 * @property string|null $mercadopago_access_token
 * @property boolean $mercadopago_sandbox
 * @property string|null $asaas_api_key
 * @property boolean $asaas_sandbox
 * @property string $gateway_pagamento
 * @property string $catalogo_path
 */
class Usuario extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_usuarios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'auth_key', 'mercadopago_public_key', 'mercadopago_access_token', 'asaas_api_key'], 'default', 'value' => null],
            [['id', 'nome', 'hash_senha', 'cpf', 'telefone'], 'required'],
            [['id'], 'string'],
            [['data_criacao', 'data_atualizacao'], 'safe'],
            [['api_de_pagamento', 'mercadopago_sandbox', 'asaas_sandbox'], 'boolean'],
            [['api_de_pagamento'], 'default', 'value' => false],
            [['mercadopago_sandbox'], 'default', 'value' => true],
            [['asaas_sandbox'], 'default', 'value' => true],
            [['nome', 'email', 'catalogo_path'], 'string', 'max' => 100],
            [['hash_senha', 'mercadopago_public_key', 'mercadopago_access_token', 'asaas_api_key'], 'string', 'max' => 255],
            [['cpf'], 'string', 'max' => 20],
            [['telefone'], 'string', 'max' => 30],
            [['auth_key'], 'string', 'max' => 32],
            [['gateway_pagamento'], 'string', 'max' => 50],
            [['gateway_pagamento'], 'default', 'value' => 'nenhum'],
            [['gateway_pagamento'], 'in', 'range' => ['nenhum', 'mercadopago', 'asaas']],
            [['catalogo_path'], 'default', 'value' => 'catalogo'],
            [['cpf'], 'unique'],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome',
            'email' => 'Email',
            'hash_senha' => 'Hash Senha',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
            'cpf' => 'Cpf',
            'telefone' => 'Telefone',
            'auth_key' => 'Auth Key',
            'api_de_pagamento' => 'API de Pagamento',
            'mercadopago_public_key' => 'Mercado Pago - Public Key',
            'mercadopago_access_token' => 'Mercado Pago - Access Token',
            'mercadopago_sandbox' => 'Mercado Pago - Modo Sandbox',
            'asaas_api_key' => 'Asaas - API Key',
            'asaas_sandbox' => 'Asaas - Modo Sandbox',
            'gateway_pagamento' => 'Gateway de Pagamento',
            'catalogo_path' => 'Caminho do Catálogo',
        ];
    }

    /**
     * ✅ Verifica se o usuário tem API de pagamento habilitada
     */
    public function temApiPagamento()
    {
        return $this->api_de_pagamento === true;
    }

    /**
     * ✅ Verifica se o Mercado Pago está configurado
     */
    public function temMercadoPagoConfigurado()
    {
        return !empty($this->mercadopago_access_token);
    }


    /**
     * Valida a senha fornecida contra o hash armazenado
     * @param string $senha
     * @return bool
     */
    public function validatePassword($senha)
    {
        return Yii::$app->security->validatePassword($senha, $this->hash_senha);
    }
    /**
     * ✅ Verifica se o Asaas está configurado
     */
    public function temAsaasConfigurado()
    {
        return !empty($this->asaas_api_key);
    }

     public static function findByLogin($login)
    {
        return static::find()->where(['cpf' => $login])->orWhere(['email' => $login])->one();
    }

    /**
     * ✅ Retorna o gateway ativo
     */
    public function getGatewayAtivo()
    {
        if ($this->gateway_pagamento === 'mercadopago' && $this->temMercadoPagoConfigurado()) {
            return 'mercadopago';
        }
        
        if ($this->gateway_pagamento === 'asaas' && $this->temAsaasConfigurado()) {
            return 'asaas';
        }
        
        return 'nenhum';
    }

    /**
     * Gets query for [[Modulos]].
     */
    public function getModulos()
    {
        return $this->hasMany(Modulo::class, ['id' => 'modulo_id'])->viaTable('sis_usuario_modulos', ['usuario_id' => 'id']);
    }

    public function getPrestCarteiraCobrancas()
    {
        return $this->hasMany(CarteiraCobranca::class, ['usuario_id' => 'id']);
    }

    public function getPrestCategorias()
    {
        return $this->hasMany(Categoria::class, ['usuario_id' => 'id']);
    }

    public function getPrestClientes()
    {
        return $this->hasMany(Clientes::class, ['usuario_id' => 'id']);
    }

    public function getPrestColaboradores()
    {
        return $this->hasMany(Colaborador::class, ['usuario_id' => 'id']);
    }

    public function getPrestComissoes()
    {
        return $this->hasMany(Comissao::class, ['usuario_id' => 'id']);
    }

    public function getPrestConfiguracoes()
    {
        return $this->hasOne(Configuracao::class, ['usuario_id' => 'id']);
    }

    public function getPrestEstoqueMovimentacoes()
    {
        return $this->hasMany(EstoqueMovimentacoes::class, ['usuario_id' => 'id']);
    }

    public function getPrestFormasPagamentos()
    {
        return $this->hasMany(FormaPagamento::class, ['usuario_id' => 'id']);
    }

    public function getPrestHistoricoCobrancas()
    {
        return $this->hasMany(HistoricoCobranca::class, ['usuario_id' => 'id']);
    }

    public function getPrestOrcamentos()
    {
        return $this->hasMany(Orcamento::class, ['usuario_id' => 'id']);
    }

    public function getPrestParcelas()
    {
        return $this->hasMany(Parcela::class, ['usuario_id' => 'id']);
    }

    public function getPrestPeriodosCobrancas()
    {
        return $this->hasMany(PeriodosCobranca::class, ['usuario_id' => 'id']);
    }

    public function getPrestProdutos()
    {
        return $this->hasMany(Produto::class, ['usuario_id' => 'id']);
    }

    public function getPrestRegioes()
    {
        return $this->hasMany(Regioes::class, ['usuario_id' => 'id']);
    }

    public function getPrestRegrasParcelamentos()
    {
        return $this->hasMany(RegraParcelamento::class, ['usuario_id' => 'id']);
    }

    public function getPrestRotasCobrancas()
    {
        return $this->hasMany(RotaCobranca::class, ['usuario_id' => 'id']);
    }

    public function getPrestVendas()
    {
        return $this->hasMany(Venda::class, ['usuario_id' => 'id']);
    }

    public function getPrestVendedores()
    {
        return $this->hasMany(Vendedor::class, ['usuario_id' => 'id']);
    }

    public function getSisAssinaturas()
    {
        return $this->hasMany(Assinaturas::class, ['usuario_id' => 'id']);
    }

    public function getSisPagamentos()
    {
        return $this->hasMany(Pagamentos::class, ['usuario_id' => 'id']);
    }

    public function getSisUsuarioModulos()
    {
        return $this->hasMany(UsuarioModulo::class, ['usuario_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public static function find()
    {
        return new \app\modules\vendas\query\UsuariosQuery(get_called_class());
    }
}