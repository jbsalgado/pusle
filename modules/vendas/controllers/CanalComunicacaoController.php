<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\components\TenantHelper;
use app\models\Usuario;
use app\modules\vendas\models\CanalSetor;
use app\modules\vendas\models\CanalSetorColaborador;
use app\modules\vendas\models\ClienteInbox;
use app\modules\vendas\models\Clientes;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\LojaConfiguracao;
use app\modules\vendas\models\LojaPermissao;

/**
 * CanalComunicacaoController
 * 
 * Controlador central do Canal de Comunicação Interno estilo WhatsApp:
 * - Multi-Tenant com isolamento estrito por Loja (usuario_id)
 * - Grupos/Setores (Vendas, Cobrança, Atendimento, etc.)
 * - Apenas o Dono da Loja (eh_dono_loja) pode criar/editar/excluir setores e vincular colaboradores
 * - Colaboradores só visualizam e respondem mensagens dos setores aos quais foram associados
 * - Liberação de acesso controlada pelo Administrador do SaaS via LojaPermissao
 */
class CanalComunicacaoController extends Controller
{
    public $layout = 'main';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Apenas usuários logados
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'enviar-mensagem' => ['POST'],
                    'upload-midia' => ['POST'],
                    'marcar-lido' => ['POST'],
                    'salvar-setor' => ['POST'],
                    'excluir-setor' => ['POST'],
                    'vincular-colaboradores' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Valida se a loja possui permissão ativa concedida pelo SaaS Admin
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $lojaId = $this->getLojaId();
        if (empty($lojaId)) {
            throw new ForbiddenHttpException('Tenant/Loja não identificada.');
        }

