<?php
/**
 * VendaDiretaController - Controller para proteger acesso à PWA de Venda Direta
 * Localização: app/controllers/VendaDiretaController.php
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\modules\vendas\models\Colaborador;

class VendaDiretaController extends Controller
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
                        'roles' => ['@'], // Apenas usuários autenticados
                    ],
                ],
            ],
        ];
    }

    /**
     * Serve a página da PWA de Venda Direta
     * Verifica autenticação e serve o HTML estático
     */
    public function actionIndex()
    {
        // Se chegou aqui, o usuário está autenticado (graças ao AccessControl)
        // Lê o arquivo HTML estático e retorna
        $basePath = Yii::getAlias('@webroot');
        $htmlPath = $basePath . '/venda-direta/index.html';
        
        if (!file_exists($htmlPath)) {
            throw new \yii\web\NotFoundHttpException('Página não encontrada');
        }
        
        $html = file_get_contents($htmlPath);
        
        // Obtém a URL base do web de forma mais robusta
        // IMPORTANTE: Arquivos estáticos devem ser acessados diretamente, sem passar pelo index.php
        $request = Yii::$app->request;
        
        // Tenta obter baseUrl do request primeiro
        $baseUrl = $request->baseUrl;
        
        // Remove /index.php do baseUrl se existir em qualquer lugar (arquivos estáticos não passam pelo index.php)
        // Remove tanto do final quanto do meio do caminho
        $baseUrl = preg_replace('#/index\.php(/|$)#', '/', $baseUrl);
        $baseUrl = rtrim($baseUrl, '/');
        
        // Se baseUrl estiver vazio, tenta usar @web alias
        if (empty($baseUrl)) {
            $webAlias = Yii::getAlias('@web');
            if (!empty($webAlias) && $webAlias !== '@web') {
                // Remove /index.php do alias também se existir
                $baseUrl = preg_replace('#/index\.php(/|$)#', '/', $webAlias);
                $baseUrl = rtrim($baseUrl, '/');
            } else {
                // Fallback: constrói a partir da URL absoluta atual
                $absoluteUrl = $request->absoluteUrl;
                $urlInfo = parse_url($absoluteUrl);
                if (isset($urlInfo['path'])) {
                    // Remove 'venda-direta' ou 'venda-direta/index' do path
                    $path = preg_replace('#/venda-direta(/index)?$#', '', $urlInfo['path']);
                    // Remove também /index.php se existir em qualquer lugar
                    $path = preg_replace('#/index\.php(/|$)#', '/', $path);
                    $baseUrl = rtrim($path, '/');
                } else {
                    // Último fallback: usa o script name e remove index.php
                    $scriptUrl = $request->scriptUrl;
                    if (!empty($scriptUrl)) {
                        $baseUrl = dirname($scriptUrl);
                        // Remove /index.php se existir em qualquer lugar
                        $baseUrl = preg_replace('#/index\.php(/|$)#', '/', $baseUrl);
                        $baseUrl = rtrim($baseUrl, '/');
                    }
                }
            }
        }
        
        // Garante que o caminho comece com / e não termine com /
        $baseUrl = '/' . ltrim(rtrim($baseUrl, '/'), '/');
        $vendaDiretaPath = $baseUrl . '/venda-direta';
        
        // Log para debug (remover em produção se necessário)
        Yii::debug("Base URL: {$baseUrl}, Venda Direta Path: {$vendaDiretaPath}", __METHOD__);
        
        // Corrige os caminhos relativos para caminhos absolutos
        $html = $this->corrigirCaminhosRecursos($html, $vendaDiretaPath);
        
        // Retorna o HTML com headers apropriados
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html; charset=utf-8');
        
        return $html;
    }
    
    /**
     * Corrige caminhos relativos de recursos no HTML
     */
    private function corrigirCaminhosRecursos($html, $basePath)
    {
        // Garante que o caminho comece com / e não termine com /
        $basePath = '/' . ltrim(rtrim($basePath, '/'), '/');
        
        // Remove o script problemático do QRCode (não é necessário)
        $html = preg_replace(
            '/<script src="https:\/\/api\.qrserver\.com[^"]*" style="display:none"><\/script>\s*/',
            '',
            $html
        );
        
        // Substituições usando regex para garantir que capture todos os casos
        // e substitua apenas caminhos relativos (que não começam com / ou http)
        $patterns = [
            '/href="(favicon\.svg)"/' => 'href="' . $basePath . '/$1"',
            '/href="(style\.css)"/' => 'href="' . $basePath . '/$1"',
            '/href="(manifest\.json)"/' => 'href="' . $basePath . '/$1"',
            '/src="(js\/idb-keyval\.js)"/' => 'src="' . $basePath . '/$1"',
            '/type="module" src="(js\/imagePlaceholder\.js)"/' => 'type="module" src="' . $basePath . '/$1"',
            '/type="module" src="(js\/pix\.js)"/' => 'type="module" src="' . $basePath . '/$1"',
            '/type="module" src="(js\/app\.js)"/' => 'type="module" src="' . $basePath . '/$1"',
        ];
        
        // Corrige CSS do status online/offline
        $html = preg_replace(
            '/#status-online \{ display: none; \}\s*html\.online #status-online \{ display: flex; \}\s*html\.offline #status-offline \{ display: flex; \}/',
            '#status-online { display: none; } #status-offline { display: none; } html.online #status-online { display: flex; } html.online #status-offline { display: none; } html.offline #status-offline { display: flex; } html.offline #status-online { display: none; }',
            $html
        );
        
        foreach ($patterns as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }
        
        return $html;
    }
}

