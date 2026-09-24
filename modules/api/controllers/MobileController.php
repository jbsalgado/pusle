<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;
use yii\db\Expression;
use Exception;

use app\models\Usuario;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\ProdutoVariante;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\Clientes;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\Venda;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\Parcela;
use app\modules\vendas\models\StatusVenda;
use app\modules\vendas\models\EstoqueMovimentacoes;

/**
 * ============================================================
 * MobileController – API exclusiva para o App Flutter Pulse
 * ============================================================
 *
 * Endpoints disponíveis (prefixo: /api/mobile/):
 *
 *  [GET]  /api/mobile/info                   – Info do tenant (loja)
 *  [GET]  /api/mobile/sync/produtos          – Delta sync de produtos
 *  [GET]  /api/mobile/sync/categorias        – Delta sync de categorias
 *  [GET]  /api/mobile/sync/clientes          – Delta sync de clientes
 *  [GET]  /api/mobile/sync/formas-pagamento  – Delta sync de formas de pagamento
 *  [GET]  /api/mobile/sync/status            – Status da última sync por tabela
 *  [POST] /api/mobile/venda/registrar        – Registrar UMA venda (offline→online)
 *  [POST] /api/mobile/venda/batch            – Envio em LOTE de vendas offline
 *  [GET]  /api/mobile/venda/historico        – Histórico de vendas do vendedor
 *  [GET]  /api/mobile/produto/buscar         – Busca rápida por código de barras/nome
 */
class MobileController extends BaseController
{
    public $enableCsrfValidation = false;

    // ---------------------------------------------------------------
    // Behaviors: todas as actions exigem JWT, exceto 'ping'
    // ---------------------------------------------------------------
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // CORS amplo para desenvolvimento; restringir em produção se necessário
        $behaviors['corsFilter'] = [
            'class'  => \yii\filters\Cors::class,
            'cors'   => [
                'Origin'                           => ['*'],
                'Access-Control-Request-Method'    => ['GET', 'POST', 'OPTIONS'],
                'Access-Control-Request-Headers'   => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age'           => 86400,
            ],
        ];

