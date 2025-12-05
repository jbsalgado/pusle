<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\ProdutoFoto;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;
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
        try {
            $model = $this->findModel($id);
        } catch (NotFoundHttpException $e) {
            Yii::error('Erro ao buscar produto: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Produto não encontrado: ' . $e->getMessage());
            return $this->redirect(['index']);
        }

        if ($model->load(Yii::$app->request->post())) {
            // 🔍 DEBUG: Log dos dados recebidos
            $postData = Yii::$app->request->post('Produto', []);
            Yii::info('Dados POST recebidos: ' . json_encode($postData), __METHOD__);
            Yii::info('Estoque no POST: ' . ($postData['estoque_atual'] ?? 'não encontrado'), __METHOD__);
            Yii::info('Estoque no model após load: ' . $model->estoque_atual, __METHOD__);
            Yii::info('Model attributes após load: ' . json_encode($model->attributes), __METHOD__);
            
            if ($model->save()) {
                Yii::info('Produto salvo com sucesso. Estoque final: ' . $model->estoque_atual, __METHOD__);
                // Upload de fotos
                $this->processUploadFotos($model);
                
                Yii::$app->session->setFlash('success', 'Produto atualizado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                // ✅ CORREÇÃO: Mostra erros de validação
                $erros = $model->getErrors();
                Yii::error('Erros de validação ao atualizar produto: ' . json_encode($erros), __METHOD__);
                
                $mensagemErro = 'Erro ao atualizar produto. Verifique os campos:';
                foreach ($erros as $campo => $mensagens) {
                    $mensagemErro .= "\n- " . $model->getAttributeLabel($campo) . ': ' . implode(', ', $mensagens);
                }
                
                Yii::$app->session->setFlash('error', $mensagemErro);
            }
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
        $produtoId = null;
        
        try {
            $foto = ProdutoFoto::findOne($id);
            
            if (!$foto) {
                throw new NotFoundHttpException('Foto não encontrada.');
            }

            $produto = $foto->produto;
            
            if (!$produto) {
                throw new NotFoundHttpException('Produto não encontrado para esta foto.');
            }
            
            // Verificar se o produto pertence ao usuário
            if ($produto->usuario_id !== Yii::$app->user->id) {
                throw new NotFoundHttpException('Acesso negado.');
            }

            // Guardar informações antes de excluir
            $ehPrincipal = $foto->eh_principal;
            $produtoId = $produto->id;

            // Verificar se é a única foto do produto
            $totalFotos = ProdutoFoto::find()->where(['produto_id' => $produto->id])->count();
            if ($totalFotos <= 1) {
                Yii::$app->session->setFlash('error', 'Não é possível excluir a única foto do produto. Adicione outra foto antes de excluir esta.');
                
                // Redirecionar de volta para a página de origem (update ou view)
                $redirectTo = Yii::$app->request->get('redirect') ?: Yii::$app->request->post('redirect', 'update');
                if (!in_array($redirectTo, ['update', 'view'])) {
                    $redirectTo = 'update';
                }
                return $this->redirect([$redirectTo, 'id' => $produtoId]);
            }

            // Excluir o arquivo físico primeiro
            $this->deleteFotoFile($foto);
            
            // Excluir o registro do banco
            $fotoId = $foto->id;
            $deleteResult = $foto->delete();
            
            if (!$deleteResult) {
                $errors = $foto->getFirstErrors();
                $errorMsg = !empty($errors) ? implode(', ', $errors) : 'Erro desconhecido ao excluir a foto.';
                Yii::$app->session->setFlash('error', 'Erro ao excluir a foto: ' . $errorMsg);
                $redirectTo = Yii::$app->request->get('redirect') ?: Yii::$app->request->post('redirect', 'update');
                if (!in_array($redirectTo, ['update', 'view'])) {
                    $redirectTo = 'update';
                }
                return $this->redirect([$redirectTo, 'id' => $produtoId]);
            }

            // Se a foto excluída era principal, definir outra como principal
            if ($ehPrincipal) {
                $outraFoto = ProdutoFoto::find()
                    ->where(['produto_id' => $produtoId])
                    ->orderBy(['ordem' => SORT_ASC])
                    ->one();
                
                if ($outraFoto) {
                    $outraFoto->eh_principal = true;
                    $outraFoto->save(false);
                }
            }

            Yii::$app->session->setFlash('success', 'Foto excluída com sucesso!');
            
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro ao excluir foto: ' . $e->getMessage());
            
            // Se conseguirmos o produto, redirecionar para ele
            if (isset($produto) && $produto) {
                $redirectTo = Yii::$app->request->get('redirect') ?: Yii::$app->request->post('redirect', 'update');
                if (!in_array($redirectTo, ['update', 'view'])) {
                    $redirectTo = 'update';
                }
                return $this->redirect([$redirectTo, 'id' => $produto->id]);
            }
            
            // Caso contrário, redirecionar para a lista
            return $this->redirect(['index']);
        }
        
        // Verificar se temos o produtoId antes de redirecionar
        if (!$produtoId) {
            Yii::$app->session->setFlash('error', 'Erro ao identificar o produto. Redirecionando para a lista.');
            return $this->redirect(['index']);
        }
        
        // Redirecionar de volta para a página de origem (update ou view)
        // Tentar pegar o parâmetro redirect do GET ou POST, padrão é 'update'
        $redirectTo = Yii::$app->request->get('redirect');
        if (!$redirectTo) {
            $redirectTo = Yii::$app->request->post('redirect');
        }
        if (!$redirectTo || !in_array($redirectTo, ['update', 'view'])) {
            $redirectTo = 'update'; // Padrão sempre é update
        }
        
        // Redirecionar usando array direto (funciona dentro do mesmo controller)
        return $this->redirect([$redirectTo, 'id' => $produtoId]);
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
        
        // Redirecionar de volta para a página de origem (update ou view)
        $redirectTo = Yii::$app->request->get('redirect', 'view');
        return $this->redirect([$redirectTo, 'id' => $produto->id]);
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
        // 🔍 DEBUG: Log para identificar o problema
        Yii::info('Buscando produto com ID: ' . $id, __METHOD__);
        Yii::info('Usuário logado ID: ' . Yii::$app->user->id, __METHOD__);
        
        if (empty($id)) {
            Yii::error('ID do produto está vazio', __METHOD__);
            throw new NotFoundHttpException('ID do produto não fornecido.');
        }
        
        // Primeiro tenta buscar apenas pelo ID para verificar se existe
        $produto = Produto::findOne($id);
        if (!$produto) {
            Yii::error('Produto não encontrado com ID: ' . $id, __METHOD__);
            throw new NotFoundHttpException('O produto solicitado não existe.');
        }
        
        // Depois verifica se pertence ao usuário
        if ($produto->usuario_id !== Yii::$app->user->id) {
            Yii::error('Produto pertence a outro usuário. Produto usuario_id: ' . $produto->usuario_id . ', Usuário logado: ' . Yii::$app->user->id, __METHOD__);
            throw new NotFoundHttpException('Você não tem permissão para acessar este produto.');
        }
        
        Yii::info('Produto encontrado com sucesso: ' . $produto->nome, __METHOD__);
        return $produto;
    }
}