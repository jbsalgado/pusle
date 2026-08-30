<?php

namespace app\modules\marketplace\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Usuario;
use app\modules\vendas\models\Categoria;

/**
 * Model: MarketplaceCategoriaMap - De-Para de Categorias Internas x Marketplace
 *
 * @property int $id
 * @property string $categoria_id
 * @property string $marketplace
 * @property string $marketplace_categoria_id
 * @property string|null $marketplace_categoria_nome
 * @property array $atributos_obrigatorios
 * @property array $atributos_valores
 * @property string $usuario_id
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @property Categoria $categoria
 * @property Usuario $usuario
 */
class MarketplaceCategoriaMap extends ActiveRecord
{
    public static function tableName()
    {
        return 'prest_marketplace_categoria_map';
    }

    public function rules()
    {
        return [
            [['categoria_id', 'marketplace', 'marketplace_categoria_id', 'usuario_id'], 'required'],
            [['categoria_id', 'usuario_id'], 'string'],
            [['marketplace'], 'string', 'max' => 50],
            [['marketplace_categoria_id'], 'string', 'max' => 100],
            [['marketplace_categoria_nome'], 'string', 'max' => 255],
            [['atributos_obrigatorios', 'atributos_valores'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'categoria_id' => 'Categoria Interna',
            'marketplace' => 'Marketplace',
            'marketplace_categoria_id' => 'ID Categoria no Marketplace',
            'marketplace_categoria_nome' => 'Nome da Categoria no Canal',
            'atributos_obrigatorios' => 'Atributos Obrigatórios',
            'atributos_valores' => 'Valores Padrão de Atributos',
        ];
    }

    public function getCategoria()
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
