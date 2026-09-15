<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\web\UploadedFile;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;

/* ============================================================================================================
 * Model: Configuracao
 * ============================================================================================================
 * Tabela: prest_configuracoes
 * 
 * @property string $id
 * @property string $usuario_id
 * @property string $nome_loja
 * @property string $logo_path
 * @property string $cor_primaria
 * @property string $cor_secundaria
 * @property boolean $catalogo_publico
 * @property boolean $aceita_orcamentos
 * @property string $whatsapp
 * @property string $instagram
 * @property string $facebook
 * @property string $endereco_completo
 * @property string $mensagem_boas_vindas
 * @property string $pix_chave
 * @property string $pix_nome
 * @property string $pix_cidade
 * @property boolean $imprimir_automatico
 * @property string $certificado_pfx
 * @property string $certificado_senha
 * @property string $cnpj
 * @property integer $crt
 * @property string $ie
 * @property string $nfce_csc
 * @property string $nfce_csc_id
 * @property integer $nfe_ambiente
 * @property string $razao_social
 * @property string $segmento
 * @property boolean $modulo_food_service
 * @property integer $nfe_serie
 * @property integer $nfe_numero_atual
 * @property string $faturador_ml_tipo
 * @property string $nfe_webhook_token
 * @property string $nfe_sistema_externo
 * @property string $ibge_municipio
 * @property string $uf_sigla
 * @property string $cnae
 * @property string $data_criacao
 * @property string $data_atualizacao
 * 
 * @property Usuario $usuario
 */
class Configuracao extends ActiveRecord
{
    // ---- Constantes de faturador / modo de emissão fiscal ----
    const FATURADOR_PULSE_ERP       = 'PULSE_ERP';       // Pulse emite via NFePHP + SEFAZ
    const FATURADOR_MERCADO_LIVRE   = 'MERCADO_LIVRE';   // Faturador nativo do Mercado Livre
    const FATURADOR_SISTEMA_EXTERNO = 'SISTEMA_EXTERNO'; // ERP/contador/Omie/Bling externo

