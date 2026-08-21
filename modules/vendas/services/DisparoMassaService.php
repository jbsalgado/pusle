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
            $listaEmailsManuais = array_filter($this->extrairLinhasDestino($emailsManuais), function($e) {
                return filter_var($e, FILTER_VALIDATE_EMAIL);
            });

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
                        $telefone = $cliente->celular ?: $cliente->telefone;
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
            $item->status = DisparoItem::STATUS_PROCESSANDO;
            $item->save(false);

            $sucesso = false;
            $erroMsg = null;

            try {
                $campanha = $item->disparo;
                $produto = $item->produto;
                $usuarioId = $campanha ? $campanha->usuario_id : $produto->usuario_id;

                // Verificar conexão do WhatsApp antes de tentar envios via Evolution API
                if ($item->canal === DisparoMassa::CANAL_STATUS || $item->canal === DisparoMassa::CANAL_WHATSAPP) {
                    $configWp = WhatsappConfig::findByEmpresa($usuarioId);
                    if (!$configWp || empty($configWp->token) || $configWp->status !== 'CONNECTED') {
                        // Tentar checar status via Evolution API
                        $wpConectado = $this->evolutionService->checkStatus($usuarioId);
                        if (!$wpConectado) {
                            throw new \Exception("Instância do WhatsApp desconectada na Evolution API. Acesse as configurações e escaneie o QR Code.");
                        }
                    }
                }

                $cardAbsPath = Yii::getAlias('@app/web/') . ltrim($item->card_path, '/');
                $cardBase64 = null;
                if (file_exists($cardAbsPath)) {
                    $cardBase64 = $this->cardService->converterImagemParaBase64($cardAbsPath);
                }

                switch ($item->canal) {
                    case DisparoMassa::CANAL_STATUS:
                        if ($cardBase64) {
                            $sucesso = $this->evolutionService->sendWhatsAppStatus($usuarioId, $cardBase64, $item->mensagem_personalizada ?: $produto->nome);
                            if (!$sucesso) {
                                $erroMsg = "Falha ao postar no Status do WhatsApp via Evolution API. Verifique a conexão.";
                            }
                        } else {
                            $erroMsg = "Arquivo de imagem do card não encontrado.";
                        }
                        break;

                    case DisparoMassa::CANAL_WHATSAPP:
                        if ($cardBase64 && !empty($item->destino)) {
                            $sucesso = $this->evolutionService->sendMedia($usuarioId, $item->destino, $cardBase64, $item->mensagem_personalizada ?: $produto->nome, 'image');
                            if (!$sucesso) {
                                $erroMsg = "Falha ao enviar mensagem de mídia para {$item->destino} via Evolution API.";
                            }
                        } else {
                            $erroMsg = "Imagem do card ou número de telefone de destino ausente.";
                        }
                        break;

                    case DisparoMassa::CANAL_EMAIL:
                        if (!empty($item->destino)) {
                            $sucesso = $this->emailService->enviarEmailCard(
                                $item->destino,
                                $produto,
                                $cardAbsPath,
                                $item->card_url,
                                $item->mensagem_personalizada
                            );
                            if (!$sucesso) {
                                $erroMsg = "Falha ao disparar e-mail para {$item->destino}. Verifique a configuração de SMTP/Mailer.";
                            }
                        } else {
                            $erroMsg = "E-mail de destino não informado ou inválido.";
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
     * Extrai linhas ou valores separados por vírgula/quebra de linha.
     */
    private function extrairLinhasDestino($input): array
    {
        if (is_array($input)) {
            return array_filter(array_map('trim', $input));
        }

        if (empty($input) || !is_string($input)) {
            return [];
        }

        $linhas = preg_split('/[\n\r,;]+/', $input);
        return array_values(array_filter(array_map('trim', $linhas)));
    }

    /**
     * Substitui variáveis dinâmicas no texto da mensagem ({NOME}, {PRODUTO}, {PRECO}).
     */
    private function substituirVariaveis(?string $texto, Produto $produto, ?Cliente $cliente = null): string
    {
        if (empty($texto)) {
            $preco = 'R$ ' . number_format((float)$produto->getPrecoFinal(), 2, ',', '.');
            return "🔥 *OFERTA ESPECIAL* 🔥\n\n*{$produto->nome}*\nPreço: *{$preco}*\n\nPeça já pelo nosso atendimento!";
        }

        $preco = 'R$ ' . number_format((float)$produto->getPrecoFinal(), 2, ',', '.');

        $replacements = [
            '{PRODUTO}' => $produto->nome,
            '{PRECO}' => $preco,
            '{MARCA}' => $produto->marca ?: '',
            '{NOME}' => $cliente ? $cliente->nome : 'Cliente',
        ];

        return strtr($texto, $replacements);
    }
}
