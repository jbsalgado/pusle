<?php

namespace app\modules\vendas\services;

use Yii;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\DisparoMassa;
use app\modules\vendas\models\DisparoItem;
use app\modules\evolution\services\EvolutionService;
use app\modules\evolution\models\WhatsappConfig;

/**
 * Serviço responsável por orquestrar a criação e execução de disparos em massa de cards.
 */
class DisparoMassaService
{
    /** @var CardGeneratorService */
    private $cardService;

    /** @var EvolutionService */
    private $evolutionService;

    /** @var EmailDisparoService */
    private $emailService;

    public function __construct()
    {
        $this->cardService = new CardGeneratorService();
        $this->evolutionService = new EvolutionService();
        $this->emailService = new EmailDisparoService();
    }

    /**
     * Cria uma nova campanha de disparo em massa e agenda os itens na fila.
     *
     * @param string $usuarioId UUID do usuário/loja
     * @param array $produtosIds Lista de UUIDs dos produtos
     * @param array $canais Lista de canais selecionados ('status', 'whatsapp', 'email')
     * @param array $clientesIds Lista de UUIDs dos clientes (para whatsapp/email)
     * @param array $visualOptions Opções de renderização do card ('template', 'corTema', 'fundoEstilo')
     * @param string|null $mensagemTexto Mensagem promocional customizada
     * @param string|array $telefonesManuais Telefones adicionais digitados manualmente
     * @param string|array $emailsManuais E-mails adicionais digitados manualmente
     * @return DisparoMassa
     * @throws \Exception
     */
    public function criarCampanhaDisparo(
        string $usuarioId,
        array $produtosIds,
        array $canais,
        array $clientesIds = [],
        array $visualOptions = [],
        ?string $mensagemTexto = null,
        $telefonesManuais = '',
        $emailsManuais = ''
    ): DisparoMassa {
        if (empty($produtosIds)) {
            throw new \Exception("Nenhum produto selecionado para o disparo em massa.");
        }

        if (empty($canais)) {
            throw new \Exception("Selecione pelo menos um canal de envio (WhatsApp Status, WhatsApp Direto ou E-mail).");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $campanha = new DisparoMassa();
            $campanha->usuario_id = $usuarioId;
            $campanha->titulo = 'Campanha Disparo em Massa - ' . date('d/m/Y H:i');
            $campanha->canais = $canais;
            $campanha->configuracoes = $visualOptions;
            $campanha->mensagem_texto = $mensagemTexto;
            $campanha->status = DisparoMassa::STATUS_PENDENTE;
            $campanha->total_itens = 0;
            $campanha->save(false);

            $produtos = Produto::findAll(['id' => $produtosIds, 'usuario_id' => $usuarioId]);
            $clientes = !empty($clientesIds) ? Cliente::findAll(['id' => $clientesIds]) : [];

            // Processar lista de telefones manuais
            $listaTelefonesManuais = $this->extrairLinhasDestino($telefonesManuais);
            // Processar lista de e-mails manuais
            $listaEmailsManuais = array_filter(array_map('trim', $this->extrairLinhasDestino($emailsManuais)));

            $totalAgendados = 0;

            foreach ($produtos as $produto) {
                // 1. Gerar os cards do produto (Stories para status, Feed para WhatsApp/Email)
                $cardFeed = null;
                $cardStories = null;

                if (in_array(DisparoMassa::CANAL_STATUS, $canais)) {
                    $cardStories = $this->cardService->gerarCard($produto, 'stories', $visualOptions);
                }

                if (in_array(DisparoMassa::CANAL_WHATSAPP, $canais) || in_array(DisparoMassa::CANAL_EMAIL, $canais)) {
                    $cardFeed = $this->cardService->gerarCard($produto, 'feed', $visualOptions);
                }

                // 2. Agendar canal: WhatsApp Status
                if (in_array(DisparoMassa::CANAL_STATUS, $canais)) {
                    $item = new DisparoItem();
                    $item->disparo_id = $campanha->id;
                    $item->produto_id = $produto->id;
                    $item->canal = DisparoMassa::CANAL_STATUS;
                    $item->card_path = $cardStories ? $cardStories->card_path : ($cardFeed ? $cardFeed->card_path : null);
                    $item->card_url = $cardStories ? $cardStories->getUrlCompleta() : ($cardFeed ? $cardFeed->getUrlCompleta() : null);
                    $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto);
                    $item->status = DisparoItem::STATUS_PENDENTE;
                    $item->save(false);
                    $totalAgendados++;
                }

                // 3. Agendar canal: WhatsApp Direto para Clientes Cadastrados
                if (in_array(DisparoMassa::CANAL_WHATSAPP, $canais)) {
                    foreach ($clientes as $cliente) {
                        $telefone = !empty($cliente->telefone) ? $cliente->telefone : $cliente->celular;
                        if (empty($telefone)) {
                            continue;
                        }

                        $item = new DisparoItem();
                        $item->disparo_id = $campanha->id;
                        $item->produto_id = $produto->id;
                        $item->cliente_id = $cliente->id;
                        $item->canal = DisparoMassa::CANAL_WHATSAPP;
                        $item->destino = $telefone;
                        $item->card_path = $cardFeed ? $cardFeed->card_path : null;
                        $item->card_url = $cardFeed ? $cardFeed->getUrlCompleta() : null;
                        $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto, $cliente);
                        $item->status = DisparoItem::STATUS_PENDENTE;
                        $item->save(false);
                        $totalAgendados++;
                    }

                    // Agendar telefones manuais adicionais
                    foreach ($listaTelefonesManuais as $telManual) {
                        $item = new DisparoItem();
                        $item->disparo_id = $campanha->id;
                        $item->produto_id = $produto->id;
                        $item->canal = DisparoMassa::CANAL_WHATSAPP;
                        $item->destino = $telManual;
                        $item->card_path = $cardFeed ? $cardFeed->card_path : null;
                        $item->card_url = $cardFeed ? $cardFeed->getUrlCompleta() : null;
                        $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto);
                        $item->status = DisparoItem::STATUS_PENDENTE;
                        $item->save(false);
                        $totalAgendados++;
                    }
                }

