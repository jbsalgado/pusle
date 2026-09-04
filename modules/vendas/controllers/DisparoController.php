<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\DisparoMassa;
use app\modules\vendas\models\DisparoItem;
use app\modules\vendas\services\DisparoMassaService;
use app\modules\vendas\services\CardGeneratorService;
use app\modules\vendas\services\MediaStorageService;
use app\modules\vendas\models\ProdutoVariante;
use app\modules\vendas\models\ProdutoCard;
use app\modules\evolution\services\EvolutionService;
use app\modules\evolution\models\WhatsappConfig;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\Url;

/**
 * Controller Web para gerenciamento e monitoramento de disparos em massa.
 */
class DisparoController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Retorna o ID da loja (dono) para usar nas queries
     */
    protected function getLojaId()
    {
        $usuario = Yii::$app->user->identity;

        if (!$usuario) {
            return null;
        }

        if (isset($usuario->eh_dono_loja) && ($usuario->eh_dono_loja === true || $usuario->eh_dono_loja === 't' || $usuario->eh_dono_loja === 1)) {
            return $usuario->id;
        }

        $colaborador = Colaborador::getColaboradorLogado();

        if ($colaborador) {
            return $colaborador->usuario_id;
        }

        return $usuario->id;
    }

    /**
     * Retorna o status de conexão da Evolution API / WhatsApp para a loja logada.
     */
    public function actionStatusWhatsapp()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $lojaId = $this->getLojaId();
        if (!$lojaId || strlen((string)$lojaId) !== 36) {
            return [
                'success' => true,
                'connected' => false,
                'instance_name' => null,
                'status' => 'DISCONNECTED',
                'message' => 'Instância não configurada.'
            ];
        }

        try {
            $service = new EvolutionService();
            $connected = $service->checkStatus($lojaId);
            $config = WhatsappConfig::findByEmpresa($lojaId);

            return [
                'success' => true,
                'connected' => $connected,
                'instance_name' => $config ? $config->instance_name : null,
                'status' => $config ? $config->status : 'DISCONNECTED',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'connected' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Retorna a lista de clientes para seleção no modal de disparo em massa.
     */
    public function actionClientes()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $lojaId = $this->getLojaId();
        
        $query = Cliente::find();
        if ($lojaId && strlen((string)$lojaId) === 36) {
            $query->andWhere(['usuario_id' => $lojaId]);
        }

        $clientesRaw = $query->select(['id', 'nome_completo', 'telefone', 'email'])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->asArray()
            ->all();

        $clientes = array_map(function($c) {
            $c['nome'] = $c['nome_completo'];
            $c['celular'] = $c['telefone'];
            $c['tem_whatsapp'] = !empty($c['telefone']);
            $c['tem_email'] = !empty($c['email']) && filter_var($c['email'], FILTER_VALIDATE_EMAIL);
            return $c;
        }, $clientesRaw);

        return [
            'success' => true,
            'clientes' => $clientes
        ];
    }

    /**
     * Ação AJAX para criar uma nova campanha de disparo em massa e iniciar o processamento.
     */
    public function actionCriar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Sessão expirada. Faça login novamente.'];
        }

        $request = Yii::$app->request;
        $lojaId = $this->getLojaId();

        // Se for requisição JSON, ler do body
        $rawBody = json_decode($request->getRawBody(), true) ?: [];

        $videosIds = $rawBody['videos_ids'] ?? $request->post('videos_ids', []);
        $cardsIds = $rawBody['cards_ids'] ?? $request->post('cards_ids', []);
        $produtosIds = $rawBody['produtos_ids'] ?? $request->post('produtos_ids', []);
        $canais = $rawBody['canais'] ?? $request->post('canais', []);
        $clientesIds = $rawBody['clientes_ids'] ?? $request->post('clientes_ids', []);
        $telefonesManuais = $rawBody['telefones_manuais'] ?? $request->post('telefones_manuais', '');
        $emailsManuais = $rawBody['emails_manuais'] ?? $request->post('emails_manuais', '');
        $mensagemTexto = $rawBody['mensagem_texto'] ?? $request->post('mensagem_texto');
        
        $antiBanConfig = [
            'delay_segundos' => (int)($rawBody['delay_segundos'] ?? $request->post('delay_segundos', 5)),
            'lote_tamanho' => (int)($rawBody['lote_tamanho'] ?? $request->post('lote_tamanho', 10)),
            'pausa_lote_segundos' => (int)($rawBody['pausa_lote_segundos'] ?? $request->post('pausa_lote_segundos', 60)),
            'incluir_optout' => !empty($rawBody['incluir_optout'] ?? $request->post('incluir_optout', false)),
        ];

        $visualOptions = [
            'template' => $rawBody['template'] ?? $request->post('template', 'modern_dark'),
            'corTema' => $rawBody['cor_tema'] ?? $request->post('cor_tema', 'dark'),
            'fundoEstilo' => $rawBody['fundo_estilo'] ?? $request->post('fundo_estilo', 'gradient'),
            'enquadramentoFoto' => $rawBody['enquadramento_foto'] ?? ($rawBody['enquadramentoFoto'] ?? $request->post('enquadramento_foto', 'auto')),
            'rotacaoFoto' => $rawBody['rotacao_foto'] ?? ($rawBody['rotacaoFoto'] ?? $request->post('rotacao_foto', 'auto')),
            'mensagemCard' => trim($rawBody['mensagem_card'] ?? ($rawBody['mensagemCard'] ?? $request->post('mensagem_card', ''))),
        ];
        $visualOptions = array_merge($visualOptions, $antiBanConfig);

        try {
            $service = new DisparoMassaService();

            if (!empty($videosIds)) {
                $campanha = $service->criarCampanhaDisparoVideosExistentes(
                    $lojaId,
                    (array)$videosIds,
                    (array)$canais,
                    (array)$clientesIds,
                    $mensagemTexto,
                    $telefonesManuais,
                    $antiBanConfig
                );
            } elseif (!empty($cardsIds)) {
                $campanha = $service->criarCampanhaDisparoCardsExistentes(
                    $lojaId,
                    (array)$cardsIds,
                    (array)$canais,
                    (array)$clientesIds,
                    $mensagemTexto,
                    $telefonesManuais,
                    $antiBanConfig
                );
            } else {
                $campanha = $service->criarCampanhaDisparo(
                    $lojaId,
                    (array)$produtosIds,
                    (array)$canais,
                    (array)$clientesIds,
                    $visualOptions,
                    $mensagemTexto,
                    $telefonesManuais,
                    $emailsManuais
                );
            }

            // Tentar processar apenas o 1º item de forma síncrona para resposta rápida (< 1s) e liberar o frontend
            try {
                $service->processarFilaDisparo($campanha->id, 1);
            } catch (\Throwable $t) {
                Yii::warning("DisparoController::actionCriar — aviso no processamento inicial da fila: " . $t->getMessage(), __METHOD__);
            }

            return [
                'success' => true,
                'message' => 'Campanha criada e disparo iniciado com sucesso!',
                'disparo_id' => $campanha->id,
                'total_itens' => $campanha->total_itens,
            ];
        } catch (\Throwable $e) {
            Yii::error("DisparoController::actionCriar — Erro ao criar disparo: " . $e->getMessage() . "\n" . $e->getTraceAsString(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Erro ao criar disparo em massa: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retorna o status e progresso em tempo real de uma campanha de disparo.
     */
    public function actionStatus($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $campanha = DisparoMassa::findOne($id);
        if (!$campanha) {
            return ['success' => false, 'message' => 'Campanha não encontrada.'];
        }

        // Tentar processar mais um item da fila se ainda houver pendentes
        if ($campanha->status === DisparoMassa::STATUS_PENDENTE || $campanha->status === DisparoMassa::STATUS_PROCESSANDO) {
            try {
                $service = new DisparoMassaService();
                $service->processarFilaDisparo($campanha->id, 1);
                $campanha->refresh();
            } catch (\Throwable $t) {
                Yii::warning("DisparoController::actionStatus — aviso ao processar rodada: " . $t->getMessage(), __METHOD__);
            }
        }

        // Buscar histórico de todos os itens processados (enviados com sucesso e com erro)
        $todosItens = DisparoItem::find()
            ->select(['id', 'canal', 'destino', 'status', 'enviado_em', 'erro_mensagem'])
            ->where(['disparo_id' => $campanha->id])
            ->andWhere(['in', 'status', [DisparoItem::STATUS_ENVIADO, DisparoItem::STATUS_ERRO]])
            ->orderBy(['id' => SORT_ASC])
            ->asArray()
            ->all();

        $errosItens = array_values(array_filter($todosItens, function($i) {
            return $i['status'] === DisparoItem::STATUS_ERRO;
        }));

        $dispData = [
            'id' => $campanha->id,
            'status' => $campanha->status,
            'total_itens' => (int)$campanha->total_itens,
            'itens_enviados' => (int)$campanha->itens_enviados,
            'itens_erro' => (int)$campanha->itens_erro,
            'progresso_percentual' => $campanha->getProgressoPercentual(),
            'itens' => $todosItens,
            'erros' => $errosItens
        ];

        return array_merge([
            'success' => true,
            'disparo' => $dispData,
        ], $dispData);
    }

    /**
     * Re-executa os envios dos itens que falharam em uma campanha.
     */
    public function actionReenviarErros($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $campanha = DisparoMassa::findOne($id);
        if (!$campanha) {
            return ['success' => false, 'message' => 'Campanha não encontrada.'];
        }

        try {
            $service = new DisparoMassaService();
            $reprocessados = $service->retentarItensComErro($campanha->id);

            return [
                'success' => true,
                'message' => "Re-processamento iniciado para {$reprocessados} item(ns) com falha.",
                'disparo_id' => $campanha->id,
                'reprocessados' => $reprocessados,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao reprocessar disparos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gera cards promocionais em lote para download ou compartilhamento manual.
     * Suporta desmembramento por matriz (1 card por variante ativa) e valida cota de 50MB.
     */
    public function actionGerarLoteCards()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Sessão expirada. Faça login novamente.'];
        }

        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $request = Yii::$app->request;
        $lojaId = $this->getLojaId();
        $rawBody = json_decode($request->getRawBody(), true) ?: [];

        $produtosIds = $rawBody['produtos_ids'] ?? $request->post('produtos_ids', []);
        $formatoEscolhido = $rawBody['formato'] ?? $request->post('formato', 'feed');
        $mensagemBase = $rawBody['mensagem_texto'] ?? $request->post('mensagem_texto', '');

        $visualOptions = [
            'template' => $rawBody['template'] ?? $request->post('template', 'modern_dark'),
            'corTema' => $rawBody['cor_tema'] ?? $request->post('cor_tema', 'dark'),
            'fundoEstilo' => $rawBody['fundo_estilo'] ?? $request->post('fundo_estilo', 'gradient'),
            'enquadramentoFoto' => $rawBody['enquadramento_foto'] ?? ($rawBody['enquadramentoFoto'] ?? $request->post('enquadramento_foto', 'auto')),
            'rotacaoFoto' => $rawBody['rotacao_foto'] ?? ($rawBody['rotacaoFoto'] ?? $request->post('rotacao_foto', 'auto')),
            'mensagemCard' => trim($rawBody['mensagem_card'] ?? ($rawBody['mensagemCard'] ?? $request->post('mensagem_card', ''))),
        ];

        $modoMatriz = $rawBody['modo_matriz'] ?? $request->post('modo_matriz', 'por_cor');
        $apenasComEstoque = isset($rawBody['apenas_com_estoque']) 
            ? filter_var($rawBody['apenas_com_estoque'], FILTER_VALIDATE_BOOLEAN) 
            : filter_var($request->post('apenas_com_estoque', true), FILTER_VALIDATE_BOOLEAN);

        if (empty($produtosIds)) {
            return ['success' => false, 'message' => 'Nenhum produto selecionado para gerar os cards.'];
        }

        // 1. Limpeza preventiva automática de cards com mais de 24h
        MediaStorageService::limparCardsExpirados(24);

        // 2. Verificar cota inicial de 50MB
        $statsInicial = MediaStorageService::getEstatisticasCards($lojaId);
        if ($statsInicial['excedido']) {
            return [
                'success' => false,
                'message' => "Limite de 50 MB de armazenamento de cards da loja atingido ({$statsInicial['usado_mb']} MB usados). Exclua cards antigos para liberar espaço.",
                'stats' => $statsInicial
            ];
        }

        // Definir formatos a serem gerados
        $formatos = [];
        if ($formatoEscolhido === 'ambos') {
            $formatos = ['feed', 'stories'];
        } elseif ($formatoEscolhido === 'stories') {
            $formatos = ['stories'];
        } else {
            $formatos = ['feed'];
        }

        $cardService = new CardGeneratorService();
        $cardsGerados = [];
        $arquivosParaZip = [];
        $limiteAtingido = false;

        foreach ((array)$produtosIds as $prodId) {
            if ($limiteAtingido) {
                break;
            }

            $produto = Produto::findOne($prodId);
            if (!$produto) {
                continue;
            }

            // Verificar se o produto possui Matriz (Cor/Tamanho)
            $itensMatriz = [];
            if ($produto->modo_grade === 'matriz' || $produto->possuiGrade || ProdutoVariante::find()->where(['produto_id' => $produto->id, 'ativo' => true])->exists()) {
                $itensMatriz = ProdutoVariante::find()
                    ->where(['produto_id' => $produto->id, 'ativo' => true])
                    ->orderBy(['cor' => SORT_ASC, 'tamanho' => SORT_ASC])
                    ->all();
            }

            // Agrupar matriz por Cor/Modelo (1 card por cor com grade de tamanhos e preços)
            $alvosGeracao = [];
            if (!empty($itensMatriz)) {
                $gruposCor = [];
                foreach ($itensMatriz as $varItem) {
                    $chaveCor = !empty(trim($varItem->cor)) ? trim($varItem->cor) : 'PADRAO';
                    if (!isset($gruposCor[$chaveCor])) {
                        $gruposCor[$chaveCor] = [
                            'cor' => !empty(trim($varItem->cor)) ? trim($varItem->cor) : null,
                            'variantes' => [],
                        ];
                    }
                    $gruposCor[$chaveCor]['variantes'][] = $varItem;
                }

                foreach ($gruposCor as $grupo) {
                    $varsGrupo = $grupo['variantes'];
                    if ($apenasComEstoque) {
                        $varsComEstoque = array_filter($varsGrupo, fn($v) => (float)$v->estoque_atual > 0);
                        if (!empty($varsComEstoque)) {
                            $varsGrupo = array_values($varsComEstoque);
                        }
                    }

                    $gradeTamanhos = [];
                    $precos = [];
                    foreach ($varsGrupo as $v) {
                        $p = (float)$v->getPrecoVendaEfetivo();
                        $precos[] = $p;
                        $gradeTamanhos[] = [
                            'tamanho' => $v->tamanho,
                            'preco' => $p,
                            'preco_formatado' => 'R$ ' . number_format($p, 2, ',', '.'),
                            'estoque' => (float)$v->estoque_atual,
                        ];
                    }
                    $mesmoPreco = (count(array_unique($precos)) <= 1);
                    $precoMin = !empty($precos) ? min($precos) : (float)$produto->getPrecoFinal();
                    $precoMax = !empty($precos) ? max($precos) : (float)$produto->getPrecoFinal();

                    $corRedundante = !empty($grupo['cor']) && (stripos($produto->nome, $grupo['cor']) !== false || stripos($grupo['cor'], $produto->nome) !== false);
                    $nomeGrupo = $produto->nome;
                    if (!empty($grupo['cor']) && !$corRedundante) {
                        $nomeGrupo = "{$produto->nome} ({$grupo['cor']})";
                    }

                    $alvosGeracao[] = [
                        'variante' => $varsGrupo[0],
                        'cor' => $grupo['cor'],
                        'nome' => $nomeGrupo,
                        'preco' => $precoMin,
                        'preco_min' => $precoMin,
                        'preco_max' => $precoMax,
                        'mesmo_preco' => $mesmoPreco,
                        'grade_tamanhos' => $gradeTamanhos,
                        'tamanhos_resumo' => implode(', ', array_unique(array_column($gradeTamanhos, 'tamanho'))),
                    ];
                }
            } else {
                $alvosGeracao[] = [
                    'variante' => null,
                    'cor' => null,
                    'nome' => $produto->nome,
                    'preco' => $produto->getPrecoFinal(),
                    'preco_min' => $produto->getPrecoFinal(),
                    'preco_max' => $produto->getPrecoFinal(),
                    'mesmo_preco' => true,
                    'grade_tamanhos' => [],
                    'tamanhos_resumo' => null,
                ];
            }

            foreach ($alvosGeracao as $alvo) {
                foreach ($formatos as $fmt) {
                    // Valida se a cota de 50MB foi atingida antes de renderizar
                    $statsAtual = MediaStorageService::getEstatisticasCards($lojaId);
                    if ($statsAtual['excedido']) {
                        $limiteAtingido = true;
                        break 3;
                    }

                    try {
                        $opts = $visualOptions;
                        if (!empty($alvo['cor'])) {
                            $opts['corMatriz'] = $alvo['cor'];
                        }
                        if (!empty($alvo['grade_tamanhos'])) {
                            $opts['gradeTamanhos'] = $alvo['grade_tamanhos'];
                            $opts['mesmoPreco'] = $alvo['mesmo_preco'];
                            $opts['precoMin'] = $alvo['preco_min'];
                            $opts['precoMax'] = $alvo['preco_max'];
                        } elseif ($alvo['variante']) {
                            $opts['variante'] = $alvo['variante'];
                        }

                        $card = $cardService->gerarCard($produto, $fmt, $opts);
                        if ($card && !empty($card->card_path)) {
                            $caminhoFisico = Yii::getAlias('@app/web/') . ltrim($card->card_path, '/');
                            if (file_exists($caminhoFisico)) {
                                if (!empty($alvo['grade_tamanhos']) && !$alvo['mesmo_preco']) {
                                    $precoFinal = 'A partir de R$ ' . number_format($alvo['preco_min'], 2, ',', '.');
                                } else {
                                    $precoFinal = 'R$ ' . number_format($alvo['preco'], 2, ',', '.');
                                }
                                $msgFormatada = str_replace(
                                    ['{PRODUTO}', '{PRECO}', '{NOME}', '{MENSAGEM_PROMOCIONAL}'],
                                    [$alvo['nome'], $precoFinal, 'Cliente', $visualOptions['mensagemCard'] ?? ''],
                                    $mensagemBase
                                );
                                if (!empty($alvo['tamanhos_resumo'])) {
                                    $msgFormatada .= "\n📏 Tamanhos: " . $alvo['tamanhos_resumo'];
                                }

                                $whatsappLink = 'https://api.whatsapp.com/send?text=' . urlencode($msgFormatada);

                                $cardInfo = [
                                    'id' => $card->id,
                                    'produto_id' => $produto->id,
                                    'produto_nome' => $alvo['nome'],
                                    'eh_matriz' => !empty($alvo['cor']) || !empty($alvo['variante']),
                                    'cor' => $alvo['cor'],
                                    'tamanho' => !empty($alvo['tamanhos_resumo']) ? $alvo['tamanhos_resumo'] : ($alvo['variante'] ? $alvo['variante']->tamanho : null),
                                    'grade_tamanhos' => $alvo['grade_tamanhos'],
                                    'formato' => $fmt,
                                    'formato_label' => $fmt === 'stories' ? 'Stories (9:16)' : 'Feed (1:1)',
                                    'card_url' => $card->getUrlCompleta(),
                                    'download_url' => Url::to(['/vendas/disparo/download-card', 'id' => $card->id]),
                                    'tamanho' => $card->getTamanhoFormatado(),
                                    'mensagem_texto' => $msgFormatada,
                                    'whatsapp_link' => $whatsappLink,
                                    'nome_arquivo' => basename($card->card_path),
                                ];

                                $cardsGerados[] = $cardInfo;

                                // Nome descritivo dentro do ZIP
                                $slugNome = Inflector::slug($alvo['nome'], '_');
                                if (empty($slugNome)) {
                                    $slugNome = 'produto_' . substr($produto->id, 0, 8);
                                }
                                $nomeNoZip = $slugNome . '_' . $fmt . '_' . substr($card->id, 0, 6) . '.' . pathinfo($caminhoFisico, PATHINFO_EXTENSION);

                                $arquivosParaZip[] = [
                                    'caminho' => $caminhoFisico,
                                    'nome_zip' => $nomeNoZip
                                ];
                            }
                        }
                    } catch (\Throwable $t) {
                        Yii::error("Erro ao gerar card para produto {$prodId} formato {$fmt}: " . $t->getMessage(), __METHOD__);
                        if (strpos($t->getMessage(), '50 MB') !== false || strpos($t->getMessage(), 'Limite') !== false) {
                            $limiteAtingido = true;
                            break 3;
                        }
                    }
                }
            }
        }

        if (empty($cardsGerados)) {
            return [
                'success' => false,
                'message' => 'Não foi possível gerar nenhum card promocional. Verifique as fotos dos produtos selecionados ou espaço em disco.',
                'stats' => MediaStorageService::getEstatisticasCards($lojaId)
            ];
        }

        // Criar ZIP se houver cards gerados
        $zipUrl = null;
        $zipNome = null;
        $zipTamanhoFormatado = null;

        if (!empty($arquivosParaZip) && class_exists('ZipArchive')) {
            $zipDir = Yii::getAlias('@app/web/uploads/cards/zip');
            if (!is_dir($zipDir)) {
                FileHelper::createDirectory($zipDir, 0777, true);
            }

            $zipNome = 'cards_promocionais_' . date('Ymd_His') . '_' . substr(uniqid(), -4) . '.zip';
            $zipCaminhoCompleto = $zipDir . DIRECTORY_SEPARATOR . $zipNome;

            $zip = new \ZipArchive();
            if ($zip->open($zipCaminhoCompleto, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($arquivosParaZip as $itemZip) {
                    $zip->addFile($itemZip['caminho'], $itemZip['nome_zip']);
                }
                $zip->close();

                if (file_exists($zipCaminhoCompleto)) {
                    $zipTamanhoBytes = filesize($zipCaminhoCompleto);
                    if ($zipTamanhoBytes >= 1048576) {
                        $zipTamanhoFormatado = number_format($zipTamanhoBytes / 1048576, 2) . ' MB';
                    } else {
                        $zipTamanhoFormatado = number_format($zipTamanhoBytes / 1024, 1) . ' KB';
                    }
                    $zipUrl = Url::to(['/vendas/disparo/download-zip', 'arquivo' => $zipNome]);
                }
            }
        }

        $mensagemRetorno = count($cardsGerados) . ' card(s) gerado(s) com sucesso!';
        if ($limiteAtingido) {
            $mensagemRetorno .= ' (Atenção: A cota de 50 MB foi atingida, novos cards foram pausados. Exclua cards antigos para gerar mais.)';
        }

        return [
            'success' => true,
            'message' => $mensagemRetorno,
            'total_cards' => count($cardsGerados),
            'limite_atingido' => $limiteAtingido,
            'zip_url' => $zipUrl,
            'zip_nome' => $zipNome,
            'zip_tamanho' => $zipTamanhoFormatado,
            'cards' => $cardsGerados,
            'stats' => MediaStorageService::getEstatisticasCards($lojaId),
        ];
    }

    /**
     * Prepara o lote de cards para geração progressiva com feedback visual em tempo real no frontend.
     * Retorna em < 100ms a lista detalhada de itens que devem ser gerados (expandindo variações de matriz).
     */
    public function actionPrepararLoteCards()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Sessão expirada. Faça login novamente.'];
        }

        $request = Yii::$app->request;
        $lojaId = $this->getLojaId();
        $rawBody = json_decode($request->getRawBody(), true) ?: [];

        $produtosIds = $rawBody['produtos_ids'] ?? $request->post('produtos_ids', []);
        $formatoEscolhido = $rawBody['formato'] ?? $request->post('formato', 'feed');
        $mensagemBase = $rawBody['mensagem_texto'] ?? $request->post('mensagem_texto', '');

        $visualOptions = [
            'template' => $rawBody['template'] ?? $request->post('template', 'modern_dark'),
            'corTema' => $rawBody['cor_tema'] ?? $request->post('cor_tema', 'dark'),
            'fundoEstilo' => $rawBody['fundo_estilo'] ?? $request->post('fundo_estilo', 'gradient'),
            'enquadramentoFoto' => $rawBody['enquadramento_foto'] ?? ($rawBody['enquadramentoFoto'] ?? $request->post('enquadramento_foto', 'auto')),
            'rotacaoFoto' => $rawBody['rotacao_foto'] ?? ($rawBody['rotacaoFoto'] ?? $request->post('rotacao_foto', 'auto')),
            'mensagemCard' => trim($rawBody['mensagem_card'] ?? ($rawBody['mensagemCard'] ?? $request->post('mensagem_card', ''))),
        ];

        if (empty($produtosIds)) {
            return ['success' => false, 'message' => 'Nenhum produto selecionado para gerar os cards.'];
        }

        // Limpeza preventiva automática de cards com mais de 24h
        MediaStorageService::limparCardsExpirados(24);

        // Verificar cota inicial de 50MB
        $statsInicial = MediaStorageService::getEstatisticasCards($lojaId);
        if ($statsInicial['excedido']) {
            return [
                'success' => false,
                'message' => "Limite de 50 MB de armazenamento de cards da loja atingido ({$statsInicial['usado_mb']} MB usados). Exclua cards antigos para liberar espaço.",
                'stats' => $statsInicial
            ];
        }

        $modoMatriz = $rawBody['modo_matriz'] ?? $request->post('modo_matriz', 'por_cor');
        $apenasComEstoque = isset($rawBody['apenas_com_estoque']) 
            ? filter_var($rawBody['apenas_com_estoque'], FILTER_VALIDATE_BOOLEAN) 
            : filter_var($request->post('apenas_com_estoque', true), FILTER_VALIDATE_BOOLEAN);

        $formatos = [];
        if ($formatoEscolhido === 'ambos') {
            $formatos = ['feed', 'stories'];
        } elseif ($formatoEscolhido === 'stories') {
            $formatos = ['stories'];
        } else {
            $formatos = ['feed'];
        }

        $itensPreparados = [];
        $index = 0;

        foreach ((array)$produtosIds as $prodId) {
            $produto = Produto::findOne($prodId);
            if (!$produto) {
                continue;
            }

            // Verificar se o produto possui Matriz (Cor/Tamanho)
            $itensMatriz = [];
            if ($produto->modo_grade === 'matriz' || $produto->possuiGrade || ProdutoVariante::find()->where(['produto_id' => $produto->id, 'ativo' => true])->exists()) {
                $itensMatriz = ProdutoVariante::find()
                    ->where(['produto_id' => $produto->id, 'ativo' => true])
                    ->orderBy(['cor' => SORT_ASC, 'tamanho' => SORT_ASC])
                    ->all();
            }

            $alvos = [];
            if (!empty($itensMatriz)) {
                if ($modoMatriz === 'por_item') {
                    // Modo desmembrado: 1 card por item individual da matriz
                    foreach ($itensMatriz as $varItem) {
                        if ($apenasComEstoque && (float)$varItem->estoque_atual <= 0) {
                            continue;
                        }
                        $alvos[] = [
                            'variante_id' => $varItem->id,
                            'nome' => $varItem->getNomeFormatado(),
                            'preco' => $varItem->getPrecoVendaEfetivo(),
                            'preco_min' => $varItem->getPrecoVendaEfetivo(),
                            'preco_max' => $varItem->getPrecoVendaEfetivo(),
                            'mesmo_preco' => true,
                            'cor' => $varItem->cor,
                            'tamanho' => $varItem->tamanho,
                            'grade_tamanhos' => [],
                            'tamanhos_resumo' => $varItem->tamanho,
                        ];
                    }
                } else {
                    // Modo padrão e recomendado: 1 card por Cor/Modelo com grade de tamanhos e preços
                    $gruposCor = [];
                    foreach ($itensMatriz as $varItem) {
                        $chaveCor = !empty(trim($varItem->cor)) ? trim($varItem->cor) : 'PADRAO';
                        if (!isset($gruposCor[$chaveCor])) {
                            $gruposCor[$chaveCor] = [
                                'cor' => !empty(trim($varItem->cor)) ? trim($varItem->cor) : null,
                                'variantes' => [],
                            ];
                        }
                        $gruposCor[$chaveCor]['variantes'][] = $varItem;
                    }

                    foreach ($gruposCor as $grupo) {
                        $varsGrupo = $grupo['variantes'];
                        if ($apenasComEstoque) {
                            $varsComEstoque = array_filter($varsGrupo, fn($v) => (float)$v->estoque_atual > 0);
                            if (!empty($varsComEstoque)) {
                                $varsGrupo = array_values($varsComEstoque);
                            }
                        }

                        $gradeTamanhos = [];
                        $precos = [];
                        foreach ($varsGrupo as $v) {
                            $p = (float)$v->getPrecoVendaEfetivo();
                            $precos[] = $p;
                            $gradeTamanhos[] = [
                                'tamanho' => $v->tamanho,
                                'preco' => $p,
                                'preco_formatado' => 'R$ ' . number_format($p, 2, ',', '.'),
                                'estoque' => (float)$v->estoque_atual,
                            ];
                        }
                        $mesmoPreco = (count(array_unique($precos)) <= 1);
                        $precoMin = !empty($precos) ? min($precos) : (float)$produto->getPrecoFinal();
                        $precoMax = !empty($precos) ? max($precos) : (float)$produto->getPrecoFinal();

                        $corRedundante = !empty($grupo['cor']) && (stripos($produto->nome, $grupo['cor']) !== false || stripos($grupo['cor'], $produto->nome) !== false);
                        $nomeGrupo = $produto->nome;
                        if (!empty($grupo['cor']) && !$corRedundante) {
                            $nomeGrupo = "{$produto->nome} ({$grupo['cor']})";
                        }

                        $alvos[] = [
                            'variante_id' => $varsGrupo[0]->id,
                            'nome' => $nomeGrupo,
                            'preco' => $precoMin,
                            'preco_min' => $precoMin,
                            'preco_max' => $precoMax,
                            'mesmo_preco' => $mesmoPreco,
                            'cor' => $grupo['cor'],
                            'tamanho' => null,
                            'grade_tamanhos' => $gradeTamanhos,
                            'tamanhos_resumo' => implode(', ', array_unique(array_column($gradeTamanhos, 'tamanho'))),
                        ];
                    }
                }
            } else {
                $alvos[] = [
                    'variante_id' => null,
                    'nome' => $produto->nome,
                    'preco' => $produto->getPrecoFinal(),
                    'preco_min' => $produto->getPrecoFinal(),
                    'preco_max' => $produto->getPrecoFinal(),
                    'mesmo_preco' => true,
                    'cor' => null,
                    'tamanho' => null,
                    'grade_tamanhos' => [],
                    'tamanhos_resumo' => null,
                ];
            }

            foreach ($alvos as $alvo) {
                foreach ($formatos as $fmt) {
                    $itensPreparados[] = [
                        'index' => $index++,
                        'produto_id' => $produto->id,
                        'variante_id' => $alvo['variante_id'],
                        'nome' => $alvo['nome'],
                        'preco' => $alvo['preco'],
                        'preco_min' => $alvo['preco_min'],
                        'preco_max' => $alvo['preco_max'],
                        'mesmo_preco' => $alvo['mesmo_preco'],
                        'cor' => $alvo['cor'],
                        'tamanho' => $alvo['tamanho'],
                        'grade_tamanhos' => $alvo['grade_tamanhos'],
                        'tamanhos_resumo' => $alvo['tamanhos_resumo'],
                        'formato' => $fmt,
                        'formato_label' => ($fmt === 'stories' ? 'Stories (9:16)' : 'Feed (1:1)'),
                        'visual_options' => $visualOptions,
                        'mensagem_texto' => $mensagemBase,
                    ];
                }
            }
        }

        if (empty($itensPreparados)) {
            return [
                'success' => false,
                'message' => 'Nenhum item válido encontrado para geração dos cards.'
            ];
        }

        // Liberar lock de sessão imediatamente
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return [
            'success' => true,
            'total' => count($itensPreparados),
            'itens' => $itensPreparados,
            'stats' => $statsInicial,
        ];
    }

    /**
     * Gera um único card de forma individual e atômica.
     * Responde em ~2 segundos sem travar a interface e sem risco de timeout.
     */
    public function actionGerarCardItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Sessão expirada. Faça login novamente.'];
        }

        $request = Yii::$app->request;
        $lojaId = $this->getLojaId();
        $rawBody = json_decode($request->getRawBody(), true) ?: [];

        $prodId = $rawBody['produto_id'] ?? $request->post('produto_id');
        $varianteId = $rawBody['variante_id'] ?? $request->post('variante_id');
        $fmt = $rawBody['formato'] ?? $request->post('formato', 'feed');
        $visualOptions = $rawBody['visual_options'] ?? $request->post('visual_options', []);
        $mensagemBase = $rawBody['mensagem_texto'] ?? $request->post('mensagem_texto', '');

        $cor = $rawBody['cor'] ?? $request->post('cor');
        $gradeTamanhos = $rawBody['grade_tamanhos'] ?? $request->post('grade_tamanhos', []);
        $mesmoPreco = isset($rawBody['mesmo_preco']) ? (bool)$rawBody['mesmo_preco'] : (bool)$request->post('mesmo_preco', true);
        $precoMin = $rawBody['preco_min'] ?? $request->post('preco_min');
        $precoMax = $rawBody['preco_max'] ?? $request->post('preco_max');
        $tamanhosResumo = $rawBody['tamanhos_resumo'] ?? $request->post('tamanhos_resumo', '');

        $produto = Produto::findOne($prodId);
        if (!$produto) {
            return ['success' => false, 'message' => 'Produto não encontrado.'];
        }

        // Valida se o armazenamento de 50MB foi atingido
        $statsAtual = MediaStorageService::getEstatisticasCards($lojaId);
        if ($statsAtual['excedido']) {
            return [
                'success' => false,
                'limite_atingido' => true,
                'message' => "Limite de 50 MB atingido ({$statsAtual['usado_mb']} MB usados).",
                'stats' => $statsAtual
            ];
        }

        $variante = null;
        if ($varianteId) {
            $variante = ProdutoVariante::findOne(['id' => $varianteId, 'produto_id' => $produto->id]);
        }

        $opts = $visualOptions;
        if (isset($rawBody['enquadramento_foto']) && !isset($opts['enquadramentoFoto'])) {
            $opts['enquadramentoFoto'] = $rawBody['enquadramento_foto'];
        }
        if (isset($rawBody['rotacao_foto']) && !isset($opts['rotacaoFoto'])) {
            $opts['rotacaoFoto'] = $rawBody['rotacao_foto'];
        }
        if (isset($rawBody['mensagem_card']) && !isset($opts['mensagemCard'])) {
            $opts['mensagemCard'] = trim($rawBody['mensagem_card']);
        } elseif (isset($rawBody['mensagemCard']) && !isset($opts['mensagemCard'])) {
            $opts['mensagemCard'] = trim($rawBody['mensagemCard']);
        }
        if (!empty($cor)) {
            $opts['corMatriz'] = $cor;
        }
        if (!empty($gradeTamanhos)) {
            $opts['gradeTamanhos'] = $gradeTamanhos;
            $opts['mesmoPreco'] = $mesmoPreco;
            if ($precoMin !== null) $opts['precoMin'] = (float)$precoMin;
            if ($precoMax !== null) $opts['precoMax'] = (float)$precoMax;
        }
        if ($variante) {
            $opts['variante'] = $variante;
        }

        // Libera sessão antes de executar o Puppeteer
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $cardService = new CardGeneratorService();
            $card = $cardService->gerarCard($produto, $fmt, $opts);

            if (!$card || empty($card->card_path)) {
                return ['success' => false, 'message' => 'Falha ao salvar o card renderizado.'];
            }

            $caminhoFisico = Yii::getAlias('@app/web/') . ltrim($card->card_path, '/');
            if (!file_exists($caminhoFisico)) {
                return ['success' => false, 'message' => 'Arquivo do card não encontrado no disco.'];
            }

            $alvoNome = !empty($cor) ? "{$produto->nome} ({$cor})" : ($variante ? $variante->getNomeFormatado() : $produto->nome);
            if (!empty($gradeTamanhos) && !$mesmoPreco && $precoMin !== null && $precoMin > 0) {
                $precoFormatado = 'A partir de R$ ' . number_format($precoMin, 2, ',', '.');
            } else {
                $alvoPreco = $variante ? $variante->getPrecoVendaEfetivo() : ($precoMin !== null ? $precoMin : $produto->getPrecoFinal());
                $precoFormatado = 'R$ ' . number_format($alvoPreco, 2, ',', '.');
            }

            $msgFormatada = str_replace(
                ['{PRODUTO}', '{PRECO}', '{NOME}', '{MENSAGEM_PROMOCIONAL}'],
                [$alvoNome, $precoFormatado, 'Cliente', $opts['mensagemCard'] ?? ''],
                $mensagemBase
            );

            if (!empty($tamanhosResumo)) {
                $msgFormatada .= "\n📏 Tamanhos disponíveis: " . $tamanhosResumo;
            }

            $whatsappLink = 'https://api.whatsapp.com/send?text=' . urlencode($msgFormatada);

            $cardInfo = [
                'id' => $card->id,
                'produto_id' => $produto->id,
                'produto_nome' => $alvoNome,
                'eh_matriz' => !empty($cor) || !empty($variante) || !empty($gradeTamanhos),
                'cor' => $cor ?: ($variante ? $variante->cor : null),
                'tamanho_grade' => !empty($tamanhosResumo) ? $tamanhosResumo : ($variante ? $variante->tamanho : null),
                'grade_tamanhos' => $gradeTamanhos,
                'mesmo_preco' => $mesmoPreco,
                'formato' => $fmt,
                'formato_label' => ($fmt === 'stories' ? 'Stories (9:16)' : 'Feed (1:1)'),
                'card_url' => $card->getUrlCompleta(),
                'download_url' => Url::to(['/vendas/disparo/download-card', 'id' => $card->id]),
                'peso_arquivo' => $card->getTamanhoFormatado(),
                'mensagem_card' => $opts['mensagemCard'] ?? '',
                'mensagem_texto' => $msgFormatada,
                'whatsapp_link' => $whatsappLink,
                'nome_arquivo' => basename($card->card_path),
            ];

            return [
                'success' => true,
                'card' => $cardInfo,
                'stats' => MediaStorageService::getEstatisticasCards($lojaId),
            ];
        } catch (\Throwable $t) {
            Yii::error("Erro ao gerar card unitário: " . $t->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Erro ao renderizar card: ' . $t->getMessage(),
            ];
        }
    }

    /**
     * Compacta os cards gerados em lote em um arquivo ZIP e retorna as estatísticas finais.
     */
    public function actionFinalizarLoteZip()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Sessão expirada. Faça login novamente.'];
        }

        $request = Yii::$app->request;
        $lojaId = $this->getLojaId();
        $rawBody = json_decode($request->getRawBody(), true) ?: [];

        $cardsIds = $rawBody['cards_ids'] ?? $request->post('cards_ids', []);

        $zipUrl = null;
        $zipTamanhoFormatado = null;

        if (!empty($cardsIds) && class_exists('ZipArchive')) {
            $zipDir = Yii::getAlias('@app/web/uploads/cards/zip');
            if (!is_dir($zipDir)) {
                FileHelper::createDirectory($zipDir, 0777, true);
            }

            $zipNome = 'cards_promocionais_' . date('Ymd_His') . '_' . substr(uniqid(), -4) . '.zip';
            $zipCaminhoCompleto = $zipDir . DIRECTORY_SEPARATOR . $zipNome;

            $zip = new \ZipArchive();
            if ($zip->open($zipCaminhoCompleto, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $cards = ProdutoCard::find()->where(['id' => $cardsIds])->all();
                foreach ($cards as $c) {
                    $caminhoFisico = Yii::getAlias('@app/web/') . ltrim($c->card_path, '/');
                    if (file_exists($caminhoFisico)) {
                        $meta = is_array($c->metadata) ? $c->metadata : json_decode($c->metadata, true);
                        $nomeProd = $meta['variante_nome'] ?? ($c->produto ? $c->produto->nome : 'card');
                        $slug = Inflector::slug($nomeProd, '_');
                        $nomeNoZip = $slug . '_' . $c->formato . '_' . substr($c->id, 0, 6) . '.' . pathinfo($caminhoFisico, PATHINFO_EXTENSION);
                        $zip->addFile($caminhoFisico, $nomeNoZip);
                    }
                }
                $zip->close();

                if (file_exists($zipCaminhoCompleto)) {
                    $bytes = filesize($zipCaminhoCompleto);
                    if ($bytes >= 1048576) {
                        $zipTamanhoFormatado = number_format($bytes / 1048576, 2) . ' MB';
                    } else {
                        $zipTamanhoFormatado = number_format($bytes / 1024, 1) . ' KB';
                    }
                    $zipUrl = Url::to(['/vendas/disparo/download-zip', 'arquivo' => $zipNome]);
                }
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return [
            'success' => true,
            'zip_url' => $zipUrl,
            'zip_tamanho' => $zipTamanhoFormatado,
            'stats' => MediaStorageService::getEstatisticasCards($lojaId),
        ];
    }

    /**
     * Download de card individual com exclusão imediata do servidor para liberação de espaço em disco.
     */
    public function actionDownloadCard($id)
    {
        $lojaId = $this->getLojaId();
        $card = ProdutoCard::findOne(['id' => $id, 'usuario_id' => $lojaId]);

        if (!$card) {
            throw new NotFoundHttpException('Card não encontrado ou já expirado/excluído.');
        }

        $caminhoFisico = Yii::getAlias('@app/web/') . ltrim($card->card_path, '/');
        if (!file_exists($caminhoFisico)) {
            $card->delete();
            throw new NotFoundHttpException('Arquivo físico do card não encontrado.');
        }

        $nomeArquivo = basename($caminhoFisico);
        $mimeType = 'image/webp';
        if (str_ends_with(strtolower($nomeArquivo), '.png')) {
            $mimeType = 'image/png';
        }

        // Registrar evento para excluir o arquivo físico e remover o registro do banco assim que for entregue ao navegador
        Yii::$app->response->on(Response::EVENT_AFTER_SEND, function() use ($caminhoFisico, $card) {
            try {
                if (file_exists($caminhoFisico)) {
                    @unlink($caminhoFisico);
                }
                $card->delete();
            } catch (\Throwable $t) {
                Yii::warning("Erro ao excluir card após download: " . $t->getMessage(), __METHOD__);
            }
        });

        return Yii::$app->response->sendFile($caminhoFisico, $nomeArquivo, [
            'mimeType' => $mimeType,
            'inline' => false
        ]);
    }

    /**
     * Download do arquivo ZIP gerado para cards em lote com exclusão imediata do arquivo compactado após envio.
     */
    public function actionDownloadZip($arquivo)
    {
        $arquivoSeguro = basename($arquivo);
        if (!preg_match('/^[a-zA-Z0-9_\-]+\.zip$/', $arquivoSeguro)) {
            throw new \yii\web\BadRequestHttpException('Nome de arquivo inválido.');
        }

        $caminhoCompleto = Yii::getAlias('@app/web/uploads/cards/zip/' . $arquivoSeguro);
        if (!file_exists($caminhoCompleto)) {
            throw new NotFoundHttpException('Arquivo compactado não encontrado ou já expirado/excluído.');
        }

        // Registrar exclusão imediata do ZIP após a entrega ao navegador
        Yii::$app->response->on(Response::EVENT_AFTER_SEND, function() use ($caminhoCompleto) {
            try {
                if (file_exists($caminhoCompleto)) {
                    @unlink($caminhoCompleto);
                }
            } catch (\Throwable $t) {
                Yii::warning("Erro ao excluir ZIP após download: " . $t->getMessage(), __METHOD__);
            }
        });

        return Yii::$app->response->sendFile($caminhoCompleto, $arquivoSeguro, [
            'mimeType' => 'application/zip',
            'inline' => false
        ]);
    }

    /**
     * Retorna estatísticas de consumo de armazenamento de cards da loja (cota de 50MB).
     */
    public function actionStatusEspaco()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Sessão expirada.'];
        }

        $lojaId = $this->getLojaId();
        MediaStorageService::limparCardsExpirados(24);

        $stats = MediaStorageService::getEstatisticasCards($lojaId);

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    /**
     * Exclui cards selecionados ou todos os cards gerados da loja para liberação de espaço em disco.
     */
    public function actionExcluirCards()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Sessão expirada.'];
        }

        $request = Yii::$app->request;
        $lojaId = $this->getLojaId();
        $rawBody = json_decode($request->getRawBody(), true) ?: [];

        $excluirTodos = !empty($rawBody['todos'] ?? $request->post('todos', false));
        $ids = $rawBody['ids'] ?? $request->post('ids', []);

        $cardsQuery = ProdutoCard::find()->where(['usuario_id' => $lojaId]);

        if (!$excluirTodos) {
            if (empty($ids) || !is_array($ids)) {
                return ['success' => false, 'message' => 'Nenhum card selecionado para exclusão.'];
            }
            $cardsQuery->andWhere(['id' => $ids]);
        }

        $cards = $cardsQuery->all();
        $excluidos = 0;
        $bytesLiberados = 0;

        foreach ($cards as $card) {
            if (!empty($card->card_path)) {
                $caminho = Yii::getAlias('@app/web/') . ltrim($card->card_path, '/');
                if (file_exists($caminho)) {
                    $bytesLiberados += filesize($caminho);
                    @unlink($caminho);
                }
            }
            if ($card->delete()) {
                $excluidos++;
            }
        }

        if ($excluirTodos) {
            // Limpa também arquivos ZIP residuais
            $diretorioZip = Yii::getAlias('@app/web/uploads/cards/zip');
            if (is_dir($diretorioZip)) {
                $zips = glob($diretorioZip . '/*.zip');
                foreach ($zips as $z) {
                    if (is_file($z)) {
                        @unlink($z);
                    }
                }
            }
        }

        MediaStorageService::limparArquivosOrfaosCards();

        $stats = MediaStorageService::getEstatisticasCards($lojaId);

        return [
            'success' => true,
            'message' => $excluirTodos 
                ? "Todos os cards gerados foram excluídos com sucesso. Espaço 100% liberado!" 
                : "{$excluidos} card(s) excluído(s) com sucesso!",
            'excluidos' => $excluidos,
            'stats' => $stats
        ];
    }
}


