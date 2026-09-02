<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\models\Usuario;
use app\modules\vendas\models\Clientes;
use app\modules\vendas\models\ClienteInbox;
use app\modules\vendas\models\Mesa;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\ComandaItem;
use app\modules\vendas\models\Produtos;
use app\modules\vendas\models\ProdutoCard;

/**
 * HubController — Direct Hub do Cliente & Comanda Digital (Multi-Tenant)
 *
 * Permite a comunicação direta (vídeos, fotos, ofertas, chat) e controle de
 * Comanda Digital para mesas/totens, com acesso via Magic Link ou QR Code.
 */
class HubController extends Controller
{
    /**
     * Desabilita validação CSRF para as requisições públicas de AJAX do Hub
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['identificar', 'chamar-garcom', 'pedir-conta', 'save-push', 'enviar-mensagem', 'mensagens', 'upload-midia'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Verifica se uma string possui o formato válido de UUID v4
     */
    private function isValidUuid($val): bool
    {
        return is_string($val) && (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $val);
    }

    /**
     * Tela Principal do Direct Hub / Comanda Digital
     *
     * @param string|null $token  Magic token do cliente
     * @param string|null $slug   Slug da loja/empresa
     * @param string|null $mesa   Número ou ID da mesa
     * @param string|null $comanda Número ou ID da comanda
     */
    public function actionIndex($token = null, $slug = null, $mesa = null, $comanda = null)
    {
        $cliente = null;
        $usuario = null;
        $mesaModel = null;
        $comandaModel = null;

        // 1. Identificação via Magic Token, Sessão ou Cookie
        if (!empty($token)) {
            $cliente = Clientes::findByMagicToken($token);
            if ($cliente !== null) {
                $usuario = Usuario::findOne($cliente->usuario_id);
                Yii::$app->session->set('hub_cliente_id', $cliente->id);
            }
        }

        if ($cliente === null) {
            $sessClienteId = Yii::$app->session->get('hub_cliente_id');
            if (!empty($sessClienteId)) {
                $cliente = Clientes::findOne($sessClienteId);
            }
        }

        // 2. Identificação via Slug / ID / Username da Loja
        if ($usuario === null && !empty($slug)) {
            // 2.1 Busca por UUID direto se formato for válido
            if ($this->isValidUuid($slug)) {
                $usuario = Usuario::findOne($slug);
            }

            // 2.2 Busca por username, catalogo_path ou nome
            if ($usuario === null) {
                $usuario = Usuario::find()
                    ->where(['or', ['username' => $slug], ['catalogo_path' => $slug], ['nome' => $slug]])
                    ->one();
            }

            // 2.3 Busca em LojaConfiguracao por nome_loja ou nome_fantasia
            if ($usuario === null) {
                $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::find()
                    ->where(['or', ['nome_loja' => $slug], ['nome_fantasia' => $slug]])
                    ->one();
                if ($lojaConfig !== null && !empty($lojaConfig->usuario_id)) {
                    $usuario = Usuario::findOne($lojaConfig->usuario_id);
                }
            }
        }

        // Se ainda não achou tenant e tem sessão de cliente
        $session = Yii::$app->session;
        if ($cliente === null && $session->has('hub_cliente_id')) {
            $cliente = Clientes::findOne($session->get('hub_cliente_id'));
            if ($cliente !== null && $usuario === null) {
                $usuario = Usuario::findOne($cliente->usuario_id);
            }
        }

        if ($usuario === null) {
            throw new NotFoundHttpException("Estabelecimento não encontrado ou link expirado.");
        }

        // 3. Resolução de Mesa / Totem (Food Service)
        if (!empty($mesa)) {
            $mesaQuery = Mesa::find()->where(['usuario_id' => $usuario->id]);
            if ($this->isValidUuid($mesa)) {
                $mesaQuery->andWhere(['or', ['id' => $mesa], ['numero_mesa' => (string)$mesa]]);
            } else {
                $mesaQuery->andWhere(['numero_mesa' => (string)$mesa]);
            }
            $mesaModel = $mesaQuery->one();
        }

        // 4. Resolução de Comanda
        if (!empty($comanda)) {
            $comandaQuery = Comanda::find()->where(['usuario_id' => $usuario->id]);
            if ($this->isValidUuid($comanda)) {
                $comandaQuery->andWhere(['or', ['id' => $comanda], ['numero_comanda' => (string)$comanda]]);
            } else {
                $comandaQuery->andWhere(['numero_comanda' => (string)$comanda]);
            }
            $comandaModel = $comandaQuery->one();
        } elseif ($mesaModel !== null) {
            $comandaModel = Comanda::find()
                ->where(['usuario_id' => $usuario->id, 'mesa_id' => $mesaModel->id, 'status' => 'aberta'])
                ->orderBy(['data_abertura' => SORT_DESC])
                ->one();

            // Se a mesa acabou de ser fechada, resgata a última comanda da mesa para exibir o comprovante
            if ($comandaModel === null) {
                $comandaModel = Comanda::find()
                    ->where(['usuario_id' => $usuario->id, 'mesa_id' => $mesaModel->id])
                    ->orderBy(['data_abertura' => SORT_DESC])
                    ->one();
            }
        }

        // 5. Carrega timeline de mensagens, vídeos e ofertas do cliente/loja com PRIVACIDADE ESTRITA
        // O cliente só vê: (1) suas próprias mensagens e respostas, (2) mensagens da sua mesa ativa, (3) vídeos e cards públicos da loja
        $inboxQuery = ClienteInbox::find()
            ->where(['usuario_id' => $usuario->id]);

        if ($cliente !== null) {
            $condicoesPrivacidade = [
                'or',
                ['cliente_id' => $cliente->id],
                ['and', ['cliente_id' => null], ['in', 'tipo', [ClienteInbox::TIPO_VIDEO]]]
            ];
            if ($mesaModel !== null) {
                $condicoesPrivacidade[] = ['mesa_id' => $mesaModel->id];
            }
            $inboxQuery->andWhere($condicoesPrivacidade);
        } elseif ($mesaModel !== null) {
            $inboxQuery->andWhere([
                'or',
                ['mesa_id' => $mesaModel->id],
                ['and', ['cliente_id' => null], ['in', 'tipo', [ClienteInbox::TIPO_VIDEO]]]
            ]);
        } else {
            // Acesso público / não identificado: exibe apenas vídeos e comunicados públicos da loja
            $inboxQuery->andWhere([
                'and',
                ['cliente_id' => null],
                ['in', 'tipo', [ClienteInbox::TIPO_VIDEO]]
            ]);
        }

        $inboxMessages = $inboxQuery
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(30)
            ->all();

        // 6. Itens da comanda aberta (se houver)
        $comandaItens = [];
        $totalComanda = 0.0;
        if ($comandaModel !== null) {
            $comandaItens = ComandaItem::find()
                ->where(['comanda_id' => $comandaModel->id])
                ->orderBy(['data_pedido' => SORT_DESC])
                ->all();

            foreach ($comandaItens as $ci) {
                $totalComanda += ((float)$ci->valor_unitario * (float)$ci->quantidade);
            }
        }

        // 7. Cards de produtos e promoções recentes
        $cardsDestaque = ProdutoCard::find()
            ->where(['usuario_id' => $usuario->id])
            ->orderBy(['data_criacao' => SORT_DESC])
            ->limit(10)
            ->all();

        $lojaConfig = \app\modules\vendas\models\LojaConfiguracao::findOne(['usuario_id' => $usuario->id]);

        return $this->render('index', [
            'usuario'       => $usuario,
            'cliente'       => $cliente,
            'mesa'          => $mesaModel,
            'comanda'       => $comandaModel,
            'comandaItens'  => $comandaItens,
            'totalComanda'  => $totalComanda,
            'inboxMessages' => $inboxMessages,
            'cardsDestaque' => $cardsDestaque,
            'lojaConfig'    => $lojaConfig,
        ]);
    }

