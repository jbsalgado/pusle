<?php

namespace app\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use app\models\UsuarioModulo;
use app\models\Modulo;

/**
 * ModuloAccessBehavior
 * 
 * Behavior que verifica se o usuário tem acesso ao módulo antes de permitir acesso ao controller
 */
class ModuloAccessBehavior extends Behavior
{
    /**
     * @var string Código do módulo que deve ser verificado
     */
    public $moduloCodigo;

    /**
     * @var string Mensagem de erro personalizada
     */
    public $message = 'Você não tem permissão para acessar este módulo.';

    /**
     * @var bool Se true, redireciona para login em vez de lançar exceção
     */
    public $redirectToLogin = false;

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    /**
     * Verifica acesso antes da action
     * 
     * @param \yii\base\ActionEvent $event
     * @return bool
     * @throws ForbiddenHttpException
     */
    public function beforeAction($event)
    {
        // Se não há código de módulo configurado, permite acesso
        if (empty($this->moduloCodigo)) {
            return true;
        }

        // Se o usuário não está logado
        if (Yii::$app->user->isGuest) {
            if ($this->redirectToLogin) {
                Yii::$app->user->loginRequired();
                return false;
            }
            throw new ForbiddenHttpException('Você precisa estar logado para acessar este módulo.');
        }

        $usuario = Yii::$app->user->identity;
        
        if (!$usuario) {
            throw new ForbiddenHttpException('Usuário não encontrado.');
        }

        // Primeiro verifica se é dono da loja (que tem acesso a tudo)
        $ehDonoLoja = $usuario->eh_dono_loja ?? false;
        
        // Converte valor boolean do PostgreSQL se necessário
        if (is_string($ehDonoLoja)) {
            $ehDonoLoja = (strtolower(trim($ehDonoLoja)) === 't' || strtolower(trim($ehDonoLoja)) === 'true' || $ehDonoLoja === '1');
        } elseif ($ehDonoLoja === 1 || $ehDonoLoja === '1') {
            $ehDonoLoja = true;
        } elseif ($ehDonoLoja === 0 || $ehDonoLoja === '0' || $ehDonoLoja === false) {
            $ehDonoLoja = false;
        }
        
        // Log para debug (apenas em desenvolvimento)
        if (YII_DEBUG) {
            Yii::info("ModuloAccessBehavior - Usuario ID: {$usuario->id}, eh_dono_loja (tipo): " . gettype($usuario->eh_dono_loja) . ", valor: " . var_export($usuario->eh_dono_loja, true) . ", convertido: " . ($ehDonoLoja ? 'true' : 'false'), __METHOD__);
        }
        
        // Donos de loja têm acesso a todos os módulos
        if ($ehDonoLoja) {
            if (YII_DEBUG) {
                Yii::info("ModuloAccessBehavior - Acesso permitido: usuário é dono da loja", __METHOD__);
            }
            return true;
        }

        // Módulos padrão do sistema que todos os usuários logados têm acesso
        $modulosPadrao = ['vendas', 'caixa', 'contas-pagar'];
        
        // Se for um módulo padrão, permite acesso a todos os usuários logados
        if (in_array($this->moduloCodigo, $modulosPadrao)) {
            if (YII_DEBUG) {
                Yii::info("ModuloAccessBehavior - Acesso permitido: módulo padrão '{$this->moduloCodigo}'", __METHOD__);
            }
            return true;
        }

        // Verifica se o módulo existe
        $modulo = Modulo::findByCodigo($this->moduloCodigo);
        
        if (!$modulo) {
            Yii::warning("Módulo '{$this->moduloCodigo}' não encontrado - permitindo acesso por padrão", __METHOD__);
            // Se o módulo não existe, permite acesso (pode ser um módulo padrão que todos têm acesso)
            return true;
        }

        // Verifica acesso direto via UsuarioModulo
        $temAcesso = UsuarioModulo::verificarAcesso($usuario->id, $this->moduloCodigo);
        
        if (YII_DEBUG) {
            Yii::info("ModuloAccessBehavior - Verificação de acesso: usuario_id={$usuario->id}, modulo={$this->moduloCodigo}, temAcesso=" . ($temAcesso ? 'sim' : 'não'), __METHOD__);
        }

        // Se não tem acesso, lança exceção ou redireciona
        if (!$temAcesso) {
            if ($this->redirectToLogin) {
                Yii::$app->session->setFlash('error', $this->message);
                Yii::$app->user->loginRequired();
                return false;
            }
            
            throw new ForbiddenHttpException($this->message);
        }

        return true;
    }
}

