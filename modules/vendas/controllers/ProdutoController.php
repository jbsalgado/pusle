<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\ProdutoFoto;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\UploadedFile;

class ProdutoController extends Controller
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
                    'delete' => ['POST'],
                    'delete-foto' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $query = Produto::find()
            ->where(['usuario_id' => Yii::$app->user->id])
            ->with(['categoria', 'fotos']);

        // Filtros
        $categoriaId = Yii::$app->request->get('categoria_id');
        $busca = Yii::$app->request->get('busca');
        $estoque = Yii::$app->request->get('estoque');
        $ativo = Yii::$app->request->get('ativo');

        if ($categoriaId) {
            $query->andWhere(['categoria_id' => $categoriaId]);
        }

        if ($busca) {
            $query->andWhere([
                'or',
                ['like', 'nome', $busca],
                ['like', 'codigo_referencia', $busca]
            ]);
        }

        if ($estoque === 'com') {
            $query->andWhere(['>', 'estoque_atual', 0]);
        } elseif ($estoque === 'sem') {
            $query->andWhere(['estoque_atual' => 0]);
        }

        if ($ativo !== null && $ativo !== '') {
            $query->andWhere(['ativo' => $ativo]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 12,
            ],
            'sort' => [
                'defaultOrder' => [
                    'nome' => SORT_ASC,
                ]
            ],
        ]);

        $categorias = Categoria::find()
            ->where(['usuario_id' => Yii::$app->user->id, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'categorias' => $categorias,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Produto();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            // Upload de fotos
            $this->processUploadFotos($model);
            
            Yii::$app->session->setFlash('success', 'Produto cadastrado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            // Upload de fotos
            $this->processUploadFotos($model);
            
            Yii::$app->session->setFlash('success', 'Produto atualizado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Deletar fotos físicas
        foreach ($model->fotos as $foto) {
            $this->deleteFotoFile($foto);
            $foto->delete();
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Produto excluído com sucesso!');

        return $this->redirect(['index']);
    }

    public function actionDeleteFoto($id)
    {
        $foto = ProdutoFoto::findOne($id);
        
        if (!$foto) {
            throw new NotFoundHttpException('Foto não encontrada.');
        }

        $produto = $foto->produto;
        
        // Verificar se o produto pertence ao usuário
        if ($produto->usuario_id !== Yii::$app->user->id) {
            throw new NotFoundHttpException('Acesso negado.');
        }

        $this->deleteFotoFile($foto);
        $foto->delete();

        Yii::$app->session->setFlash('success', 'Foto excluída com sucesso!');
        return $this->redirect(['view', 'id' => $produto->id]);
    }

    public function actionSetFotoPrincipal($id)
    {
        $foto = ProdutoFoto::findOne($id);
        
        if (!$foto) {
            throw new NotFoundHttpException('Foto não encontrada.');
        }

        $produto = $foto->produto;
        
        if ($produto->usuario_id !== Yii::$app->user->id) {
            throw new NotFoundHttpException('Acesso negado.');
        }

        // Desmarcar outras fotos principais
        ProdutoFoto::updateAll(
            ['eh_principal' => false],
            ['produto_id' => $produto->id]
        );

        // Marcar esta como principal
        $foto->eh_principal = true;
        $foto->save(false);

        Yii::$app->session->setFlash('success', 'Foto principal definida!');
        return $this->redirect(['view', 'id' => $produto->id]);
    }

    protected function processUploadFotos($model)
    {
        $uploadPath = Yii::getAlias('@webroot/uploads/produtos/' . $model->id);
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $files = UploadedFile::getInstancesByName('fotos');
        
        if ($files) {
            $ordem = ProdutoFoto::find()->where(['produto_id' => $model->id])->count();
            
            foreach ($files as $file) {
                $filename = uniqid() . '.' . $file->extension;
                $filePath = $uploadPath . '/' . $filename;
                
                if ($file->saveAs($filePath)) {
                    $foto = new ProdutoFoto();
                    $foto->produto_id = $model->id;
                    $foto->arquivo_nome = $file->name;
                    $foto->arquivo_path = 'uploads/produtos/' . $model->id . '/' . $filename;
                    $foto->ordem = $ordem++;
                    
                    // Se for a primeira foto, marcar como principal
                    if (ProdutoFoto::find()->where(['produto_id' => $model->id])->count() == 0) {
                        $foto->eh_principal = true;
                    }
                    
                    $foto->save();
                }
            }
        }
    }

    protected function deleteFotoFile($foto)
    {
        $filePath = Yii::getAlias('@webroot/' . $foto->arquivo_path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    protected function findModel($id)
    {
        if (($model = Produto::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('O produto solicitado não existe.');
    }
}