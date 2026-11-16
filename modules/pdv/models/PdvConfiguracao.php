<?php

namespace app\modules\pdv\models;

use yii\db\ActiveRecord;

/**
 * Configurações de Leitor de Código de Barras e Balança do PDV por Loja.
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $etiqueta_prefixo
 * @property int $etiqueta_tamanho_codigo
 * @property string $etiqueta_tipo_valor PESO|PRECO
 * @property string $balanca_protocolo TOLEDO|FILIZOLA|URANO|ELGIN
 * @property int $balanca_baud_rate
 * @property bool $imprimir_cupom_auto
 * @property bool $som_leitor_ativo
 * @property string $data_atualizacao
 */
class PdvConfiguracao extends ActiveRecord
{
    const TIPO_VALOR_PESO = 'PESO';
    const TIPO_VALOR_PRECO = 'PRECO';

    public static function tableName()
    {
        return 'prest_pdv_configuracoes';
    }

    public function rules()
    {
        return [
            [['usuario_id'], 'required'],
            [['usuario_id'], 'string'],
            [['etiqueta_prefixo'], 'string', 'max' => 5],
            [['etiqueta_prefixo'], 'default', 'value' => '2'],
            [['etiqueta_tamanho_codigo'], 'integer', 'min' => 4, 'max' => 6],
            [['etiqueta_tamanho_codigo'], 'default', 'value' => 5],
            [['etiqueta_tipo_valor'], 'in', 'range' => [self::TIPO_VALOR_PESO, self::TIPO_VALOR_PRECO]],
            [['etiqueta_tipo_valor'], 'default', 'value' => self::TIPO_VALOR_PESO],
            [['balanca_protocolo'], 'in', 'range' => ['TOLEDO', 'FILIZOLA', 'URANO', 'ELGIN']],
            [['balanca_protocolo'], 'default', 'value' => 'TOLEDO'],
            [['balanca_baud_rate'], 'integer'],
            [['balanca_baud_rate'], 'default', 'value' => 9600],
            [['imprimir_cupom_auto', 'som_leitor_ativo'], 'boolean'],
            [['imprimir_cupom_auto'], 'default', 'value' => false],
            [['som_leitor_ativo'], 'default', 'value' => true],
        ];
    }

    /**
     * Retorna a configuração da loja ou cria valores padrão em memória caso a tabela/registro ainda não exista.
     */
    public static function obterOuPadrao($usuarioId)
    {
        try {
            $config = self::findOne(['usuario_id' => $usuarioId]);
            if ($config) {
                return $config;
            }
        } catch (\Throwable $e) {
            // Caso a migration ainda não tenha sido aplicada em algum ambiente, retorna defaults seguros
        }

        $padrao = new self();
        $padrao->usuario_id = $usuarioId;
        $padrao->etiqueta_prefixo = '2';
        $padrao->etiqueta_tamanho_codigo = 5;
        $padrao->etiqueta_tipo_valor = self::TIPO_VALOR_PESO;
        $padrao->balanca_protocolo = 'TOLEDO';
        $padrao->balanca_baud_rate = 9600;
        $padrao->imprimir_cupom_auto = false;
        $padrao->som_leitor_ativo = true;
        return $padrao;
    }
}

