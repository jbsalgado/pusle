<?php

namespace app\modules\api\controllers;

use Yii;
use yii\db\Query;

/**
 * NotificacaoController
 * Gerencia alertas e avisos para o aplicativo mobile (Tausz-Pulse)
 */
class NotificacaoController extends BaseController
{
    /**
     * Lista as notificações não lidas para o usuário autenticado
     * GET /api/notificacao
     */
    public function actionIndex()
    {
        $usuarioId = Yii::$app->user->id;

        $notificacoes = (new Query())
            ->select(['id', 'titulo', 'mensagem', 'data_criacao'])
            ->from('sys_notificacoes_app')
            ->where(['usuario_id' => $usuarioId, 'lida' => false])
            ->orderBy(['data_criacao' => SORT_DESC])
            ->all();

        return $this->success($notificacoes, count($notificacoes) . ' nova(s) notificação(ões)');
    }

    /**
     * Marca uma notificação como lida
     * PATCH /api/notificacao/ler?id=123
     */
    public function actionLer($id)
    {
        $usuarioId = Yii::$app->user->id;

        $affected = Yii::$app->db->createCommand()
            ->update('sys_notificacoes_app', 
                ['lida' => true], 
                ['id' => $id, 'usuario_id' => $usuarioId]
            )
            ->execute();

        if ($affected) {
            return $this->success([], 'Marcada como lida');
        }

        return $this->error('Notificação não encontrada ou já lida', 404);
    }

    /**
     * Método auxiliar para disparar notificações (pode ser usado via console ou outros controllers)
     * Ex: NotificacaoController::disparar(1, 'Pedido Pronto', 'Seu pedido #50 está pronto.');
     */
    public static function disparar($usuarioId, $titulo, $mensagem)
    {
        return Yii::$app->db->createCommand()
            ->insert('sys_notificacoes_app', [
                'usuario_id' => $usuarioId,
                'titulo' => $titulo,
                'mensagem' => $mensagem,
                'lida' => false,
                'data_criacao' => date('Y-m-d H:i:s'),
            ])
            ->execute();
    }
}