    /**
     * Ação Ajax para identificação rápida por Nome + Telefone
     */
    public function actionIdentificar(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $post = json_decode($request->getRawBody(), true) ?: $request->post();
        $usuarioId = $post['usuario_id'] ?? null;
        $nome      = trim((string)($post['nome'] ?? ''));
        $telefone  = trim((string)($post['telefone'] ?? ''));
        $mesaId    = !empty($post['mesa_id']) ? $post['mesa_id'] : null;

        if (empty($usuarioId) || empty($telefone)) {
            return ['success' => false, 'message' => 'Por favor, informe seu WhatsApp para continuar.'];
        }

        try {
            $cliente = Clientes::findOrCreateQuick($usuarioId, $nome, $telefone);
            $token   = $cliente->getMagicToken();

            Yii::$app->session->set('hub_cliente_id', $cliente->id);

            // Se estiver em uma mesa, vincula/registra comanda se não houver
            if (!empty($mesaId)) {
                $comanda = Comanda::find()
                    ->where(['usuario_id' => $usuarioId, 'mesa_id' => $mesaId, 'status' => 'aberta'])
                    ->one();

                if ($comanda === null) {
                    $comanda = new Comanda();
                    $comanda->usuario_id = $usuarioId;
                    $comanda->mesa_id = $mesaId;
                    $comanda->numero_comanda = 'M' . substr(str_replace('-', '', $mesaId), 0, 4) . '-' . date('His');
                    $comanda->cliente_nome = $cliente->nome_completo;
                    $comanda->status = 'aberta';
                    $comanda->data_abertura = date('Y-m-d H:i:s');
                    $comanda->save(false);
                }
            }

            return [
                'success' => true,
                'token'   => $token,
                'cliente' => [
                    'id'   => $cliente->id,
                    'nome' => $cliente->nome_completo,
                    'tel'  => $cliente->telefone,
                ],
            ];
        } catch (\Throwable $t) {
            return ['success' => false, 'message' => 'Erro ao identificar: ' . $t->getMessage()];
        }
    }

