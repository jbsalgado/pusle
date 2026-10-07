<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\Response;
use app\modules\vendas\models\Produto;

/**
 * API Controller para consulta de produtos no módulo Prestanista
 */
class ProdutoApiController extends BaseController
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        $behaviors['authenticator']['optional'] = ['buscar'];
        return $behaviors;
    }

    /**
     * GET /api/produto-api/buscar?q=termo&usuario_id=uuid
     * Busca produtos por nome ou código de referência
     */
    public function actionBuscar()
    {
        $q = Yii::$app->request->get('q');
        $usuarioId = Yii::$app->request->get('usuario_id');

        if (!$usuarioId) {
            Yii::$app->response->statusCode = 400;
            return ['erro' => 'usuario_id é obrigatório'];
        }

        $query = Produto::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true]);

        if (!empty($q)) {
            $buscaTrim = trim(preg_replace('/\s+/', ' ', $q));
            $palavras = array_values(array_filter(explode(' ', $buscaTrim), function ($p) {
                return trim($p) !== '';
            }));

            if (!empty($palavras)) {
                $rankParams = [
                    ':rank_prefix_full' => $buscaTrim . '%',
                    ':rank_word_first' => $palavras[0] . ' %',
                    ':rank_prefix_first' => $palavras[0] . '%',
                    ':rank_contains_full' => '%' . $buscaTrim . '%',
                ];

                foreach ($palavras as $i => $palavra) {
                    $pRaw = trim($palavra);
                    $pSafe = str_replace(['%', '_'], ['\%', '\_'], $pRaw);
                    $pStartParam = ':p_start_' . $i;
                    $pWordParam = ':p_word_' . $i;
                    $pExactParam = ':p_exact_' . $i;

                    $query->andWhere([
                        'or',
                        ['ilike', new \yii\db\Expression('unaccent(nome)'), new \yii\db\Expression("unaccent({$pStartParam})", [$pStartParam => $pSafe . '%'])],
                        ['ilike', new \yii\db\Expression('unaccent(nome)'), new \yii\db\Expression("unaccent({$pWordParam})", [$pWordParam => '% ' . $pSafe . '%'])],
                        ['ilike', new \yii\db\Expression('unaccent(codigo_referencia)'), new \yii\db\Expression("unaccent({$pExactParam})", [$pExactParam => '%' . $pSafe . '%'])],
                        ['ilike', 'codigo_barras', $pRaw],
                    ]);
                }

                $query->orderBy(new \yii\db\Expression("
                    CASE 
                        WHEN unaccent(nome) ILIKE unaccent(:rank_prefix_full) THEN 1
                        WHEN unaccent(nome) ILIKE unaccent(:rank_word_first) THEN 2
                        WHEN unaccent(nome) ILIKE unaccent(:rank_prefix_first) THEN 3
                        WHEN unaccent(nome) ILIKE unaccent(:rank_contains_full) THEN 4
                        ELSE 5
                    END ASC,
                    nome ASC
                ", $rankParams));
            }
        } else {
            $query->orderBy(['nome' => SORT_ASC]);
        }

        $produtos = $query->limit(20)->all();

        return [
            'sucesso' => true,
            'produtos' => array_map(function ($p) {
                return [
                    'id' => $p->id,
                    'nome' => $p->nome,
                    'preco' => $p->preco_venda_sugerido,
                    'codigo' => $p->codigo_referencia
                ];
            }, $produtos)
        ];
    }
}