                // 4. Agendar canal: E-mail Marketing para Clientes Cadastrados
                if (in_array(DisparoMassa::CANAL_EMAIL, $canais)) {
                    foreach ($clientes as $cliente) {
                        if (empty($cliente->email) || !filter_var($cliente->email, FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }

                        $item = new DisparoItem();
                        $item->disparo_id = $campanha->id;
                        $item->produto_id = $produto->id;
                        $item->cliente_id = $cliente->id;
                        $item->canal = DisparoMassa::CANAL_EMAIL;
                        $item->destino = $cliente->email;
                        $item->card_path = $cardFeed ? $cardFeed->card_path : null;
                        $item->card_url = $cardFeed ? $cardFeed->getUrlCompleta() : null;
                        $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto, $cliente);
                        $item->status = DisparoItem::STATUS_PENDENTE;
                        $item->save(false);
                        $totalAgendados++;
                    }

                    // Agendar e-mails manuais adicionais
                    foreach ($listaEmailsManuais as $emailManual) {
                        $item = new DisparoItem();
                        $item->disparo_id = $campanha->id;
                        $item->produto_id = $produto->id;
                        $item->canal = DisparoMassa::CANAL_EMAIL;
                        $item->destino = $emailManual;
                        $item->card_path = $cardFeed ? $cardFeed->card_path : null;
                        $item->card_url = $cardFeed ? $cardFeed->getUrlCompleta() : null;
                        $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto);
                        $item->status = DisparoItem::STATUS_PENDENTE;
                        $item->save(false);
                        $totalAgendados++;
                    }
                }
            }

            $campanha->total_itens = $totalAgendados;
            $campanha->save(false);

            $transaction->commit();

            return $campanha;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Erro ao criar campanha de disparo em massa: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Processa os itens pendentes na fila de disparos.
     *
     * @param string|null $disparoId ID específico do disparo ou null para todos pendentes
     * @param int $limit Número máximo de itens a processar nesta rodada
     * @return int Quantidade de itens processados
     */
    public function processarFilaDisparo(?string $disparoId = null, int $limit = 50): int
    {
        $query = DisparoItem::find()
            ->where(['status' => DisparoItem::STATUS_PENDENTE])
            ->orderBy(['created_at' => SORT_ASC])
            ->limit($limit);

        if ($disparoId) {
            $query->andWhere(['disparo_id' => $disparoId]);
        }

        /** @var DisparoItem[] $itens */
        $itens = $query->all();
        $processados = 0;

        foreach ($itens as $item) {
            $affected = DisparoItem::updateAll(
                ['status' => DisparoItem::STATUS_PROCESSANDO],
                ['id' => $item->id, 'status' => DisparoItem::STATUS_PENDENTE]
            );
            if ($affected === 0) {
                continue;
            }
            $item->status = DisparoItem::STATUS_PROCESSANDO;

            $sucesso = false;
            $erroMsg = null;

            try {
                $campanha = $item->disparo;
                $produto = $item->produto;
                $usuarioId = $campanha ? $campanha->usuario_id : $produto->usuario_id;

                // Verificar conexão do WhatsApp DIRETAMENTE na Evolution API (ignora cache do banco local)
                if ($item->canal === DisparoMassa::CANAL_STATUS || $item->canal === DisparoMassa::CANAL_WHATSAPP) {
                    $configWp = WhatsappConfig::findByEmpresa($usuarioId);
                    if (!$configWp || empty($configWp->token)) {
                        throw new \Exception("WhatsApp não configurado para esta loja. Acesse Configurações → WhatsApp e conecte sua instância.");
                    }
                    // Sempre consultar o status ao vivo na Evolution API — não confiar no cache do banco local
                    $wpConectado = $this->evolutionService->checkStatus($usuarioId);
                    if (!$wpConectado) {
                        throw new \Exception("Instância do WhatsApp desconectada na Evolution API. Acesse as configurações e escaneie o QR Code.");
                    }
                }

                $cardAbsPath = Yii::getAlias('@app/web/') . ltrim($item->card_path, '/');
                $cardBase64 = null;
                if (file_exists($cardAbsPath) && !empty($item->card_path)) {
                    $ext = strtolower(pathinfo($item->card_path, PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                        $cardBase64 = $this->cardService->converterImagemParaBase64($cardAbsPath);
                    }
                }

                $isVideo = (!empty($item->card_path) && strtolower(pathinfo($item->card_path, PATHINFO_EXTENSION)) === 'mp4')
                        || (!empty($item->card_url) && strtolower(pathinfo(parse_url($item->card_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) === 'mp4');

                $mediaType = $isVideo ? 'video' : 'image';
                $urlAbsoluta = $this->garantirUrlAbsoluta(!empty($item->card_url) ? $item->card_url : $item->card_path);
                $mediaParam = !empty($urlAbsoluta) ? $urlAbsoluta : $cardBase64;

                switch ($item->canal) {
                    case DisparoMassa::CANAL_STATUS:
                        if ($mediaParam) {
                            $sucesso = $this->evolutionService->sendWhatsAppStatus($usuarioId, $mediaParam, $item->mensagem_personalizada ?: $produto->nome, $mediaType);
                            if (!$sucesso) {
                                $erroMsg = $this->evolutionService->lastError ?: "Falha ao postar no Status do WhatsApp via Evolution API. Verifique a conexão.";
                            }
                        } else {
                            $erroMsg = "Arquivo de mídia não encontrado.";
                        }
                        break;

                    case DisparoMassa::CANAL_WHATSAPP:
                        if ($mediaParam && !empty($item->destino)) {
                            $sucesso = $this->evolutionService->sendMedia($usuarioId, $item->destino, $mediaParam, $item->mensagem_personalizada ?: $produto->nome, $mediaType);
                            if (!$sucesso) {
                                $erroMsg = $this->evolutionService->lastError ?: "Falha ao enviar mensagem de mídia para {$item->destino} via Evolution API.";
                            }
                        } else {
                            $erroMsg = "Mídia ou número de telefone de destino ausente.";
                        }
                        break;

                    case DisparoMassa::CANAL_EMAIL:
                        $emailDest = trim((string)$item->destino);
                        if (empty($emailDest) || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
                            $sucesso = false;
                            $erroMsg = "E-mail de destino inválido ou incompleto: '{$item->destino}'. Certifique-se de informar o endereço completo com domínio (ex: usuario@gmail.com).";
                        } else {
                            $sucesso = $this->emailService->enviarEmailCard(
                                $emailDest,
                                $produto,
                                $cardAbsPath,
                                $item->card_url,
                                $item->mensagem_personalizada
                            );
                            if (!$sucesso) {
                                $erroMsg = "Falha ao disparar e-mail para {$emailDest}. Verifique a configuração de SMTP/Mailer.";
                            }
                        }
                        break;
                }
            } catch (\Exception $e) {
                $sucesso = false;
                $erroMsg = $e->getMessage();
            }

            if ($sucesso) {
                $item->status = DisparoItem::STATUS_ENVIADO;
                $item->enviado_em = date('Y-m-d H:i:s');
                $item->erro_mensagem = null;
                
                if ($item->disparo) {
                    $item->disparo->updateCounters(['itens_enviados' => 1]);
                }
            } else {
                $item->status = DisparoItem::STATUS_ERRO;
                $item->erro_mensagem = $erroMsg ?: 'Falha de envio desconhecida.';
                
                if ($item->disparo) {
                    $item->disparo->updateCounters(['itens_erro' => 1]);
                }
            }

            $item->save(false);
            $processados++;

            if ($item->disparo) {
                $c = $item->disparo;
                if (($c->itens_enviados + $c->itens_erro) >= $c->total_itens) {
                    $c->status = DisparoMassa::STATUS_CONCLUIDO;
                    $c->save(false);
                } else if ($c->status === DisparoMassa::STATUS_PENDENTE) {
                    $c->status = DisparoMassa::STATUS_PROCESSANDO;
                    $c->save(false);
                }
            }
        }

        return $processados;
    }

    /**
     * Reseta os itens de uma campanha que apresentaram erro para 'pendente' e reprocessa a fila.
     *
     * @param string $disparoId
     * @return int Quantidade de itens reprocessados
     */
    public function retentarItensComErro(string $disparoId): int
    {
        $campanha = DisparoMassa::findOne($disparoId);
        if (!$campanha) {
            return 0;
        }

        $itensErro = DisparoItem::findAll(['disparo_id' => $disparoId, 'status' => DisparoItem::STATUS_ERRO]);
        if (empty($itensErro)) {
            return 0;
        }

        foreach ($itensErro as $item) {
            $item->status = DisparoItem::STATUS_PENDENTE;
            $item->erro_mensagem = null;
            $item->save(false);
        }

        // Recalcular contadores da campanha
        $campanha->itens_erro = max(0, (int)$campanha->itens_erro - count($itensErro));
        $campanha->status = DisparoMassa::STATUS_PENDENTE;
        $campanha->save(false);

        // Processar a fila imediatamente
        return $this->processarFilaDisparo($disparoId, 50);
    }

    /**
     * Cria uma nova campanha de disparo a partir de uma lista de IDs de ProdutoCard (cards pré-gerados).
     *
     * @param string $usuarioId
     * @param array $cardsIds IDs dos ProdutoCard pré-gerados
     * @param array $canais ('whatsapp', 'status')
     * @param array $clientesIds
     * @param string|null $mensagemTexto
     * @param string|array $telefonesManuais
     * @param array $antiBanConfig Opções anti-ban ('delay_min', 'delay_max', 'lote_tamanho', 'lote_pausa_segundos', 'incluir_optout')
     * @return DisparoMassa
     * @throws \Exception
     */
    public function criarCampanhaDisparoCardsExistentes(
        string $usuarioId,
        array $cardsIds,
        array $canais,
        array $clientesIds = [],
        ?string $mensagemTexto = null,
        $telefonesManuais = '',
        array $antiBanConfig = []
    ): DisparoMassa {
        if (empty($cardsIds)) {
            throw new \Exception("Nenhum card selecionado para o disparo.");
        }

        if (empty($canais)) {
            throw new \Exception("Selecione pelo menos um canal de envio (WhatsApp Status ou WhatsApp Direto).");
        }

        $cards = \app\modules\vendas\models\ProdutoCard::find()
            ->where(['id' => $cardsIds, 'usuario_id' => $usuarioId])
            ->all();

        if (empty($cards)) {
            throw new \Exception("Nenhum card válido encontrado para envio.");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $campanha = new DisparoMassa();
            $campanha->usuario_id = $usuarioId;
            $campanha->titulo = 'Disparo de Cards - ' . date('d/m/Y H:i');
            $campanha->canais = $canais;
            $campanha->configuracoes = $antiBanConfig;
            $campanha->mensagem_texto = $mensagemTexto;
            $campanha->status = DisparoMassa::STATUS_PENDENTE;
            $campanha->total_itens = 0;
            $campanha->save(false);

            $clientes = !empty($clientesIds) ? Cliente::findAll(['id' => $clientesIds]) : [];
            $listaTelefonesManuais = $this->extrairLinhasDestino($telefonesManuais);
            $totalAgendados = 0;

            $incluirOptout = !empty($antiBanConfig['incluir_optout']);

            foreach ($cards as $card) {
                $produto = $card->produto;
                if (!$produto) {
                    continue;
                }

                $cardPath = $card->card_path;
                $cardUrl = $card->getUrlCompleta();

                // 1. WhatsApp Status
                if (in_array(DisparoMassa::CANAL_STATUS, $canais)) {
                    $item = new DisparoItem();
                    $item->disparo_id = $campanha->id;
                    $item->produto_id = $produto->id;
                    $item->canal = DisparoMassa::CANAL_STATUS;
                    $item->card_path = $cardPath;
                    $item->card_url = $cardUrl;
                    $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto, null, $incluirOptout);
                    $item->status = DisparoItem::STATUS_PENDENTE;
                    $item->save(false);
                    $totalAgendados++;
                }

                // 2. WhatsApp Direto para Clientes Cadastrados
                if (in_array(DisparoMassa::CANAL_WHATSAPP, $canais)) {
                    foreach ($clientes as $cliente) {
                        $telefone = !empty($cliente->telefone) ? $cliente->telefone : $cliente->celular;
                        if (empty($telefone)) {
                            continue;
                        }

                        $item = new DisparoItem();
                        $item->disparo_id = $campanha->id;
                        $item->produto_id = $produto->id;
                        $item->cliente_id = $cliente->id;
                        $item->canal = DisparoMassa::CANAL_WHATSAPP;
                        $item->destino = $telefone;
                        $item->card_path = $cardPath;
                        $item->card_url = $cardUrl;
                        $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto, $cliente, $incluirOptout);
                        $item->status = DisparoItem::STATUS_PENDENTE;
                        $item->save(false);
                        $totalAgendados++;
                    }

                    // Agendar telefones manuais adicionais
                    foreach ($listaTelefonesManuais as $telManual) {
                        $item = new DisparoItem();
                        $item->disparo_id = $campanha->id;
                        $item->produto_id = $produto->id;
                        $item->canal = DisparoMassa::CANAL_WHATSAPP;
                        $item->destino = $telManual;
                        $item->card_path = $cardPath;
                        $item->card_url = $cardUrl;
                        $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto, null, $incluirOptout);
                        $item->status = DisparoItem::STATUS_PENDENTE;
                        $item->save(false);
                        $totalAgendados++;
                    }
                }
            }

            $campanha->total_itens = $totalAgendados;
            $campanha->save(false);

            $transaction->commit();

            return $campanha;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Erro ao criar campanha de disparo de cards pré-gerados: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Cria e enfileira uma nova campanha de disparo para VÍDEOS PROMOCIONAIS pré-gerados.
     */
    public function criarCampanhaDisparoVideosExistentes(
        string $lojaId,
        array $videosIds,
        array $canais,
        array $clientesIds,
        ?string $mensagemTexto = null,
        ?string $telefonesManuais = null,
        array $antiBanConfig = []
    ): DisparoMassa {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $campanha = new DisparoMassa();
            $campanha->usuario_id = $lojaId;
            $campanha->titulo = 'Disparo de Vídeos - ' . date('d/m/Y H:i');
            $campanha->canais = $canais;
            $campanha->configuracoes = $antiBanConfig;
            $campanha->mensagem_texto = $mensagemTexto;
            $campanha->status = DisparoMassa::STATUS_PENDENTE;
            $campanha->total_itens = 0;
            $campanha->save(false);

            $videos = \app\modules\vendas\models\ProdutoVideo::find()->where(['id' => $videosIds])->all();

            if (empty($videos)) {
                throw new \Exception("Nenhum vídeo válido encontrado para disparo.");
            }

            $clientes = [];
            if (!empty($clientesIds)) {
                $clientes = Cliente::find()->where(['id' => $clientesIds])->all();
            }

            $listaTelefonesManuais = $this->extrairLinhasDestino($telefonesManuais);
            $incluirOptout = !empty($antiBanConfig['incluir_optout']);
            $totalAgendados = 0;

            foreach ($videos as $vid) {
                $produto = $vid->produto;
                if (!$produto) {
                    continue;
                }

                $videoPath = $vid->video_path;
                $videoUrl = $vid->getUrlCompleta();

                // 1. WhatsApp Status
                if (in_array(DisparoMassa::CANAL_STATUS, $canais)) {
                    $item = new DisparoItem();
                    $item->disparo_id = $campanha->id;
                    $item->produto_id = $produto->id;
                    $item->canal = DisparoMassa::CANAL_STATUS;
                    $item->destino = 'status@broadcast';
                    $item->card_path = $videoPath;
                    $item->card_url = $videoUrl;
                    $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto, null, $incluirOptout);
                    $item->status = DisparoItem::STATUS_PENDENTE;
                    $item->save(false);
                    $totalAgendados++;
                }

                // 2. WhatsApp Direto para Clientes Cadastrados
                if (in_array(DisparoMassa::CANAL_WHATSAPP, $canais)) {
                    foreach ($clientes as $cliente) {
                        $telefone = !empty($cliente->telefone) ? $cliente->telefone : $cliente->celular;
                        if (empty($telefone)) {
                            continue;
                        }

                        $item = new DisparoItem();
                        $item->disparo_id = $campanha->id;
                        $item->produto_id = $produto->id;
                        $item->cliente_id = $cliente->id;
                        $item->canal = DisparoMassa::CANAL_WHATSAPP;
                        $item->destino = $telefone;
                        $item->card_path = $videoPath;
                        $item->card_url = $videoUrl;
                        $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto, $cliente, $incluirOptout);
                        $item->status = DisparoItem::STATUS_PENDENTE;
                        $item->save(false);
                        $totalAgendados++;
                    }

                    // Telefones Manuais
                    foreach ($listaTelefonesManuais as $telManual) {
                        $item = new DisparoItem();
                        $item->disparo_id = $campanha->id;
                        $item->produto_id = $produto->id;
                        $item->canal = DisparoMassa::CANAL_WHATSAPP;
                        $item->destino = $telManual;
                        $item->card_path = $videoPath;
                        $item->card_url = $videoUrl;
                        $item->mensagem_personalizada = $this->substituirVariaveis($mensagemTexto, $produto, null, $incluirOptout);
                        $item->status = DisparoItem::STATUS_PENDENTE;
                        $item->save(false);
                        $totalAgendados++;
                    }
                }
            }

            if ($totalAgendados === 0) {
                throw new \Exception("Nenhum destinatário válido foi selecionado. Selecione ao menos um cliente, insira um número manual ou marque a caixa do WhatsApp Status.");
            }

            $campanha->total_itens = $totalAgendados;
            $campanha->save(false);

            $transaction->commit();

            return $campanha;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Erro ao criar campanha de disparo de vídeos pré-gerados: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Garante que uma URL de mídia seja um link público absoluto com protocolo (https://...).
     */
    private function garantirUrlAbsoluta(?string $urlOrPath): ?string
    {
        if (empty($urlOrPath)) {
            return null;
        }

        if (strpos($urlOrPath, 'data:image') === 0 || strpos($urlOrPath, 'data:video') === 0) {
            return $urlOrPath;
        }

        $resUrl = null;

        if (strpos($urlOrPath, 'http://') === 0 || strpos($urlOrPath, 'https://') === 0) {
            $resUrl = $urlOrPath;
        } else {
            $caminho = ltrim($urlOrPath, '/');
            if (Yii::$app->has('request') && Yii::$app->get('request') instanceof \yii\web\Request && !empty(Yii::$app->request->hostInfo)) {
                $resUrl = \yii\helpers\Url::to('@web/' . $caminho, true);
            } else {
                $baseUrl = Yii::$app->params['domain'] ?? 'https://alex-bird.oncode.app.br';
                $resUrl = rtrim($baseUrl, '/') . '/' . $caminho;
            }
        }

        // Higieniza removendo trechos index.php/ de URLs de arquivos de mídia estáticos
        if (!empty($resUrl) && (strpos($resUrl, '/uploads/') !== false || strpos($resUrl, '/assets/') !== false || strpos($resUrl, '/imagens/') !== false)) {
            $resUrl = str_replace(['/index.php/', '/index.php'], ['/', ''], $resUrl);
        }

        return $resUrl;
    }

    /**
     * Extrai linhas ou valores separados por vírgula, ponto e vírgula, espaço ou quebra de linha.
     */
    private function extrairLinhasDestino($input): array
    {
        if (is_array($input)) {
            return array_filter(array_map('trim', $input));
        }

        if (empty($input) || !is_string($input)) {
            return [];
        }

        $linhas = preg_split('/[\n\r,;\s]+/', $input);
        return array_values(array_filter(array_map('trim', $linhas)));
    }

    /**
     * Substitui variáveis dinâmicas no texto da mensagem ({NOME}, {PRODUTO}, {PRECO}) e aplica SpinTax.
     */
    private function substituirVariaveis(?string $texto, Produto $produto, ?Cliente $cliente = null, bool $incluirOptout = false): string
    {
        if (empty($texto)) {
            $preco = 'R$ ' . number_format((float)$produto->getPrecoFinal(), 2, ',', '.');
            $texto = "🔥 *OFERTA ESPECIAL* 🔥\n\n*{$produto->nome}*\nPreço: *{$preco}*\n\nPeça já pelo nosso atendimento!";
        } else {
            $preco = 'R$ ' . number_format((float)$produto->getPrecoFinal(), 2, ',', '.');

            $replacements = [
                '{PRODUTO}' => $produto->nome,
                '{PRECO}' => $preco,
                '{MARCA}' => $produto->marca ?: '',
                '{NOME}' => $cliente ? (!empty($cliente->nome_completo) ? $cliente->nome_completo : $cliente->nome) : 'Cliente',
            ];

            $texto = strtr($texto, $replacements);
        }

        // Processar sintaxe SpinTax {opção1|opção2|opção3}
        $texto = $this->processarSpintax($texto);

        if ($incluirOptout && strpos($texto, 'PARAR') === false) {
            $texto .= "\n\n_Para não receber mais ofertas, responda PARAR._";
        }

        return $texto;
    }

    /**
     * Processa sintaxe SpinTax em strings no formato {opção1|opção2|opção3}
     */
    public function processarSpintax(string $texto): string
    {
        return preg_replace_callback('/\{([^{}]+)\}/', function ($matches) {
            $choices = explode('|', $matches[1]);
            if (count($choices) > 1) {
                return $choices[array_rand($choices)];
            }
            return $matches[0];
        }, $texto);
    }
}