    /**
     * Ação Ajax para Chamar o Garçom / Atendente
     */
    public function actionChamarGarcom(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $post      = Yii::$app->request->post();
        $usuarioId = $post['usuario_id'] ?? null;
        $clienteId = $post['cliente_id'] ?? null;
        $mesaId    = $post['mesa_id'] ?? null;
        $motivo    = $post['motivo'] ?? 'Atendimento solicitado na mesa';

        if (empty($usuarioId)) {
            return ['success' => false, 'message' => 'Parâmetros inválidos.'];
        }

        $mesa = !empty($mesaId) ? Mesa::findOne($mesaId) : null;
        $mesaNome = $mesa ? "Mesa {$mesa->numero_mesa}" : "Balcão";

        ClienteInbox::postar(
            $usuarioId,
            $clienteId,
            ClienteInbox::TIPO_CHAMADO,
            "🔔 Chamado de Atendimento ({$mesaNome})",
            $motivo,
            null,
            ['mesa_id' => $mesaId, 'status' => 'aguardando'],
            $mesaId
        );

        return [
            'success' => true,
            'message' => "Chamado enviado! Nosso garçom/atendente já está a caminho da sua {$mesaNome}.",
        ];
    }

    /**
     * Ação Ajax para Pedir Fechamento de Conta / PIX
     */
    public function actionPedirConta(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $post      = Yii::$app->request->post();
        $usuarioId = $post['usuario_id'] ?? null;
        $clienteId = $post['cliente_id'] ?? null;
        $comandaId = $post['comanda_id'] ?? null;

        if (empty($usuarioId) || empty($comandaId)) {
            return ['success' => false, 'message' => 'Comanda não informada.'];
        }

        $comanda = Comanda::findOne($comandaId);
        if (!$comanda) {
            return ['success' => false, 'message' => 'Comanda não encontrada.'];
        }

        // Notifica painel do restaurante/loja
        ClienteInbox::postar(
            $usuarioId,
            $clienteId,
            ClienteInbox::TIPO_CONTA,
            "🧾 Fechamento de Conta Solicitado",
            "Cliente da Comanda {$comanda->numero_comanda} solicitou a conta.",
            null,
            ['comanda_id' => $comandaId, 'status' => 'fechamento_solicitado'],
            $comanda->mesa_id,
            $comandaId
        );

        return [
            'success' => true,
            'message' => 'Solicitação de conta enviada ao caixa.',
        ];
    }

