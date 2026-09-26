<?php

namespace app\components;

use Yii;
use yii\web\ErrorAction as BaseErrorAction;

/**
 * ErrorAction — estende o `yii\web\ErrorAction` para dar rastreabilidade aos erros 500.
 *
 * Motivação: a página "Error (#2) / An internal server error occurred." não trazia
 * nenhuma forma de correlacionar o que o usuário viu com a linha no log. Com o
 * `YII_DEBUG=false` em produção a mensagem técnica fica oculta (comportamento
 * correto), então este componente:
 *
 *  1. gera um ID curto de incidente, exibido na página de erro;
 *  2. grava no log um contexto CURADO (URL, método, usuário, tenant, UA),
 *     sem depender do dump automático de `$_SERVER`/`$_ENV` do Yii.
 *
 * Registrado em `app\controllers\SiteController::actions()['error']`.
 */
class ErrorAction extends BaseErrorAction
{
    /**
     * @var string|null ID curto do incidente, exibido na página e no log.
     */
    public $incidentId;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (empty($this->incidentId)) {
            $this->incidentId = $this->generateIncidentId();
        }

        $this->logIncident();

        return parent::run();
    }

    /**
     * {@inheritdoc}
     */
    protected function getViewRenderParams()
    {
        $params = parent::getViewRenderParams();
        $params['incidentId'] = $this->incidentId;

        return $params;
    }

    /**
     * Gera um identificador curto e legível para suporte.
     * Ex.: A1B2C3D4E5
     */
    protected function generateIncidentId(): string
    {
        try {
            return strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        } catch (\Throwable $e) {
            return strtoupper(substr(md5(uniqid('', true)), 0, 10));
        }
    }

    /**
     * Registra o incidente no log com contexto curado.
     * Nunca deixa o logging mascarar a exceção original.
     */
    protected function logIncident(): void
    {
        try {
            $exception = $this->exception;

            $usuarioId = null;
            $usuarioEhAdmin = null;
            $tenantId = null;

            if (Yii::$app->has('user') && !Yii::$app->user->getIsGuest()) {
                $usuarioId = Yii::$app->user->getId();
                $identity = Yii::$app->user->identity;
                if ($identity !== null) {
                    $usuarioEhAdmin = $identity->is_admin ?? null;
                    $tenantId = $identity->eh_dono_loja ? $usuarioId : ($identity->usuario_id ?? null);
                }
            }

            $request = Yii::$app->has('request') ? Yii::$app->request : null;

            $contexto = [
                'incident_id' => $this->incidentId,
                'exception'   => get_class($exception),
                'code'        => $exception->getCode(),
                'file'        => $exception->getFile() . ':' . $exception->getLine(),
                'url'         => $request ? $request->getUrl() : null,
                'method'      => $request ? $request->getMethod() : null,
                'usuario_id'  => $usuarioId,
                'is_admin'    => $usuarioEhAdmin,
                'tenant_id'   => $tenantId,
                'user_agent'  => $request ? $request->getUserAgent() : null,
            ];

            Yii::error(
                sprintf(
                    '[%s] %s (#%s): %s | %s',
                    (string)$this->incidentId,
                    get_class($exception),
                    (string)$exception->getCode(),
                    $exception->getMessage(),
                    json_encode($contexto, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ),
                __METHOD__
            );
        } catch (\Throwable $e) {
            // Silencioso por design: logging nunca deve ofuscar o erro original.
        }
    }
}
