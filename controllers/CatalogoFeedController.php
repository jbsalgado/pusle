<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\models\Usuario;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\LojaConfiguracao;

/**
 * CatalogoFeedController - Gera feeds automatizados de produtos para canais externos
 * 
 * Suporta:
 * - Meta Commerce Manager (Facebook & Instagram Shopping) no padrão Google Merchant XML (RSS 2.0)
 * - Parâmetros opcionais: ?loja={usuario_id} ou detecção automática pelo domínio
 */
class CatalogoFeedController extends Controller
{
    /**
     * Desativa proteção CSRF para chamadas de crawlers do Meta / Google
     */
    public $enableCsrfValidation = false;

    /**
     * Gera o Feed XML de Produtos no formato Google Shopping / Meta Catalog (RSS 2.0).
     * 
     * URL: /catalogo/meta-feed.xml?loja={loja_id}
     * 
     * @param string|null $loja ID do usuário/lojista (UUID)
     * @return string Conteúdo XML
     */
    public function actionMetaXml($loja = null)
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/xml; charset=utf-8');
        Yii::$app->response->headers->set('Cache-Control', 'public, max-age=3600');

        $lojaId = $loja ?: Yii::$app->request->get('loja_id') ?: Yii::$app->request->get('usuario_id');

        if (!$lojaId) {
            // Busca o primeiro lojista com produtos ativos como fallback
            $primeiroProduto = Produto::find()->where(['ativo' => true])->one();
            if ($primeiroProduto) {
                $lojaId = $primeiroProduto->usuario_id;
            }
        }

        $usuarioLoja = null;
        if ($lojaId) {
            $usuarioLoja = Usuario::findOne($lojaId);
        }

        $nomeLoja = $usuarioLoja ? ($usuarioLoja->nome_loja ?: $usuarioLoja->nome) : 'Pulse Catálogo';
        $host = (Yii::$app->request->hasMethod('getHostInfo') ? Yii::$app->request->hostInfo : '') ?: (Yii::$app->params['base_url'] ?? 'https://catalogos.oncode.app.br');
        $baseUrl = rtrim(rtrim($host, '/') . '/' . ltrim(Yii::$app->request->baseUrl, '/'), '/');

        // Busca produtos ativos com fotos
        $produtosQuery = Produto::find()
            ->where(['ativo' => true])
            ->with(['fotos', 'categoria'])
            ->orderBy(['nome' => SORT_ASC]);

        if ($lojaId) {
            $produtosQuery->andWhere(['usuario_id' => $lojaId]);
        }

        $produtos = $produtosQuery->all();

        // Montagem do XML
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

        $xml->startElement('channel');
        $xml->writeElement('title', htmlspecialchars($nomeLoja, ENT_XML1, 'UTF-8'));
        $xml->writeElement('link', htmlspecialchars($baseUrl, ENT_XML1, 'UTF-8'));
        $xml->writeElement('description', 'Catálogo Oficial de Produtos para Meta Commerce Manager e Instagram Shopping');

        foreach ($produtos as $p) {
            $preco = (float)($p->getPrecoFinal() ?: $p->preco_venda_sugerido);
            if ($preco <= 0) {
                continue; // Meta rejeita produtos sem preço válido
            }

            // Foto principal
            $fotoUrl = '';
            $fotoPrincipal = $p->getFotoPrincipal();
            if ($fotoPrincipal && !empty($fotoPrincipal->arquivo_path)) {
                $fotoUrl = $baseUrl . '/' . ltrim($fotoPrincipal->arquivo_path, '/');
            } elseif (!empty($p->fotos) && isset($p->fotos[0]->arquivo_path)) {
                $fotoUrl = $baseUrl . '/' . ltrim($p->fotos[0]->arquivo_path, '/');
            }

            if (empty($fotoUrl)) {
                // Meta exige imagem para produtos de catálogo
                continue;
            }

            // Link do produto no Catálogo PWA ou Hub
            $productUrl = $baseUrl . '/catalogo/?loja=' . $p->usuario_id . '#produto-' . $p->id;

            $estoque = (float)($p->estoque_atual ?? 0);
            $disponibilidade = ($estoque > 0) ? 'in stock' : 'out of stock';

            $xml->startElement('item');
            $xml->writeElement('g:id', (string)$p->id);
            $xml->writeElement('g:title', htmlspecialchars($p->nome, ENT_XML1, 'UTF-8'));
            
            $descricao = !empty($p->descricao) ? $p->descricao : $p->nome . ' - Disponível para compra online.';
            $xml->writeElement('g:description', htmlspecialchars(strip_tags($descricao), ENT_XML1, 'UTF-8'));
            
            $xml->writeElement('g:link', htmlspecialchars($productUrl, ENT_XML1, 'UTF-8'));
            $xml->writeElement('g:image_link', htmlspecialchars($fotoUrl, ENT_XML1, 'UTF-8'));
            $xml->writeElement('g:brand', htmlspecialchars($p->marca ?: $nomeLoja, ENT_XML1, 'UTF-8'));
            $xml->writeElement('g:condition', 'new');
            $xml->writeElement('g:availability', $disponibilidade);
            $xml->writeElement('g:price', number_format($preco, 2, '.', '') . ' BRL');

            if (!empty($p->categoria) && !empty($p->categoria->nome)) {
                $xml->writeElement('g:product_type', htmlspecialchars($p->categoria->nome, ENT_XML1, 'UTF-8'));
            }

            if (!empty($p->codigo_barras)) {
                $xml->writeElement('g:gtin', htmlspecialchars($p->codigo_barras, ENT_XML1, 'UTF-8'));
            }

            $xml->endElement(); // item
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