        // Verifica liberação do módulo pelo SaaS Admin
        $liberado = LojaPermissao::temPermissao('canal-comunicacao-interno', $lojaId);
        if (!$liberado && !TenantHelper::isAdmin()) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->data = [
                    'success' => false,
                    'message' => 'O Canal de Comunicação Interno não está habilitado para esta loja pelo Administrador do SaaS.'
                ];
                return false;
            }
            throw new ForbiddenHttpException('O Canal de Comunicação Interno não está habilitado para esta loja pelo Administrador do SaaS.');
        }

        return true;
    }

    /**
     * Retorna o ID da loja atual (tenant)
     */
    protected function getLojaId(): ?string
    {
        return TenantHelper::getId();
    }

    /**
     * Verifica se o usuário atual é dono da loja ou super admin
     */
    protected function isDonoLoja(): bool
    {
        $usuario = Yii::$app->user->identity;
        if (!$usuario) {
            return false;
        }
        if (TenantHelper::isAdmin()) {
            return true;
        }
        $lojaId = $this->getLojaId();
        return ($usuario->eh_dono_loja || $usuario->id === $lojaId);
    }

    /**
     * Retorna o nome de exibição do atendente atual
     */
    protected function getNomeAtendente(): string
    {
        $colaborador = Colaborador::getColaboradorLogado();
        if ($colaborador && !empty($colaborador->nome_completo)) {
            return $colaborador->nome_completo;
        }
        $usuario = Yii::$app->user->identity;
        return $usuario ? ($usuario->nome ?: 'Atendente') : 'Atendente';
    }

    /**
     * Tela Principal do Canal de Comunicação Interno
     */
    public function actionIndex()
    {
        $lojaId = $this->getLojaId();
        $usuarioLoja = Usuario::findOne($lojaId);
        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $lojaId]);
        $usuarioLogado = Yii::$app->user->identity;

        // Garante criação dos setores padrão se a loja for nova
        CanalSetor::criarSetoresPadrao($lojaId);

        $setoresPermitidos = CanalSetor::getSetoresPermitidosParaUsuario($lojaId, $usuarioLogado);
        $ehDono = $this->isDonoLoja();

        $colaboradores = [];
        if ($ehDono) {
            $colaboradores = Colaborador::find()
                ->where(['usuario_id' => $lojaId, 'ativo' => true])
                ->orderBy(['nome_completo' => SORT_ASC])
                ->all();
        }

        $hubUrlCompleta = $this->gerarHubUrlCompleta($usuarioLoja);

        return $this->render('index', [
            'usuarioLoja' => $usuarioLoja,
            'lojaConfig' => $lojaConfig,
            'setoresPermitidos' => $setoresPermitidos,
            'ehDono' => $ehDono,
            'colaboradores' => $colaboradores,
            'hubUrlCompleta' => $hubUrlCompleta,
        ]);
    }

    /**
     * Endpoint para gerar a URL pública do Direct Hub da Loja
     */
    protected function gerarHubUrlCompleta(?Usuario $usuarioLoja): string
    {
        if (!$usuarioLoja) {
            return '';
        }
        $slug = !empty($usuarioLoja->slug) ? $usuarioLoja->slug : $usuarioLoja->id;
        return \yii\helpers\Url::to(['/hub/index', 'slug' => $slug], true);
    }

    /**
     * Retorna a lista de conversas/threads agrupadas por cliente ou chamado
     */
    public function actionGetConversas($setor_id = null, $q = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();
        $usuarioLogado = Yii::$app->user->identity;
        $ehDono = $this->isDonoLoja();

        // 1. Determina quais setores o usuário pode ver
        $setoresPermitidos = CanalSetor::getSetoresPermitidosParaUsuario($lojaId, $usuarioLogado);
        $setorIdsPermitidos = array_map(function($s) { return $s->id; }, $setoresPermitidos);

        $query = ClienteInbox::find()->where(['usuario_id' => $lojaId]);

        // 2. Filtro por Setor
        if (!empty($setor_id) && $setor_id !== 'todos') {
            if (!$ehDono && !in_array($setor_id, $setorIdsPermitidos)) {
                return [
                    'success' => false,
                    'message' => 'Você não tem acesso a este setor.',
                    'conversas' => [],
                    'total_nao_lidos' => 0
                ];
            }
            $query->andWhere(['setor_id' => $setor_id]);
        } else {
            // Se não é dono da loja, filtra estritamente pelos setores onde tem permissão
            if (!$ehDono) {
                if (empty($setorIdsPermitidos)) {
                    return [
                        'success' => true,
                        'conversas' => [],
                        'total_nao_lidos' => 0
                    ];
                }
                $query->andWhere(['or', ['setor_id' => $setorIdsPermitidos], ['setor_id' => null]]);
            }
        }

        // 3. Filtro de busca por texto/cliente
        $q = trim((string)$q);
        if (!empty($q)) {
            $query->andWhere([
                'or',
                ['ilike', 'titulo', $q],
                ['ilike', 'conteudo_texto', $q],
                new \yii\db\Expression("acoes_json::text ILIKE :q", [':q' => "%{$q}%"])
            ]);
        }

        // Busca mensagens recentes para agrupar em conversas (estilo WhatsApp)
        $mensagens = $query->orderBy(['created_at' => SORT_DESC])->limit(200)->all();

        // Mapa de setores para enriquecer retorno
        $setoresMap = [];
        $todosSetores = CanalSetor::find()->where(['usuario_id' => $lojaId])->all();
        foreach ($todosSetores as $s) {
            $setoresMap[$s->id] = [
                'id' => $s->id,
                'nome' => $s->nome,
                'icone' => $s->icone ?: '💬',
                'cor' => $s->cor ?: 'emerald',
            ];
        }

        $conversas = [];

        foreach ($mensagens as $msg) {
            $acoes = is_array($msg->acoes_json) ? $msg->acoes_json : (json_decode($msg->acoes_json, true) ?: []);
            
            // Chave da conversa: cliente_id > telefone > mesa_id > id
            $clienteId = $msg->cliente_id;
            $telefone = $acoes['telefone'] ?? null;
            $mesaId = $msg->mesa_id;

            if (!empty($clienteId)) {
                $conversaKey = 'cli_' . $clienteId;
            } elseif (!empty($telefone)) {
                $conversaKey = 'tel_' . preg_replace('/[^0-9]/', '', $telefone);
            } elseif (!empty($mesaId)) {
                $conversaKey = 'mesa_' . $mesaId;
            } else {
                $conversaKey = 'msg_' . $msg->id;
            }

            $setorInfo = (!empty($msg->setor_id) && isset($setoresMap[$msg->setor_id]))
                ? $setoresMap[$msg->setor_id]
                : ['id' => null, 'nome' => 'Geral', 'icone' => '💬', 'cor' => 'teal'];

            $isCliente = (!isset($acoes['origem']) || $acoes['origem'] !== 'loja');
            $isNaoLido = ($isCliente && !$msg->lido);

            $nomeRemetente = $acoes['remetente'] ?? $acoes['cliente'] ?? ($msg->cliente ? $msg->cliente->nome_completo : ($isCliente ? 'Cliente' : 'Atendimento'));
            if ($msg->mesa) {
                $nomeRemetente = "Mesa {$msg->mesa->numero_mesa} (" . $nomeRemetente . ")";
            }

            $timestamp = strtotime($msg->created_at);

            if (!isset($conversas[$conversaKey])) {
                $conversas[$conversaKey] = [
                    'conversa_id' => $conversaKey,
                    'cliente_id' => $clienteId,
                    'cliente_nome' => $nomeRemetente,
                    'cliente_telefone' => $telefone ?: ($msg->cliente ? $msg->cliente->telefone : ''),
                    'mesa_id' => $mesaId,
                    'setor' => $setorInfo,
                    'setor_id' => $msg->setor_id,
                    'ultima_mensagem' => $msg->conteudo_texto ?: ($msg->midia_url ? '📷 Foto' : 'Nova mensagem'),
                    'ultimo_envio_formatado' => date('d/m H:i', $timestamp),
                    'ultimo_envio_ts' => $timestamp,
                    'tempo_relativo' => Yii::$app->formatter->asRelativeTime($msg->created_at),
                    'nao_lidos_count' => $isNaoLido ? 1 : 0,
                    'origem_ultima' => $isCliente ? 'cliente' : 'loja',
                ];
            } else {
                if ($isNaoLido) {
                    $conversas[$conversaKey]['nao_lidos_count']++;
                }
                // Garante que o mais recente define o snippet e horário
                if ($timestamp > $conversas[$conversaKey]['ultimo_envio_ts']) {
                    $conversas[$conversaKey]['ultima_mensagem'] = $msg->conteudo_texto ?: ($msg->midia_url ? '📷 Foto' : 'Nova mensagem');
                    $conversas[$conversaKey]['ultimo_envio_formatado'] = date('d/m H:i', $timestamp);
                    $conversas[$conversaKey]['ultimo_envio_ts'] = $timestamp;
                    $conversas[$conversaKey]['tempo_relativo'] = Yii::$app->formatter->asRelativeTime($msg->created_at);
                    $conversas[$conversaKey]['origem_ultima'] = $isCliente ? 'cliente' : 'loja';
                    if (!empty($msg->setor_id) && isset($setoresMap[$msg->setor_id])) {
                        $conversas[$conversaKey]['setor'] = $setoresMap[$msg->setor_id];
                        $conversas[$conversaKey]['setor_id'] = $msg->setor_id;
                    }
                }
            }
        }

        // Ordena conversas pela mais recente no topo
        $listaConversas = array_values($conversas);
        usort($listaConversas, function($a, $b) {
            return $b['ultimo_envio_ts'] <=> $a['ultimo_envio_ts'];
        });

        // Contagem total de não lidos para os setores permitidos
        $totalNaoLidosQuery = ClienteInbox::find()
            ->where(['usuario_id' => $lojaId, 'lido' => false]);
        if (!$ehDono && !empty($setorIdsPermitidos)) {
            $totalNaoLidosQuery->andWhere(['or', ['setor_id' => $setorIdsPermitidos], ['setor_id' => null]]);
        }
        $totalNaoLidos = (int) $totalNaoLidosQuery->count();

        return [
            'success' => true,
            'conversas' => $listaConversas,
            'total_nao_lidos' => $totalNaoLidos,
        ];
    }

    /**
     * Retorna o histórico de mensagens de uma conversa e marca as mensagens como lidas
     */
    public function actionGetMensagens($conversa_id = null, $cliente_id = null, $mesa_id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();
        $ehDono = $this->isDonoLoja();
        $usuarioLogado = Yii::$app->user->identity;

        $query = ClienteInbox::find()->where(['usuario_id' => $lojaId]);

        // Decodifica conversa_id se passado
        if (!empty($conversa_id)) {
            if (strpos($conversa_id, 'cli_') === 0) {
                $cliente_id = substr($conversa_id, 4);
            } elseif (strpos($conversa_id, 'tel_') === 0) {
                $tel = substr($conversa_id, 4);
                $query->andWhere(new \yii\db\Expression("acoes_json->>'telefone' ILIKE :tel", [':tel' => "%{$tel}%"]));
            } elseif (strpos($conversa_id, 'mesa_') === 0) {
                $mesa_id = substr($conversa_id, 5);
            } elseif (strpos($conversa_id, 'msg_') === 0) {
                $msgId = substr($conversa_id, 4);
                $query->andWhere(['id' => $msgId]);
            }
        }

        if (!empty($cliente_id)) {
            $query->andWhere(['cliente_id' => $cliente_id]);
        } elseif (!empty($mesa_id)) {
            $query->andWhere(['mesa_id' => $mesa_id]);
        }

        // Validação de setor para colaboradores
        if (!$ehDono) {
            $setoresPermitidos = CanalSetor::getSetoresPermitidosParaUsuario($lojaId, $usuarioLogado);
            $setorIds = array_map(function($s) { return $s->id; }, $setoresPermitidos);
            $query->andWhere(['or', ['setor_id' => $setorIds], ['setor_id' => null]]);
        }

        $mensagens = $query->orderBy(['created_at' => SORT_ASC])->limit(300)->all();

        // Marca como lidas as mensagens recebidas do cliente nesta conversa
        $idsParaMarcar = [];
        $itensFormatados = [];

        foreach ($mensagens as $m) {
            $acoes = is_array($m->acoes_json) ? $m->acoes_json : (json_decode($m->acoes_json, true) ?: []);
            $origem = $acoes['origem'] ?? 'cliente';
            $isLoja = ($origem === 'loja');

            if (!$isLoja && !$m->lido) {
                $idsParaMarcar[] = $m->id;
            }

            $itensFormatados[] = [
                'id' => $m->id,
                'lado' => $isLoja ? 'direita' : 'esquerda',
                'autor' => $acoes['atendente_nome'] ?? $acoes['autor'] ?? $acoes['remetente'] ?? ($isLoja ? 'Loja' : 'Cliente'),
                'setor_nome' => $m->setor ? $m->setor->nome : ($acoes['setor_nome'] ?? null),
                'setor_icone' => $m->setor ? $m->setor->icone : '💬',
                'tipo' => $m->tipo,
                'titulo' => $m->titulo,
                'texto' => $m->conteudo_texto,
                'midia_url' => $m->midia_url,
                'acoes' => $acoes,
                'lido' => (bool)$m->lido,
                'hora' => date('H:i', strtotime($m->created_at)),
                'data' => date('d/m/Y', strtotime($m->created_at)),
                'created_at_ts' => strtotime($m->created_at),
            ];
        }

        if (!empty($idsParaMarcar)) {
            ClienteInbox::updateAll(['lido' => true], ['id' => $idsParaMarcar]);
        }

        return [
            'success' => true,
            'mensagens' => $itensFormatados,
            'total' => count($itensFormatados),
        ];
    }

    /**
     * Envia mensagem de resposta pelo canal interno
     */
    public function actionEnviarMensagem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();
        $usuarioLogado = Yii::$app->user->identity;
        $ehDono = $this->isDonoLoja();

        $request = Yii::$app->request;
        $post = json_decode($request->getRawBody(), true) ?: $request->post();

        $clienteId = !empty($post['cliente_id']) ? $post['cliente_id'] : null;
        $mesaId = !empty($post['mesa_id']) ? $post['mesa_id'] : null;
        $setorId = !empty($post['setor_id']) ? $post['setor_id'] : null;
        $mensagemTexto = trim((string)($post['mensagem'] ?? ''));
        $midiaUrl = trim((string)($post['midia_url'] ?? ''));

        if (empty($mensagemTexto) && empty($midiaUrl)) {
            return ['success' => false, 'message' => 'Digite sua mensagem ou anexe uma foto.'];
        }

        // Validação de setor
        $setor = null;
        if (!empty($setorId)) {
            $setor = CanalSetor::findOne(['id' => $setorId, 'usuario_id' => $lojaId]);
            if (!$setor) {
                return ['success' => false, 'message' => 'Setor não encontrado.'];
            }
            if (!$ehDono) {
                $setoresPermitidos = CanalSetor::getSetoresPermitidosParaUsuario($lojaId, $usuarioLogado);
                $setorIds = array_map(function($s) { return $s->id; }, $setoresPermitidos);
                if (!in_array($setorId, $setorIds)) {
                    return ['success' => false, 'message' => 'Você não tem permissão para responder neste setor.'];
                }
            }
        }

        $colaborador = Colaborador::getColaboradorLogado();
        $nomeAtendente = $this->getNomeAtendente();
        $rotuloAutor = $setor ? "{$nomeAtendente} ({$setor->nome})" : $nomeAtendente;

        if (empty($mensagemTexto) && !empty($midiaUrl)) {
            $mensagemTexto = '📷 Imagem enviada pela loja';
        }

        $resposta = ClienteInbox::postar(
            $lojaId,
            $clienteId,
            ClienteInbox::TIPO_TEXTO,
            $rotuloAutor,
            $mensagemTexto,
            !empty($midiaUrl) ? $midiaUrl : null,
            [
                'origem' => 'loja',
                'autor' => $rotuloAutor,
                'atendente_nome' => $nomeAtendente,
                'colaborador_id' => $colaborador ? $colaborador->id : null,
                'setor_id' => $setor ? $setor->id : null,
                'setor_nome' => $setor ? $setor->nome : null,
            ],
            $mesaId,
            null,
            $setorId
        );

        if ($resposta) {
            // Marca anteriores do cliente como lidas
            if ($clienteId) {
                ClienteInbox::updateAll(
                    ['lido' => true],
                    ['usuario_id' => $lojaId, 'cliente_id' => $clienteId, 'lido' => false]
                );
            }

            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso!',
                'item' => [
                    'id' => $resposta->id,
                    'lado' => 'direita',
                    'autor' => $rotuloAutor,
                    'setor_nome' => $setor ? $setor->nome : null,
                    'setor_icone' => $setor ? $setor->icone : '💬',
                    'texto' => $resposta->conteudo_texto,
                    'midia_url' => $resposta->midia_url,
                    'hora' => date('H:i'),
                    'data' => date('d/m/Y'),
                    'lido' => true,
                ],
            ];
        }

        return ['success' => false, 'message' => 'Erro ao salvar mensagem no servidor.'];
    }

    /**
     * Upload de imagens e fotos para o chat
     */
    public function actionUploadMidia()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $foto = UploadedFile::getInstanceByName('foto') ?: UploadedFile::getInstanceByName('midia');
        if (!$foto) {
            return ['success' => false, 'message' => 'Nenhum arquivo enviado.'];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower($foto->extension);
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Formato inválido. Use JPG, PNG ou WebP.'];
        }

        $uploadDir = Yii::getAlias('@app/web/uploads/chat');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $filename = 'chat_' . date('Ymd_His') . '_' . substr(md5(uniqid(rand(), true)), 0, 8) . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if ($foto->saveAs($destPath)) {
            $url = \yii\helpers\Url::to('@web/uploads/chat/' . $filename, true);
            return [
                'success' => true,
                'url' => $url,
                'path' => '/uploads/chat/' . $filename,
            ];
        }

        return ['success' => false, 'message' => 'Falha ao salvar a imagem no servidor.'];
    }

    /**
     * Marca mensagens como lidas
     */
    public function actionMarcarLido()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();
        $request = Yii::$app->request;
        $post = json_decode($request->getRawBody(), true) ?: $request->post();

        $id = $post['id'] ?? $request->get('id');
        $clienteId = $post['cliente_id'] ?? null;

        if ($id === 'todos') {
            $afetados = ClienteInbox::updateAll(
                ['lido' => true],
                ['usuario_id' => $lojaId, 'lido' => false]
            );
        } elseif (!empty($clienteId)) {
            $afetados = ClienteInbox::updateAll(
                ['lido' => true],
                ['usuario_id' => $lojaId, 'cliente_id' => $clienteId, 'lido' => false]
            );
        } elseif (!empty($id)) {
            $afetados = ClienteInbox::updateAll(
                ['lido' => true],
                ['id' => $id, 'usuario_id' => $lojaId]
            );
        } else {
            return ['success' => false, 'message' => 'Parâmetros insuficientes.'];
        }

        return [
            'success' => true,
            'afetados' => (int)$afetados,
        ];
    }

    /**
     * Endpoint ultra-rápido para polling de mensagens não lidas
     */
    public function actionGetNaoLidosCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();
        $ehDono = $this->isDonoLoja();
        $usuarioLogado = Yii::$app->user->identity;

        $setoresPermitidos = CanalSetor::getSetoresPermitidosParaUsuario($lojaId, $usuarioLogado);
        $setorIds = array_map(function($s) { return $s->id; }, $setoresPermitidos);

        $query = ClienteInbox::find()
            ->where(['usuario_id' => $lojaId, 'lido' => false]);

        if (!$ehDono && !empty($setorIds)) {
            $query->andWhere(['or', ['setor_id' => $setorIds], ['setor_id' => null]]);
        }

        $totalNaoLidos = (int)$query->count();

        // Contagem por setor
        $porSetor = [];
        foreach ($setoresPermitidos as $s) {
            $porSetor[$s->id] = (int) ClienteInbox::find()
                ->where(['usuario_id' => $lojaId, 'setor_id' => $s->id, 'lido' => false])
                ->count();
        }

        return [
            'success' => true,
            'total_nao_lidos' => $totalNaoLidos,
            'por_setor' => $porSetor,
        ];
    }

    // =========================================================================
    // GERENCIAMENTO DE SETORES (EXCLUSIVO DO DONO DA LOJA)
    // =========================================================================

    /**
     * Retorna a lista completa de setores e colaboradores da loja (Apenas Dono)
     */
    public function actionListarSetores()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        if (!$this->isDonoLoja()) {
            throw new ForbiddenHttpException('Apenas o Dono da Loja tem permissão para gerenciar grupos e setores.');
        }

        CanalSetor::criarSetoresPadrao($lojaId);

        $setores = CanalSetor::find()
            ->where(['usuario_id' => $lojaId])
            ->orderBy(['ativo' => SORT_DESC, 'nome' => SORT_ASC])
            ->all();

        $colaboradores = Colaborador::find()
            ->where(['usuario_id' => $lojaId, 'ativo' => true])
            ->orderBy(['nome_completo' => SORT_ASC])
            ->all();

        $dadosSetores = [];
        foreach ($setores as $s) {
            $colabIds = CanalSetorColaborador::find()
                ->select('colaborador_id')
                ->where(['setor_id' => $s->id])
                ->column();

            $dadosSetores[] = [
                'id' => $s->id,
                'nome' => $s->nome,
                'descricao' => $s->descricao,
                'icone' => $s->icone ?: '💬',
                'cor' => $s->cor ?: 'emerald',
                'ativo' => (bool)$s->ativo,
                'colaborador_ids' => $colabIds,
                'total_colaboradores' => count($colabIds),
                'nao_lidas' => $s->getNaoLidasCount(),
            ];
        }

        $dadosColaboradores = [];
        foreach ($colaboradores as $c) {
            $dadosColaboradores[] = [
                'id' => $c->id,
                'nome' => $c->nome_completo,
                'funcao' => $c->eh_administrador ? 'Administrador' : ($c->eh_vendedor ? 'Vendedor' : ($c->eh_cobrador ? 'Cobrador' : 'Colaborador')),
            ];
        }

        return [
            'success' => true,
            'setores' => $dadosSetores,
            'colaboradores' => $dadosColaboradores,
        ];
    }

    /**
     * Salva ou edita um setor e vincula os colaboradores selecionados (Apenas Dono)
     */
    public function actionSalvarSetor()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        if (!$this->isDonoLoja()) {
            throw new ForbiddenHttpException('Apenas o Dono da Loja pode criar ou editar setores.');
        }

        $request = Yii::$app->request;
        $post = json_decode($request->getRawBody(), true) ?: $request->post();

        $id = !empty($post['id']) ? $post['id'] : null;
        $nome = trim((string)($post['nome'] ?? ''));
        $descricao = trim((string)($post['descricao'] ?? ''));
        $icone = trim((string)($post['icone'] ?? '💬'));
        $cor = trim((string)($post['cor'] ?? 'emerald'));
        $ativo = isset($post['ativo']) ? (bool)$post['ativo'] : true;
        $colaboradorIds = isset($post['colaborador_ids']) && is_array($post['colaborador_ids']) ? $post['colaborador_ids'] : [];

        if (empty($nome)) {
            return ['success' => false, 'message' => 'O nome do setor é obrigatório.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($id) {
                $setor = CanalSetor::findOne(['id' => $id, 'usuario_id' => $lojaId]);
                if (!$setor) {
                    throw new NotFoundHttpException('Setor não encontrado.');
                }
            } else {
                $setor = new CanalSetor();
                $setor->usuario_id = $lojaId;
            }

            $setor->nome = $nome;
            $setor->descricao = $descricao;
            $setor->icone = $icone;
            $setor->cor = $cor;
            $setor->ativo = $ativo;

            if (!$setor->save()) {
                $transaction->rollBack();
                return ['success' => false, 'message' => 'Erro ao salvar setor: ' . implode(', ', $setor->getFirstErrors())];
            }

            // Atualiza vínculos de colaboradores
            CanalSetorColaborador::deleteAll(['setor_id' => $setor->id]);

            foreach ($colaboradorIds as $colabId) {
                // Valida se o colaborador pertence a este tenant
                $colabExiste = Colaborador::find()->where(['id' => $colabId, 'usuario_id' => $lojaId])->exists();
                if ($colabExiste) {
                    $vinculo = new CanalSetorColaborador();
                    $vinculo->setor_id = $setor->id;
                    $vinculo->colaborador_id = $colabId;
                    $vinculo->usuario_id = $lojaId;
                    $vinculo->save(false);
                }
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Setor salvo com sucesso!',
                'setor' => [
                    'id' => $setor->id,
                    'nome' => $setor->nome,
                    'descricao' => $setor->descricao,
                    'icone' => $setor->icone,
                    'cor' => $setor->cor,
                    'ativo' => (bool)$setor->ativo,
                ],
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error("Erro ao salvar setor: " . $e->getMessage(), __METHOD__);
            return ['success' => false, 'message' => 'Erro interno ao salvar setor: ' . $e->getMessage()];
        }
    }

    /**
     * Exclui ou desativa um setor (Apenas Dono)
     */
    public function actionExcluirSetor()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lojaId = $this->getLojaId();

        if (!$this->isDonoLoja()) {
            throw new ForbiddenHttpException('Apenas o Dono da Loja pode excluir setores.');
        }

        $request = Yii::$app->request;
        $post = json_decode($request->getRawBody(), true) ?: $request->post();
        $id = $post['id'] ?? $request->get('id');

        if (empty($id)) {
            return ['success' => false, 'message' => 'ID do setor não informado.'];
        }

        $setor = CanalSetor::findOne(['id' => $id, 'usuario_id' => $lojaId]);
        if (!$setor) {
            return ['success' => false, 'message' => 'Setor não encontrado.'];
        }

        // Verifica se há mensagens associadas
        $temMensagens = ClienteInbox::find()->where(['setor_id' => $setor->id])->exists();
        if ($temMensagens) {
            // Desativação lógica para não quebrar histórico
            $setor->ativo = false;
            $setor->save(false);
            return [
                'success' => true,
                'message' => 'Setor desativado com sucesso (histórico de mensagens preservado).',
            ];
        }

        // Se não tem mensagens, pode deletar
        CanalSetorColaborador::deleteAll(['setor_id' => $setor->id]);
        $setor->delete();

        return [
            'success' => true,
            'message' => 'Setor removido com sucesso.',
        ];
    }
}
