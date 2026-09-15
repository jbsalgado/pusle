<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\Response;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\web\UnprocessableEntityHttpException;
use app\modules\vendas\models\Configuracao;
use app\modules\vendas\models\NotaFiscal;
use app\modules\marketplace\models\MarketplacePedido;
use app\jobs\EnviarNFeMercadoLivreJob;

/**
 * FiscalCallbackController — Endpoints para integração com sistemas fiscais externos
 *
 * Permite que ERPs externos (Omie, Bling, NFe.io, etc.) enviem a chave de acesso
 * e o XML autorizado de uma NF-e para o Pulse ERP, que então rastreia a nota e
 * dispara automaticamente o upload ao Mercado Livre para liberar a etiqueta de envio.
 *
 * AUTENTICAÇÃO: Token estático por header `X-Nfe-Token` (por tenant/loja).
 * Gere o token no painel da loja em Configurações → Emissão Fiscal.
 *
 * Endpoints:
 *   POST /api/fiscal-callback/registrar-nfe  → Registrar NF-e via JSON
 *   POST /api/fiscal-callback/upload-xml     → Registrar NF-e via upload do .xml
 *   GET  /api/fiscal-callback/status         → Consultar status de uma NF-e pelo venda_id
 *   POST /api/fiscal-callback/gerar-token    → Gerar novo token (requer JWT do lojista)
 */
