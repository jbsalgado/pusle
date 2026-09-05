<?php

namespace app\modules\api\controllers;

use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use app\modules\vendas\models\Produto;
use yii\web\Response;
use yii\web\BadRequestHttpException;

class ProdutoController extends BaseController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'view' => ['GET', 'HEAD'],
            'marcas' => ['GET', 'HEAD'],
            'generate-card' => ['POST'],
        ];
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['index', 'view', 'marcas', 'generate-card'];
        return $behaviors;
    }

    /**
     * Lista todos os produtos ativos para o catálogo.
     * GET /api/produto?usuario_id=xxx
     * 
     * REQUER usuario_id obrigatório para multi-tenancy
     */
    public function actionIndex()
    {
        // Pega o usuario_id da query string
        $usuarioId = \Yii::$app->request->get('usuario_id');

        // Se não informar usuario_id, retorna vazio (segurança multi-tenancy)
        if (!$usuarioId) {
            \Yii::warning("Tentativa de acessar produtos sem usuario_id - bloqueado", 'api');

            // Retorna ActiveDataProvider vazio
            return new ActiveDataProvider([
                'query' => Produto::find()->where('1=0'), // Query que nunca retorna resultados
                'pagination' => false,
            ]);

            // OU pode retornar erro 400:
            // throw new BadRequestHttpException('O parâmetro usuario_id é obrigatório');
        }

        \Yii::info("Filtrando produtos por usuario_id: {$usuarioId}", 'api');

        // Verifica se o catálogo da loja está ativo para o público externo
        $catalogoAtivo = (new \yii\db\Query())
            ->select(['COALESCE(lc.catalogo_ativo, pc.catalogo_publico, true)'])
            ->from('prest_usuarios u')
            ->leftJoin('loja_configuracao lc', 'lc.usuario_id = u.id')
            ->leftJoin('prest_configuracoes pc', 'pc.usuario_id = u.id')
            ->where(['u.id' => $usuarioId])
            ->scalar();

        if ($catalogoAtivo === 'f' || $catalogoAtivo === '0' || $catalogoAtivo === 0 || $catalogoAtivo === false) {
            \Yii::info("Catálogo desativado (em implantação) para usuario_id: {$usuarioId}", 'api');
            return $this->success(new ActiveDataProvider([
                'query' => Produto::find()->where('1=0'),
                'pagination' => false,
            ]));
        }

        $query = Produto::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->andWhere(['parent_id' => null]) // ✅ Shopee Style: Apenas Mestres na Vitrine
            ->with(['fotos', 'categoria']);

        // Filtro por Categoria
        $categoriaId = \Yii::$app->request->get('categoria_id');
        if ($categoriaId) {
            $query->andWhere(['categoria_id' => $categoriaId]);
        }

        // Filtro por Marca (busca parcial insensível a acentos/caixa)
        $marca = \Yii::$app->request->get('marca');
        if ($marca && trim($marca) !== '') {
            $termoMarca = '%' . trim($marca) . '%';
            $query->andWhere(['ilike', new \yii\db\Expression('unaccent(marca)'), new \yii\db\Expression('unaccent(:m)', [':m' => $termoMarca])]);
        }

        // Suporte a busca inteligente por palavras (Busca no Mestre OU nos Filhos)
        $busca = \Yii::$app->request->get('q') ?: \Yii::$app->request->get('busca');
        if ($busca && trim($busca) !== '') {
            $palavras = explode(' ', trim($busca));
            foreach ($palavras as $palavra) {
                if (trim($palavra) === '') continue;
                
                $termo = '%' . trim($palavra) . '%';

                // Busca no Mestre OU em qualquer um de seus Filhos
                $query->andWhere([
                    'OR',
                    ['ilike', new \yii\db\Expression('unaccent(nome)'), new \yii\db\Expression('unaccent(:p)', [':p' => $termo])],
                    ['ilike', new \yii\db\Expression('unaccent(codigo_referencia)'), $termo],
                    ['ilike', 'codigo_barras', $termo],
                    ['exists', (new \yii\db\Query())
                        ->select(new \yii\db\Expression('1'))
                        ->from('prest_produtos child')
                        ->where('child.parent_id = prest_produtos.id')
                        ->andWhere([
                            'OR',
                            ['ilike', new \yii\db\Expression('unaccent(child.nome)'), new \yii\db\Expression('unaccent(:p)', [':p' => $termo])],
                            ['ilike', new \yii\db\Expression('unaccent(child.cor)'), new \yii\db\Expression('unaccent(:p)', [':p' => $termo])],
                            ['ilike', new \yii\db\Expression('unaccent(child.tamanho)'), new \yii\db\Expression('unaccent(:p)', [':p' => $termo])],
                            ['ilike', new \yii\db\Expression('unaccent(child.codigo_referencia)'), $termo],
                            ['ilike', 'child.codigo_barras', $termo]
                        ])
                    ]
                ]);
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => \Yii::$app->request->get('per-page', 20),
            ],
            'sort' => [
                'defaultOrder' => ['nome' => SORT_ASC]
            ],
        ]);

        return $this->success($dataProvider);
    }

    /**
     * Vê um produto específico.
     * GET /api/produto/123
     */
    public function actionView($id)
    {
        $model = Produto::find()
            ->where(['id' => $id, 'ativo' => true])
            ->with(['fotos', 'categoria', 'variacoes'])
            ->one();

        if ($model === null) {
            // Suporte para resolução direta de variante da Matriz
            $variante = \app\modules\vendas\models\ProdutoVariante::find()
                ->where(['id' => $id, 'ativo' => true])
                ->one();

            if ($variante !== null) {
                return $this->success($variante);
            }

            throw new \yii\web\NotFoundHttpException("Produto não encontrado.");
        }

        return $this->success($model);
    }

    /**
     * Retorna todas as marcas únicas dos produtos ativos do usuário.
     * GET /api/produto/marcas?usuario_id=xxx
     */
    public function actionMarcas()
    {
        $usuarioId = \Yii::$app->request->get('usuario_id');
        if (!$usuarioId) {
            return $this->success([]);
        }

        $marcas = Produto::find()
            ->select(['marca'])
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->andWhere(['is not', 'marca', null])
            ->andWhere(['!=', 'marca', ''])
            ->distinct()
            ->orderBy(['marca' => SORT_ASC])
            ->column();

        return $this->success($marcas);
    }

    /**
     * Geração automatizada de card profissional de produto.
     * POST /api/produto/<id>/generate-card
     * POST /api/v1/products/<id>/generate-card
     */
    public function actionGenerateCard($id)
    {
        $request = \Yii::$app->request;
        $formato = $request->post('formato') ?: $request->get('formato', 'feed');
        $options = [
            'template' => $request->post('template') ?: $request->get('template', 'modern_dark'),
            'corTema' => $request->post('cor_tema') ?: $request->post('corTema') ?: $request->get('cor_tema', 'dark'),
            'fundoEstilo' => $request->post('fundo_estilo') ?: $request->post('fundoEstilo') ?: $request->get('fundo_estilo', 'gradient'),
            'imagemFundo' => $request->post('imagem_fundo') ?: $request->post('imagemFundo') ?: $request->get('imagem_fundo', null),
        ];

        try {
            $service = new \app\modules\vendas\services\CardGeneratorService();
            $card = $service->gerarCard($id, $formato, $options);

            return $this->success([
                'card_id' => $card->id,
                'produto_id' => $card->produto_id,
                'formato' => $card->formato,
                'card_url' => $card->getUrlCompleta(),
                'card_path' => $card->card_path,
                'metadata' => $card->metadata
            ], 'Card gerado com sucesso.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
