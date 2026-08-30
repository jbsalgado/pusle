<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $model->isNewRecord ? 'Novo Plano' : 'Editar Plano: ' . htmlspecialchars($model->nome) ?> | PULSE SAAS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #0D0E1F;
            --bg-card: #141627;
            --bg-input: #1C1E35;
            --primary: #6C63FF;
            --primary-light: rgba(108,99,255,0.12);
            --text: #F0F0FF;
            --text-muted: #7A7998;
            --border: rgba(255,255,255,0.07);
            --green: #43E97B;
            --yellow: #FFD166;
            --red: #FF6584;
            --blue: #43AFFF;
            --radius: 14px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; padding: 40px 20px; }
        .form-box { max-width: 700px; margin: auto; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; }
        h1 { font-size: 22px; font-weight: 800; margin-bottom: 24px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
        .form-control { width: 100%; background: var(--bg-input); border: 1px solid var(--border); color: var(--text); padding: 10px 14px; border-radius: 8px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: var(--primary); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; }
        .btn-success { background: var(--green); color: #0D0E1F; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
    </style>
</head>
<body>
<div class="form-box">
    <h1><i class="fa fa-tags" style="color: var(--primary);"></i> <?= $model->isNewRecord ? 'Criar Novo Plano' : 'Editar Plano' ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <div class="form-group">
        <label>Nome do Plano</label>
        <?= $form->field($model, 'nome')->textInput(['class' => 'form-control', 'placeholder' => 'Ex: Plano Pro'])->label(false) ?>
    </div>

    <div class="form-group">
        <label>Descrição</label>
        <?= $form->field($model, 'descricao')->textarea(['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Benefícios do plano...'])->label(false) ?>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label>Mensalidade Fixa (R$)</label>
            <?= $form->field($model, 'valor_mensalidade')->textInput(['type' => 'number', 'step' => '0.01', 'class' => 'form-control'])->label(false) ?>
        </div>
        <div class="form-group">
            <label>Comissão Catálogo / Split (%)</label>
            <?= $form->field($model, 'percentual_comissao_catalogo')->textInput(['type' => 'number', 'step' => '0.01', 'class' => 'form-control'])->label(false) ?>
        </div>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label>Comissão Marketplaces (%)</label>
            <?= $form->field($model, 'percentual_comissao_marketplace')->textInput(['type' => 'number', 'step' => '0.01', 'class' => 'form-control'])->label(false) ?>
        </div>
        <div class="form-group">
            <label>Pedidos Inclusos / Mês</label>
            <?= $form->field($model, 'limite_pedidos_inclusos')->textInput(['type' => 'number', 'class' => 'form-control'])->label(false) ?>
        </div>
    </div>

    <div class="grid-2">
        <div class="form-group">
            <label>Valor por Pedido Excedente (R$)</label>
            <?= $form->field($model, 'valor_pedido_excedente')->textInput(['type' => 'number', 'step' => '0.01', 'class' => 'form-control'])->label(false) ?>
        </div>
        <div class="form-group" style="display: flex; align-items: center; gap: 20px; padding-top: 25px;">
            <?= $form->field($model, 'destaque')->checkbox(['label' => 'Destacar como Mais Popular']) ?>
            <?= $form->field($model, 'ativo')->checkbox(['label' => 'Plano Ativo']) ?>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
        <a href="/admin/financeiro/planos" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Cancelar</a>
        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Salvar Plano</button>
    </div>

    <?php ActiveForm::end(); ?>
</div>
</body>
</html>
