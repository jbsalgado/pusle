<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model LojaPermissao - Controle de Módulos (Liga/Desliga) por Dono de Loja
 * Table: prest_loja_permissoes
 */
class LojaPermissao extends ActiveRecord
{
    public static function tableName()
    {
        return 'prest_loja_permissoes';
    }

    public function rules()
    {
        return [
            [['usuario_id', 'modulo_chave'], 'required'],
            [['usuario_id', 'modulo_chave'], 'string'],
            [['ativo'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['data_atualizacao'], 'safe'],
            [['modulo_chave'], 'unique', 'targetAttribute' => ['usuario_id', 'modulo_chave']],
        ];
    }

    /**
     * Retorna array de permissões ativas/inativas para um usuário.
     * Módulos sem registro na tabela assumem TRUE por padrão.
     *
     * @param string $usuarioId
     * @return array ['modulo_chave' => bool]
     */
    public static function getPermissoesUsuario($usuarioId)
    {
        if (!$usuarioId) return [];

        $records = static::find()
            ->where(['usuario_id' => $usuarioId])
            ->all();

        $permissoes = [];
        foreach ($records as $rec) {
            $val = $rec->ativo;
            if (is_string($val)) {
                $val = (strtolower(trim($val)) === 't' || strtolower(trim($val)) === 'true' || $val === '1');
            } else {
                $val = (bool)$val;
            }
            $permissoes[$rec->modulo_chave] = $val;
        }

        return $permissoes;
    }

    /**
     * Define/Atualiza o status de um módulo para determinado usuário.
     */
    public static function setPermissao($usuarioId, $moduloChave, $ativo)
    {
        $perm = static::findOne(['usuario_id' => $usuarioId, 'modulo_chave' => $moduloChave]);
        if (!$perm) {
            $perm = new static();
            $perm->usuario_id = $usuarioId;
            $perm->modulo_chave = $moduloChave;
        }
        $perm->ativo = (bool)$ativo;
        $perm->data_atualizacao = date('Y-m-d H:i:s');
        return $perm->save();
    }

    /**
     * Lista completa dos módulos gerenciáveis do sistema
     */
    public static function getTodosModulosDisponiveis()
    {
        return [
            // Ações Rápidas do Topo
            'nova-venda' => [
                'grupo' => 'Ações Rápidas',
                'label' => 'Nova Venda (PDV Direto)',
                'descricao' => 'Acesso ao PDV de Venda Direta / PWA',
                'icone' => '🛍️',
                'cor' => 'blue',
            ],
            'novo-orcamento' => [
                'grupo' => 'Ações Rápidas',
                'label' => 'Novo Orçamento',
                'descricao' => 'Criação e emissão de orçamentos',
                'icone' => '📋',
                'cor' => 'orange',
            ],
            'confirmar-pagamentos' => [
                'grupo' => 'Ações Rápidas',
                'label' => 'Confirmar Pagamentos On-line',
                'descricao' => 'Aprovação de pedidos vindos do Catálogo',
                'icone' => '✅',
                'cor' => 'green',
            ],
            'dashboard-geral' => [
                'grupo' => 'Ações Rápidas',
                'label' => 'Dashboard Geral',
                'descricao' => 'Estatísticas e visão geral de vendas',
                'icone' => '📊',
                'cor' => 'indigo',
            ],
            'dashboard-executivo' => [
                'grupo' => 'Ações Rápidas',
                'label' => 'Dashboard Executivo',
                'descricao' => 'Indicadores de BI e metas executivas',
                'icone' => '📈',
                'cor' => 'purple',
            ],
            'fluxo-caixa' => [
                'grupo' => 'Ações Rápidas',
                'label' => 'Fluxo de Caixa (Financeiro)',
                'descricao' => 'Dashboard financeiro em tempo real',
                'icone' => '💰',
                'cor' => 'teal',
            ],

            // Cards de Gerenciamento
            'clientes' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Clientes',
                'descricao' => 'Cadastro e gestão de clientes',
                'icone' => '👥',
                'cor' => 'blue',
            ],
            'produtos' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Produtos',
                'descricao' => 'Gestão do catálogo e estoque de produtos',
                'icone' => '📦',
                'cor' => 'green',
            ],
            'categorias' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Categorias',
                'descricao' => 'Categorias de produtos',
                'icone' => '🏷️',
                'cor' => 'purple',
            ],
            'trilha-sonora' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Gestão de Músicas',
                'descricao' => 'Trilhas sonoras para vídeos 9:16',
                'icone' => '🎵',
                'cor' => 'purple',
            ],
            'fornecedores' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Fornecedores',
                'descricao' => 'Cadastro de fornecedores',
                'icone' => '🏭',
                'cor' => 'orange',
            ],
            'compras' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Compras',
                'descricao' => 'Gestão de compras e resuprimentos',
                'icone' => '🛒',
                'cor' => 'teal',
            ],
            'dados-financeiros' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Precificação',
                'descricao' => 'Precificação inteligente (Markup Divisor)',
                'icone' => '💲',
                'cor' => 'purple',
            ],
            'lojas' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Lojas / Filiais',
                'descricao' => 'Gestão de filiais e multiloja',
                'icone' => '🏪',
                'cor' => 'indigo',
            ],
            'unidades-medida' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Unidades de Medida',
                'descricao' => 'Unidades e escalas de produtos',
                'icone' => '📏',
                'cor' => 'cyan',
            ],
            'colaboradores' => [
                'grupo' => 'Gestão Geral',
                'label' => 'Colaboradores',
                'descricao' => 'Equipe de trabalho e permissões',
                'icone' => '👔',
                'cor' => 'indigo',
            ],
            'caixa' => [
                'grupo' => 'Financeiro & Caixa',
                'label' => 'Caixa',
                'descricao' => 'Abertura, fechamento e controle de caixa',
                'icone' => '💵',
                'cor' => 'green',
            ],
            'contas-pagar' => [
                'grupo' => 'Financeiro & Caixa',
                'label' => 'Contas a Pagar',
                'descricao' => 'Lançamento e quitação de despesas',
                'icone' => '📄',
                'cor' => 'red',
            ],
            'tipos-despesa' => [
                'grupo' => 'Financeiro & Caixa',
                'label' => 'Tipos de Despesa',
                'descricao' => 'Categorização de custos',
                'icone' => '📁',
                'cor' => 'pink',
            ],
            'orcamentos' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Orçamentos',
                'descricao' => 'Histórico e gestão de orçamentos',
                'icone' => '📝',
                'cor' => 'yellow',
            ],
            'vendas' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Vendas Efetivadas',
                'descricao' => 'Histórico de vendas realizadas',
                'icone' => '📑',
                'cor' => 'blue',
            ],
            'formas-pagamento' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Formas de Pagamento',
                'descricao' => 'Canais de pagamento da loja',
                'icone' => '💳',
                'cor' => 'teal',
            ],
            'comissoes' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Comissões',
                'descricao' => 'Relatório e pagamento de comissões',
                'icone' => '💎',
                'cor' => 'purple',
            ],
            'comissao-config' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Config. Comissões',
                'descricao' => 'Regras e percentuais de comissão',
                'icone' => '⚙️',
                'cor' => 'purple',
            ],
            'periodo-cobranca' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Período Cobrança',
                'descricao' => 'Ciclos e prazos de cobrança',
                'icone' => '📅',
                'cor' => 'orange',
            ],
            'carteira-cobranca' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Carteira Cobrança',
                'descricao' => 'Gestão de títulos e carteira',
                'icone' => '👝',
                'cor' => 'red',
            ],
            'itens-avulsos' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Itens Avulsos / Pendentes',
                'descricao' => 'Produtos sem cadastro completo',
                'icone' => '⚠️',
                'cor' => 'yellow',
            ],
            'historico-cobranca' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Histórico Cobrança',
                'descricao' => 'Registro de cobranças realizadas',
                'icone' => '📜',
                'cor' => 'pink',
            ],
            'taxa-entrega' => [
                'grupo' => 'Logística & Regiões',
                'label' => 'Gestão de Fretes',
                'descricao' => 'Taxas por Cidade, Bairro e CEP',
                'icone' => '🚚',
                'cor' => 'orange',
            ],
            'parcelas' => [
                'grupo' => 'Vendas & Cobrança',
                'label' => 'Parcelas',
                'descricao' => 'Gestão detalhada de parcelas',
                'icone' => '🔢',
                'cor' => 'cyan',
            ],
            'regioes' => [
                'grupo' => 'Logística & Regiões',
                'label' => 'Região',
                'descricao' => 'Territórios e regiões atendidas',
                'icone' => '🗺️',
                'cor' => 'green',
            ],
            'rotas-cobranca' => [
                'grupo' => 'Logística & Regiões',
                'label' => 'Rotas Cobrança',
                'descricao' => 'Roteiros de cobrança em campo',
                'icone' => '🛣️',
                'cor' => 'indigo',
            ],
            'status-parcela' => [
                'grupo' => 'Configurações do Sistema',
                'label' => 'Status Parcela',
                'descricao' => 'Tipos de status de parcelas',
                'icone' => '📌',
                'cor' => 'blue',
            ],
            'status-venda' => [
                'grupo' => 'Configurações do Sistema',
                'label' => 'Status Vendas',
                'descricao' => 'Tipos de status de vendas',
                'icone' => '📌',
                'cor' => 'purple',
            ],
            'marketplaces' => [
                'grupo' => 'Integrações',
                'label' => 'Marketplaces',
                'descricao' => 'Integrações multicanal e e-commerce',
                'icone' => '🌐',
                'cor' => 'cyan',
            ],
            'usuarios' => [
                'grupo' => 'Configurações do Sistema',
                'label' => 'Usuários',
                'descricao' => 'Acessos e login de usuários',
                'icone' => '👤',
                'cor' => 'indigo',
            ],
            'configuracoes' => [
                'grupo' => 'Configurações do Sistema',
                'label' => 'Configurações',
                'descricao' => 'Ajustes gerais do sistema',
                'icone' => '⚙️',
                'cor' => 'gray',
            ],
            'dados-loja' => [
                'grupo' => 'Configurações do Sistema',
                'label' => 'Dados da Loja',
                'descricao' => 'Nome, CNPJ, endereço e marca da loja',
                'icone' => '🏢',
                'cor' => 'blue',
            ],
            'whatsapp-evolution' => [
                'grupo' => 'Integrações',
                'label' => 'WhatsApp Evolution',
                'descricao' => 'Instância e conexões do WhatsApp',
                'icone' => '📲',
                'cor' => 'green',
            ],
        ];
    }
}
