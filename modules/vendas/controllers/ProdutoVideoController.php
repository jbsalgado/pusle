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
                        'actions' => ['generate', 'status', 'list'],
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'studio'   => ['GET', 'POST'],
                    'generate' => ['POST'],
                    'status'   => ['GET'],
                    'list'     => ['GET'],
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
    public function actionStudio($produto_id = null)
    {
        Yii::$app->response->format = Response::FORMAT_HTML;

        $lojaId = $this->getLojaId();

        $query = Produto::find()->where(['ativo' => true]);
        if ($lojaId) {
            $query->andWhere(['usuario_id' => $lojaId]);
        }

        $produtos = $query->orderBy(['nome' => SORT_ASC])->all();

        $produtoSelecionado = null;
        if ($produto_id) {
            $qSel = Produto::find()->where(['id' => $produto_id]);
            if ($lojaId) {
                $qSel->andWhere(['usuario_id' => $lojaId]);
            }
            $produtoSelecionado = $qSel->one();
        }

        if (!$produtoSelecionado && !empty($produtos)) {
            $produtoSelecionado = $produtos[0];
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
