<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use app\components\TenantHelper;
use app\modules\vendas\models\TrilhaSonora;
use app\modules\vendas\services\VideoGeneratorService;

/**
 * Controller para CRUD e gerenciamento de Trilhas Sonoras (Músicas de Fundo dos Vídeos)
 */
class TrilhaSonoraController extends Controller
{
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
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'upload' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista todas as trilhas sonoras (padrão + customizadas)
     */
    public function actionIndex()
    {
        $usuarioId = TenantHelper::getId();

        $customizadas = TrilhaSonora::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $padrao = VideoGeneratorService::getMusicasDisponiveis();

        $newModel = new TrilhaSonora();

        return $this->render('index', [
            'customizadas' => $customizadas,
            'padrao' => $padrao,
            'model' => $newModel,
        ]);
    }

    /**
     * Upload de nova trilha sonora ou efeito especial
     */
    public function actionUpload()
    {
        $usuarioId = TenantHelper::getId();
        $model = new TrilhaSonora();
        $model->usuario_id = $usuarioId;

        $isAjax = Yii::$app->request->isAjax 
            || isset(Yii::$app->request->acceptableContentTypes['application/json'])
            || (strpos((string)Yii::$app->request->headers->get('Accept'), 'application/json') !== false);

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->audioFile = UploadedFile::getInstance($model, 'audioFile');

            if (!$model->audioFile) {
                // Tenta pegar sem o prefixo do model se enviado via AJAX FormData
                $model->audioFile = UploadedFile::getInstanceByName('audioFile');
            }

            if ($model->audioFile) {
                $diretorio = Yii::getAlias('@app/web/uploads/audio');
                if (!is_dir($diretorio)) {
                    FileHelper::createDirectory($diretorio, 0777, true);
                }

                $ext = strtolower($model->audioFile->extension);
                $nomeArquivoSalvo = sprintf('trilha_%s_%s.%s', time(), uniqid(), $ext);
                $caminhoAbsoluto = $diretorio . DIRECTORY_SEPARATOR . $nomeArquivoSalvo;
                $caminhoRelativo = 'uploads/audio/' . $nomeArquivoSalvo;

                if ($model->audioFile->saveAs($caminhoAbsoluto)) {
                    $model->arquivo_nome = $model->audioFile->name;
                    $model->arquivo_path = $caminhoRelativo;
                    $model->formato = $ext;
                    $model->tamanho_bytes = $model->audioFile->size;

                    if (empty($model->titulo)) {
                        $model->titulo = pathinfo($model->audioFile->name, PATHINFO_FILENAME);
                    }

                    if (empty($model->tipo)) {
                        $model->tipo = TrilhaSonora::TIPO_MUSICA;
                    }

                    if ($model->save(false)) {
                        if ($isAjax) {
                            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                            return [
                                'success' => true,
                                'message' => 'Áudio cadastrado com sucesso!',
                                'trilha' => [
                                    'id' => $model->id,
                                    'titulo' => $model->titulo,
                                    'arquivo' => $model->arquivo_path,
                                    'tipo' => $model->tipo,
                                    'tipo_label' => $model->getTipoLabel(),
                                    'url' => $model->getUrl(),
                                ]
                            ];
                        }
                        Yii::$app->session->setFlash('success', 'Trilha sonora enviada e cadastrada com sucesso!');
                    } else {
                        if ($isAjax) {
                            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                            return ['success' => false, 'message' => 'Erro ao salvar os dados da trilha sonora no banco.'];
                        }
                        Yii::$app->session->setFlash('error', 'Erro ao salvar os dados da trilha sonora no banco.');
                    }
                } else {
                    if ($isAjax) {
                        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                        return ['success' => false, 'message' => 'Falha ao salvar o arquivo de áudio no servidor.'];
                    }
                    Yii::$app->session->setFlash('error', 'Falha ao salvar o arquivo de áudio no servidor.');
                }
            } else {
                if ($isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return ['success' => false, 'message' => 'Por favor, selecione um arquivo de áudio válido.'];
                }
                Yii::$app->session->setFlash('error', 'Por favor, selecione um arquivo de áudio válido.');
            }
        }

        if ($isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['success' => false, 'message' => 'Requisição inválida.'];
        }

        return $this->redirect(['index']);
    }

    /**
     * Exclui uma trilha sonora customizada
     */
    public function actionDelete($id)
    {
        $usuarioId = TenantHelper::getId();
        $model = TrilhaSonora::findOne(['id' => $id, 'usuario_id' => $usuarioId]);

        if (!$model) {
            throw new NotFoundHttpException('Trilha sonora não encontrada.');
        }

        // Apagar arquivo físico se existir
        if (!empty($model->arquivo_path)) {
            $caminhoAbsoluto = Yii::getAlias('@app/web/') . ltrim($model->arquivo_path, '/');
            if (file_exists($caminhoAbsoluto)) {
                @unlink($caminhoAbsoluto);
            }
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Trilha sonora removida com sucesso!');

        return $this->redirect(['index']);
    }
}
