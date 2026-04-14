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
        ];
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['index', 'view'];
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

        $query = Produto::find()
            ->where(['ativo' => true, 'usuario_id' => $usuarioId])
            ->andWhere(['parent_id' => null]) // ✅ Shopee Style: Apenas Mestres na Vitrine
            ->with(['fotos', 'categoria']);

        // Filtro por Categoria
        $categoriaId = \Yii::$app->request->get('categoria_id');
        if ($categoriaId) {
            $query->andWhere(['categoria_id' => $categoriaId]);
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
            throw new \yii\web\NotFoundHttpException("Produto não encontrado.");
        }

        return $this->success($model);
    }
}