    /**
     * Salva assinatura Web Push do navegador do cliente
     */
    public function actionSavePush(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        $token = $post['token'] ?? null;
        $subscription = $post['subscription'] ?? null;

        if (empty($token) || empty($subscription)) {
            return ['success' => false, 'message' => 'Dados inválidos.'];
        }

        $cliente = Clientes::findByMagicToken($token);
        if ($cliente !== null) {
            $current = is_array($cliente->push_subscriptions) ? $cliente->push_subscriptions : [];
            $current[] = $subscription;
            $cliente->push_subscriptions = array_values(array_unique($current, SORT_REGULAR));
            $cliente->save(false, ['push_subscriptions']);
            return ['success' => true, 'message' => 'Notificações Push ativadas com sucesso!'];
        }

        return ['success' => false, 'message' => 'Cliente não localizado.'];
    }

    /**
     * Upload de mídia (fotos, comprovantes) do chat do cliente
     */
    public function actionUploadMidia(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $foto = \yii\web\UploadedFile::getInstanceByName('foto');
        if (!$foto) {
            return ['success' => false, 'message' => 'Nenhum arquivo enviado.'];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower($foto->extension);
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Formato de imagem inválido. Use JPG, PNG ou WebP.'];
        }

        $uploadDir = Yii::getAlias('@app/web/uploads/chat');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $filename = 'chat_' . date('Ymd_His') . '_' . substr(md5(uniqid(rand(), true)), 0, 8) . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if ($foto->saveAs($destPath)) {
            $url = Url::to('@web/uploads/chat/' . $filename, true);
            return [
                'success' => true,
                'url'     => $url,
                'path'    => '/uploads/chat/' . $filename
            ];
        }

        return ['success' => false, 'message' => 'Falha ao salvar a imagem no servidor.'];
    }