        // JWT obrigatório em tudo (exceto OPTIONS por CORS)
        $behaviors['authenticator'] = [
            'class'    => \yii\filters\auth\HttpBearerAuth::class,
            'optional' => ['options'],
        ];

        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'info'              => ['GET', 'HEAD'],
            'minhas-lojas'      => ['GET', 'HEAD'],
            'sync-produtos'     => ['GET', 'HEAD'],
            'sync-categorias'   => ['GET', 'HEAD'],
            'sync-clientes'     => ['GET', 'HEAD'],
            'sync-formas-pagamento' => ['GET', 'HEAD'],
            'sync-status'       => ['GET', 'HEAD'],
            'venda-registrar'   => ['POST'],
            'venda-batch'       => ['POST'],
            'venda-historico'   => ['GET', 'HEAD'],
            'produto-buscar'    => ['GET', 'HEAD'],
        ];
    }

    // ==============================================================
    // [GET] /api/mobile/minhas-lojas
    // Retorna a lista de todas as lojas que o usuário autenticado tem acesso
    // ==============================================================
    public function actionMinhasLojas()
    {
        /** @var \app\models\Usuario|null $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity) {
            throw new UnauthorizedHttpException('Token inválido ou expirado.');
        }

        $lojas = $identity->getLojasDisponiveis();

        return $this->success([
            'lojas' => $lojas,
            'total' => count($lojas),
        ], 'Lojas do usuário retornadas com sucesso.');
    }

    // ==============================================================
    // HELPER: resolve o tenant_id (usuario_id) a partir do JWT e header X-Loja-Id
    // ==============================================================
    private function resolveTenantId(): string
    {
        /** @var \app\models\Usuario|null $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity) {
            throw new UnauthorizedHttpException('Token inválido ou expirado.');
        }

        // 1. Verifica se foi informado o cabeçalho X-Loja-Id ou X-Tenant-Id
        $headerLojaId = Yii::$app->request->headers->get('X-Loja-Id')
            ?? Yii::$app->request->headers->get('X-Tenant-Id')
            ?? Yii::$app->request->get('loja_id');

        $lojas = $identity->getLojasDisponiveis();

        if (empty($lojas)) {
            return (string)$identity->id;
        }

        // Se informou a loja no cabeçalho ou parâmetro, valida a permissão
        if (!empty($headerLojaId)) {
            foreach ($lojas as $loja) {
                if ($loja['loja_id'] === $headerLojaId) {
                    return $headerLojaId;
                }
            }
            throw new UnauthorizedHttpException("Acesso negado para a loja informada ({$headerLojaId}).");
        }

        // Se não informou a loja, mas possui apenas 1 loja vinculada: usa como padrão
        if (count($lojas) === 1) {
            return (string)$lojas[0]['loja_id'];
        }

        // Se possui mais de uma loja vinculada e não informou o header:
        throw new BadRequestHttpException('Usuário vinculado a mais de uma loja. O cabeçalho X-Loja-Id é obrigatório para definir a loja ativa.');
    }

    // ==============================================================
    // [GET] /api/mobile/info
    // Retorna as configurações básicas da loja/tenant para o app
    // ==============================================================
    public function actionInfo()
    {
        $tenantId = $this->resolveTenantId();

        $usuario = Usuario::findOne($tenantId);
        if (!$usuario) {
            throw new NotFoundHttpException('Loja não encontrada.');
        }

        // Loja configuracao (pode ser null se não configurada)
        $lojaConfig = (new \yii\db\Query())
            ->select([
                'nome_loja', 'logo_url', 'telefone_loja', 'endereco_loja',
                'cidade_loja', 'estado_loja', 'permite_venda_sem_estoque',
                'catalogo_ativo',
            ])
            ->from('loja_configuracao')
            ->where(['usuario_id' => $tenantId])
            ->one();

        // Configurações gerais (prest_configuracoes)
        $configuracao = (new \yii\db\Query())
            ->select([
                'pix_chave', 'pix_tipo', 'catalogo_publico',
            ])
            ->from('prest_configuracoes')
            ->where(['usuario_id' => $tenantId])
            ->one();

        /** @var \app\models\Usuario $identity */
        $identity = Yii::$app->user->identity;

        // Perfil de colaborador nesta loja (se houver)
        $colaborador = \app\modules\vendas\models\Colaborador::find()
            ->where([
                'prest_usuario_login_id' => $identity->id,
                'usuario_id'             => $tenantId,
                'ativo'                  => true,
            ])
            ->one();

        return $this->success([
            'tenant_id'          => $tenantId,
            'nome_usuario'       => $usuario->nome ?? $usuario->username,
            'email'              => $usuario->email,
            'loja'               => $lojaConfig ?: [],
            'configuracao'       => $configuracao ?: [],
            'colaborador'        => $colaborador ? [
                'id'          => (string)$colaborador->id,
                'nome'        => $colaborador->nome_completo,
                'eh_vendedor' => (bool)$colaborador->eh_vendedor,
                'eh_cobrador' => (bool)$colaborador->eh_cobrador,
            ] : null,
            'lojas_disponiveis'  => $identity->getLojasDisponiveis(),
            'servidor_timestamp' => date('Y-m-d\TH:i:sP'),
        ], 'Informações do tenant retornadas com sucesso.');
    }

    // ==============================================================
    // [GET] /api/mobile/sync/produtos
    // Delta sync: retorna produtos modificados desde ?since=YYYY-MM-DDTHH:mm:ss
    // ?since=2026-01-01T00:00:00  (ISO 8601)
    // ?page=1&per_page=200
    // ==============================================================
    public function actionSyncProdutos()
    {
        $tenantId  = $this->resolveTenantId();
        $since     = Yii::$app->request->get('since');         // ISO 8601 ou null
        $page      = max(1, (int)Yii::$app->request->get('page', 1));
        $perPage   = min(500, max(10, (int)Yii::$app->request->get('per_page', 200)));

        $query = Produto::find()
            ->where(['ativo' => true, 'usuario_id' => $tenantId])
            ->with(['fotos', 'categoria', 'variantesNovas'])
            ->orderBy(['data_atualizacao' => SORT_ASC, 'id' => SORT_ASC]);

        // Delta: apenas registros atualizados após a data informada
        if ($since) {
            $sinceFormatado = $this->parseSinceParam($since);
            if ($sinceFormatado) {
                $query->andWhere(['>=', 'data_atualizacao', $sinceFormatado]);
            }
        }

        $totalCount = (clone $query)->count();
        $offset     = ($page - 1) * $perPage;
        $produtos   = $query->limit($perPage)->offset($offset)->all();

        $items = [];
        foreach ($produtos as $produto) {
            $items[] = $this->serializarProduto($produto);
        }

        return $this->success([
            'items'       => $items,
            'meta'        => [
                'total'        => (int)$totalCount,
                'page'         => $page,
                'per_page'     => $perPage,
                'total_pages'  => (int)ceil($totalCount / $perPage),
                'has_more'     => ($page * $perPage) < $totalCount,
            ],
            'sync_timestamp' => date('Y-m-d\TH:i:sP'),
        ], 'Produtos sincronizados.');
    }

    // ==============================================================
    // [GET] /api/mobile/sync/categorias
    // ==============================================================
    public function actionSyncCategorias()
    {
        $tenantId = $this->resolveTenantId();
        $since    = Yii::$app->request->get('since');

        $query = Categoria::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC]);

        if ($since) {
            $sinceFormatado = $this->parseSinceParam($since);
            if ($sinceFormatado) {
                $query->andWhere(['>=', 'data_atualizacao', $sinceFormatado]);
            }
        }

        $categorias = $query->asArray()->all();

        return $this->success([
            'items'          => $categorias,
            'total'          => count($categorias),
            'sync_timestamp' => date('Y-m-d\TH:i:sP'),
        ], 'Categorias sincronizadas.');
    }

    // ==============================================================
    // [GET] /api/mobile/sync/clientes
    // ==============================================================
    public function actionSyncClientes()
    {
        $tenantId = $this->resolveTenantId();
        $since    = Yii::$app->request->get('since');
        $page     = max(1, (int)Yii::$app->request->get('page', 1));
        $perPage  = min(500, max(10, (int)Yii::$app->request->get('per_page', 200)));

        $query = Clientes::find()
            ->select([
                'id', 'usuario_id', 'nome_completo', 'cpf', 'telefone',
                'email', 'endereco_logradouro', 'endereco_numero',
                'endereco_bairro', 'endereco_cidade', 'endereco_estado',
                'endereco_cep', 'ativo', 'data_criacao', 'data_atualizacao',
            ])
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['data_atualizacao' => SORT_ASC]);

        if ($since) {
            $sinceFormatado = $this->parseSinceParam($since);
            if ($sinceFormatado) {
                $query->andWhere(['>=', 'data_atualizacao', $sinceFormatado]);
            }
        }

        $totalCount = (clone $query)->count();
        $offset     = ($page - 1) * $perPage;
        $clientes   = $query->limit($perPage)->offset($offset)->asArray()->all();

        return $this->success([
            'items'       => $clientes,
            'meta'        => [
                'total'       => (int)$totalCount,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int)ceil($totalCount / $perPage),
                'has_more'    => ($page * $perPage) < $totalCount,
            ],
            'sync_timestamp' => date('Y-m-d\TH:i:sP'),
        ], 'Clientes sincronizados.');
    }

    // ==============================================================
    // [GET] /api/mobile/sync/formas-pagamento
    // ==============================================================
    public function actionSyncFormasPagamento()
    {
        $tenantId = $this->resolveTenantId();

        $formas = FormaPagamento::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->asArray()
            ->all();

        return $this->success([
            'items'          => $formas,
            'total'          => count($formas),
            'sync_timestamp' => date('Y-m-d\TH:i:sP'),
        ], 'Formas de pagamento sincronizadas.');
    }

    // ==============================================================
    // [GET] /api/mobile/sync/status
    // Retorna contagens para o app saber o que precisa sincronizar
    // ==============================================================
    public function actionSyncStatus()
    {
        $tenantId = $this->resolveTenantId();

        $totalProdutos   = Produto::find()->where(['usuario_id' => $tenantId, 'ativo' => true])->count();
        $totalCategorias = Categoria::find()->where(['usuario_id' => $tenantId, 'ativo' => true])->count();
        $totalClientes   = Clientes::find()->where(['usuario_id' => $tenantId, 'ativo' => true])->count();
        $totalFormas     = FormaPagamento::find()->where(['usuario_id' => $tenantId, 'ativo' => true])->count();

        $ultimoProduto   = Produto::find()->where(['usuario_id' => $tenantId])->max('data_atualizacao');
        $ultimoCliente   = Clientes::find()->where(['usuario_id' => $tenantId])->max('data_atualizacao');

        return $this->success([
            'tabelas' => [
                'produtos'        => ['total' => (int)$totalProdutos,   'ultima_atualizacao' => $ultimoProduto],
                'categorias'      => ['total' => (int)$totalCategorias, 'ultima_atualizacao' => null],
                'clientes'        => ['total' => (int)$totalClientes,   'ultima_atualizacao' => $ultimoCliente],
                'formas_pagamento'=> ['total' => (int)$totalFormas,     'ultima_atualizacao' => null],
            ],
            'servidor_timestamp' => date('Y-m-d\TH:i:sP'),
        ], 'Status de sync retornado com sucesso.');
    }

    // ==============================================================
    // [POST] /api/mobile/venda/registrar
    // Registra UMA venda gerada offline, aceitando UUID externo
    //
    // Payload esperado:
    // {
    //   "id": "uuid-gerado-no-app",          ← UUID gerado localmente
    //   "usuario_id": "uuid-do-tenant",
    //   "cliente_id": "uuid|null",
    //   "forma_pagamento_id": "uuid",
    //   "data_venda": "2026-09-24T10:00:00",
    //   "valor_total": 150.00,
    //   "numero_parcelas": 1,
    //   "observacoes": "...",
    //   "tipo_venda": "BALCAO",
    //   "acrescimo_valor": 0,
    //   "acrescimo_tipo": null,
    //   "desconto_global_valor": 0,
    //   "desconto_global_tipo": "VALOR",
    //   "cpf_consumidor": "000.000.000-00",
    //   "itens": [
    //     {
    //       "id": "uuid-do-item",             ← UUID gerado localmente
    //       "produto_id": "uuid",
    //       "variante_id": "uuid|null",
    //       "quantidade": 2,
    //       "preco_unitario": 75.00,
    //       "desconto_percentual": 0,
    //       "desconto_valor": 0,
    //       "nome_item_manual": null
    //     }
    //   ],
    //   "parcelas": [                         ← opcional; se vazio, gera automaticamente
    //     {
    //       "id": "uuid",
    //       "numero_parcela": 1,
    //       "valor_parcela": 150.00,
    //       "data_vencimento": "2026-09-24"
    //     }
    //   ]
    // }
    // ==============================================================
    public function actionVendaRegistrar()
    {
        $data = $this->parseJsonBody();
        $tenantId = $this->resolveTenantId();

        // Validação do tenant: o usuario_id do payload deve ser do próprio token
        $usuarioIdPayload = $data['usuario_id'] ?? null;
        if ($usuarioIdPayload && $usuarioIdPayload !== $tenantId) {
            // Verifica se o usuário autenticado é colaborador do tenant do payload
            $isColaboradorDoTenant = $this->verificarColaboradorDoTenant($tenantId, $usuarioIdPayload);
            if (!$isColaboradorDoTenant) {
                throw new UnauthorizedHttpException('usuario_id do payload não corresponde ao tenant autenticado.');
            }
            // Se for colaborador, usamos o usuario_id do dono da loja
        }

        // Valida campos obrigatórios
        $this->validarCamposObrigatoriosVenda($data);

        // UUID: se o app enviou um ID, usa ele; caso contrário, gera no servidor
        $vendaId = !empty($data['id']) ? $data['id'] : $this->gerarUuid();

        // Verifica se a venda com esse ID já existe (idempotência)
        $vendaExistente = Venda::findOne($vendaId);
        if ($vendaExistente) {
            Yii::info("[Mobile] Venda {$vendaId} já existe no servidor – retornando existente.", 'mobile');
            return $this->success(
                $this->serializarVenda($vendaExistente),
                'Venda já registrada anteriormente (idempotente).'
            );
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $venda = $this->criarVendaAPartirDoPayload($data, $tenantId, $vendaId);

            // Registrar itens
            $this->criarItensVenda($venda, $data['itens'], $tenantId);

            // Gerar ou importar parcelas
            $parcelasPayload = $data['parcelas'] ?? [];
            if (!empty($parcelasPayload)) {
                $this->importarParcelas($venda, $parcelasPayload);
            } else {
                // Gera automaticamente pelo método do model
                $venda->gerarParcelas(
                    $data['forma_pagamento_id'],
                    $data['data_primeiro_vencimento'] ?? null,
                    30,
                    true // isVendaDireta
                );
            }

            $transaction->commit();
            Yii::info("[Mobile] Venda {$vendaId} registrada com sucesso.", 'mobile');

            return $this->success(
                $this->serializarVenda(Venda::findOne($vendaId)),
                'Venda registrada com sucesso.'
            );

        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error("[Mobile] Erro ao registrar venda: " . $e->getMessage(), 'mobile');
            throw new ServerErrorHttpException('Erro ao registrar venda: ' . $e->getMessage());
        }
    }

    // ==============================================================
    // [POST] /api/mobile/venda/batch
    // Envio em LOTE de vendas geradas offline
    //
    // Payload:
    // {
    //   "vendas": [ { ...mesma estrutura de /venda/registrar... }, ... ]
    // }
    //
    // Resposta:
    // {
    //   "success": true,
    //   "data": {
    //     "processadas": 5,
    //     "com_sucesso": 4,
    //     "com_erro": 1,
    //     "resultados": [
    //       { "id": "uuid", "sucesso": true },
    //       { "id": "uuid", "sucesso": false, "erro": "..." }
    //     ]
    //   }
    // }
    // ==============================================================
    public function actionVendaBatch()
    {
        $data     = $this->parseJsonBody();
        $tenantId = $this->resolveTenantId();

        $vendasPayload = $data['vendas'] ?? [];

        if (empty($vendasPayload) || !is_array($vendasPayload)) {
            throw new BadRequestHttpException('O campo "vendas" é obrigatório e deve ser um array.');
        }

        if (count($vendasPayload) > 100) {
            throw new BadRequestHttpException('Máximo de 100 vendas por lote. Divida em múltiplas requisições.');
        }

        $resultados    = [];
        $comSucesso    = 0;
        $comErro       = 0;

        foreach ($vendasPayload as $index => $vendaData) {
            $vendaId = $vendaData['id'] ?? null;

            try {
                // Valida campos mínimos
                $this->validarCamposObrigatoriosVenda($vendaData);

                // UUID
                if (empty($vendaId)) {
                    $vendaId = $this->gerarUuid();
                }

                // Idempotência: se já existe, conta como sucesso
                $vendaExistente = Venda::findOne($vendaId);
                if ($vendaExistente) {
                    $resultados[] = [
                        'id'        => $vendaId,
                        'sucesso'   => true,
                        'ja_existe' => true,
                        'mensagem'  => 'Venda já registrada anteriormente.',
                    ];
                    $comSucesso++;
                    continue;
                }

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $venda = $this->criarVendaAPartirDoPayload($vendaData, $tenantId, $vendaId);
                    $this->criarItensVenda($venda, $vendaData['itens'], $tenantId);

                    $parcelasPayload = $vendaData['parcelas'] ?? [];
                    if (!empty($parcelasPayload)) {
                        $this->importarParcelas($venda, $parcelasPayload);
                    } else {
                        $venda->gerarParcelas(
                            $vendaData['forma_pagamento_id'],
                            $vendaData['data_primeiro_vencimento'] ?? null,
                            30,
                            true
                        );
                    }

                    $transaction->commit();
                    $comSucesso++;
                    $resultados[] = [
                        'id'      => $vendaId,
                        'sucesso' => true,
                    ];
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    throw $e;
                }

            } catch (\Throwable $e) {
                $comErro++;
                $resultados[] = [
                    'id'      => $vendaId ?? "item_{$index}",
                    'sucesso' => false,
                    'erro'    => $e->getMessage(),
                ];
                Yii::error("[Mobile][Batch] Erro na venda {$vendaId}: " . $e->getMessage(), 'mobile');
            }
        }

        return $this->success([
            'processadas'  => count($vendasPayload),
            'com_sucesso'  => $comSucesso,
            'com_erro'     => $comErro,
            'resultados'   => $resultados,
        ], "Lote processado: {$comSucesso} com sucesso, {$comErro} com erro.");
    }

    // ==============================================================
    // [GET] /api/mobile/venda/historico
    // Retorna histórico de vendas do tenant (últimas N vendas)
    // ?page=1&per_page=50&since=YYYY-MM-DDTHH:mm:ss
    // ==============================================================
    public function actionVendaHistorico()
    {
        $tenantId = $this->resolveTenantId();
        $page     = max(1, (int)Yii::$app->request->get('page', 1));
        $perPage  = min(100, max(10, (int)Yii::$app->request->get('per_page', 50)));
        $since    = Yii::$app->request->get('since');

        $query = Venda::find()
            ->where(['usuario_id' => $tenantId])
            ->with(['itens', 'parcelas', 'statusVenda', 'formaPagamento'])
            ->orderBy(['data_venda' => SORT_DESC]);

        if ($since) {
            $sinceFormatado = $this->parseSinceParam($since);
            if ($sinceFormatado) {
                $query->andWhere(['>=', 'data_criacao', $sinceFormatado]);
            }
        }

        $totalCount = (clone $query)->count();
        $offset     = ($page - 1) * $perPage;
        $vendas     = $query->limit($perPage)->offset($offset)->all();

        $items = array_map(fn($v) => $this->serializarVenda($v), $vendas);

        return $this->success([
            'items' => $items,
            'meta'  => [
                'total'       => (int)$totalCount,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int)ceil($totalCount / $perPage),
                'has_more'    => ($page * $perPage) < $totalCount,
            ],
        ], 'Histórico de vendas retornado.');
    }

    // ==============================================================
    // [GET] /api/mobile/produto/buscar
    // Busca rápida por código de barras ou nome (para o scanner)
    // ?q=CODIGO_BARRAS_OU_NOME&usuario_id=xxx
    // ==============================================================
    public function actionProdutoBuscar()
    {
        $tenantId = $this->resolveTenantId();
        $q        = trim((string)Yii::$app->request->get('q', ''));

        if (strlen($q) < 2) {
            throw new BadRequestHttpException('Parâmetro "q" deve ter pelo menos 2 caracteres.');
        }

        $query = Produto::find()
            ->where(['ativo' => true, 'usuario_id' => $tenantId])
            ->with(['fotos', 'variantesNovas'])
            ->limit(20);

        // Prioridade 1: código de barras exato
        $porCodigo = (clone $query)
            ->andWhere(['OR',
                ['codigo_barras' => $q],
                ['codigo_referencia' => $q],
            ])->all();

        if (!empty($porCodigo)) {
            $items = array_map(fn($p) => $this->serializarProduto($p), $porCodigo);
            return $this->success(['items' => $items, 'total' => count($items)], 'Produto encontrado por código.');
        }

        // Prioridade 2: busca por variante com código de barras exato
        $variante = ProdutoVariante::find()
            ->where(['codigo_barras' => $q])
            ->with('produto')
            ->one();

        if ($variante && $variante->produto && $variante->produto->usuario_id === $tenantId) {
            $produtoVar = $variante->produto;
            $produtoVar->populateRelation('fotos', $produtoVar->fotos);
            $serializado = $this->serializarProduto($produtoVar);
            $serializado['variante_encontrada'] = [
                'id'            => $variante->id,
                'nome_formatado'=> $variante->getNomeFormatado(),
                'preco'         => $variante->getPrecoVendaEfetivo(),
                'estoque_atual' => $variante->estoque_atual,
                'codigo_barras' => $variante->codigo_barras,
            ];
            return $this->success(['items' => [$serializado], 'total' => 1], 'Produto encontrado via variante.');
        }

        // Prioridade 3: busca textual por nome
        $termo = '%' . $q . '%';
        $porNome = (clone $query)
            ->andWhere(['OR',
                ['ilike', new Expression('unaccent(nome)'), new Expression('unaccent(:t)', [':t' => $termo])],
                ['ilike', 'codigo_barras', $termo],
                ['ilike', 'codigo_referencia', $termo],
            ])->all();

        $items = array_map(fn($p) => $this->serializarProduto($p), $porNome);

        return $this->success([
            'items' => $items,
            'total' => count($items),
        ], empty($items) ? 'Nenhum produto encontrado.' : 'Produtos encontrados.');
    }

    // ==============================================================
    // MÉTODOS PRIVADOS DE SUPORTE
    // ==============================================================

    /**
     * Lê e valida o corpo JSON da requisição
     */
    private function parseJsonBody(): array
    {
        $rawBody = Yii::$app->request->getRawBody();
        $data    = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('JSON inválido: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new BadRequestHttpException('Corpo da requisição deve ser um objeto JSON.');
        }

        return $data;
    }

    /**
     * Valida campos mínimos de uma venda
     */
    private function validarCamposObrigatoriosVenda(array $data): void
    {
        $obrigatorios = ['forma_pagamento_id', 'itens'];

        foreach ($obrigatorios as $campo) {
            if (empty($data[$campo])) {
                throw new BadRequestHttpException("Campo obrigatório ausente: {$campo}");
            }
        }

        if (!is_array($data['itens']) || count($data['itens']) === 0) {
            throw new BadRequestHttpException('A venda deve conter pelo menos 1 item.');
        }
    }

    /**
     * Cria o registro de Venda a partir do payload normalizado
     */
    private function criarVendaAPartirDoPayload(array $data, string $tenantId, string $vendaId): Venda
    {
        $venda = new Venda();
        $venda->id                      = $vendaId;
        $venda->usuario_id              = $tenantId;
        $venda->cliente_id              = !empty($data['cliente_id']) ? $data['cliente_id'] : null;
        $venda->colaborador_vendedor_id = $data['colaborador_vendedor_id'] ?? null;

        // Se o colaborador_vendedor_id não foi informado, busca o colaborador correspondente ao usuário logado nesta loja
        if (empty($venda->colaborador_vendedor_id) && !Yii::$app->user->isGuest) {
            $colab = \app\modules\vendas\models\Colaborador::find()
                ->where([
                    'prest_usuario_login_id' => Yii::$app->user->id,
                    'usuario_id'             => $tenantId,
                    'ativo'                  => true,
                ])
                ->one();
            if ($colab) {
                $venda->colaborador_vendedor_id = $colab->id;
            }
        }
        $venda->forma_pagamento_id      = $data['forma_pagamento_id'];
        $venda->data_venda              = $data['data_venda'] ?? date('Y-m-d H:i:s');
        $venda->numero_parcelas         = max(1, (int)($data['numero_parcelas'] ?? 1));
        $venda->status_venda_codigo     = $data['status_venda_codigo'] ?? 'QUITADA';
        $venda->observacoes             = $data['observacoes'] ?? null;
        $venda->tipo_venda              = $data['tipo_venda'] ?? Venda::TIPO_BALCAO;
        $venda->acrescimo_valor         = (float)($data['acrescimo_valor'] ?? 0);
        $venda->acrescimo_tipo          = $data['acrescimo_tipo'] ?? null;
        $venda->observacao_acrescimo    = $data['observacao_acrescimo'] ?? null;
        $venda->desconto_global_valor   = (float)($data['desconto_global_valor'] ?? 0);
        $venda->desconto_global_tipo    = $data['desconto_global_tipo'] ?? 'VALOR';
        $venda->observacao_desconto_global = $data['observacao_desconto_global'] ?? null;
        $venda->data_primeiro_vencimento   = $data['data_primeiro_vencimento'] ?? null;

        // CPF consumidor: formata se vier apenas dígitos
        $cpfRaw = trim((string)($data['cpf_consumidor'] ?? ''));
        if ($cpfRaw) {
            $cpfDigitos = preg_replace('/\D/', '', $cpfRaw);
            $venda->cpf_consumidor = strlen($cpfDigitos) === 11
                ? preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpfDigitos)
                : null;
        }

        // Calcula valor total a partir dos itens (se não informado ou zero)
        $valorTotalPayload = (float)($data['valor_total'] ?? 0);
        if ($valorTotalPayload <= 0) {
            $valorTotalPayload = $this->calcularValorTotalItens($data['itens']);
        }
        $venda->valor_total = $valorTotalPayload;

        // Seta o ID manualmente antes do save (beforeSave só gera se estiver vazio)
        if (!$venda->save()) {
            throw new Exception('Erro ao salvar venda: ' . json_encode($venda->errors));
        }

        return $venda;
    }

    /**
     * Cria os itens de uma venda
     */
    private function criarItensVenda(Venda $venda, array $itensData, string $tenantId): void
    {
        foreach ($itensData as $index => $itemData) {
            if (empty($itemData['produto_id']) || !isset($itemData['quantidade']) || !isset($itemData['preco_unitario'])) {
                throw new Exception("Item #{$index}: dados incompletos (produto_id, quantidade e preco_unitario são obrigatórios).");
            }

            $vendaItem = new VendaItem();
            $vendaItem->venda_id              = $venda->id;
            $vendaItem->produto_id            = $itemData['produto_id'];
            $vendaItem->variante_id           = !empty($itemData['variante_id']) ? $itemData['variante_id'] : null;
            $vendaItem->quantidade            = (float)$itemData['quantidade'];
            $vendaItem->preco_unitario_venda  = (float)$itemData['preco_unitario'];
            $vendaItem->desconto_percentual   = (float)($itemData['desconto_percentual'] ?? 0);
            $vendaItem->desconto_valor        = (float)($itemData['desconto_valor'] ?? 0);
            $vendaItem->nome_item_manual      = $itemData['nome_item_manual'] ?? null;

            // ID externo (app gerou UUID localmente)
            if (!empty($itemData['id'])) {
                $vendaItem->id = $itemData['id'];
            }

            if (!$vendaItem->save()) {
                throw new Exception("Erro ao salvar item #{$index}: " . json_encode($vendaItem->errors));
            }

            // Movimenta estoque (se não for item avulso)
            if ($itemData['produto_id'] !== '00000000-0000-0000-0000-000000000000') {
                $this->movimentarEstoque(
                    $itemData['produto_id'],
                    $itemData['variante_id'] ?? null,
                    $tenantId,
                    $venda->id,
                    (float)$itemData['quantidade']
                );
            }
        }
    }

    /**
     * Importa parcelas enviadas pelo app (geradas localmente offline)
     */
    private function importarParcelas(Venda $venda, array $parcelasData): void
    {
        foreach ($parcelasData as $index => $parcelaData) {
            $parcela = new Parcela();

            if (!empty($parcelaData['id'])) {
                $parcela->id = $parcelaData['id'];
            }

            $parcela->venda_id             = $venda->id;
            $parcela->usuario_id           = $venda->usuario_id;
            $parcela->numero_parcela       = (int)($parcelaData['numero_parcela'] ?? ($index + 1));
            $parcela->valor_parcela        = (float)($parcelaData['valor_parcela'] ?? 0);
            $parcela->data_vencimento      = $parcelaData['data_vencimento'] ?? date('Y-m-d');
            $parcela->status_parcela_codigo = $parcelaData['status_parcela_codigo'] ?? 'ABERTA';
            $parcela->forma_pagamento_id   = $parcelaData['forma_pagamento_id'] ?? $venda->forma_pagamento_id;
            $parcela->observacoes          = $parcelaData['observacoes'] ?? null;

            if (!$parcela->save()) {
                throw new Exception("Erro ao salvar parcela #{$index}: " . json_encode($parcela->errors));
            }
        }
    }

    /**
     * Movimenta estoque de saída ao registrar uma venda offline
     */
    private function movimentarEstoque(
        string $produtoId,
        ?string $varianteId,
        string $tenantId,
        string $vendaId,
        float $quantidade
    ): void {
        try {
            // Busca saldo atual
            $produto = Produto::findOne($produtoId);
            if (!$produto) {
                return;
            }

            $saldoAnterior = $varianteId
                ? (int)(\app\modules\vendas\models\ProdutoVariante::findOne($varianteId)->estoque_atual ?? 0)
                : (int)$produto->estoque_atual;

            $saldoNovo = max(0, $saldoAnterior - (int)$quantidade);

            // Atualiza estoque no produto/variante
            if ($varianteId) {
                \app\modules\vendas\models\ProdutoVariante::updateAll(
                    ['estoque_atual' => $saldoNovo],
                    ['id' => $varianteId]
                );
            } else {
                Produto::updateAll(
                    ['estoque_atual' => $saldoNovo],
                    ['id' => $produtoId]
                );
            }

            // Registra movimentação
            $mov = new EstoqueMovimentacoes();
            $mov->id                = $this->gerarUuid();
            $mov->produto_id        = $produtoId;
            $mov->usuario_id        = $tenantId;
            $mov->tipo_movimentacao = 'SAIDA';
            $mov->quantidade        = (int)$quantidade;
            $mov->saldo_anterior    = $saldoAnterior;
            $mov->saldo_novo        = $saldoNovo;
            $mov->venda_id          = $vendaId;
            $mov->observacao        = 'Venda via App Mobile (offline sync)';
            $mov->data_movimentacao = date('Y-m-d H:i:s');
            $mov->save(false); // false = não revalida FK para performance

        } catch (\Throwable $e) {
            // Não bloqueia a venda por erro de estoque
            Yii::warning("[Mobile] Erro ao movimentar estoque do produto {$produtoId}: " . $e->getMessage(), 'mobile');
        }
    }

    /**
     * Serializa um Produto para o formato esperado pelo app
     */
    private function serializarProduto(Produto $produto): array
    {
        $fotoUrl = null;
        if (!empty($produto->fotos)) {
            $fotoUrl = $produto->fotos[0]->url ?? null;
        }

        $variantes = [];
        if (!empty($produto->variantesNovas)) {
            foreach ($produto->variantesNovas as $v) {
                $variantes[] = [
                    'id'              => $v->id,
                    'nome_formatado'  => $v->getNomeFormatado(),
                    'preco_venda'     => $v->getPrecoVendaEfetivo(),
                    'estoque_atual'   => $v->estoque_atual,
                    'codigo_barras'   => $v->codigo_barras,
                    'cor'             => $v->cor ?? null,
                    'tamanho'         => $v->tamanho ?? null,
                ];
            }
        }

        return [
            'id'                    => $produto->id,
            'usuario_id'            => $produto->usuario_id,
            'nome'                  => $produto->nome,
            'codigo_barras'         => $produto->codigo_barras,
            'codigo_referencia'     => $produto->codigo_referencia,
            'categoria_id'          => $produto->categoria_id,
            'categoria_nome'        => $produto->categoria->nome ?? null,
            'preco_venda_sugerido'  => (float)$produto->preco_venda_sugerido,
            'preco_custo'           => (float)$produto->preco_custo,
            'preco_promocional'     => (float)$produto->preco_promocional,
            'preco_vigente'         => (float)$produto->getPrecoFinal(),
            'em_promocao'           => (bool)$produto->emPromocao,
            'data_inicio_promocao'  => $produto->data_inicio_promocao,
            'data_fim_promocao'     => $produto->data_fim_promocao,
            'estoque_atual'         => (int)$produto->estoque_atual,
            'estoque_minimo'        => (int)$produto->estoque_minimo,
            'unidade_medida'        => $produto->unidade_medida ?? 'UN',
            'venda_fracionada'      => (bool)$produto->venda_fracionada,
            'marca'                 => $produto->marca,
            'ativo'                 => (bool)$produto->ativo,
            'foto_url'              => $fotoUrl,
            'variantes'             => $variantes,
            'data_atualizacao'      => $produto->data_atualizacao,
        ];
    }

    /**
     * Serializa uma Venda para o formato do app
     */
    private function serializarVenda(Venda $venda): array
    {
        $itens = [];
        foreach (($venda->itens ?? []) as $item) {
            $itens[] = [
                'id'                   => $item->id,
                'produto_id'           => $item->produto_id,
                'variante_id'          => $item->variante_id,
                'nome_exibicao'        => $item->getNomeExibicao(),
                'quantidade'           => (float)$item->quantidade,
                'preco_unitario_venda' => (float)$item->preco_unitario_venda,
                'desconto_percentual'  => (float)$item->desconto_percentual,
                'desconto_valor'       => (float)$item->desconto_valor,
                'valor_total_item'     => (float)$item->valor_total_item,
            ];
        }

        $parcelas = [];
        foreach (($venda->parcelas ?? []) as $parcela) {
            $parcelas[] = [
                'id'                   => $parcela->id,
                'numero_parcela'       => (int)$parcela->numero_parcela,
                'valor_parcela'        => (float)$parcela->valor_parcela,
                'data_vencimento'      => $parcela->data_vencimento,
                'status_parcela_codigo'=> $parcela->status_parcela_codigo,
            ];
        }

        return [
            'id'                    => $venda->id,
            'usuario_id'            => $venda->usuario_id,
            'cliente_id'            => $venda->cliente_id,
            'forma_pagamento_id'    => $venda->forma_pagamento_id,
            'forma_pagamento_nome'  => $venda->formaPagamento->nome ?? null,
            'data_venda'            => $venda->data_venda,
            'valor_total'           => (float)$venda->valor_total,
            'numero_parcelas'       => (int)$venda->numero_parcelas,
            'status_venda_codigo'   => $venda->status_venda_codigo,
            'tipo_venda'            => $venda->tipo_venda,
            'observacoes'           => $venda->observacoes,
            'cpf_consumidor'        => $venda->cpf_consumidor,
            'data_criacao'          => $venda->data_criacao,
            'itens'                 => $itens,
            'parcelas'              => $parcelas,
        ];
    }

    /**
     * Calcula o valor total a partir dos itens do payload
     */
    private function calcularValorTotalItens(array $itens): float
    {
        $total = 0.0;
        foreach ($itens as $item) {
            $qtd     = (float)($item['quantidade'] ?? 0);
            $preco   = (float)($item['preco_unitario'] ?? 0);
            $descVal = (float)($item['desconto_valor'] ?? 0);
            $descPct = (float)($item['desconto_percentual'] ?? 0);

            $subtotal = $qtd * $preco;
            if ($descPct > 0 && $descVal == 0) {
                $descVal = $subtotal * ($descPct / 100);
            }
            $total += max(0, $subtotal - $descVal);
        }
        return $total;
    }

    /**
     * Gera UUID v4 via PostgreSQL
     */
    private function gerarUuid(): string
    {
        return Yii::$app->db->createCommand('SELECT gen_random_uuid()')->queryScalar();
    }

    /**
     * Normaliza o parâmetro ?since para formato aceito pelo PostgreSQL
     */
    private function parseSinceParam(string $since): ?string
    {
        // Aceita ISO 8601 com T ou com espaço
        $since = str_replace('T', ' ', $since);
        // Remove timezone offset para comparação simples
        $since = preg_replace('/[+-]\d{2}:\d{2}$/', '', $since);
        $since = trim($since);

        // Valida se parece uma data
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $since)) {
            return $since;
        }

        Yii::warning("[Mobile] Parâmetro 'since' inválido: {$since}", 'mobile');
        return null;
    }

    /**
     * Verifica se o tenant autenticado é colaborador do tenant do payload
     */
    private function verificarColaboradorDoTenant(string $colaboradorTenantId, string $payloadTenantId): bool
    {
        // O colaborador usa o usuarioId do dono; se for o mesmo, ok
        return $colaboradorTenantId === $payloadTenantId;
    }
}
