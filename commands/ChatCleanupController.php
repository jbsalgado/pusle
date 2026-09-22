<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\modules\vendas\helpers\ChatMediaHelper;

class ChatCleanupController extends Controller
{
    /**
     * Limpa fotos e mídias antigas do chat com mais de X horas (padrão: 720h = 30 dias).
     * Uso: php yii chat-cleanup/limpar [horas]
     */
    public function actionLimpar($horas = 720)
    {
        $dias = round($horas / 24, 1);
        $this->stdout("Iniciando limpeza de fotos e mídias do chat com mais de {$horas} horas (~{$dias} dias)...\n");
        $removidos = ChatMediaHelper::limparMidiasAntigas($horas);
        $this->stdout("Limpeza concluída! {$removidos} arquivo(s) de mídia removido(s) do disco.\n");

        return ExitCode::OK;
    }
}
