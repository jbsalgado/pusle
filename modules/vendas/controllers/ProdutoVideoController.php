<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\ProdutoVideo;
use app\modules\vendas\services\VideoGeneratorService;

/**
 * Controller para gerenciar a interface do Studio de Vídeos e APIs de geração/status.
 */
class ProdutoVideoController extends Controller
{
    /**
     * Desabilita validação CSRF para endpoints API chamados via JSON.
     */
    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['generate', 'status', 'list', 'verificar-matriz', 'preparar-lote', 'gerar-item-lote'],
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'studio'           => ['GET', 'POST'],
                    'generate'         => ['POST'],
                    'status'           => ['GET'],
                    'list'             => ['GET'],
                    'verificar-matriz' => ['POST'],
                    'preparar-lote'    => ['POST'],
                    'gerar-item-lote'  => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Retorna o ID da loja (dono) logado
     */
    protected function getLojaId()
    {
        $usuario = Yii::$app->user->identity;

        if (!$usuario) {
            return null;
        }

        if ($usuario->eh_dono_loja === true || $usuario->eh_dono_loja === 't' || $usuario->eh_dono_loja === 1) {
            return $usuario->id;
        }

        $colaborador = Colaborador::getColaboradorLogado();

        if ($colaborador) {
            return $colaborador->usuario_id;
        }

        return $usuario->id;
    }

    /**
     * Formato de resposta: HTML para views web e JSON para APIs.
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if (in_array($action->id, ['studio', 'index'])) {
                Yii::$app->response->format = Response::FORMAT_HTML;
            } else {
                Yii::$app->response->format = Response::FORMAT_JSON;
            }
            return true;
        }
        return false;
    }

    /**
     * Interface Web (Studio) para geração e visualização dos vídeos promocionais (9:16).
     */
    public function actionStudio($produto_id = null, $produto_ids = null)
    {
        Yii::$app->response->format = Response::FORMAT_HTML;

        $lojaId = $this->getLojaId();

        $query = Produto::find()->where(['ativo' => true]);
        if ($lojaId) {
            $query->andWhere(['usuario_id' => $lojaId]);
        }

        $produtos = $query->orderBy(['nome' => SORT_ASC])->all();

        // Mapear contagem de fotos e variações de matriz de forma otimizada
        $prodIds = array_map(fn($p) => $p->id, $produtos);
        $fotosCountMap = [];
        $matrizCountMap = [];

        if (!empty($prodIds)) {
            $countsFotos = (new \yii\db\Query())
                ->select(['produto_id', 'COUNT(*) as total'])
                ->from('prest_produto_fotos')
                ->where(['produto_id' => $prodIds])
                ->groupBy('produto_id')
                ->all();
            foreach ($countsFotos as $cf) {
                $fotosCountMap[$cf['produto_id']] = (int)$cf['total'];
            }

            $vars = (new \yii\db\Query())
                ->select(['produto_id', 'COUNT(DISTINCT cor) as total_cores', 'COUNT(*) as total_variantes'])
                ->from('prest_produto_variantes')
                ->where(['produto_id' => $prodIds, 'ativo' => true])
                ->groupBy('produto_id')
                ->all();
            foreach ($vars as $v) {
                $matrizCountMap[$v['produto_id']] = [
                    'total_cores' => (int)$v['total_cores'],
                    'total_variantes' => (int)$v['total_variantes']
                ];
            }
        }

        // Processar IDs recebidos por parâmetro
        $produtosIdsIniciais = [];
        if (!empty($produto_ids)) {
            $produtosIdsIniciais = is_array($produto_ids) ? $produto_ids : explode(',', $produto_ids);
            $produtosIdsIniciais = array_values(array_filter(array_map('trim', $produtosIdsIniciais)));
        } elseif (!empty($produto_id)) {
            $produtosIdsIniciais = [trim((string)$produto_id)];
        }

        $produtoSelecionado = null;
        if (!empty($produtosIdsIniciais)) {
            $qSel = Produto::find()->where(['id' => $produtosIdsIniciais[0]]);
            if ($lojaId) {
                $qSel->andWhere(['usuario_id' => $lojaId]);
            }
            $produtoSelecionado = $qSel->one();
        }

        if (!$produtoSelecionado && !empty($produtos)) {
            // Prioriza um produto que tenha fotos
            foreach ($produtos as $p) {
                if (($fotosCountMap[$p->id] ?? 0) > 0) {
                    $produtoSelecionado = $p;
                    break;
                }
            }
            if (!$produtoSelecionado) {
                $produtoSelecionado = $produtos[0];
            }
            if (empty($produtosIdsIniciais) && $produtoSelecionado) {
                $produtosIdsIniciais = [$produtoSelecionado->id];
            }
        }

        $videosRecentes = [];
        if ($produtoSelecionado) {
            $videosRecentes = ProdutoVideo::find()
                ->where(['produto_id' => $produtoSelecionado->id])
                ->orderBy(['data_criacao' => SORT_DESC])
                ->limit(10)
                ->all();
        }

        return $this->render('studio', [
            'produtos' => $produtos,
            'produtoSelecionado' => $produtoSelecionado,
            'videosRecentes' => $videosRecentes,
            'fotosCountMap' => $fotosCountMap,
            'matrizCountMap' => $matrizCountMap,
            'produtosIdsIniciais' => $produtosIdsIniciais,
        ]);
    }

