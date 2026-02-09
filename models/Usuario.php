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
use app\components\JwtHelper;
use Yii;
// ✅ IMPORTAÇÕES ADICIONADAS
use yii\web\IdentityInterface;
use yii\base\NotSupportedException;

/**
 * Model class for table "prest_usuarios".
 *
 * (Propriedades @property... omitidas por brevidade)
 *
 */
// ✅ CLASSE AGORA IMPLEMENTA A INTERFACE
class Usuario extends \yii\db\ActiveRecord implements IdentityInterface
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
            [['email', 'auth_key', 'mercadopago_public_key', 'mercadopago_access_token', 'mp_access_token', 'mp_refresh_token', 'mp_public_key', 'mp_user_id', 'mp_token_expiration', 'asaas_api_key', 'blocked_at', 'confirmed_at'], 'default', 'value' => null],
            [['id', 'nome', 'hash_senha', 'cpf', 'telefone', 'username'], 'required'],
            [['id'], 'string'],
            [['data_criacao', 'data_atualizacao', 'blocked_at', 'confirmed_at', 'mp_token_expiration'], 'safe'],
            [['api_de_pagamento', 'mercadopago_sandbox', 'asaas_sandbox', 'eh_dono_loja'], 'boolean'],
            [['api_de_pagamento'], 'default', 'value' => false],
            [['eh_dono_loja'], 'default', 'value' => false],
            [['mercadopago_sandbox'], 'default', 'value' => true],
            [['asaas_sandbox'], 'default', 'value' => true],
            [['nome', 'email', 'catalogo_path'], 'string', 'max' => 100],
            [['username'], 'string', 'max' => 50],
            [['username'], 'unique'],
            [['hash_senha', 'mercadopago_public_key', 'mercadopago_access_token', 'mp_access_token', 'mp_refresh_token', 'mp_public_key', 'mp_user_id', 'asaas_api_key'], 'string', 'max' => 255],
            [['cpf'], 'string', 'max' => 20],
            [['telefone'], 'string', 'max' => 30],
            [['auth_key'], 'string', 'max' => 32],
            [['gateway_pagamento'], 'string', 'max' => 50],
            [['gateway_pagamento'], 'default', 'value' => 'nenhum'],
            [['gateway_pagamento'], 'in', 'range' => ['nenhum', 'mercadopago', 'asaas']],
            [['catalogo_path'], 'default', 'value' => 'catalogo'],
            // Campos de endereço
            [['endereco'], 'string', 'max' => 255],
            [['bairro', 'cidade'], 'string', 'max' => 100],
            [['estado'], 'string', 'max' => 2],
            [['logo_path'], 'string', 'max' => 500],
            [['cpf'], 'unique'],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        // ... (attributeLabels originais)
        return [
            'id' => 'ID',
            'nome' => 'Nome',
            'username' => 'Usuário',
            'email' => 'Email',
            'hash_senha' => 'Hash Senha',
            'data_criacao' => 'Data Criacao',
            'data_atualizacao' => 'Data Atualizacao',
            'cpf' => 'Cpf',
            'telefone' => 'Telefone',
            'auth_key' => 'Auth Key',
            'eh_dono_loja' => 'É Dono da Loja',
            'blocked_at' => 'Bloqueado em',
            'confirmed_at' => 'Confirmado em',
            'api_de_pagamento' => 'API de Pagamento',
            'mercadopago_public_key' => 'Mercado Pago - Public Key',
            'mercadopago_access_token' => 'Mercado Pago - Access Token',
            'mp_access_token' => 'MP OAuth - Access Token (seller)',
            'mp_refresh_token' => 'MP OAuth - Refresh Token',
            'mp_public_key' => 'MP OAuth - Public Key',
            'mp_user_id' => 'MP OAuth - User ID',
            'mp_token_expiration' => 'MP OAuth - Expiração do Token',
            'mercadopago_sandbox' => 'Mercado Pago - Modo Sandbox',
            'asaas_api_key' => 'Asaas - API Key',
            'asaas_sandbox' => 'Asaas - Modo Sandbox',
            'gateway_pagamento' => 'Gateway de Pagamento',
            'catalogo_path' => 'Caminho do Catálogo',
        ];
    }

    // ===================================================================
    // ✅ INÍCIO: MÉTODOS DA IDENTITYINTERFACE
    // ===================================================================

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        $usuario = static::findOne(['id' => $id]);
        if ($usuario) {
            // Converte valores boolean do PostgreSQL após ler do banco
            $usuario->converterBooleanFields();
        }
        return $usuario;
    }

    /**
     * Converte valores boolean do PostgreSQL para PHP boolean
     * PostgreSQL pode retornar 't'/'f' como string
     */
    protected function converterBooleanFields()
    {
        // Converte eh_dono_loja
        if (property_exists($this, 'eh_dono_loja') && isset($this->eh_dono_loja)) {
            $valorOriginal = $this->eh_dono_loja;

            if (is_string($valorOriginal)) {
                $this->eh_dono_loja = (strtolower(trim($valorOriginal)) === 't' || strtolower(trim($valorOriginal)) === 'true' || $valorOriginal === '1');
                Yii::info("🔍 converterBooleanFields - eh_dono_loja convertido de '{$valorOriginal}' (string) para " . ($this->eh_dono_loja ? 'true' : 'false'), __METHOD__);
            } elseif ($valorOriginal === 1 || $valorOriginal === '1') {
                $this->eh_dono_loja = true;
                Yii::info("🔍 converterBooleanFields - eh_dono_loja convertido de {$valorOriginal} (int) para true", __METHOD__);
            } elseif ($valorOriginal === 0 || $valorOriginal === '0' || $valorOriginal === false) {
                $this->eh_dono_loja = false;
            } elseif ($valorOriginal === true) {
                $this->eh_dono_loja = true;
            } else {
                // Se não conseguir converter, assume false
                $this->eh_dono_loja = false;
                Yii::warning("⚠️ converterBooleanFields - Não conseguiu converter eh_dono_loja. Valor original: " . var_export($valorOriginal, true) . " (tipo: " . gettype($valorOriginal) . ")", __METHOD__);
            }
        }

        // Converte outros campos boolean também
        $booleanFields = ['api_de_pagamento', 'mercadopago_sandbox', 'asaas_sandbox'];
        foreach ($booleanFields as $field) {
            if (property_exists($this, $field) && isset($this->$field)) {
                if (is_string($this->$field)) {
                    $this->$field = (strtolower(trim($this->$field)) === 't' || strtolower(trim($this->$field)) === 'true' || $this->$field === '1');
                } elseif ($this->$field === 1) {
                    $this->$field = true;
                } elseif ($this->$field === 0) {
                    $this->$field = false;
                }
            }
        }
    }

    /**
     * Hook afterFind para converter boolean fields
     * Este método é chamado automaticamente pelo Yii2 após ler um registro do banco
     */
    public function afterFind()
    {
        parent::afterFind();
        $this->converterBooleanFields();
    }

    /**
     * Override __get para garantir conversão ao acessar o atributo
     * Isso garante que mesmo se afterFind não for chamado, a conversão acontece
     */
    public function __get($name)
    {
        $value = parent::__get($name);

        // Se for um campo boolean e ainda não foi convertido, converte
        if (in_array($name, ['eh_dono_loja', 'api_de_pagamento', 'mercadopago_sandbox', 'asaas_sandbox'])) {
            if (is_string($value)) {
                $converted = (strtolower(trim($value)) === 't' || strtolower(trim($value)) === 'true' || $value === '1');
                // Atualiza o valor no objeto para não precisar converter novamente
                $this->$name = $converted;
                Yii::info("🔍 __get - Campo {$name} convertido de '{$value}' (string) para " . ($converted ? 'true' : 'false'), __METHOD__);
                return $converted;
            } elseif ($value === 1 || $value === '1') {
                $this->$name = true;
                return true;
            } elseif ($value === 0 || $value === '0' || $value === false) {
                $this->$name = false;
                return false;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $secret = Yii::$app->request->cookieValidationKey;
        $payload = JwtHelper::decode($token, $secret);

        if ($payload && isset($payload['sub'])) {
            return static::findIdentity($payload['sub']);
        }

        return null;
    }

    /**
     * Gera um Token JWT para o usuário
     * @return string
     */
    public function generateJwt()
    {
        $secret = Yii::$app->request->cookieValidationKey;
        $payload = [
            'sub' => $this->id,
            'username' => $this->username,
            'exp' => time() + (3600 * 24 * 30), // 30 dias de expiração
            'iat' => time(),
        ];

        return JwtHelper::encode($payload, $secret);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    // ===================================================================
    // ✅ FIM: MÉTODOS DA IDENTITYINTERFACE
    // ===================================================================


    // ===================================================================
    // ✅ INÍCIO: HELPERS DE SENHA E AUTH_KEY
    // ===================================================================

    /**
     * Gera hash da senha e armazena em 'hash_senha'
     * @param string $senha
     */
    public function setPassword($senha)
    {
        $this->hash_senha = Yii::$app->security->generatePasswordHash($senha);
    }

    /**
     * Gera um novo "auth key" para o login "lembrar-me"
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
    
    // ===================================================================
    // ✅ FIM: HELPERS DE SENHA E AUTH_KEY
    // ===================================================================


    /**
     * Valida a senha fornecida contra o hash armazenado
     * @param string $senha
     * @return bool
     */
    public function validatePassword($senha)
    {
        return Yii::$app->security->validatePassword($senha, $this->hash_senha);
    }

    public static function findByLogin($login)
    {
        // Busca por username, email ou CPF
        $usuario = static::find()
            ->where(['username' => $login])
            ->orWhere(['email' => $login])
            ->orWhere(['cpf' => $login])
            ->one();

        if ($usuario) {
            // Converte valores boolean do PostgreSQL após ler do banco
            $usuario->converterBooleanFields();
        }

        return $usuario;
    }

    /**
     * Verifica se o usuário é dono da loja
     * @return bool
     */
    public function isDonoLoja()
    {
        return $this->eh_dono_loja === true;
    }

    /**
     * Verifica se o usuário está bloqueado
     * @return bool
     */
    public function isBlocked()
    {
        return $this->blocked_at !== null;
    }

    /**
     * Verifica se o usuário está confirmado
     * @return bool
     */
    public function isConfirmed()
    {
        return $this->confirmed_at !== null;
    }

    /**
     * Bloqueia o usuário
     */
    public function bloquear()
    {
        $this->blocked_at = date('Y-m-d H:i:s');
        return $this->save(false);
    }

    /**
     * Desbloqueia o usuário
     */
    public function desbloquear()
    {
        $this->blocked_at = null;
        return $this->save(false);
    }

    /**
     * Confirma o email do usuário
     */
    public function confirmar()
    {
        if ($this->confirmed_at === null) {
            $this->confirmed_at = date('Y-m-d H:i:s');
            return $this->save(false);
        }
        return true;
    }
    
    // ... (Restante do código original de Usuario.php)

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
        return !empty($this->mp_access_token) || !empty($this->mercadopago_access_token);
    }

    /**
     * ✅ Verifica se o Asaas está configurado
     */
    public function temAsaasConfigurado()
    {
        return !empty($this->asaas_api_key);
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
     * Retorna primeiro nome
     * @return string
     */
    public function getPrimeiroNome()
    {
        $palavras = explode(' ', trim($this->nome));
        return $palavras[0] ?? '';
    }

    public function getIniciais()
    {
        $palavras = explode(' ', trim($this->nome));
        $iniciais = '';

        if (isset($palavras[0])) {
            $iniciais .= mb_substr($palavras[0], 0, 1);
        }

        if (count($palavras) > 1) {
            $iniciais .= mb_substr(end($palavras), 0, 1);
        }

        return strtoupper($iniciais);
    }
    /**
     * Gets query for [[Modulos]].
     */
    public function getModulos()
    {
        return $this->hasMany(Modulo::class, ['id' => 'modulo_id'])->viaTable('sis_usuario_modulos', ['usuario_id' => 'id']);
    }

    // ... (Restante das relações ...Query(get_called_class());
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
