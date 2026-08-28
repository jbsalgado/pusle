<?php

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\modules\vendas\models\Comanda;
use app\modules\vendas\models\ComandaItem;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;
use app\models\Usuario;
use app\modules\evolution\services\EvolutionService;

class TotemController extends Controller
{
    public $layout = false; // Tela limpa em formato Kiosk Fullscreen
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'finalizar-pedido' => ['POST'],
                ],
            ],
        ];
    }

    private function resolveTenantId()
    {
        $tenantId = \app\components\TenantHelper::getId();
        if (!empty($tenantId)) {
            return $tenantId;
        }

        $loja = Usuario::find()->one();
        return $loja ? $loja->id : null;
    }

    /**
     * Interface do Totem de Autoatendimento Fast-Food
     */
    public function actionIndex()
    {
        $tenantId = $this->resolveTenantId();
        $loja = $tenantId ? Usuario::findOne($tenantId) : null;

        $categorias = $tenantId ? Categoria::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->orderBy(['nome' => SORT_ASC])
            ->all() : [];

        $produtos = $tenantId ? Produto::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true])
            ->with(['opcionais'])
            ->orderBy(['nome' => SORT_ASC])
            ->all() : [];

        return $this->render('index', [
            'loja' => $loja,
            'categorias' => $categorias,
            'produtos' => $produtos,
        ]);
    }

    /**
     * Ação: Finalizar Pedido no Totem & Gerar Senha de Balcão
     */
    public function actionFinalizarPedido()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $tenantId = $this->resolveTenantId();
        $request = Yii::$app->request->post();

        $clienteNome = trim($request['cliente_nome'] ?? 'Cliente Totem');
        $clienteTelefone = trim($request['cliente_telefone'] ?? '');
        $itensRaw = $request['itens'] ?? '[]';
        $tipoConsumo = trim($request['tipo_consumo'] ?? 'comer_aqui'); // 'comer_aqui' ou 'levar'

        $itens = json_decode($itensRaw, true);
        if (empty($itens)) {
            return ['success' => false, 'message' => 'Nenhum item selecionado no carrinho.'];
        }

        // Gera número sequencial de senha para o dia (ex: #042)
        $totalHoje = Comanda::find()
            ->where(['usuario_id' => $tenantId])
            ->andWhere(['>=', 'data_abertura', date('Y-m-d 00:00:00')])
            ->count();
        $numSenha = str_pad($totalHoje + 1, 3, '0', STR_PAD_LEFT);
        $senhaTexto = "#" . $numSenha;

        $comanda = new Comanda();
        $comanda->id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $comanda->usuario_id = $tenantId;
        $comanda->numero_comanda = 'TOTEM-' . $numSenha;
        $comanda->cliente_nome = $clienteNome . ($tipoConsumo === 'levar' ? ' (p/ Levar)' : ' (Comer Aqui)');
        $comanda->cliente_telefone = $clienteTelefone;
        $comanda->senha_balcao = $senhaTexto;
        $comanda->tipo_atendimento = 'balcao';
        $comanda->status = Comanda::STATUS_ABERTA;

        if ($comanda->save(false)) {
            $salvos = 0;
            $valorTotal = 0;
            foreach ($itens as $it) {
                $prodId = $it['produto_id'] ?? null;
                $qtd = (float)($it['quantidade'] ?? 1);
                $valorAdicional = (float)($it['valor_adicional'] ?? 0);
                $obs = trim($it['observacoes'] ?? '');

                $produto = Produto::findOne(['id' => $prodId, 'usuario_id' => $tenantId]);
                if ($produto) {
                    $item = new ComandaItem();
                    $item->comanda_id = $comanda->id;
                    $item->produto_id = $produto->id;
                    $item->quantidade = $qtd > 0 ? $qtd : 1;
                    $item->valor_unitario = (float)$produto->getPrecoFinal() + $valorAdicional;
                    $item->observacoes = $obs;
                    $item->destino_preparo = 'cozinha';
                    $item->status_preparo = ComandaItem::STATUS_PENDENTE;
                    if ($item->save(false)) {
                        $salvos++;
                        $valorTotal += $item->getSubtotal();
                    }
                }
            }

            // Notifica por WhatsApp se o telefone foi informado
            if (!empty($clienteTelefone)) {
                try {
                    $loja = Usuario::findOne($tenantId);
                    $nomeLoja = $loja ? ($loja->nome ?: 'Nosso Estabelecimento') : 'Nosso Estabelecimento';
                    $msg = "👋 Olá, *{$clienteNome}*! Seu pedido no *{$nomeLoja}* foi realizado com sucesso!\n\n🎟️ *SUA SENHA DE RETIRADA É: {$senhaTexto}*\n\nAcompanhe a sua senha no Painel da TV do Salão. Bom apetite! ❤️";
                    $evolution = new EvolutionService();
                    $evolution->sendMessage($tenantId, $clienteTelefone, $msg);
                } catch (\Exception $e) {
                    Yii::warning("Erro ao enviar senha via WhatsApp no Totem: " . $e->getMessage(), __METHOD__);
                }
            }

            return [
                'success' => true,
                'senha' => $senhaTexto,
                'valor_total' => $valorTotal,
                'valor_total_formatado' => number_format($valorTotal, 2, ',', '.'),
                'message' => 'Pedido realizado com sucesso!',
            ];
        }

        return ['success' => false, 'message' => 'Erro ao gerar pedido no totem.'];
    }
}
