<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Configuracao;

/**
 * FeedController para geração de feeds XML de produtos
 */
class FeedController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['google-shopping'],
                        'roles' => ['?', '@'], // Público
                    ],
                ],
            ],
        ];
    }

    /**
     * Gera o feed no padrão Google Shopping / Meta (RSS 2.0)
     * URL: /vendas/feed/google-shopping?u=UUID_DO_USUARIO
     */
    public function actionGoogleShopping($u = null)
    {
        if (!$u) {
            // Se não informar o usuário, tenta pegar o configurado globalmente ou o primeiro
            $config = Configuracao::find()->one();
            $u = $config ? $config->usuario_id : null;
        }

        if (!$u) {
            die("Usuário não identificado.");
        }

        $produtos = Produto::find()
            ->where(['usuario_id' => $u, 'ativo' => true])
            ->all();

        $configLoja = Configuracao::findOne(['usuario_id' => $u]);
        $nomeLoja = $configLoja ? $configLoja->nome_loja : 'Minha Loja';

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/xml; charset=utf-8');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>' . htmlspecialchars($nomeLoja) . ' - Catálogo de Produtos</title>' . "\n";
        $xml .= '    <link>' . htmlspecialchars(Yii::$app->request->hostInfo) . '</link>' . "\n";
        $xml .= '    <description>Feed de produtos para Google Shopping e Meta</description>' . "\n";

        foreach ($produtos as $produto) {
            $preco = number_format($produto->preco_venda_sugerido, 2, '.', '') . ' BRL';
            $availability = $produto->estoque_atual > 0 ? 'in stock' : 'out of stock';

            // Link do produto no catálogo público
            $linkProduto = Yii::$app->request->hostInfo . '/catalogo/?u=' . $u . '#/produto/' . $produto->id;

            // Foto principal
            $foto = $produto->getFotoPrincipal();
            $imageLink = $foto ? $foto->getUrl() : '';

            $xml .= '    <item>' . "\n";
            $xml .= '      <g:id>' . htmlspecialchars($produto->id) . '</g:id>' . "\n";
            $xml .= '      <g:title>' . htmlspecialchars($produto->nome) . '</g:title>' . "\n";
            $xml .= '      <g:description>' . htmlspecialchars($produto->descricao ?: $produto->nome) . '</g:description>' . "\n";
            $xml .= '      <g:link>' . htmlspecialchars($linkProduto) . '</g:link>' . "\n";
            $xml .= '      <g:image_link>' . htmlspecialchars($imageLink) . '</g:image_link>' . "\n";
            $xml .= '      <g:condition>new</g:condition>' . "\n";
            $xml .= '      <g:availability>' . $availability . '</g:availability>' . "\n";
            $xml .= '      <g:price>' . $preco . '</g:price>' . "\n";

            if ($produto->emPromocao) {
                $precoPromo = number_format($produto->preco_promocional, 2, '.', '') . ' BRL';
                $xml .= '      <g:sale_price>' . $precoPromo . '</g:sale_price>' . "\n";
            }

            if ($produto->marca) {
                $xml .= '      <g:brand>' . htmlspecialchars($produto->marca) . '</g:brand>' . "\n";
            } else {
                $xml .= '      <g:brand>' . htmlspecialchars($nomeLoja) . '</g:brand>' . "\n";
            }

            if ($produto->codigo_barras) {
                $xml .= '      <g:gtin>' . htmlspecialchars($produto->codigo_barras) . '</g:gtin>' . "\n";
            }

            if ($produto->categoria) {
                $xml .= '      <g:product_type>' . htmlspecialchars($produto->categoria->nome) . '</g:product_type>' . "\n";
            }

            $xml .= '    </item>' . "\n";
        }

        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }
}
