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
        
        // Obtém a URL base do web (ex: /pulse/basic/web ou /web)
        // Usa baseUrl do request que é mais confiável
        $baseUrl = Yii::$app->request->baseUrl;
        
        // Se baseUrl estiver vazio, tenta usar @web como fallback
        if (empty($baseUrl)) {
            $baseUrl = Yii::getAlias('@web');
        }
        
        // Remove barra inicial se existir para evitar duplicação
        $baseUrl = ltrim($baseUrl, '/');
        $vendaDiretaPath = '/' . $baseUrl . '/venda-direta';
        
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
        // Remove barra final se existir
        $basePath = rtrim($basePath, '/');
        
        // Remove o script problemático do QRCode (não é necessário)
        $html = preg_replace(
            '/<script src="https:\/\/api\.qrserver\.com[^"]*" style="display:none"><\/script>\s*/',
            '',
            $html
        );
        
        // Substituições diretas para recursos relativos
        $substituicoes = [
            'href="favicon.svg"' => 'href="' . $basePath . '/favicon.svg"',
            'href="style.css"' => 'href="' . $basePath . '/style.css"',
            'href="manifest.json"' => 'href="' . $basePath . '/manifest.json"',
            'src="js/idb-keyval.js"' => 'src="' . $basePath . '/js/idb-keyval.js"',
            'type="module" src="js/imagePlaceholder.js"' => 'type="module" src="' . $basePath . '/js/imagePlaceholder.js"',
            'type="module" src="js/pix.js"' => 'type="module" src="' . $basePath . '/js/pix.js"',
            'type="module" src="js/app.js"' => 'type="module" src="' . $basePath . '/js/app.js"',
        ];
        
        // Corrige CSS do status online/offline
        $html = preg_replace(
            '/#status-online \{ display: none; \}\s*html\.online #status-online \{ display: flex; \}\s*html\.offline #status-offline \{ display: flex; \}/',
            '#status-online { display: none; } #status-offline { display: none; } html.online #status-online { display: flex; } html.online #status-offline { display: none; } html.offline #status-offline { display: flex; } html.offline #status-online { display: none; }',
            $html
        );
        
        foreach ($substituicoes as $busca => $substitui) {
            $html = str_replace($busca, $substitui, $html);
        }
        
        return $html;
    }
}

