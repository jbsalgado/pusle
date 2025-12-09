<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\DadosFinanceiros;
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
        // Carrega o produto com fotos e categoria usando eager loading
        $model = Produto::find()
            ->where(['id' => $id, 'usuario_id' => Yii::$app->user->id])
            ->with(['fotos', 'categoria'])
            ->one();
        
        if (!$model) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionCreate()
    {
        $model = new Produto();
        $model->usuario_id = Yii::$app->user->id;
        $model->ativo = true;

        // Carrega configuração financeira global
        $dadosFinanceiros = DadosFinanceiros::getConfiguracaoGlobal($model->usuario_id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            // Salva dados financeiros se foram informados
            $postDadosFinanceiros = Yii::$app->request->post('DadosFinanceiros', []);
            if (!empty($postDadosFinanceiros['taxa_fixa_percentual']) || 
                !empty($postDadosFinanceiros['taxa_variavel_percentual']) || 
                !empty($postDadosFinanceiros['lucro_liquido_percentual'])) {
                
                // Verifica se deve criar configuração específica ou usar global
                $usarConfiguracaoEspecifica = !empty($postDadosFinanceiros['usar_configuracao_especifica']);
                
                if ($usarConfiguracaoEspecifica) {
                    // Cria ou atualiza configuração específica do produto
                    $dadosFinanceirosProduto = DadosFinanceiros::find()
                        ->where(['produto_id' => $model->id, 'usuario_id' => $model->usuario_id])
                        ->one();
                    
                    if (!$dadosFinanceirosProduto) {
                        $dadosFinanceirosProduto = new DadosFinanceiros();
                        $dadosFinanceirosProduto->usuario_id = $model->usuario_id;
                        $dadosFinanceirosProduto->produto_id = $model->id;
                    }
                    
                    $dadosFinanceirosProduto->taxa_fixa_percentual = $postDadosFinanceiros['taxa_fixa_percentual'] ?? 0;
                    $dadosFinanceirosProduto->taxa_variavel_percentual = $postDadosFinanceiros['taxa_variavel_percentual'] ?? 0;
                    $dadosFinanceirosProduto->lucro_liquido_percentual = $postDadosFinanceiros['lucro_liquido_percentual'] ?? 0;
                    $dadosFinanceirosProduto->save();
                }
            }
            
            // Upload de fotos (sempre executa, mesmo se houver erros anteriores)
            try {
                $this->processUploadFotos($model);
            } catch (\Exception $e) {
                Yii::error('Erro ao processar upload de fotos: ' . $e->getMessage(), __METHOD__);
                Yii::error('Stack trace: ' . $e->getTraceAsString(), __METHOD__);
                // Não interrompe o fluxo, apenas loga o erro
            }
            
            Yii::$app->session->setFlash('success', 'Produto cadastrado com sucesso!');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'dadosFinanceiros' => $dadosFinanceiros,
        ]);
    }
    
    /**
     * Gera código de referência sugerido baseado na categoria
     */
    public function actionGerarCodigoReferencia()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $categoriaId = Yii::$app->request->get('categoria_id');
        $usuarioId = Yii::$app->user->id;
        
        if (!$categoriaId) {
            return ['success' => false, 'message' => 'Categoria não informada'];
        }
        
        $codigo = Produto::gerarCodigoReferencia($categoriaId, $usuarioId);
        
        return [
            'success' => true,
            'codigo' => $codigo
        ];
    }
    
    /**
     * Verifica se o código de referência já existe (para validação em tempo real)
     */
    public function actionVerificarCodigoReferencia()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $codigo = Yii::$app->request->get('codigo');
        $produtoId = Yii::$app->request->get('produto_id'); // Para edição, excluir o próprio produto
        $usuarioId = Yii::$app->user->id;
        
        if (empty($codigo)) {
            return [
                'success' => true,
                'disponivel' => true,
                'message' => ''
            ];
        }
        
        $query = Produto::find()
            ->where(['usuario_id' => $usuarioId, 'codigo_referencia' => $codigo]);
        
        // Se estiver editando, exclui o próprio produto da verificação
        if ($produtoId) {
            $query->andWhere(['!=', 'id', $produtoId]);
        }
        
        $existe = $query->exists();
        
        return [
            'success' => true,
            'disponivel' => !$existe,
            'message' => $existe ? 'Este código de referência já está em uso. Escolha outro.' : 'Código disponível.'
        ];
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
                // Salva dados financeiros se foram informados
                $postDadosFinanceiros = Yii::$app->request->post('DadosFinanceiros', []);
                if (!empty($postDadosFinanceiros['taxa_fixa_percentual']) || 
                    !empty($postDadosFinanceiros['taxa_variavel_percentual']) || 
                    !empty($postDadosFinanceiros['lucro_liquido_percentual'])) {
                    
                    $usarConfiguracaoEspecifica = !empty($postDadosFinanceiros['usar_configuracao_especifica']);
                    
                    if ($usarConfiguracaoEspecifica) {
                        $dadosFinanceirosProduto = DadosFinanceiros::find()
                            ->where(['produto_id' => $model->id, 'usuario_id' => $model->usuario_id])
                            ->one();
                        
                        if (!$dadosFinanceirosProduto) {
                            $dadosFinanceirosProduto = new DadosFinanceiros();
                            $dadosFinanceirosProduto->usuario_id = $model->usuario_id;
                            $dadosFinanceirosProduto->produto_id = $model->id;
                        }
                        
                        $dadosFinanceirosProduto->taxa_fixa_percentual = $postDadosFinanceiros['taxa_fixa_percentual'] ?? 0;
                        $dadosFinanceirosProduto->taxa_variavel_percentual = $postDadosFinanceiros['taxa_variavel_percentual'] ?? 0;
                        $dadosFinanceirosProduto->lucro_liquido_percentual = $postDadosFinanceiros['lucro_liquido_percentual'] ?? 0;
                        $dadosFinanceirosProduto->save();
                    }
                }
                
                Yii::info('Produto salvo com sucesso. Estoque final: ' . $model->estoque_atual, __METHOD__);
                // Upload de fotos (sempre executa, mesmo se houver erros anteriores)
                try {
                    $this->processUploadFotos($model);
                } catch (\Exception $e) {
                    Yii::error('Erro ao processar upload de fotos: ' . $e->getMessage(), __METHOD__);
                    Yii::error('Stack trace: ' . $e->getTraceAsString(), __METHOD__);
                    // Não interrompe o fluxo, apenas loga o erro
                }
                
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

        // Carrega configuração financeira (específica ou global)
        $dadosFinanceiros = DadosFinanceiros::getConfiguracaoParaProduto($model->id, $model->usuario_id);

        return $this->render('update', [
            'model' => $model,
            'dadosFinanceiros' => $dadosFinanceiros,
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
        
        // 🔍 DEBUG: Log do caminho de upload
        Yii::info('Caminho de upload: ' . $uploadPath, __METHOD__);
        Yii::info('@webroot resolve para: ' . Yii::getAlias('@webroot'), __METHOD__);
        
        if (!is_dir($uploadPath)) {
            $created = @mkdir($uploadPath, 0755, true);
            if (!$created) {
                Yii::error('Erro ao criar diretório: ' . $uploadPath, __METHOD__);
                $parentDir = dirname($uploadPath);
                if (is_dir($parentDir)) {
                    Yii::error('Permissões do diretório pai: ' . substr(sprintf('%o', fileperms($parentDir)), -4), __METHOD__);
                }
                return; // Não continua se não conseguir criar o diretório
            } else {
                Yii::info('Diretório criado com sucesso: ' . $uploadPath, __METHOD__);
            }
        } else {
            Yii::info('Diretório já existe: ' . $uploadPath, __METHOD__);
        }
        
        // Verifica se o diretório é gravável
        if (!is_writable($uploadPath)) {
            Yii::error('Diretório não é gravável: ' . $uploadPath, __METHOD__);
            Yii::error('Permissões atuais: ' . substr(sprintf('%o', fileperms($uploadPath)), -4), __METHOD__);
            // Tenta corrigir as permissões
            @chmod($uploadPath, 0755);
            if (!is_writable($uploadPath)) {
                Yii::error('Não foi possível tornar o diretório gravável mesmo após chmod', __METHOD__);
                return;
            }
        }

        $files = UploadedFile::getInstancesByName('fotos');
        
        // 🔍 DEBUG: Log para verificar se as fotos estão sendo recebidas
        Yii::info('Processando upload de fotos para produto: ' . $model->id, __METHOD__);
        Yii::info('Número de arquivos recebidos: ' . ($files ? count($files) : 0), __METHOD__);
        
        if ($files && count($files) > 0) {
            Yii::info('Arquivos recebidos: ' . json_encode(array_map(function($f) { return $f->name; }, $files)), __METHOD__);
            $ordem = ProdutoFoto::find()->where(['produto_id' => $model->id])->count();
            
            foreach ($files as $file) {
                $tempPath = $file->tempName;
                $filename = uniqid() . '.jpg'; // Sempre salva como JPG otimizado
                $filePath = $uploadPath . '/' . $filename;
                
                // Otimiza a imagem antes de salvar
                if ($this->optimizeImage($tempPath, $filePath)) {
                    $foto = new ProdutoFoto();
                    $foto->produto_id = $model->id;
                    $foto->arquivo_nome = $file->name;
                    $foto->arquivo_path = 'uploads/produtos/' . $model->id . '/' . $filename;
                    $foto->ordem = $ordem++;
                    
                    // Se for a primeira foto, marcar como principal
                    if (ProdutoFoto::find()->where(['produto_id' => $model->id])->count() == 0) {
                        $foto->eh_principal = true;
                    }
                    
                    // 🔍 DEBUG: Log antes de salvar
                    Yii::info('Tentando salvar foto: ' . json_encode([
                        'produto_id' => $foto->produto_id,
                        'arquivo_nome' => $foto->arquivo_nome,
                        'arquivo_path' => $foto->arquivo_path,
                        'ordem' => $foto->ordem,
                        'eh_principal' => $foto->eh_principal,
                    ]), __METHOD__);
                    
                    if ($foto->save()) {
                        Yii::info('Foto salva com sucesso. ID: ' . $foto->id, __METHOD__);
                    } else {
                        Yii::error('Erro ao salvar foto no banco: ' . json_encode($foto->errors), __METHOD__);
                        Yii::error('Dados da foto: ' . json_encode($foto->attributes), __METHOD__);
                    }
                } else {
                    Yii::error('Erro ao otimizar imagem: ' . $file->name, __METHOD__);
                    Yii::error('Caminho temporário: ' . $tempPath, __METHOD__);
                    Yii::error('Caminho destino: ' . $filePath, __METHOD__);
                    Yii::error('Diretório existe: ' . (is_dir($uploadPath) ? 'sim' : 'não'), __METHOD__);
                    Yii::error('Diretório é gravável: ' . (is_writable($uploadPath) ? 'sim' : 'não'), __METHOD__);
                }
            }
        }
    }
    
    /**
     * Otimiza imagem: redimensiona e comprime para tamanho entre 50-200KB
     * 
     * @param string $sourcePath Caminho da imagem original
     * @param string $destinationPath Caminho onde salvar a imagem otimizada
     * @param int $maxWidth Largura máxima (padrão: 1920)
     * @param int $maxHeight Altura máxima (padrão: 1920)
     * @param int $minSizeKB Tamanho mínimo em KB (padrão: 50)
     * @param int $maxSizeKB Tamanho máximo em KB (padrão: 200)
     * @return bool True se sucesso, False se erro
     */
    protected function optimizeImage($sourcePath, $destinationPath, $maxWidth = 1920, $maxHeight = 1920, $minSizeKB = 50, $maxSizeKB = 200)
    {
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        // Detecta o tipo da imagem
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }
        
        $mimeType = $imageInfo['mime'];
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        
        // Cria imagem resource baseado no tipo
        // Usa namespace global explícito para evitar problemas
        switch ($mimeType) {
            case 'image/jpeg':
                if (!function_exists('imagecreatefromjpeg')) {
                    Yii::error('Função imagecreatefromjpeg não está disponível. Verifique se a extensão GD está instalada.', __METHOD__);
                    return false;
                }
                $sourceImage = @\imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                if (!function_exists('imagecreatefrompng')) {
                    Yii::error('Função imagecreatefrompng não está disponível. Verifique se a extensão GD está instalada.', __METHOD__);
                    return false;
                }
                $sourceImage = @\imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                if (!function_exists('imagecreatefromgif')) {
                    Yii::error('Função imagecreatefromgif não está disponível. Verifique se a extensão GD está instalada.', __METHOD__);
                    return false;
                }
                $sourceImage = @\imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (!function_exists('imagecreatefromwebp')) {
                    Yii::error('Função imagecreatefromwebp não está disponível. Verifique se a extensão GD com suporte WebP está instalada.', __METHOD__);
                    return false;
                }
                $sourceImage = @\imagecreatefromwebp($sourcePath);
                break;
            default:
                Yii::warning("Tipo de imagem não suportado: {$mimeType}", __METHOD__);
                return false;
        }
        
        if ($sourceImage === false) {
            return false;
        }
        
        // Calcula novas dimensões mantendo proporção
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        // Se a imagem já é menor que o máximo, mantém o tamanho original
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }
        
        // Cria nova imagem redimensionada
        if (!function_exists('imagecreatetruecolor')) {
            Yii::error('Função imagecreatetruecolor não está disponível. Verifique se a extensão GD está instalada.', __METHOD__);
            \imagedestroy($sourceImage);
            return false;
        }
        $newImage = \imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserva transparência para PNG
        if ($mimeType === 'image/png') {
            \imagealphablending($newImage, false);
            \imagesavealpha($newImage, true);
            $transparent = \imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            \imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Redimensiona a imagem
        \imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Libera memória da imagem original
        \imagedestroy($sourceImage);
        
        // Tenta diferentes qualidades para atingir o tamanho desejado
        $quality = 85;
        $attempts = 0;
        $maxAttempts = 10;
        
        do {
            // Salva como JPEG (sempre converte para JPEG para melhor compressão)
            if (!function_exists('imagejpeg')) {
                Yii::error('Função imagejpeg não está disponível. Verifique se a extensão GD está instalada.', __METHOD__);
                \imagedestroy($newImage);
                return false;
            }
            $success = @\imagejpeg($newImage, $destinationPath, $quality);
            
            if ($success) {
                $fileSize = filesize($destinationPath);
                $sizeKB = $fileSize / 1024;
                
                // Se está dentro do range desejado, sucesso
                if ($sizeKB >= $minSizeKB && $sizeKB <= $maxSizeKB) {
                    \imagedestroy($newImage);
                    return true;
                }
                
                // Se está muito grande, reduz qualidade
                if ($sizeKB > $maxSizeKB && $quality > 30) {
                    $quality = max(30, $quality - 10);
                }
                // Se está muito pequena, aumenta qualidade (mas não muito)
                elseif ($sizeKB < $minSizeKB && $quality < 95) {
                    $quality = min(95, $quality + 5);
                } else {
                    // Aceita o resultado atual se não conseguir ajustar mais
                    \imagedestroy($newImage);
                    return true;
                }
            } else {
                \imagedestroy($newImage);
                return false;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);
        
        \imagedestroy($newImage);
        return true;
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