    /**
     * @var UploadedFile|null Atributo virtual para o upload do certificado .pfx
     */
    public $certificado_arquivo;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_configuracoes';
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
            [['usuario_id'], 'required'],
            [['usuario_id', 'razao_social', 'cnpj', 'ie', 'nfce_csc', 'nfce_csc_id', 'certificado_pfx', 'certificado_senha'], 'string'],
            [['catalogo_publico', 'aceita_orcamentos', 'imprimir_automatico', 'modulo_food_service'], 'boolean'],
            [['endereco_completo', 'mensagem_boas_vindas'], 'string'],
            [['nome_loja'], 'string', 'max' => 150],
            [['logo_path'], 'string', 'max' => 500],
            [['cor_primaria', 'cor_secundaria'], 'string', 'max' => 7],
            [['cor_primaria', 'cor_secundaria'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/'],
            [['whatsapp'], 'string', 'max' => 20],
            [['instagram', 'facebook'], 'string', 'max' => 100],
            [['pix_chave'], 'string', 'max' => 100],
            [['pix_nome'], 'string', 'max' => 100],
            [['pix_cidade'], 'string', 'max' => 50],
            [['crt', 'nfe_ambiente', 'nfe_serie', 'nfe_numero_atual'], 'integer'],
            [['faturador_ml_tipo'], 'string', 'max' => 30],
            [['faturador_ml_tipo'], 'in', 'range' => [
                self::FATURADOR_PULSE_ERP,
                self::FATURADOR_MERCADO_LIVRE,
                self::FATURADOR_SISTEMA_EXTERNO,
            ]],
            [['nfe_webhook_token'], 'string', 'max' => 128],
            [['nfe_sistema_externo'], 'string', 'max' => 100],
            [['ibge_municipio'], 'string', 'max' => 7],
            [['uf_sigla'], 'string', 'max' => 2],
            [['cnae'], 'string', 'max' => 10],
            [['segmento'], 'string', 'max' => 30],
            [['usuario_id'], 'unique'],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['certificado_arquivo'], 'file', 'extensions' => 'pfx', 'skipOnEmpty' => true],
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
            'nome_loja' => 'Nome da Loja',
            'logo_path' => 'Logo',
            'cor_primaria' => 'Cor Primária',
            'cor_secundaria' => 'Cor Secundária',
            'catalogo_publico' => 'Catálogo Público',
            'aceita_orcamentos' => 'Aceita Orçamentos',
            'whatsapp' => 'WhatsApp',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'endereco_completo' => 'Endereço Completo',
            'mensagem_boas_vindas' => 'Mensagem de Boas-Vindas',
            'pix_chave' => 'Chave PIX',
            'pix_nome' => 'Nome do Recebedor PIX',
            'pix_cidade' => 'Cidade do Recebedor PIX',
            'razao_social' => 'Razão Social',
            'cnpj' => 'CNPJ',
            'ie' => 'Inscrição Estadual',
            'crt' => 'Regime Tributário (CRT: 1=Simples Nacional/MEI)',
            'nfe_ambiente' => 'Ambiente NFe/NFCe (1=Produção, 2=Homologação)',
            'nfce_csc' => 'Token CSC (NFCe)',
            'nfce_csc_id' => 'ID CSC (NFCe)',
            'certificado_pfx' => 'Certificado Digital (PFX)',
            'certificado_senha' => 'Senha do Certificado',
            'nfe_serie' => 'Série da NF-e (Modelo 55)',
            'nfe_numero_atual' => 'Último Número Emitido (NF-e 55)',
            'faturador_ml_tipo'   => 'Modo de Emissão Fiscal (PULSE_ERP | MERCADO_LIVRE | SISTEMA_EXTERNO)',
            'nfe_webhook_token'   => 'Token de Autenticação para Callback Fiscal Externo',
            'nfe_sistema_externo' => 'Nome do Sistema Externo de Emissão Fiscal',
            'ibge_municipio'      => 'Código IBGE do Município (7 dígitos)',
            'uf_sigla'            => 'UF (Estado)',
            'cnae'                => 'CNAE Fiscal',
            'imprimir_automatico' => 'Impressão Automática (Térmica)',
            'data_criacao'        => 'Data de Criação',
            'data_atualizacao'    => 'Última Atualização',
        ];
    }

    /**
     * Retorna a chave de criptografia de certificados e credenciais fiscais
     */
    public static function getFiscalEncryptionKey(): string
    {
        $key = getenv('APP_FISCAL_ENCRYPTION_KEY') ?: ($_ENV['APP_FISCAL_ENCRYPTION_KEY'] ?? null);
        if (!empty($key)) {
            return $key;
        }
        if (!empty(Yii::$app->params['cookieValidationKey'])) {
            return Yii::$app->params['cookieValidationKey'];
        }
        return 'pulse-fiscal-master-encryption-key-2026';
    }

    /**
     * Criptografa o certificado PFX e a senha em repouso no PostgreSQL antes de salvar
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $key = self::getFiscalEncryptionKey();

        // 1. Processamento de novo arquivo de certificado (.pfx) enviado via upload
        if ($this->certificado_arquivo instanceof UploadedFile) {
            $pfxRaw = file_get_contents($this->certificado_arquivo->tempName);
            if ($pfxRaw !== false && strlen($pfxRaw) > 0) {
                // Valida se a senha fornecida abre o certificado
                $senhaParaValidar = $this->certificado_senha;
                if (!empty($senhaParaValidar)) {
                    $certs = [];
                    if (!@openssl_pkcs12_read($pfxRaw, $certs, $senhaParaValidar)) {
                        $this->addError('certificado_senha', 'A senha informada não é válida para este arquivo de Certificado Digital (.pfx).');
                        return false;
                    }
                }

                // Criptografa o binário do certificado
                $encryptedCert = Yii::$app->security->encryptByKey($pfxRaw, $key);
                $this->certificado_pfx = base64_encode($encryptedCert);
            }
        } elseif ($this->isAttributeChanged('certificado_pfx') && !empty($this->certificado_pfx)) {
            // Se o atributo certificado_pfx foi setado como string direta
            $raw = base64_decode($this->certificado_pfx);
            if ($raw !== false) {
                $decrypted = Yii::$app->security->decryptByKey($raw, $key);
                if ($decrypted === false) {
                    // Não estava criptografado com a chave atual; criptografa agora
                    $encryptedCert = Yii::$app->security->encryptByKey($raw, $key);
                    $this->certificado_pfx = base64_encode($encryptedCert);
                }
            }
        }

        // 2. Criptografa a senha se alterada e não vazia
        if ($this->isAttributeChanged('certificado_senha') && !empty($this->certificado_senha)) {
            $rawSenha = base64_decode($this->certificado_senha, true);
            $decryptedSenha = ($rawSenha !== false) ? Yii::$app->security->decryptByKey($rawSenha, $key) : false;
            if ($decryptedSenha === false) {
                // Estava em texto plano! Criptografa
                $encryptedSenha = Yii::$app->security->encryptByKey($this->certificado_senha, $key);
                $this->certificado_senha = base64_encode($encryptedSenha);
            }
        }

        return true;
    }

    /**
     * Retorna o binário descriptografado do Certificado Digital A1 (.pfx)
     */
    public function getCertificadoBinarioDescriptografado(): ?string
    {
        if (empty($this->certificado_pfx)) {
            return null;
        }

        $key = self::getFiscalEncryptionKey();
        $raw = base64_decode($this->certificado_pfx);
        if ($raw === false) {
            return null;
        }

        $decrypted = Yii::$app->security->decryptByKey($raw, $key);
        if ($decrypted !== false) {
            return $decrypted;
        }

        // Fallback para certificados legados não cifrados
        return $raw;
    }

    /**
     * Retorna a senha descriptografada do Certificado Digital A1
     */
    public function getCertificadoSenhaDescriptografada(): ?string
    {
        if (empty($this->certificado_senha)) {
            return null;
        }

        $key = self::getFiscalEncryptionKey();
        $raw = base64_decode($this->certificado_senha, true);
        if ($raw !== false) {
            $decrypted = Yii::$app->security->decryptByKey($raw, $key);
            if ($decrypted !== false) {
                return $decrypted;
            }
        }

        // Fallback para senhas legadas em texto plano
        return $this->certificado_senha;
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    /**
     * Verifica se a loja está configurada para emissão fiscal pelo próprio Pulse ERP
     */
    public function isPulseErp(): bool
    {
        return $this->faturador_ml_tipo === self::FATURADOR_PULSE_ERP
            || empty($this->faturador_ml_tipo);
    }

    /**
     * Verifica se a loja usa o faturador nativo do Mercado Livre
     */
    public function isFaturadorML(): bool
    {
        return $this->faturador_ml_tipo === self::FATURADOR_MERCADO_LIVRE;
    }

    /**
     * Verifica se a loja usa um sistema externo para emissão de NF-e
     */
    public function isFaturadorExterno(): bool
    {
        return $this->faturador_ml_tipo === self::FATURADOR_SISTEMA_EXTERNO;
    }

    /**
     * Gera um novo token seguro para autenticar callbacks do sistema externo.
     * Salva no modelo mas NÃO persiste automaticamente — chame save() após.
     */
    public function gerarWebhookToken(): string
    {
        $token = Yii::$app->security->generateRandomString(48);
        $this->nfe_webhook_token = $token;
        return $token;
    }

    /**
     * Retorna a URL de callback para o sistema externo enviar a NF-e ao Pulse
     */
    public function getCallbackUrl(): string
    {
        $base = rtrim(Yii::$app->request->hostInfo ?? 'https://catalogos.oncode.app.br', '/');
        return $base . '/fiscal/callback/registrar-nfe';
    }

    /**
     * Retorna configuração do usuário logado
     */
    public static function getConfiguracaoAtual()
    {
        $usuarioId = Yii::$app->user->id;
        $config = self::findOne(['usuario_id' => $usuarioId]);

        if (!$config) {
            $config = new self();
            $config->usuario_id = $usuarioId;
            $config->cor_primaria = '#3B82F6';
            $config->cor_secundaria = '#10B981';
            $config->catalogo_publico = false;
            $config->aceita_orcamentos = true;
            $config->segmento = 'geral';
            $config->modulo_food_service = false;
            $config->nfe_serie = 1;
            $config->nfe_numero_atual = 0;
            $config->faturador_ml_tipo = self::FATURADOR_PULSE_ERP;
            $config->save(false);
        }

        return $config;
    }
}
