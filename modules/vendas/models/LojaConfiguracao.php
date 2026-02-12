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
}
