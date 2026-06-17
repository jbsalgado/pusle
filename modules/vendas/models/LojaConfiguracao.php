<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Model para Configuração da Loja
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $nome_loja
 * @property string $nome_fantasia
 * @property string $razao_social
 * @property string $cpf_cnpj
 * @property string $inscricao_estadual
 * @property string $inscricao_municipal
 * @property string $telefone
 * @property string $celular
 * @property string $email
 * @property string $site
 * @property string $cep
 * @property string $logradouro
 * @property string $numero
 * @property string $complemento
 * @property string $bairro
 * @property string $cidade
 * @property string $estado
 * @property string $codigo_municipio_ibge
 * @property string $logo_path
 * @property string $pix_chave
 * @property string $pix_nome
 * @property string $pix_cidade
 * @property string $created_at
 * @property string $updated_at
 */
class LojaConfiguracao extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'loja_configuracao';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'nome_loja', 'cpf_cnpj'], 'required'],
            [['usuario_id'], 'string', 'max' => 36],
            [['nome_loja', 'nome_fantasia', 'razao_social', 'logradouro', 'email', 'site'], 'string', 'max' => 255],
            [['cpf_cnpj'], 'string', 'max' => 18],
            [['inscricao_estadual', 'inscricao_municipal', 'telefone', 'celular', 'numero'], 'string', 'max' => 20],
            [['cep'], 'string', 'max' => 10],
            [['complemento', 'bairro', 'cidade'], 'string', 'max' => 100],
            [['estado'], 'string', 'max' => 2],
            [['codigo_municipio_ibge'], 'string', 'max' => 7],
            [['logo_path'], 'string', 'max' => 500],
            [['pix_chave', 'pix_nome', 'pix_cidade'], 'string', 'max' => 255],
            [['aparencia_tema'], 'string', 'max' => 50],
            [['aparencia_cor_primaria', 'aparencia_cor_secundaria'], 'string', 'max' => 7],
            [['aparencia_cor_primaria', 'aparencia_cor_secundaria'], 'match', 'pattern' => '/^#[0-9a-fA-F]{6}$/', 'message' => 'A cor deve ser um hexadecimal válido (ex: #3B82F6).'],
            [['usuario_id'], 'unique'],

            // Validação de CPF/CNPJ
            ['cpf_cnpj', 'match', 'pattern' => '/^[\d.\-\/]+$/', 'message' => 'CPF/CNPJ deve conter apenas números, pontos, traços e barra.'],

            // Validação de estado
            ['estado', 'in', 'range' => ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO']],

            // Validação de email
            ['email', 'email'],

            // Validação de URL
            ['site', 'url'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'nome_loja' => 'Nome da Loja',
            'nome_fantasia' => 'Nome Fantasia',
            'razao_social' => 'Razão Social',
            'cpf_cnpj' => 'CPF/CNPJ',
            'inscricao_estadual' => 'Inscrição Estadual',
            'inscricao_municipal' => 'Inscrição Municipal',
            'telefone' => 'Telefone',
            'celular' => 'Celular',
            'email' => 'E-mail',
            'site' => 'Site',
            'cep' => 'CEP',
            'logradouro' => 'Logradouro',
            'numero' => 'Número',
            'complemento' => 'Complemento',
            'bairro' => 'Bairro',
            'cidade' => 'Cidade',
            'estado' => 'Estado',
            'codigo_municipio_ibge' => 'Código IBGE',
            'logo_path' => 'Logo',
            'pix_chave' => 'Chave PIX',
            'pix_nome' => 'Nome (Recebedor PIX)',
            'pix_cidade' => 'Cidade (Recebedor PIX)',
            'aparencia_tema' => 'Tema do Sistema',
            'aparencia_cor_primaria' => 'Cor Primária Customizada',
            'aparencia_cor_secundaria' => 'Cor Secundária Customizada',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ];
    }

    /**
     * Retorna endereço completo formatado
     * @return string
     */
    public function getEnderecoCompleto()
    {
        $partes = array_filter([
            $this->logradouro,
            $this->numero ? "nº {$this->numero}" : null,
            $this->complemento,
            $this->bairro,
            $this->cidade,
            $this->estado,
            $this->cep ? "CEP: {$this->cep}" : null,
        ]);

        return implode(', ', $partes);
    }

    /**
     * Formata CPF/CNPJ para exibição
     * @return string
     */
    public function getCpfCnpjFormatado()
    {
        $numeros = preg_replace('/[^0-9]/', '', $this->cpf_cnpj);

        if (strlen($numeros) === 11) {
            // CPF: 000.000.000-00
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $numeros);
        } elseif (strlen($numeros) === 14) {
            // CNPJ: 00.000.000/0000-00
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $numeros);
        }

        return $this->cpf_cnpj;
    }

    /**
     * Formata telefone para exibição
     * @return string
     */
    public function getTelefoneFormatado()
    {
        $numeros = preg_replace('/[^0-9]/', '', $this->telefone);

        if (strlen($numeros) === 11) {
            // Celular: (00) 00000-0000
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $numeros);
        } elseif (strlen($numeros) === 10) {
            // Fixo: (00) 0000-0000
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $numeros);
        }

        return $this->telefone;
    }

    /**
     * Retorna a lista de temas pré-programados com suas respectivas escalas de cores.
     * @return array
     */
    public static function getTemasDisponiveis()
    {
        return [
            'azul' => [
                'nome' => 'Ocean Blue',
                'cores' => [
                    '50' => '#f0f7ff',
                    '100' => '#e0effe',
                    '200' => '#bae0fd',
                    '300' => '#7cc8fb',
                    '400' => '#38aaf7',
                    '500' => '#0e8ce9',
                    '600' => '#026ec7',
                    '700' => '#0358a1',
                    '800' => '#074b85',
                    '900' => '#0c3f6e',
                    '950' => '#082849',
                ]
            ],
            'verde' => [
                'nome' => 'Forest Green',
                'cores' => [
                    '50' => '#f0vdf4', // fallback safe
                    '50' => '#f0fdf4',
                    '100' => '#dcfce7',
                    '200' => '#bbf7d0',
                    '300' => '#86efac',
                    '400' => '#4ade80',
                    '500' => '#10b981',
                    '600' => '#059669',
                    '700' => '#047857',
                    '800' => '#065f46',
                    '900' => '#064e3b',
                    '950' => '#022c22',
                ]
            ],
            'roxo' => [
                'nome' => 'Purple Sunset',
                'cores' => [
                    '50' => '#faf5ff',
                    '100' => '#f3e8ff',
                    '200' => '#e9d5ff',
                    '300' => '#d8b4fe',
                    '400' => '#c084fc',
                    '500' => '#8b5cf6',
                    '600' => '#7c3aed',
                    '700' => '#6d28d9',
                    '800' => '#5b21b6',
                    '900' => '#4c1d95',
                    '950' => '#2e1065',
                ]
            ],
            'laranja' => [
                'nome' => 'Sunset Orange',
                'cores' => [
                    '50' => '#fff7ed',
                    '100' => '#ffedd5',
                    '200' => '#fed7aa',
                    '300' => '#fdba74',
                    '400' => '#fb923c',
                    '500' => '#f97316',
                    '600' => '#ea580c',
                    '700' => '#c2410c',
                    '800' => '#9a3412',
                    '900' => '#7c2d12',
                    '950' => '#431407',
                ]
            ],
            'rosa' => [
                'nome' => 'Rose Premium',
                'cores' => [
                    '50' => '#fff1f2',
                    '100' => '#ffe4e6',
                    '200' => '#fecdd3',
                    '300' => '#fda4af',
                    '400' => '#fb7185',
                    '500' => '#f43f5e',
                    '600' => '#e11d48',
                    '700' => '#be123c',
                    '800' => '#9f1239',
                    '900' => '#881337',
                    '950' => '#4c0519',
                ]
            ],
            'dark' => [
                'nome' => 'Dark Slate',
                'cores' => [
                    '50' => '#f8fafc',
                    '100' => '#f1f5f9',
                    '200' => '#e2e8f0',
                    '300' => '#cbd5e1',
                    '400' => '#94a3b8',
                    '500' => '#64748b',
                    '600' => '#475569',
                    '700' => '#334155',
                    '800' => '#1e293b',
                    '900' => '#0f172a',
                    '950' => '#020617',
                ]
            ]
        ];
    }

    /**
     * Retorna a escala de cores resolvida do tema ativo.
     * @return array
     */
    public function getEscalaCores()
    {
        $temas = self::getTemasDisponiveis();
        $temaAtivo = $this->aparencia_tema ?: 'azul';

        if ($temaAtivo !== 'customizado' && isset($temas[$temaAtivo])) {
            return $temas[$temaAtivo]['cores'];
        }

        // Se for customizado ou não encontrado, gera a escala baseada na cor primária fornecida (ou azul como fallback)
        $corPrimaria = $this->aparencia_cor_primaria ?: '#0e8ce9';
        
        return [
            '50' => '#f0f7ff',
            '100' => '#e0effe',
            '200' => '#bae0fd',
            '300' => '#7cc8fb',
            '400' => '#38aaf7',
            '500' => $corPrimaria,
            '600' => $this->aparencia_cor_secundaria ?: $corPrimaria,
            '700' => $this->aparencia_cor_secundaria ?: $corPrimaria,
            '800' => '#074b85',
            '900' => '#0c3f6e',
            '950' => '#082849',
        ];
    }
}
