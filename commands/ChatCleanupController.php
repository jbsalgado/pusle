<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\vendas\helpers\ChatMediaHelper;

class ChatCleanupController extends Controller
{
    /**
     * Limpa fotos de chat com mais de 24 horas.
     * Uso: php yii chat-cleanup/limpar
     */
    public function actionLimpar($horas = 24)
    {
        $this->stdout("Iniciando limpeza de fotos do chat com mais de {$horas} horas...\n");
        $removidos = ChatMediaHelper::limparMidiasAntigas($horas);
        $this->stdout("Limpeza concluída! {$removidos} arquivo(s) temporário(s) removido(s).\n");

        return ExitCode::OK;
    }
}
