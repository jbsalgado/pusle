<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Termos de Uso e Serviço — Pulse SaaS';
?>

<div class="min-h-screen bg-slate-900 text-slate-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto bg-slate-800/80 border border-slate-700/80 rounded-3xl p-6 sm:p-10 shadow-2xl backdrop-blur-xl">
        
        <!-- Header -->
        <div class="border-b border-slate-700/70 pb-8 mb-8 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/10 text-purple-400 text-xs font-bold uppercase tracking-wider mb-2">
                    <span>📜 Termos de Serviço da Plataforma</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">Termos de Uso</h1>
                <p class="text-sm text-slate-400 mt-1">Última atualização: 19 de Setembro de 2026</p>
            </div>
            <a href="<?= Url::to(['/site/index']) ?>" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold transition shadow">
                ← Voltar ao Início
            </a>
        </div>

        <!-- Conteúdo -->
        <div class="prose prose-invert max-w-none space-y-8 text-slate-300 leading-relaxed text-sm sm:text-base">
            
            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">1.</span> Aceitação dos Termos
                </h2>
                <p class="mt-2">
                    Ao criar uma conta, acessar ou utilizar o <strong>Pulse SaaS</strong> (software como serviço fornecido por <strong>Only-code</strong>, CNPJ 47.037.952/0001-43), você declara ter lido, compreendido e concordado integralmente com estes Termos de Uso e com nossa <a href="<?= Url::to(['/site/politica-privacidade']) ?>" class="text-purple-400 hover:underline">Política de Privacidade</a>. Se você não concorda com qualquer disposição, não deve utilizar os serviços.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">2.</span> Descrição dos Serviços
                </h2>
                <p class="mt-2">
                    O Pulse é uma plataforma tecnológica de gestão comercial multitenant que inclui PDV, emissão de documentos fiscais, controle de estoque, catálogos digitais, estúdio de marketing para geração de cards e vídeos de produtos e integração para publicação automatizada em redes sociais (Meta — Facebook/Instagram e TikTok).
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">3.</span> Uso das Integrações com Redes Sociais
                </h2>
                <p class="mt-2">
                    Ao utilizar as ferramentas de publicação social do Pulse:
                </p>
                <ul class="list-disc list-inside mt-2 space-y-2 text-slate-300 ml-2">
                    <li>Você declara ser o legítimo titular ou ter autorização expressa para administrar as Páginas do Facebook, contas do Instagram e perfis do TikTok conectados.</li>
                    <li>Você se compromete a respeitar integralmente as <strong>Políticas da Plataforma Meta</strong>, os <strong>Padrões da Comunidade do Facebook/Instagram</strong> e os <strong>Termos do TikTok</strong>.</li>
                    <li>É expressamente proibido publicar conteúdo falso, enganoso, discriminatório, que viole direitos autorais de terceiros, ou que promova produtos/serviços ilegais.</li>
                    <li>O Pulse fornece a infraestrutura técnica para despacho de mídias, mas a responsabilidade pelo conteúdo publicado recai integralmente sobre o lojista ou afiliado emitente.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">4.</span> Programa de Afiliados e Comissionamento
                </h2>
                <p class="mt-2">
                    Quando aplicável, os links promocionais gerados com parâmetros de afiliados (`ref`) destinam-se exclusivamente à correta atribuição de vendas e comissões da respectiva loja. É vedada qualquer prática de spam, tráfego artificial, fraude de cliques ou práticas desleais de publicidade.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">5.</span> Disponibilidade e Nível de Serviço
                </h2>
                <p class="mt-2">
                    Empregamos esforços comercialmente razoáveis para garantir alta disponibilidade da plataforma. Não obstante, o serviço pode sofrer interrupções momentâneas para manutenções programadas, instabilidades de redes de telecomunicações ou alterações/quedas de APIs de provedores externos (Meta, TikTok, etc.), sobre as quais não recai responsabilidade indenizatória.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">6.</span> Rescisão e Encerramento de Contas
                </h2>
                <p class="mt-2">
                    O lojista ou usuário pode encerrar o uso da plataforma a qualquer momento. A Only-code reserva-se o direito de suspender ou rescindir o acesso de qualquer usuário que viole estes termos, pratique fraudes ou desrespeite os limites técnicos da API.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-purple-400">7.</span> Foro e Legislação Aplicável
                </h2>
                <p class="mt-2">
                    Estes Termos são regidos pelas leis da República Federativa do Brasil. Fica eleito o Foro da Comarca de Recife, Estado de Pernambuco, como competente para dirimir quaisquer dúvidas ou litígios decorrentes deste instrumento.
                </p>
            </section>

        </div>

        <!-- Footer -->
        <div class="mt-12 pt-6 border-t border-slate-700/70 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Only-code. Todos os direitos reservados. Pulse SaaS &bull; 
            <a href="<?= Url::to(['/site/politica-privacidade']) ?>" class="text-purple-400 hover:underline">Política de Privacidade</a> &bull; 
            <a href="<?= Url::to(['/site/exclusao-dados']) ?>" class="text-purple-400 hover:underline">Exclusão de Dados</a>
        </div>

    </div>
</div>