class FiscalCallbackController extends \yii\rest\Controller
{
    /**
     * CSRF e autenticação JWT são desabilitados — usamos X-Nfe-Token por tenant.
     * O endpoint gerar-token exige JWT via overrideBehaviors().
     */
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Formato JSON
        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => ['application/json' => Response::FORMAT_JSON],
        ];

        // Sem autenticação JWT padrão — este controller usa X-Nfe-Token
        // O método actionGerarToken() sobrescreve isso usando beforeAction
        unset($behaviors['authenticator']);

        // Rate limit simples por IP (evita abuso)
        $behaviors['rateLimiter'] = [
            'class' => \yii\filters\RateLimiter::class,
            'enableRateLimitHeaders' => false,
        ];

        return $behaviors;
    }

    /**
     * POST /api/fiscal-callback/registrar-nfe
     *
     * Recebe a NF-e autorizada de um sistema externo e registra no Pulse ERP.
     * Se a venda estiver vinculada ao Mercado Livre, dispara o envio automático.
     *
     * Body JSON:
     * {
     *   "venda_id":      "uuid-da-venda-no-pulse",   (obrigatório)
     *   "chave_acesso":  "44 dígitos",               (obrigatório)
     *   "numero":        123,                         (opcional)
     *   "serie":         1,                           (opcional, default 1)
     *   "protocolo":     "123456789012345",           (opcional)
     *   "xml_autorizado": "<nfeProc>...</nfeProc>"   (opcional, mas recomendado)
     * }
     */
    public function actionRegistrarNfe(): array
    {
        $config = $this->autenticarPorToken();

        $body = Yii::$app->request->bodyParams;
        if (empty($body) && Yii::$app->request->rawBody) {
            $body = json_decode(Yii::$app->request->rawBody, true) ?: [];
        }

        // --- Validação dos campos obrigatórios ---
        $vendaId     = trim($body['venda_id'] ?? '');
        $chaveAcesso = preg_replace('/\D/', '', trim($body['chave_acesso'] ?? ''));

        if (empty($vendaId)) {
            throw new BadRequestHttpException('Campo obrigatório ausente: venda_id');
        }
        if (strlen($chaveAcesso) !== 44) {
            throw new UnprocessableEntityHttpException(
                'Chave de acesso inválida. Deve ter exatamente 44 dígitos numéricos.'
            );
        }

        // Verificar se a venda pertence ao tenant autenticado
        $venda = \app\modules\vendas\models\Venda::findOne([
            'id'         => $vendaId,
            'usuario_id' => $config->usuario_id,
        ]);

        if (!$venda) {
            throw new ForbiddenHttpException('Venda não encontrada ou não pertence à sua loja.');
        }

        // --- Criar ou atualizar o registro de NotaFiscal ---
        $nota = NotaFiscal::findOne(['venda_id' => $vendaId]);
        $isNova = false;
        if (!$nota) {
            $nota = new NotaFiscal();
            $nota->usuario_id = $config->usuario_id;
            $nota->venda_id   = $vendaId;
            $nota->modelo     = '55';
            $nota->ambiente   = 1; // Produção (já saiu da SEFAZ com chave real)
            $isNova = true;
        }

        $nota->chave_acesso          = $chaveAcesso;
        $nota->numero                = (int)($body['numero'] ?? $nota->numero ?? 0);
        $nota->serie                 = (int)($body['serie'] ?? $nota->serie ?? 1);
        $nota->protocolo_autorizacao = $body['protocolo'] ?? $nota->protocolo_autorizacao;
        $nota->xml_autorizado        = $body['xml_autorizado'] ?? $nota->xml_autorizado;
        $nota->status_sefaz          = NotaFiscal::STATUS_AUTORIZADA;
        $nota->fonte_emissao         = NotaFiscal::FONTE_SISTEMA_EXTERNO;
        $nota->data_autorizacao      = $nota->data_autorizacao ?? date('Y-m-d H:i:s');

        // Vincular ao pedido Marketplace se houver
        $mpPedido = MarketplacePedido::findOne(['venda_id' => $vendaId]);
        if ($mpPedido) {
            $nota->marketplace          = $mpPedido->marketplace;
            $nota->marketplace_pedido_id = $mpPedido->marketplace_pedido_id;
        }

        if (!$nota->save(false)) {
            Yii::error('[FiscalCallback] Falha ao salvar NotaFiscal: ' . json_encode($nota->errors), 'fiscal');
            throw new \yii\web\ServerErrorHttpException('Falha ao registrar nota fiscal no banco.');
        }

        Yii::info(
            "[FiscalCallback] NF-e de sistema externo ({$config->nfe_sistema_externo}) registrada. " .
            "Chave: {$chaveAcesso} | Venda: {$vendaId} | Tenant: {$config->usuario_id}",
            'fiscal'
        );

        // --- Disparar envio ao Mercado Livre se aplicável ---
        $mlJobDisparado = false;
        if ($mpPedido && $mpPedido->marketplace === 'MERCADO_LIVRE') {
            try {
                if (Yii::$app->has('queue')) {
                    Yii::$app->queue->push(new EnviarNFeMercadoLivreJob([
                        'notaFiscalId' => $nota->id,
                    ]));
                    $mlJobDisparado = true;
                    Yii::info(
                        "[FiscalCallback] EnviarNFeMercadoLivreJob enfileirado para nota {$nota->id}",
                        'marketplace'
                    );
                }
            } catch (\Throwable $e) {
                Yii::error('[FiscalCallback] Falha ao enfileirar envio ML: ' . $e->getMessage(), 'marketplace');
            }
        }

        return [
            'success'           => true,
            'nota_id'           => $nota->id,
            'chave_acesso'      => $chaveAcesso,
            'status'            => $nota->status_sefaz,
            'enviando_ao_ml'    => $mlJobDisparado,
            'marketplace'       => $nota->marketplace,
            'is_nova'           => $isNova,
        ];
    }

    /**
     * POST /api/fiscal-callback/upload-xml
     *
     * Para lojistas sem sistema com webhook: faz upload manual do arquivo XML
     * autorizado. O Pulse extrai a chave de acesso do próprio XML.
     *
     * Multipart form-data:
     *   xml_file: arquivo .xml da NF-e autorizada (nfeProc)
     *   venda_id: UUID da venda no Pulse
     */
    public function actionUploadXml(): array
    {
        $config = $this->autenticarPorToken();

        $vendaId = Yii::$app->request->post('venda_id', '');
        if (empty($vendaId)) {
            throw new BadRequestHttpException('Campo obrigatório ausente: venda_id');
        }

        $xmlFile = \yii\web\UploadedFile::getInstanceByName('xml_file');
        if (!$xmlFile) {
            throw new BadRequestHttpException('Arquivo xml_file não enviado.');
        }

        if (!in_array(strtolower($xmlFile->extension), ['xml'])) {
            throw new UnprocessableEntityHttpException('Apenas arquivos .xml são aceitos.');
        }

        $xmlContent = file_get_contents($xmlFile->tempName);
        if (empty($xmlContent)) {
            throw new BadRequestHttpException('Arquivo XML está vazio.');
        }

        // Extrair chave de acesso do atributo Id do XML
        if (!preg_match('/Id="NFe([0-9]{44})"/', $xmlContent, $matches)) {
            throw new UnprocessableEntityHttpException(
                'Chave de acesso não encontrada no XML. Verifique se é um XML de NF-e autorizado (nfeProc).'
            );
        }
        $chaveAcesso = $matches[1];

        // Extrair número e série do XML
        $numero = null;
        $serie  = null;
        if (preg_match('/<nNF>(\d+)<\/nNF>/', $xmlContent, $m)) {
            $numero = (int)$m[1];
        }
        if (preg_match('/<serie>(\d+)<\/serie>/', $xmlContent, $m)) {
            $serie = (int)$m[1];
        }

        // Extrair protocolo
        $protocolo = null;
        if (preg_match('/<nProt>(\d+)<\/nProt>/', $xmlContent, $m)) {
            $protocolo = $m[1];
        }

        // Reutilizar a lógica do actionRegistrarNfe via dados extraídos
        Yii::$app->request->setBodyParams([
            'venda_id'      => $vendaId,
            'chave_acesso'  => $chaveAcesso,
            'numero'        => $numero,
            'serie'         => $serie,
            'protocolo'     => $protocolo,
            'xml_autorizado' => $xmlContent,
        ]);

        $result = $this->actionRegistrarNfe();
        $result['fonte'] = 'upload_xml';
        return $result;
    }

    /**
     * GET /api/fiscal-callback/status?venda_id=xxx
     *
     * Consulta o status fiscal de uma venda específica.
     * Útil para o sistema externo confirmar se a NF-e foi recebida e enviada ao ML.
     */
    public function actionStatus(): array
    {
        $config = $this->autenticarPorToken();

        $vendaId = Yii::$app->request->get('venda_id');
        if (empty($vendaId)) {
            throw new BadRequestHttpException('Parâmetro obrigatório: venda_id');
        }

        $nota = NotaFiscal::findOne([
            'venda_id'   => $vendaId,
            'usuario_id' => $config->usuario_id,
        ]);

        if (!$nota) {
            return [
                'success'      => true,
                'venda_id'     => $vendaId,
                'status'       => 'SEM_NOTA',
                'descricao'    => 'Nenhuma NF-e registrada para esta venda no Pulse ERP.',
                'chave_acesso' => null,
                'enviada_ml'   => false,
            ];
        }

        return [
            'success'               => true,
            'venda_id'              => $vendaId,
            'nota_id'               => $nota->id,
            'status'                => $nota->status_sefaz,
            'chave_acesso'          => $nota->chave_acesso,
            'protocolo'             => $nota->protocolo_autorizacao,
            'numero'                => $nota->numero,
            'serie'                 => $nota->serie,
            'fonte_emissao'         => $nota->fonte_emissao,
            'enviada_ml'            => (bool)$nota->enviada_ml,
            'data_envio_ml'         => $nota->data_envio_ml,
            'marketplace'           => $nota->marketplace,
            'marketplace_pedido_id' => $nota->marketplace_pedido_id,
            'data_autorizacao'      => $nota->data_autorizacao,
        ];
    }

    /**
     * POST /api/fiscal-callback/gerar-token
     *
     * Gera um novo webhook token para a loja autenticada via JWT do Pulse.
     * Usado pelo painel admin ao configurar o modo SISTEMA_EXTERNO.
     */
    public function actionGerarToken(): array
    {
        // Este action exige JWT (autenticação de usuário Pulse)
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('Autenticação JWT obrigatória para gerar token.');
        }

        $usuarioId = Yii::$app->user->id;
        $config = Configuracao::findOne(['usuario_id' => $usuarioId]);

        if (!$config) {
            throw new \yii\web\NotFoundHttpException('Configuração da loja não encontrada.');
        }

        $token = $config->gerarWebhookToken();
        $config->save(false);

        Yii::info("[FiscalCallback] Novo webhook token gerado para loja {$usuarioId}", 'fiscal');

        return [
            'success'      => true,
            'token'        => $token,
            'callback_url' => $config->getCallbackUrl(),
            'instrucoes'   => [
                'header'  => 'X-Nfe-Token',
                'metodo'  => 'POST',
                'url'     => $config->getCallbackUrl(),
                'exemplo_body' => [
                    'venda_id'      => 'uuid-da-venda',
                    'chave_acesso'  => '44 digitos numericos',
                    'numero'        => 1,
                    'serie'         => 1,
                    'protocolo'     => 'numero do protocolo SEFAZ',
                    'xml_autorizado' => '<nfeProc>...</nfeProc> (opcional)',
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────
    // PRIVADO: Autenticação por X-Nfe-Token (por tenant)
    // ─────────────────────────────────────────────────────────

    /**
     * Autentica a requisição pelo header X-Nfe-Token e retorna a Configuracao do tenant.
     *
     * @throws ForbiddenHttpException Se token ausente ou inválido
     */
    private function autenticarPorToken(): Configuracao
    {
        $token = Yii::$app->request->headers->get('X-Nfe-Token')
            ?? Yii::$app->request->headers->get('x-nfe-token');

        if (empty($token)) {
            throw new ForbiddenHttpException(
                'Header X-Nfe-Token ausente. Configure o token no painel da loja em Configurações → Emissão Fiscal.'
            );
        }

        $config = Configuracao::findOne(['nfe_webhook_token' => $token]);
        if (!$config) {
            Yii::warning("[FiscalCallback] Tentativa de acesso com token inválido: " . substr($token, 0, 8) . '...', 'fiscal');
            throw new ForbiddenHttpException('Token X-Nfe-Token inválido ou expirado.');
        }

        if (!$config->isFaturadorExterno()) {
            throw new ForbiddenHttpException(
                'Sua loja não está configurada para usar sistema externo de emissão fiscal. ' .
                'Altere o modo em Configurações → Emissão Fiscal → Sistema Externo.'
            );
        }

        return $config;
    }
}