    /**
     * Ação Ajax para o cliente enviar uma mensagem ou dúvida diretamente no Feed do Hub (Multi-Módulos)
     */
    public function actionEnviarMensagem(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request   = Yii::$app->request;
        $post      = json_decode($request->getRawBody(), true) ?: $request->post();
        $usuarioId = $post['usuario_id'] ?? null;
        $clienteId = $post['cliente_id'] ?? null;
        $mesaId    = !empty($post['mesa_id']) ? $post['mesa_id'] : null;
        $nome      = trim((string)($post['nome'] ?? ''));
        $telefone  = trim((string)($post['telefone'] ?? ''));
        $mensagem  = trim((string)($post['mensagem'] ?? ''));
        $midiaUrl  = trim((string)($post['midia_url'] ?? ''));

        if (empty($usuarioId) || (empty($mensagem) && empty($midiaUrl))) {
            return ['success' => false, 'message' => 'Por favor, digite sua mensagem ou anexe uma foto.'];
        }

        if (empty($mensagem) && !empty($midiaUrl)) {
            $mensagem = '📷 Foto enviada';
        }

        // Se o cliente não tem cliente_id mas passou telefone, localiza ou cadastra
        $cliente = null;
        if (!empty($clienteId)) {
            $cliente = Clientes::findOne($clienteId);
        } elseif (!empty($telefone)) {
            $cliente = Clientes::findOrCreateQuick($usuarioId, $nome, $telefone);
            $clienteId = $cliente->id;
            Yii::$app->session->set('hub_cliente_id', $cliente->id);
        }

        $mesa = !empty($mesaId) ? Mesa::findOne($mesaId) : null;
        $nomeRemetente = $cliente ? $cliente->nome_completo : ($nome ?: ($mesa ? "Mesa {$mesa->numero_mesa}" : 'Cliente'));
        $origem = $mesa ? "Mesa {$mesa->numero_mesa}" : "Direct Hub (Web)";

        $inbox = ClienteInbox::postar(
            $usuarioId,
            $clienteId,
            ClienteInbox::TIPO_TEXTO,
            "💬 Mensagem de {$nomeRemetente} ({$origem})",
            $mensagem,
            !empty($midiaUrl) ? $midiaUrl : null,
            [
                'origem'    => 'cliente',
                'remetente' => $nomeRemetente,
                'telefone'  => $cliente ? $cliente->telefone : $telefone,
                'mesa_id'   => $mesaId
            ],
            $mesaId
        );

        if ($inbox) {
            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso! Nossa equipe foi notificada.',
                'item'    => [
                    'id'             => $inbox->id,
                    'tipo'           => $inbox->tipo,
                    'titulo'         => $inbox->titulo,
                    'conteudo_texto' => $inbox->conteudo_texto,
                    'midia_url'      => $inbox->midia_url,
                    'created_at'     => 'Agora',
                    'remetente'      => $nomeRemetente,
                    'origem'         => 'cliente'
                ],
                'cliente' => $cliente ? [
                    'id'    => $cliente->id,
                    'nome'  => $cliente->nome_completo,
                    'token' => $cliente->getMagicToken(),
                ] : null
            ];
        }

        return ['success' => false, 'message' => 'Erro ao salvar mensagem no servidor.'];
    }

    /**
     * Retorna a lista atualizada de mensagens/feed do cliente em tempo real com PRIVACIDADE ESTRITA
     */
    public function actionMensagens($usuario_id, $cliente_id = null, $mesa_id = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = ClienteInbox::find()
            ->where(['usuario_id' => $usuario_id]);

        if (!empty($cliente_id)) {
            $condicoesPrivacidade = [
                'or',
                ['cliente_id' => $cliente_id],
                ['and', ['cliente_id' => null], ['in', 'tipo', [ClienteInbox::TIPO_VIDEO]]]
            ];
            if (!empty($mesa_id)) {
                $condicoesPrivacidade[] = ['mesa_id' => $mesa_id];
            }
            $query->andWhere($condicoesPrivacidade);
        } elseif (!empty($mesa_id)) {
            $query->andWhere([
                'or',
                ['mesa_id' => $mesa_id],
                ['and', ['cliente_id' => null], ['in', 'tipo', [ClienteInbox::TIPO_VIDEO]]]
            ]);
        } else {
            $query->andWhere([
                'and',
                ['cliente_id' => null],
                ['in', 'tipo', [ClienteInbox::TIPO_VIDEO]]
            ]);
        }

        $mensagens = $query->orderBy(['created_at' => SORT_DESC])->limit(30)->all();

        $dados = [];
        foreach ($mensagens as $m) {
            $dados[] = [
                'id'             => $m->id,
                'tipo'           => $m->tipo,
                'titulo'         => $m->titulo,
                'conteudo_texto' => $m->conteudo_texto,
                'midia_url'      => $m->midia_url,
                'acoes_json'     => $m->acoes_json,
                'created_at'     => Yii::$app->formatter->asRelativeTime($m->created_at),
                'is_cliente'     => (isset($m->acoes_json['origem']) && $m->acoes_json['origem'] === 'cliente')
            ];
        }

        return ['success' => true, 'mensagens' => $dados];
    }
}
