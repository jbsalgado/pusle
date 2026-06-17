<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\CupomFiscal;
use app\modules\vendas\models\Venda;
use yii\web\Response;
use app\components\nfe\NFeBuilder;
use app\components\nfe\NFeService;

/**
 * CupomFiscalController implements the management for CupomFiscal models.
 */
class CupomFiscalController extends Controller
{
    public $layout = 'main';

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
                ],
            ],
        ];
    }

    /**
     * Lists all CupomFiscal models.
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;
        $dataProvider = new ActiveDataProvider([
            'query' => CupomFiscal::find()->where(['usuario_id' => $usuarioId])->orderBy(['data_emissao' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CupomFiscal model.
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Generates and outputs the DANFE PDF.
     */
    public function actionPdf($id)
    {
        $model = $this->findModel($id);

        if (!file_exists($model->xml_path)) {
            Yii::$app->session->setFlash('error', 'Arquivo XML não encontrado em repatitório local.');
            return $this->redirect(['index']);
        }

        $xmlContent = file_get_contents($model->xml_path);

        try {
            $pdf = Yii::$app->nfwService->gerarDanfe($xmlContent, $model->modelo);

            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->add('Content-Type', 'application/pdf');
            Yii::$app->response->headers->add('Content-Disposition', 'inline; filename="DANFE_' . $model->chave_acesso . '.pdf"');

            return $pdf;
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro ao gerar PDF: ' . $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * Downloads the XML file.
     */
    public function actionXml($id)
    {
        $model = $this->findModel($id);

        if (!file_exists($model->xml_path)) {
            throw new NotFoundHttpException('O arquivo XML não existe.');
        }

        return Yii::$app->response->sendFile($model->xml_path, $model->chave_acesso . '.xml');
    }

    /**
     * Emite NFe/NFCe para uma venda
     */
    public function actionEmitir($venda_id, $modelo = '65')
    {
        $venda = Venda::findOne(['id' => $venda_id, 'usuario_id' => Yii::$app->user->id]);

        if (!$venda) {
            throw new NotFoundHttpException('Venda não encontrada.');
        }

        // Validações
        $cupomExistente = CupomFiscal::findOne(['venda_id' => $venda_id, 'status' => CupomFiscal::STATUS_AUTORIZADA]);
        if ($cupomExistente) {
            Yii::$app->session->setFlash('error', 'Venda já possui nota fiscal autorizada.');
            return $this->redirect(['/vendas/venda/view', 'id' => $venda_id]);
        }

        if (!$venda->cliente_id) {
            Yii::$app->session->setFlash('error', 'Venda deve ter um cliente cadastrado.');
            return $this->redirect(['/vendas/venda/view', 'id' => $venda_id]);
        }

        try {
            // Gerar XML
            $xml = NFeBuilder::buildFromVenda($venda, $modelo);

            // Criar registro do cupom fiscal
            $cupom = new CupomFiscal();
            $cupom->venda_id = $venda->id;
            $cupom->usuario_id = Yii::$app->user->id;
            $cupom->modelo = $modelo;
            $cupom->serie = $modelo === '65' ? Yii::$app->params['nfe']['nfce']['serie'] : Yii::$app->params['nfe']['nfe']['serie'];
            $cupom->ambiente = Yii::$app->params['nfe']['ambiente'] === 'producao' ? CupomFiscal::AMBIENTE_PRODUCAO : CupomFiscal::AMBIENTE_HOMOLOGACAO;
            $cupom->status = CupomFiscal::STATUS_PENDENTE;
            $cupom->data_emissao = date('Y-m-d H:i:s');

            // Obter próximo número
            $ultimoNumero = CupomFiscal::find()
                ->where(['modelo' => $modelo, 'usuario_id' => Yii::$app->user->id])
                ->max('numero');
            $cupom->numero = ($ultimoNumero ?? 0) + 1;

            if (!$cupom->save()) {
                throw new \Exception('Erro ao salvar cupom fiscal: ' . json_encode($cupom->errors));
            }

            // Transmitir para SEFAZ
            $service = new NFeService();
            $resultado = $service->transmitir($xml, $modelo);

            if ($resultado['success']) {
                // Atualizar cupom com dados da autorização
                $cupom->status = CupomFiscal::STATUS_AUTORIZADA;
                $cupom->chave_acesso = $resultado['chave'];
                $cupom->protocolo = $resultado['protocolo'] ?? null;
                $cupom->mensagem_retorno = $resultado['mensagem'];

                // Salvar XML
                $xmlDir = Yii::getAlias('@runtime') . '/nfe';
                if (!is_dir($xmlDir)) {
                    mkdir($xmlDir, 0755, true);
                }
                $xmlPath = $xmlDir . '/' . $resultado['chave'] . '.xml';
                file_put_contents($xmlPath, $resultado['xml_autorizado'] ?? $resultado['xml_assinado']);
                $cupom->xml_path = $xmlPath;

                $cupom->save(false);

                Yii::$app->session->setFlash('success', 'NFe emitida com sucesso! Chave: ' . $resultado['chave']);
            } else {
                // Erro na transmissão
                $cupom->status = CupomFiscal::STATUS_ERRO;
                $cupom->mensagem_retorno = $resultado['mensagem'];
                $cupom->save(false);

                Yii::$app->session->setFlash('error', 'Erro ao emitir NFe: ' . $resultado['mensagem']);
            }

            return $this->redirect(['view', 'id' => $cupom->id]);
        } catch (\Exception $e) {
            Yii::error('Erro ao emitir NFe: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Erro ao emitir NFe: ' . $e->getMessage());
            return $this->redirect(['/vendas/venda/view', 'id' => $venda_id]);
        }
    }

    /**
     * Consulta status da NFe na SEFAZ
     */
    public function actionConsultar($id)
    {
        $model = $this->findModel($id);

        if (!$model->chave_acesso) {
            Yii::$app->session->setFlash('error', 'Nota fiscal não possui chave de acesso.');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $service = new NFeService();
            $resultado = $service->consultar($model->chave_acesso, $model->modelo);

            if ($resultado['success']) {
                Yii::$app->session->setFlash('success', 'Consulta realizada: ' . $resultado['mensagem']);
            } else {
                Yii::$app->session->setFlash('warning', 'Consulta: ' . $resultado['mensagem']);
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro na consulta: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Cancela NFe
     */
    public function actionCancelar($id)
    {
        $model = $this->findModel($id);

        if ($model->status !== CupomFiscal::STATUS_AUTORIZADA) {
            Yii::$app->session->setFlash('error', 'Apenas notas autorizadas podem ser canceladas.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $justificativa = Yii::$app->request->post('justificativa');

        if (!$justificativa) {
            return $this->render('cancelar', ['model' => $model]);
        }

        if (strlen($justificativa) < 15) {
            Yii::$app->session->setFlash('error', 'Justificativa deve ter no mínimo 15 caracteres.');
            return $this->render('cancelar', ['model' => $model]);
        }

        try {
            $service = new NFeService();
            $resultado = $service->cancelar(
                $model->chave_acesso,
                $model->protocolo,
                $justificativa,
                $model->modelo
            );

            if ($resultado['success']) {
                $model->status = CupomFiscal::STATUS_CANCELADA;
                $model->mensagem_retorno = 'Cancelada: ' . $resultado['mensagem'];
                $model->save(false);

                Yii::$app->session->setFlash('success', 'NFe cancelada com sucesso!');
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao cancelar: ' . $resultado['mensagem']);
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro ao cancelar: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Finds the CupomFiscal model based on its primary key value.
     */
    protected function findModel($id)
    {
        if (($model = CupomFiscal::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A página solicitada não existe.');
    }
}
