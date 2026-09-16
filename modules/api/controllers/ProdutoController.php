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
            'destaques' => ['GET', 'HEAD'],
            'generate-card' => ['POST'],
        ];
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['index', 'view', 'marcas', 'destaques', 'generate-card'];
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

        // Suporte a ordenação dinâmica
        $ordem = \Yii::$app->request->get('ordem');
        $sortConfig = [
            'defaultOrder' => ['nome' => SORT_ASC],
        ];

        if ($ordem === 'random' || $ordem === 'aleatorio') {
            $query->orderBy(new \yii\db\Expression('RANDOM()'));
            $sortConfig = false;
        } elseif ($ordem === 'menor_preco') {
            $query->orderBy(new \yii\db\Expression('COALESCE(NULLIF(prest_produtos.preco_promocional, 0), prest_produtos.preco_venda_sugerido) ASC'));
            $sortConfig = false;
        } elseif ($ordem === 'maior_preco') {
            $query->orderBy(new \yii\db\Expression('COALESCE(NULLIF(prest_produtos.preco_promocional, 0), prest_produtos.preco_venda_sugerido) DESC'));
            $sortConfig = false;
        } elseif ($ordem === 'mais_vendidos') {
            $subVendas = (new \yii\db\Query())
                ->select([new \yii\db\Expression('COALESCE(SUM(vi.quantidade), 0)')])
                ->from('prest_venda_itens vi')
                ->where('vi.produto_id = prest_produtos.id OR vi.produto_id IN (SELECT p_filho.id FROM prest_produtos p_filho WHERE p_filho.parent_id = prest_produtos.id)');
            $query->orderBy([$subVendas => SORT_DESC, 'prest_produtos.nome' => SORT_ASC]);
            $sortConfig = false;
        } elseif ($ordem === 'recentes') {
            $query->orderBy(['prest_produtos.data_criacao' => SORT_DESC]);
            $sortConfig = false;
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => \Yii::$app->request->get('per-page', 24),
            ],
            'sort' => $sortConfig,
        ]);

        return $this->success($dataProvider);
    }

    /**
     * Retorna os produtos em destaque para o slideshow / carrossel hero da loja:
     * 1. Prioridade 1: Produtos em promoção ativa (preco_promocional > 0 e menor que preco_venda_sugerido).
     * 2. Prioridade 2: Se não houver promoções suficientes, produtos mais vendidos da loja.
     * 3. Prioridade 3: Se nova loja sem vendas, produtos ativos do catálogo.
     * GET /api/produto/destaques?usuario_id=xxx
     */
    public function actionDestaques()
    {
        $usuarioId = \Yii::$app->request->get('usuario_id');
        if (!$usuarioId) {
            return $this->success([]);
        }

        // Verifica se o catálogo da loja está ativo
        $catalogoAtivo = (new \yii\db\Query())
            ->select(['COALESCE(lc.catalogo_ativo, pc.catalogo_publico, true)'])
            ->from('prest_usuarios u')
            ->leftJoin('loja_configuracao lc', 'lc.usuario_id = u.id')
            ->leftJoin('prest_configuracoes pc', 'pc.usuario_id = u.id')
            ->where(['u.id' => $usuarioId])
            ->scalar();

        if ($catalogoAtivo === 'f' || $catalogoAtivo === '0' || $catalogoAtivo === 0 || $catalogoAtivo === false) {
            return $this->success([]);
        }

        $destaques = [];
        $idsJaInclusos = [];

        // 1. Prioridade 1: Produtos em promoção ativa
        $produtosPromo = Produto::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId, 'parent_id' => null])
            ->andWhere(['>', 'preco_promocional', 0])
            ->andWhere('preco_promocional < preco_venda_sugerido')
            ->andWhere([
                'OR',
                ['AND', ['IS NOT', 'data_inicio_promocao', null], ['IS NOT', 'data_fim_promocao', null], 'NOW() BETWEEN data_inicio_promocao AND data_fim_promocao'],
                ['AND', ['IS', 'data_inicio_promocao', null], ['IS', 'data_fim_promocao', null]],
                ['AND', ['IS NOT', 'data_inicio_promocao', null], ['IS', 'data_fim_promocao', null], 'NOW() >= data_inicio_promocao'],
                ['AND', ['IS', 'data_inicio_promocao', null], ['IS NOT', 'data_fim_promocao', null], 'NOW() <= data_fim_promocao'],
            ])
            ->with(['fotos', 'categoria'])
            ->limit(6)
            ->all();

        foreach ($produtosPromo as $prod) {
            $destaques[] = $this->formatarItemDestaque($prod, 'PROMOÇÃO', '🔥 Super Oferta');
            $idsJaInclusos[] = $prod->id;
        }

        // 2. Prioridade 2: Se menos de 4 produtos, buscar mais vendidos da loja
        if (count($destaques) < 4) {
            $exprMestre = new \yii\db\Expression('COALESCE(p.parent_id, p.id)');
            $queryMaisVendidos = (new \yii\db\Query())
                ->select(['produto_mestre_id' => $exprMestre, 'total_vendido' => new \yii\db\Expression('SUM(vi.quantidade)')])
                ->from('prest_venda_itens vi')
                ->innerJoin('prest_vendas v', 'v.id = vi.venda_id')
                ->innerJoin('prest_produtos p', 'p.id = vi.produto_id')
                ->where(['v.usuario_id' => $usuarioId, 'p.ativo' => true])
                ->groupBy($exprMestre)
                ->orderBy(['total_vendido' => SORT_DESC])
                ->limit(10);

            if (!empty($idsJaInclusos)) {
                $queryMaisVendidos->andWhere(['NOT IN', $exprMestre, $idsJaInclusos]);
            }

            $maisVendidosRows = $queryMaisVendidos->all();
            $idsMaisVendidos = array_column($maisVendidosRows, 'produto_mestre_id');

            if (!empty($idsMaisVendidos)) {
                $produtosMaisVendidos = Produto::find()
                    ->where(['id' => $idsMaisVendidos, 'ativo' => true, 'usuario_id' => $usuarioId, 'parent_id' => null])
                    ->with(['fotos', 'categoria'])
                    ->all();

                $indexados = [];
                foreach ($produtosMaisVendidos as $pmv) {
                    $indexados[$pmv->id] = $pmv;
                }

                foreach ($idsMaisVendidos as $idMv) {
                    if (isset($indexados[$idMv]) && count($destaques) < 6) {
                        $destaques[] = $this->formatarItemDestaque($indexados[$idMv], 'MAIS VENDIDO', '⭐ Mais Vendido');
                        $idsJaInclusos[] = $idMv;
                    }
                }
            }
        }

        // 3. Prioridade 3: Se ainda menos de 3 produtos, buscar produtos recentes/ativos da loja
        if (count($destaques) < 4) {
            $limiteFaltante = 6 - count($destaques);
            $queryFallback = Produto::find()
                ->where(['ativo' => true, 'usuario_id' => $usuarioId, 'parent_id' => null])
                ->with(['fotos', 'categoria'])
                ->orderBy(['data_criacao' => SORT_DESC])
                ->limit($limiteFaltante);

            if (!empty($idsJaInclusos)) {
                $queryFallback->andWhere(['NOT IN', 'id', $idsJaInclusos]);
            }

            $fallbacks = $queryFallback->all();
            foreach ($fallbacks as $fb) {
                $destaques[] = $this->formatarItemDestaque($fb, 'DESTAQUE', '✨ Destaque da Loja');
                $idsJaInclusos[] = $fb->id;
            }
        }

        return $this->success($destaques);
    }

    /**
     * Formata um produto para exibição enriquecida no slideshow hero
     */
    private function formatarItemDestaque($produto, $tipoTag, $badgeTexto)
    {
        $precoOriginal = (float)$produto->preco_venda_sugerido;
        $precoFinal = (float)$produto->precoFinal ?: $precoOriginal;
        
        $descontoPercentual = 0;
        if ($precoOriginal > 0 && $precoFinal < $precoOriginal) {
            $descontoPercentual = (int)round((($precoOriginal - $precoFinal) / $precoOriginal) * 100);
        }

        // Resolução de imagem principal
        $imagemUrl = null;
        if (!empty($produto->fotos)) {
            $fotoPrincipal = null;
            foreach ($produto->fotos as $foto) {
                if ($foto->eh_principal) {
                    $fotoPrincipal = $foto;
                    break;
                }
            }
            if (!$fotoPrincipal && isset($produto->fotos[0])) {
                $fotoPrincipal = $produto->fotos[0];
            }
            if ($fotoPrincipal) {
                $imagemUrl = $fotoPrincipal->getUrlCompleta();
            }
        }

        return [
            'id' => $produto->id,
            'nome' => $produto->nome,
            'descricao' => $produto->descricao,
            'codigo_referencia' => $produto->codigo_referencia,
            'categoria_nome' => $produto->categoria ? $produto->categoria->nome : null,
            'preco_original' => $precoOriginal,
            'preco_promocional' => (float)$produto->preco_promocional,
            'preco_final' => $precoFinal,
            'desconto_percentual' => $descontoPercentual,
            'tag_destaque' => $tipoTag,
            'badge_texto' => $badgeTexto,
            'imagem_destaque' => $imagemUrl,
            'possui_grade' => (bool)$produto->possuiGrade,
            'unidade_medida' => $produto->unidade_medida,
            'estoque_atual' => (int)$produto->estoque_atual,
            'fotos' => $produto->fotos,
        ];
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