    public function actionIndex()
    {
        return $this->redirect(['studio']);
    }

    /**
     * Solicitante de Geração do Vídeo.
     * POST /vendas/produto-video/generate ou POST /api/v1/products/<id>/generate-video
     * Body JSON ou Form Data: { "produto_id": "...", "duracao": 15, "template": "modern_dark", "corTema": "dark" }
     */
    public function actionGenerate()
    {
        $request = Yii::$app->request;
        $params = $request->getBodyParams();

        $produtoId = $params['produto_id'] ?? $request->post('produto_id') ?? $request->get('produto_id');
        $duracao = (int)($params['duracao'] ?? $request->post('duracao') ?? 15);
        $formato = $params['formato'] ?? $request->post('formato') ?? 'stories';
        $template = $params['template'] ?? $request->post('template') ?? 'modern_dark';
        $corTema = $params['corTema'] ?? $params['cor_tema'] ?? $request->post('corTema') ?? 'dark';
        $fundoEstilo = $params['fundoEstilo'] ?? $params['fundo_estilo'] ?? $request->post('fundoEstilo') ?? 'gradient';
        $trilhaSonora = $params['trilhaSonora'] ?? $params['trilha_sonora'] ?? $request->post('trilha_sonora') ?? $request->post('trilhaSonora') ?? 'promo_bg.mp3';
        $efeitoVisual = $params['efeitoVisual'] ?? $params['efeito_visual'] ?? $request->post('efeitoVisual') ?? $request->post('efeito_visual') ?? 'none';
        $modoComposicao = $params['modoComposicao'] ?? $params['modo_composicao'] ?? $request->post('modoComposicao') ?? $request->post('modo_composicao') ?? 'hibrido';
        $ajusteDuracao = $params['ajusteDuracao'] ?? $params['ajuste_duracao'] ?? $request->post('ajusteDuracao') ?? $request->post('ajuste_duracao') ?? 'trim';
        $ajusteProporcao = $params['ajusteProporcao'] ?? $params['ajuste_proporcao'] ?? $request->post('ajusteProporcao') ?? $request->post('ajuste_proporcao') ?? 'smart_blur';

        if (empty($produtoId)) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'O parâmetro produto_id é obrigatório.'
            ];
        }

        try {
            $service = new VideoGeneratorService();
            $videoModel = $service->solicitarGeracaoVideo($produtoId, $duracao, [
                'formato' => $formato,
                'template' => $template,
                'corTema' => $corTema,
                'fundoEstilo' => $fundoEstilo,
                'trilhaSonora' => $trilhaSonora,
                'efeitoVisual' => $efeitoVisual,
                'modoComposicao' => $modoComposicao,
                'ajusteDuracao' => $ajusteDuracao,
                'ajusteProporcao' => $ajusteProporcao,
            ]);

            return [
                'success' => true,
                'video_id' => $videoModel->id,
                'produto_id' => $videoModel->produto_id,
                'duracao' => $videoModel->duracao,
                'status' => $videoModel->status,
                'message' => 'Solicitação de vídeo enfileirada com sucesso. Acompanhe pelo status.'
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return [
                'success' => false,
                'message' => 'Falha ao solicitar geração do vídeo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida os produtos selecionados (se têm fotos) e analisa se possuem matriz de variações (cores/modelos).
     * POST /vendas/produto-video/verificar-matriz
     */
    public function actionVerificarMatriz()
    {
        $request = Yii::$app->request;
        $rawBody = json_decode($request->getRawBody(), true) ?: [];
        $bodyParams = $request->getBodyParams() ?: [];
        $produtosIds = $rawBody['produto_ids'] ?? $rawBody['produtos_ids'] ?? $bodyParams['produto_ids'] ?? $bodyParams['produtos_ids'] ?? $request->post('produto_ids', $request->post('produtos_ids', []));

        if (empty($produtosIds)) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'Nenhum produto foi selecionado.'
            ];
        }

        $produtosIds = (array)$produtosIds;
        $produtos = Produto::find()->where(['id' => $produtosIds, 'ativo' => true])->all();

        if (empty($produtos)) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Nenhum dos produtos selecionados foi localizado.'
            ];
        }

        $produtosSemFotos = [];
        $produtosComMatriz = [];
        $produtosSimples = [];
        $totalVideosUnico = 0;
        $totalVideosPorModo = 0;

        foreach ($produtos as $prod) {
            $totalFotos = (int)$prod->getFotos()->count();
            if ($totalFotos === 0) {
                $produtosSemFotos[] = [
                    'id' => $prod->id,
                    'nome' => $prod->nome
                ];
                continue;
            }

            // Verifica se possui matriz de variações
            $variantes = \app\modules\vendas\models\ProdutoVariante::find()
                ->where(['produto_id' => $prod->id, 'ativo' => true])
                ->orderBy(['cor' => SORT_ASC, 'tamanho' => SORT_ASC])
                ->all();

            $gruposCores = [];
            foreach ($variantes as $v) {
                $corNome = !empty(trim($v->cor)) ? trim($v->cor) : 'PADRAO';
                if (!isset($gruposCores[$corNome])) {
                    $gruposCores[$corNome] = [
                        'cor' => $corNome,
                        'total_tamanhos' => 0,
                        'estoque' => 0,
                    ];
                }
                $gruposCores[$corNome]['total_tamanhos']++;
                $gruposCores[$corNome]['estoque'] += (float)$v->estoque_atual;
            }

            if (!empty($gruposCores)) {
                $coresList = array_values(array_map(fn($g) => $g['cor'], $gruposCores));
                $produtosComMatriz[] = [
                    'id' => $prod->id,
                    'nome' => $prod->nome,
                    'total_fotos' => $totalFotos,
                    'total_cores' => count($coresList),
                    'cores' => $coresList,
                ];
                $totalVideosUnico += 1;
                $totalVideosPorModo += count($coresList);
            } else {
                $produtosSimples[] = [
                    'id' => $prod->id,
                    'nome' => $prod->nome,
                    'total_fotos' => $totalFotos,
                ];
                $totalVideosUnico += 1;
                $totalVideosPorModo += 1;
            }
        }

        return [
            'success' => true,
            'total_selecionados' => count($produtos),
            'total_validos' => count($produtosComMatriz) + count($produtosSimples),
            'tem_matriz' => count($produtosComMatriz) > 0,
            'produtos_com_matriz' => $produtosComMatriz,
            'produtos_simples' => $produtosSimples,
            'produtos_sem_fotos' => $produtosSemFotos,
            'previsao_unico' => $totalVideosUnico,
            'previsao_por_modo' => $totalVideosPorModo,
        ];
    }

    /**
     * Prepara a fila de renderização em lote distribuindo as músicas selecionadas e configurando os itens.
     * POST /vendas/produto-video/preparar-lote
     */
    public function actionPrepararLote()
    {
        $request = Yii::$app->request;
        $rawBody = json_decode($request->getRawBody(), true) ?: [];
        $bodyParams = $request->getBodyParams() ?: [];

        $produtosIds = $rawBody['produto_ids'] ?? $rawBody['produtos_ids'] ?? $bodyParams['produto_ids'] ?? $bodyParams['produtos_ids'] ?? $request->post('produto_ids', $request->post('produtos_ids', []));
        $trilhasSonoras = $rawBody['trilhas_sonoras'] ?? $rawBody['trilhas'] ?? $bodyParams['trilhas_sonoras'] ?? $bodyParams['trilhas'] ?? $request->post('trilhas_sonoras', $request->post('trilhas', []));
        $modoMatriz = $rawBody['modo_matriz'] ?? $bodyParams['modo_matriz'] ?? $request->post('modo_matriz', 'unico'); // 'unico' ou 'por_cor' / 'por_modo'

        $duracao = (int)($rawBody['duracao'] ?? $bodyParams['duracao'] ?? $request->post('duracao', 15));
        $formato = $rawBody['formato'] ?? $bodyParams['formato'] ?? $request->post('formato', 'stories');
        $template = $rawBody['template'] ?? $bodyParams['template'] ?? $request->post('template', 'modern_dark');
        $corTema = $rawBody['corTema'] ?? ($rawBody['cor_tema'] ?? ($bodyParams['corTema'] ?? ($bodyParams['cor_tema'] ?? $request->post('corTema', 'dark'))));
        $fundoEstilo = $rawBody['fundoEstilo'] ?? ($rawBody['fundo_estilo'] ?? ($bodyParams['fundoEstilo'] ?? ($bodyParams['fundo_estilo'] ?? $request->post('fundoEstilo', 'gradient'))));
        $efeitoVisual = $rawBody['efeitoVisual'] ?? ($rawBody['efeito_visual'] ?? ($bodyParams['efeitoVisual'] ?? ($bodyParams['efeito_visual'] ?? $request->post('efeitoVisual', 'none'))));
        $modoComposicao = $rawBody['modoComposicao'] ?? ($rawBody['modo_composicao'] ?? ($bodyParams['modoComposicao'] ?? ($bodyParams['modo_composicao'] ?? $request->post('modoComposicao', 'hibrido'))));
        $ajusteDuracao = $rawBody['ajusteDuracao'] ?? ($rawBody['ajuste_duracao'] ?? ($bodyParams['ajusteDuracao'] ?? ($bodyParams['ajuste_duracao'] ?? $request->post('ajusteDuracao', 'trim'))));
        $ajusteProporcao = $rawBody['ajusteProporcao'] ?? ($rawBody['ajuste_proporcao'] ?? ($bodyParams['ajusteProporcao'] ?? ($bodyParams['ajuste_proporcao'] ?? $request->post('ajusteProporcao', 'smart_blur'))));

        if (is_string($trilhasSonoras)) {
            $trilhasSonoras = array_filter(array_map('trim', explode(',', $trilhasSonoras)));
        }
        if (empty($trilhasSonoras)) {
            $trilhasSonoras = ['promo_bg.mp3'];
        }
        $trilhasSonoras = array_values($trilhasSonoras);

        $musicasMap = VideoGeneratorService::getMusicasDisponiveis();

        $produtosIds = (array)$produtosIds;
        $produtos = Produto::find()->where(['id' => $produtosIds, 'ativo' => true])->all();

        if (empty($produtos)) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'Nenhum produto válido para preparar o lote.'
            ];
        }

        $itens = [];
        $itemIndex = 0;

        foreach ($produtos as $prod) {
            $totalFotos = (int)$prod->getFotos()->count();
            if ($totalFotos === 0) {
                continue; // Ignora sem fotos
            }

            // Variantes da matriz
            $variantes = \app\modules\vendas\models\ProdutoVariante::find()
                ->where(['produto_id' => $prod->id, 'ativo' => true])
                ->orderBy(['cor' => SORT_ASC, 'tamanho' => SORT_ASC])
                ->all();

            $gruposCores = [];
            foreach ($variantes as $v) {
                $corNome = !empty(trim($v->cor)) ? trim($v->cor) : 'PADRAO';
                if (!isset($gruposCores[$corNome])) {
                    $gruposCores[$corNome] = true;
                }
            }

            if (!empty($gruposCores) && in_array($modoMatriz, ['por_modo', 'por_cor'])) {
                // Modo B: 1 vídeo para cada cor da matriz
                foreach (array_keys($gruposCores) as $corNome) {
                    $trilhaKey = (count($trilhasSonoras) === 1) 
                        ? $trilhasSonoras[0] 
                        : $trilhasSonoras[$itemIndex % count($trilhasSonoras)];

                    $nomeItem = (stripos($prod->nome, $corNome) !== false) ? $prod->nome : "{$prod->nome} ({$corNome})";

                    $itens[] = [
                        'item_id' => $itemIndex + 1,
                        'produto_id' => $prod->id,
                        'nome' => $nomeItem,
                        'titulo_preview' => $nomeItem,
                        'cor' => $corNome,
                        'modo_matriz' => 'por_cor',
                        'descricao_item' => "Vídeo individual para a cor {$corNome}",
                        'trilha_sonora' => $trilhaKey,
                        'trilha_nome' => $musicasMap[$trilhaKey]['nome'] ?? $trilhaKey,
                        'duracao' => $duracao,
                        'formato' => $formato,
                        'template' => $template,
                        'corTema' => $corTema,
                        'fundoEstilo' => $fundoEstilo,
                        'efeitoVisual' => $efeitoVisual,
                        'modoComposicao' => $modoComposicao,
                        'ajusteDuracao' => $ajusteDuracao,
                        'ajusteProporcao' => $ajusteProporcao,
                    ];
                    $itemIndex++;
                }
            } else {
                // Modo A (Vídeo Único) OU Produto Simples
                $trilhaKey = (count($trilhasSonoras) === 1) 
                    ? $trilhasSonoras[0] 
                    : $trilhasSonoras[$itemIndex % count($trilhasSonoras)];

                $desc = !empty($gruposCores) ? 'Vídeo carrossel compilando todas as cores da coleção' : 'Vídeo padrão do produto';

                $itens[] = [
                    'item_id' => $itemIndex + 1,
                    'produto_id' => $prod->id,
                    'nome' => $prod->nome,
                    'titulo_preview' => $prod->nome,
                    'cor' => null,
                    'modo_matriz' => !empty($gruposCores) ? 'unico' : null,
                    'descricao_item' => $desc,
                    'trilha_sonora' => $trilhaKey,
                    'trilha_nome' => $musicasMap[$trilhaKey]['nome'] ?? $trilhaKey,
                    'duracao' => $duracao,
                    'formato' => $formato,
                    'template' => $template,
                    'corTema' => $corTema,
                    'fundoEstilo' => $fundoEstilo,
                    'efeitoVisual' => $efeitoVisual,
                    'modoComposicao' => $modoComposicao,
                    'ajusteDuracao' => $ajusteDuracao,
                    'ajusteProporcao' => $ajusteProporcao,
                ];
                $itemIndex++;
            }
        }

        return [
            'success' => true,
            'total_itens' => count($itens),
            'modo_matriz' => $modoMatriz,
            'total_trilhas_selecionadas' => count($trilhasSonoras),
            'fila' => $itens,
            'itens' => $itens,
        ];
    }

    /**
     * Renderiza 1 vídeo atômico do lote de forma síncrona.
     * POST /vendas/produto-video/gerar-item-lote
     */
    public function actionGerarItemLote()
    {
        $request = Yii::$app->request;
        $rawBody = json_decode($request->getRawBody(), true) ?: [];
        $bodyParams = $request->getBodyParams() ?: [];

        $itemData = $rawBody['item'] ?? $bodyParams['item'] ?? $rawBody;
        if (empty($itemData)) {
            $itemData = $bodyParams ?: $_POST;
        }

        $produtoId = $itemData['produto_id'] ?? $request->post('produto_id');
        $duracao = (int)($itemData['duracao'] ?? $request->post('duracao', 15));
        $cor = $itemData['cor'] ?? $request->post('cor', null);
        $modoMatriz = $itemData['modo_matriz'] ?? $request->post('modo_matriz', null);
        $trilhaSonora = $itemData['trilha_sonora'] ?? $itemData['trilhaSonora'] ?? $request->post('trilha_sonora', 'promo_bg.mp3');

        $options = [
            'formato' => $itemData['formato'] ?? $request->post('formato', 'stories'),
            'template' => $itemData['template'] ?? $request->post('template', 'modern_dark'),
            'corTema' => $itemData['corTema'] ?? ($itemData['cor_tema'] ?? $request->post('cor_tema', 'dark')),
            'fundoEstilo' => $itemData['fundoEstilo'] ?? ($itemData['fundo_estilo'] ?? $request->post('fundo_estilo', 'gradient')),
            'trilhaSonora' => $trilhaSonora,
            'efeitoVisual' => $itemData['efeitoVisual'] ?? ($itemData['efeito_visual'] ?? $request->post('efeito_visual', 'none')),
            'modoComposicao' => $itemData['modoComposicao'] ?? ($itemData['modo_composicao'] ?? $request->post('modo_composicao', 'hibrido')),
            'ajusteDuracao' => $itemData['ajusteDuracao'] ?? ($itemData['ajuste_duracao'] ?? $request->post('ajuste_duracao', 'trim')),
            'ajusteProporcao' => $itemData['ajusteProporcao'] ?? ($itemData['ajuste_proporcao'] ?? $request->post('ajuste_proporcao', 'smart_blur')),
            'cor' => $cor,
            'modo_matriz' => $modoMatriz,
        ];

        if (empty($produtoId)) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'message' => 'produto_id é obrigatório.'];
        }

        try {
            $service = new VideoGeneratorService();
            $videoModel = $service->solicitarGeracaoVideo($produtoId, $duracao, $options, true);

            $videoData = [
                'id' => $videoModel->id,
                'produto_id' => $videoModel->produto_id,
                'nome' => $videoModel->produto ? $videoModel->produto->nome : 'Produto',
                'cor' => $cor,
                'url' => $videoModel->getUrlCompleta(),
                'download_url' => \yii\helpers\Url::to(['/vendas/produto-video/download', 'id' => $videoModel->id]),
                'formato' => $videoModel->formato,
                'duracao' => $videoModel->duracao,
                'tamanho_formatado' => $videoModel->getTamanhoFormatado(),
                'data_criacao' => date('d/m/Y H:i', strtotime($videoModel->data_criacao)),
                'resumo' => $videoModel->getResumoRecursosFormatted(),
            ];

            return [
                'success' => true,
                'video' => $videoData,
                'video_id' => $videoModel->id,
                'produto_id' => $videoModel->produto_id,
                'nome' => $videoData['nome'],
                'cor' => $cor,
                'video_url' => $videoModel->getUrlCompleta(),
                'formato' => $videoModel->formato,
                'duracao' => $videoModel->duracao,
                'tamanho_formatado' => $videoModel->getTamanhoFormatado(),
                'data_criacao' => $videoData['data_criacao'],
                'resumo' => $videoData['resumo'],
                'stats' => \app\modules\vendas\services\MediaStorageService::getEstatisticasVideos()
            ];
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return [
                'success' => false,
                'message' => 'Falha ao renderizar vídeo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Consulta do Status da Renderização.
     * GET /vendas/produto-video/status?id=<video_id>
     */
    public function actionStatus($id = null)
    {
        if (empty($id)) {
            $id = Yii::$app->request->get('id');
        }

        if (empty($id)) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'O parâmetro id é obrigatório.'
            ];
        }

        $videoModel = ProdutoVideo::findOne($id);
        if (!$videoModel) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Vídeo não encontrado para o ID especificado.'
            ];
        }

        return [
            'success' => true,
            'id' => $videoModel->id,
            'produto_id' => $videoModel->produto_id,
            'duracao' => $videoModel->duracao,
            'status' => $videoModel->status,
            'video_url' => $videoModel->getUrlCompleta(),
            'video_path' => $videoModel->video_path,
            'erro_mensagem' => $videoModel->erro_mensagem,
            'data_criacao' => $videoModel->data_criacao,
            'data_atualizacao' => $videoModel->data_atualizacao,
        ];
    }

    /**
     * Lista de Vídeos Gerados para o Produto.
     * GET /vendas/produto-video/list?produto_id=<produto_id>
     */
    public function actionList($produto_id = null)
    {
        if (empty($produto_id)) {
            $produto_id = Yii::$app->request->get('produto_id');
        }

        if (empty($produto_id)) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'O parâmetro produto_id é obrigatório.'
            ];
        }

        $videos = ProdutoVideo::find()
            ->where(['produto_id' => $produto_id])
            ->orderBy(['data_criacao' => SORT_DESC])
            ->all();

        $result = [];
        foreach ($videos as $v) {
            $result[] = [
                'id' => $v->id,
                'duracao' => $v->duracao,
                'formato' => $v->formato,
                'status' => $v->status,
                'video_url' => $v->getUrlCompleta(),
                'data_criacao' => $v->data_criacao,
            ];
        }

        return [
            'success' => true,
            'count' => count($result),
            'videos' => $result
        ];
    }

    /**
     * Exclui um vídeo e remove o arquivo .mp4 do disco para liberar espaço.
     * POST /vendas/produto-video/delete?id=<id>
     */
    public function actionDelete($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (empty($id)) {
            $id = Yii::$app->request->post('id') ?? Yii::$app->request->get('id');
        }

        if (empty($id)) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'message' => 'O ID do vídeo é obrigatório.'];
        }

        $videoModel = ProdutoVideo::findOne($id);
        if (!$videoModel) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'message' => 'Vídeo não encontrado.'];
        }

        // Deleta o arquivo físico do servidor se existir
        if (!empty($videoModel->video_path)) {
            $caminhoAbsoluto = Yii::getAlias('@app/web/') . ltrim($videoModel->video_path, '/');
            if (file_exists($caminhoAbsoluto)) {
                @unlink($caminhoAbsoluto);
            }
        }

        // Exclui o registro no banco
        if ($videoModel->delete()) {
            return [
                'success' => true,
                'message' => 'Vídeo excluído com sucesso e espaço em disco liberado.',
                'stats' => \app\modules\vendas\services\MediaStorageService::getEstatisticasVideos()
            ];
        }

        Yii::$app->response->statusCode = 500;
        return ['success' => false, 'message' => 'Erro ao excluir o registro do vídeo.'];
    }

    /**
     * Retorna o status de cota de armazenamento em megabytes dos vídeos do tenant.
     * GET /vendas/produto-video/storage-status
     */
    public function actionStorageStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'success' => true,
            'stats' => \app\modules\vendas\services\MediaStorageService::getEstatisticasVideos()
        ];
    }

    /**
     * Download direto do vídeo gerado.
     * GET /vendas/produto-video/download?id=<id>
     */
    public function actionDownload($id = null)
    {
        if (empty($id)) {
            $id = Yii::$app->request->get('id');
        }

        $videoModel = ProdutoVideo::findOne($id);
        if (!$videoModel || empty($videoModel->video_path)) {
            throw new \yii\web\NotFoundHttpException('Vídeo não encontrado para download.');
        }

        $caminhoAbsoluto = Yii::getAlias('@app/web/') . ltrim($videoModel->video_path, '/');
        if (!file_exists($caminhoAbsoluto)) {
            throw new \yii\web\NotFoundHttpException('Arquivo físico do vídeo não foi localizado no servidor.');
        }

        $nomeDownload = "video_promo_{$videoModel->duracao}s.mp4";
        return Yii::$app->response->sendFile($caminhoAbsoluto, $nomeDownload);
    }
}
