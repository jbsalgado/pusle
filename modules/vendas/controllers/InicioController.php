<?php
/**
 * InicioController - VERSÃO DE TESTE ESTÁTICO
 */
namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\behaviors\ModuloAccessBehavior;

class InicioController extends Controller
{
    public $layout = 'main';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Garante que só utilizadores logados acedem
                    ],
                ],
            ],
            // O behavior de acesso ao módulo pode ser mantido
            'moduloAccess' => [
                'class' => ModuloAccessBehavior::class,
                'moduloCodigo' => 'vendas',
            ],
        ];
    }

    /**
     * A action mais simples possível.
     * Apenas chama a view, sem passar nenhuma variável.
     */
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        
        if (!$usuario) {
            Yii::warning("⚠️ Usuário não autenticado!", __METHOD__);
            return $this->redirect(['/auth/login']);
        }
        
        // 🔍 DEBUG: Verifica valor direto do banco antes de qualquer conversão
        $ehDonoLojaRaw = $usuario->eh_dono_loja;
        Yii::info("🔍 DEBUG RAW - usuario->eh_dono_loja (tipo): " . gettype($ehDonoLojaRaw) . ", valor: " . var_export($ehDonoLojaRaw, true), __METHOD__);
        
        // Força recarregar do banco para garantir que temos o valor mais recente
        $usuario->refresh();
        Yii::info("🔍 DEBUG AFTER REFRESH - usuario->eh_dono_loja (tipo): " . gettype($usuario->eh_dono_loja) . ", valor: " . var_export($usuario->eh_dono_loja, true), __METHOD__);
        
        // Verifica se é dono da loja (acesso completo automático)
        // Helper para converter valor boolean do PostgreSQL para PHP boolean
        $ehDonoLoja = $this->converterParaBoolean($usuario->eh_dono_loja);
        
        Yii::info("🔍 DEBUG AFTER CONVERSION - ehDonoLoja: " . ($ehDonoLoja ? 'true' : 'false'), __METHOD__);
        
        // Busca o colaborador associado ao usuário (se houver)
        $colaborador = null;
        $ehAdministrador = false;
        
        // Se é dono da loja, tem acesso completo
        if ($ehDonoLoja) {
            $ehAdministrador = true;
            Yii::info("✅ Usuário é dono da loja - Acesso completo concedido. ID: {$usuario->id}, eh_dono_loja: " . var_export($usuario->eh_dono_loja, true), __METHOD__);
        } else {
            // Se não é dono, verifica se é colaborador administrador
            // Usa o método helper do modelo Colaborador que suporta ambos os cenários
            $colaborador = \app\modules\vendas\models\Colaborador::getColaboradorLogado();
            
            if ($colaborador) {
                // Helper para converter valor boolean do PostgreSQL para PHP boolean
                $ehAdministrador = $this->converterParaBoolean($colaborador->eh_administrador);
                Yii::info("Colaborador encontrado - eh_administrador: " . var_export($colaborador->eh_administrador, true) . " -> " . ($ehAdministrador ? 'true' : 'false') . ", prest_usuario_login_id: " . var_export($colaborador->prest_usuario_login_id, true) . ", usuario_id: " . var_export($colaborador->usuario_id, true), __METHOD__);
            } else {
                Yii::info("Colaborador não encontrado ou inativo para usuário ID: {$usuario->id}", __METHOD__);
            }
        }
        
        Yii::info("🔍 DEBUG InicioController - ehDonoLoja: " . ($ehDonoLoja ? 'true' : 'false') . ", ehAdministrador: " . ($ehAdministrador ? 'true' : 'false') . ", usuario->eh_dono_loja: " . var_export($usuario->eh_dono_loja, true), __METHOD__);
        
        // Buscar contagem de vendas pendentes do catálogo
        $countVendasPendentes = 0;
        if ($ehAdministrador || $ehDonoLoja) {
            $countVendasPendentes = \app\modules\vendas\models\Venda::find()
                ->where(['usuario_id' => $usuario->id])
                ->andWhere(['status_venda_codigo' => \app\modules\vendas\models\StatusVenda::EM_ABERTO])
                ->andWhere(['or',
                    ['!=', 'observacoes', 'Venda Direta'],
                    ['is', 'observacoes', null],
                    ['observacoes' => ''],
                    ['like', 'observacoes', 'Pedido PWA']
                ])
                ->count();
        }
        
        return $this->render('index', [
            'colaborador' => $colaborador,
            'ehAdministrador' => $ehAdministrador,
            'ehDonoLoja' => $ehDonoLoja,
            'countVendasPendentes' => $countVendasPendentes,
        ]);
    }
    
    /**
     * Lista vendas pendentes do catálogo para confirmação de pagamento
     */
    public function actionConfirmarPagamentos()
    {
        $usuario = Yii::$app->user->identity;
        
        if (!$usuario) {
            Yii::$app->session->setFlash('error', 'Usuário não autenticado.');
            return $this->redirect(['index']);
        }
        
        // Verifica se é administrador ou dono da loja
        $ehDonoLoja = $this->converterParaBoolean($usuario->eh_dono_loja);
        $ehAdministrador = false;
        
        if ($ehDonoLoja) {
            $ehAdministrador = true;
        } else {
            $colaborador = \app\modules\vendas\models\Colaborador::getColaboradorLogado();
            if ($colaborador) {
                $ehAdministrador = $this->converterParaBoolean($colaborador->eh_administrador);
            }
        }
        
        if (!$ehAdministrador && !$ehDonoLoja) {
            Yii::$app->session->setFlash('error', 'Você não tem permissão para acessar esta página.');
            return $this->redirect(['index']);
        }
        
        // Buscar vendas pendentes do catálogo (status EM_ABERTO)
        // Vendas do catálogo são identificadas por:
        // - status_venda_codigo = 'EM_ABERTO' (aguardando pagamento)
        // - observacoes != 'Venda Direta' (ou null/vazio)
        $query = \app\modules\vendas\models\Venda::find()
            ->where(['usuario_id' => $usuario->id])
            ->andWhere(['status_venda_codigo' => \app\modules\vendas\models\StatusVenda::EM_ABERTO])
            ->andWhere(['or',
                ['!=', 'observacoes', 'Venda Direta'],
                ['is', 'observacoes', null],
                ['observacoes' => ''],
                ['like', 'observacoes', 'Pedido PWA']
            ])
            ->with(['cliente', 'formaPagamento', 'itens.produto'])
            ->orderBy(['data_criacao' => SORT_DESC]);
        
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        
        return $this->render('confirmar-pagamentos', [
            'dataProvider' => $dataProvider,
            'ehAdministrador' => $ehAdministrador,
            'ehDonoLoja' => $ehDonoLoja,
        ]);
    }
    
    /**
     * Confirma recebimento de venda do catálogo
     */
    public function actionConfirmarPagamento($id)
    {
        $usuario = Yii::$app->user->identity;
        if (!$usuario) {
            Yii::$app->session->setFlash('error', 'Usuário não autenticado.');
            return $this->redirect(['index']);
        }
        
        // ✅ Verifica se é administrador ou dono da loja
        $ehDonoLoja = $this->converterParaBoolean($usuario->eh_dono_loja);
        $ehAdministrador = false;
        
        if ($ehDonoLoja) {
            $ehAdministrador = true;
        } else {
            $colaborador = \app\modules\vendas\models\Colaborador::getColaboradorLogado();
            if ($colaborador) {
                $ehAdministrador = $this->converterParaBoolean($colaborador->eh_administrador);
            }
        }
        
        if (!$ehAdministrador && !$ehDonoLoja) {
            Yii::$app->session->setFlash('error', 'Apenas administradores podem confirmar pagamentos.');
            return $this->redirect(['confirmar-pagamentos']);
        }
        
        $venda = \app\modules\vendas\models\Venda::findOne($id);
        
        if (!$venda) {
            Yii::$app->session->setFlash('error', 'Venda não encontrada.');
            return $this->redirect(['confirmar-pagamentos']);
        }
        
        // Verifica se a venda pertence ao usuário
        if ($venda->usuario_id !== $usuario->id) {
            Yii::$app->session->setFlash('error', 'Você não tem permissão para confirmar esta venda.');
            return $this->redirect(['confirmar-pagamentos']);
        }
        
        // Verifica se a venda já está quitada
        if ($venda->status_venda_codigo === \app\modules\vendas\models\StatusVenda::QUITADA) {
            Yii::$app->session->setFlash('warning', 'Esta venda já está quitada.');
            return $this->redirect(['confirmar-pagamentos']);
        }
        
        // ✅ Verificar estoque ANTES de confirmar o pagamento
        $itensSemEstoque = [];
        foreach ($venda->itens as $item) {
            $produto = $item->produto;
            if ($produto) {
                $produto->refresh();
                if ($produto->estoque_atual < $item->quantidade) {
                    $itensSemEstoque[] = [
                        'produto' => $produto->nome,
                        'quantidade_solicitada' => $item->quantidade,
                        'estoque_disponivel' => $produto->estoque_atual
                    ];
                }
            } else {
                $itensSemEstoque[] = [
                    'produto' => 'Produto não encontrado (ID: ' . $item->produto_id . ')',
                    'quantidade_solicitada' => $item->quantidade,
                    'estoque_disponivel' => 0
                ];
            }
        }
        
        if (!empty($itensSemEstoque)) {
            $mensagemErro = "❌ Não é possível confirmar o pagamento. Estoque insuficiente para os seguintes itens:\n\n";
            foreach ($itensSemEstoque as $itemErro) {
                $mensagemErro .= "• <strong>{$itemErro['produto']}</strong>: Solicitado {$itemErro['quantidade_solicitada']} unidade(s), Disponível {$itemErro['estoque_disponivel']} unidade(s)\n";
            }
            $mensagemErro .= "\nPor favor, verifique o estoque antes de confirmar o pagamento.";
            Yii::$app->session->setFlash('error', $mensagemErro);
            Yii::warning("Tentativa de confirmar pagamento com estoque insuficiente. Venda ID: {$venda->id}", 'vendas');
            return $this->redirect(['confirmar-pagamentos']);
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Atualiza status para QUITADA
            $venda->status_venda_codigo = \app\modules\vendas\models\StatusVenda::QUITADA;
            $venda->data_atualizacao = new \yii\db\Expression('NOW()');
            
            if (!$venda->save(false, ['status_venda_codigo', 'data_atualizacao'])) {
                throw new \Exception('Erro ao atualizar status da venda.');
            }
            
            // Baixa estoque dos itens (já validado acima)
            foreach ($venda->itens as $item) {
                $produto = $item->produto;
                if ($produto) {
                    $produto->refresh();
                    $produto->estoque_atual -= $item->quantidade;
                    if (!$produto->save(false, ['estoque_atual'])) {
                        throw new \Exception("Erro ao atualizar estoque do produto '{$produto->nome}'.");
                    }
                    Yii::info("✅ Estoque de '{$produto->nome}' baixado: {$item->quantidade} unidades. Estoque restante: {$produto->estoque_atual}", 'vendas');
                }
            }
            
            // Registra entrada no caixa
            try {
                $movimentacao = \app\modules\caixa\helpers\CaixaHelper::registrarEntradaVenda(
                    $venda->id,
                    $venda->valor_total,
                    $venda->forma_pagamento_id,
                    $venda->usuario_id
                );
                
                if (!$movimentacao) {
                    Yii::warning("⚠️ Não foi possível registrar entrada no caixa para Venda ID {$venda->id} (caixa pode não estar aberto)", 'vendas');
                }
            } catch (\Exception $e) {
                Yii::error("Erro ao registrar entrada no caixa (não crítico): " . $e->getMessage(), 'vendas');
            }
            
            $transaction->commit();
            
            // ✅ Redireciona para a página de comprovante após confirmação
            return $this->redirect(['comprovante', 'id' => $venda->id]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Erro ao confirmar pagamento: ' . $e->getMessage());
            Yii::error('Erro ao confirmar pagamento: ' . $e->getMessage(), __METHOD__);
            return $this->redirect(['confirmar-pagamentos']);
        }
    }
    
    /**
     * Exibe comprovante de venda após confirmação de pagamento
     */
    public function actionComprovante($id)
    {
        $usuario = Yii::$app->user->identity;
        if (!$usuario) {
            Yii::$app->session->setFlash('error', 'Usuário não autenticado.');
            return $this->redirect(['index']);
        }
        
        $venda = \app\modules\vendas\models\Venda::findOne($id);
        
        if (!$venda) {
            Yii::$app->session->setFlash('error', 'Venda não encontrada.');
            return $this->redirect(['confirmar-pagamentos']);
        }
        
        // Verifica se a venda pertence ao usuário
        if ($venda->usuario_id !== $usuario->id) {
            Yii::$app->session->setFlash('error', 'Você não tem permissão para visualizar esta venda.');
            return $this->redirect(['confirmar-pagamentos']);
        }
        
        // Carrega relacionamentos
        $venda->populateRelation('cliente', $venda->cliente);
        $venda->populateRelation('formaPagamento', $venda->formaPagamento);
        $venda->populateRelation('itens', $venda->itens);
        $venda->populateRelation('parcelas', $venda->parcelas);
        
        foreach ($venda->itens as $item) {
            $item->populateRelation('produto', $item->produto);
        }
        
        return $this->render('comprovante', [
            'venda' => $venda,
        ]);
    }
    
    /**
     * Converte valor boolean do PostgreSQL para PHP boolean
     * PostgreSQL pode retornar: true, false, 't', 'f', '1', '0', 1, 0
     * 
     * @param mixed $valor
     * @return bool
     */
    protected function converterParaBoolean($valor)
    {
        if ($valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true') {
            return true;
        }
        
        if (is_string($valor) && strtolower(trim($valor)) === 't') {
            return true;
        }
        
        return false;
    }
}