<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\modules\vendas\models\Encarte;
use app\modules\vendas\services\EncartePdfService;
use kartik\mpdf\Pdf;

class EncartePublicoController extends Controller
{
    /**
     * Define o layout nulo/vazio para permitir experiência 100% personalizada e imersiva
     */
    public $layout = false;

    /**
     * Desabilita controle de acesso (permissão pública)
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * Exibe a página pública interativa do Encarte Digital Flipbook
     */
    public function actionVer($token)
    {
        $encarte = Encarte::find()
            ->where(['token_publico' => $token, 'status' => 'ativo'])
            ->with(['usuario', 'encarteProdutos.produto.fotos', 'encarteProdutos.produto.categoria'])
            ->one();

        if (!$encarte) {
            throw new NotFoundHttpException('O encarte solicitado não foi encontrado ou expirou.');
        }

        // Incrementa visualizações
        $encarte->updateCounters(['visualizacoes_count' => 1]);

        return $this->render('ver', [
            'encarte' => $encarte,
            'loja' => $encarte->usuario,
            'encarteProdutos' => $encarte->encarteProdutos,
        ]);
    }

    /**
     * Download do PDF público
     */
    public function actionPdf($token)
    {
        $encarte = Encarte::find()
            ->where(['token_publico' => $token, 'status' => 'ativo'])
            ->one();

        if (!$encarte) {
            throw new NotFoundHttpException('O encarte solicitado não foi encontrado.');
        }

        return EncartePdfService::gerarPdf($encarte, Pdf::DEST_BROWSER);
    }
}
