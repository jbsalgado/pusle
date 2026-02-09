<?php

namespace app\modules\contas_pagar\controllers;

use Yii;
use app\modules\contas_pagar\models\ContaPagar;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;

/**
 * ContaPagarController implementa as ações CRUD para ContaPagar
 */
class ContaPagarController extends Controller
{
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'pagar' => ['POST'],
                    'cancelar' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista todas as contas a pagar
     * @return string
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        $query = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['data_vencimento' => SORT_ASC]);

        // Filtros
        $status = Yii::$app->request->get('status');
        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Exporta as contas a pagar para CSV
     * @return mixed
     */
    public function actionExport()
    {
        $usuarioId = Yii::$app->user->id;

        $query = ContaPagar::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['data_vencimento' => SORT_ASC]);

        // Filtros (mesma lógica do index)
        $status = Yii::$app->request->get('status');
        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        $models = $query->all();

        $filename = 'contas_pagar_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM para abrir corretamente no Excel (UTF-8)
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalhos
        fputcsv($output, ['ID', 'Descrição', 'Valor', 'Vencimento', 'Status', 'Fornecedor', 'Pagamento', 'Observações']);

        foreach ($models as $model) {
            fputcsv($output, [
                $model->id,
                $model->descricao,
                number_format($model->valor, 2, ',', '.'),
                date('d/m/Y', strtotime($model->data_vencimento)),
                $model->status,
                $model->fornecedor ? $model->fornecedor->nome : '-',
                $model->data_pagamento ? date('d/m/Y', strtotime($model->data_pagamento)) : '-',
                $model->observacoes
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Visualiza uma conta a pagar específica
     * @param string $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria uma nova conta a pagar
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new ContaPagar();
        $model->usuario_id = Yii::$app->user->id;
        $model->status = ContaPagar::STATUS_PENDENTE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            // Processa Upload
            if ($this->processUpload($model)) {
                $model->save(false);
            }

            Yii::$app->session->setFlash('success', 'Conta a pagar criada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Atualiza uma conta a pagar existente
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Não permite editar conta paga ou cancelada
        if ($model->isPaga() || $model->status === ContaPagar::STATUS_CANCELADA) {
            Yii::$app->session->setFlash('error', 'Não é possível editar uma conta paga ou cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            // Processa Upload
            if ($this->processUpload($model)) {
                $model->save(false);
            }

            Yii::$app->session->setFlash('success', 'Conta a pagar atualizada com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Processa upload de comprovante
     */
    protected function processUpload($model)
    {
        $file = \yii\web\UploadedFile::getInstance($model, 'comprovanteFile');

        if ($file) {
            $path = 'uploads/contas_pagar/' . $model->id;
            $fullPath = Yii::getAlias('@webroot/' . $path);

            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            $fileName = uniqid() . '.' . $file->extension;
            $filePath = $fullPath . '/' . $fileName;

            if ($file->saveAs($filePath)) {
                // Remove arquivo antigo se existir
                if ($model->arquivo_comprovante) {
                    $oldFile = Yii::getAlias('@webroot/' . $model->arquivo_comprovante);
                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
                }

                $model->arquivo_comprovante = $path . '/' . $fileName;
                return true;
            }
        }
        return false;
    }

    /**
     * Marca uma conta como paga
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionPagar($id)
    {
        $model = $this->findModel($id);

        if ($model->isPaga()) {
            Yii::$app->session->setFlash('error', 'Esta conta já está paga.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->status === ContaPagar::STATUS_CANCELADA) {
            Yii::$app->session->setFlash('error', 'Não é possível pagar uma conta cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $dataPagamento = Yii::$app->request->post('data_pagamento');
        if ($model->marcarComoPaga($dataPagamento)) {
            // INTEGRAÇÃO CAIXA: Registra saída
            $movimentacao = \app\modules\caixa\helpers\CaixaHelper::registrarSaidaContaPagar($model->id, $model->valor);

            $msg = 'Conta marcada como paga com sucesso!';
            if ($movimentacao) {
                $msg .= ' Débito registrado no caixa.';
            } else {
                $msg .= ' <br><small>⚠️ O débito não foi registrado no caixa (verifique se o caixa está aberto).</small>';
            }

            Yii::$app->session->setFlash('success', $msg);
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao marcar conta como paga: ' . implode(', ', $model->getFirstErrors()));
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Cancela uma conta a pagar
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionCancelar($id)
    {
        $model = $this->findModel($id);

        if ($model->isPaga()) {
            Yii::$app->session->setFlash('error', 'Não é possível cancelar uma conta já paga.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($model->status === ContaPagar::STATUS_CANCELADA) {
            Yii::$app->session->setFlash('error', 'Esta conta já está cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->status = ContaPagar::STATUS_CANCELADA;
        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Conta cancelada com sucesso!');
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao cancelar conta: ' . implode(', ', $model->getFirstErrors()));
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Deleta uma conta a pagar
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Não permite deletar conta paga
        if ($model->isPaga()) {
            Yii::$app->session->setFlash('error', 'Não é possível deletar uma conta já paga.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Conta a pagar deletada com sucesso!');

        return $this->redirect(['index']);
    }

    /**
     * Encontra o modelo ContaPagar baseado no ID
     * @param string $id
     * @return ContaPagar
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    protected function findModel($id)
    {
        $usuarioId = Yii::$app->user->id;

        if (($model = ContaPagar::findOne(['id' => $id, 'usuario_id' => $usuarioId])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A conta a pagar solicitada não foi encontrada.');
    }
}
