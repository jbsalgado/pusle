<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\marketplace\models\MarketplaceConfig;

$this->title = 'Mapeamento de Categorias de Marketplaces';
?>

<div class="marketplace-categoria-map-index">
    <div class="page-header" style="margin-bottom: 24px;">
        <h1><i class="fa fa-sitemap" style="color: #6C63FF;"></i> <?= Html::encode($this->title) ?></h1>
        <p class="text-muted">Vincule as categorias de produtos da sua loja com as categorias oficiais da Shopee, Mercado Livre, Magalu e Temu para publicação sem erros.</p>
    </div>

    <!-- Abas de Marketplaces -->
    <div class="nav-tabs-custom" style="background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 16px; margin-bottom: 24px;">
        <ul class="nav nav-pills">
            <?php foreach (MarketplaceConfig::getMarketplacesDisponiveis() as $key => $nome): ?>
                <li class="<?= $marketplace === $key ? 'active' : '' ?>">
                    <a href="<?= Url::to(['index', 'marketplace' => $key]) ?>" style="font-weight: 600;">
                        <i class="fa fa-store"></i> <?= Html::encode($nome) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="box box-primary" style="background: #fff; border-radius: 8px; padding: 20px;">
        <div class="box-header with-border" style="margin-bottom: 16px;">
            <h3 class="box-title">Categorias Internas da Loja (<?= count($categorias) ?> encontradas)</h3>
        </div>

        <div class="box-body table-responsive no-padding">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th style="width: 30%;">Categoria no Pulse ERP</th>
                        <th style="width: 25%;">ID Categoria no <?= Html::encode(MarketplaceConfig::getMarketplacesDisponiveis()[$marketplace] ?? $marketplace) ?></th>
                        <th style="width: 30%;">Nome / Caminho da Categoria</th>
                        <th style="width: 15%; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="padding: 30px;">
                                Nenhuma categoria cadastrada no Pulse ERP. Cadastre categorias em Vendas ➔ Categorias.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $cat): ?>
                            <?php $map = $mapeamentos[$cat->id] ?? null; ?>
                            <tr id="row-<?= $cat->id ?>">
                                <td>
                                    <b><?= Html::encode($cat->nome) ?></b>
                                </td>
                                <td>
                                    <input type="text" 
                                           id="cat-id-<?= $cat->id ?>" 
                                           class="form-control input-sm" 
                                           value="<?= Html::encode($map ? $map->marketplace_categoria_id : '') ?>" 
                                           placeholder="Ex: 100045">
                                </td>
                                <td>
                                    <input type="text" 
                                           id="cat-nome-<?= $cat->id ?>" 
                                           class="form-control input-sm" 
                                           value="<?= Html::encode($map ? $map->marketplace_categoria_nome : '') ?>" 
                                           placeholder="Ex: Roupas Femininas > Vestidos">
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" 
                                            class="btn btn-sm btn-success" 
                                            onclick="salvarMapeamento('<?= $cat->id ?>', '<?= $marketplace ?>')">
                                        <i class="fa fa-save"></i> Salvar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function salvarMapeamento(categoriaId, marketplace) {
    var catId = document.getElementById('cat-id-' + categoriaId).value;
    var catNome = document.getElementById('cat-nome-' + categoriaId).value;

    if (!catId) {
        alert('Por favor, informe o ID da categoria no marketplace.');
        return;
    }

    var formData = new FormData();
    formData.append('categoria_id', categoriaId);
    formData.append('marketplace', marketplace);
    formData.append('marketplace_categoria_id', catId);
    formData.append('marketplace_categoria_nome', catNome);
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('<?= Url::to(['save']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Mapeamento salvo com sucesso!');
        } else {
            alert('❌ Erro: ' + (data.message || 'Falha ao salvar.'));
        }
    })
    .catch(e => {
        alert('❌ Erro de comunicação com o servidor.');
    });
}
</script>
