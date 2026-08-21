const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

async function main() {
    const args = process.argv.slice(2);
    if (args.length < 1) {
        console.error('Uso: node card_renderer.js <caminho_json_payload> [caminho_saida_png]');
        process.exit(1);
    }

    const payloadPath = args[0];
    if (!fs.existsSync(payloadPath)) {
        console.error(`Arquivo de payload não encontrado: ${payloadPath}`);
        process.exit(1);
    }

    const payloadRaw = fs.readFileSync(payloadPath, 'utf8');
    const data = JSON.parse(payloadRaw);

    const formato = (data.formato || 'feed').toLowerCase();
    const isStories = formato === 'stories';
    const width = 1080;
    const height = isStories ? 1920 : 1080;
    const outputPath = args[1] || data.outputPath || path.join(__dirname, `card_${Date.now()}.png`);

    const htmlContent = generateHtmlTemplate(data, isStories);

    let browser;
    try {
        const launchOptions = {
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-crash-reporter',
                '--disable-gpu',
                '--user-data-dir=/tmp/puppeteer_user_data',
                '--font-render-hinting=medium'
            ],
            headless: 'new'
        };

        let executablePath = process.env.PUPPETEER_EXECUTABLE_PATH;

        if (!executablePath) {
            const possiblePaths = [
                '/srv/http/.cache/puppeteer',
                '/root/.cache/puppeteer',
                path.join(process.env.HOME || '', '.cache/puppeteer')
            ];

            for (const cacheDir of possiblePaths) {
                if (fs.existsSync(cacheDir)) {
                    try {
                        const findExecutable = (dir, targetName) => {
                            const files = fs.readdirSync(dir);
                            for (const file of files) {
                                const fullPath = path.join(dir, file);
                                const stat = fs.statSync(fullPath);
                                if (stat.isDirectory()) {
                                    const found = findExecutable(fullPath, targetName);
                                    if (found) return found;
                                } else if (file === targetName && (stat.mode & 0o111)) {
                                    return fullPath;
                                }
                            }
                            return null;
                        };

                        const foundHeadless = findExecutable(cacheDir, 'chrome-headless-shell');
                        if (foundHeadless) {
                            executablePath = foundHeadless;
                            break;
                        }

                        const foundChrome = findExecutable(cacheDir, 'chrome');
                        if (foundChrome) {
                            executablePath = foundChrome;
                            break;
                        }
                    } catch (e) {}
                }
            }
        }

        if (executablePath) {
            launchOptions.executablePath = executablePath;
        }

        browser = await puppeteer.launch(launchOptions);
        const page = await browser.newPage();

        await page.setViewport({
            width: width,
            height: height,
            deviceScaleFactor: 2
        });

        await page.setContent(htmlContent, { waitUntil: ['domcontentloaded', 'networkidle0'] });

        await page.evaluate(async () => {
            if (document.fonts) {
                await document.fonts.ready;
            }
        });

        const outputDir = path.dirname(outputPath);
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        await page.screenshot({
            path: outputPath,
            type: 'png',
            fullPage: false,
            omitBackground: false
        });

        console.log(JSON.stringify({
            success: true,
            outputPath: outputPath,
            formato: formato,
            template: data.template || 'modern_dark',
            corTema: data.corTema || 'dark',
            width: width * 2,
            height: height * 2
        }));

    } catch (err) {
        console.error(JSON.stringify({
            success: false,
            error: err.message,
            stack: err.stack
        }));
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

/**
 * Paletas de Cores Pré-definidas
 */
const COLOR_PALETTES = {
    dark: {
        primary: '#0E8CE9',
        secondary: '#026EC7',
        bgGradient: 'linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%)',
        cardBg: 'rgba(255, 255, 255, 0.06)',
        infoBg: 'rgba(15, 23, 42, 0.88)',
        textColor: '#FFFFFF',
        mutedText: '#94A3B8',
        accent: '#38BDF8',
        badgeBg: 'linear-gradient(135deg, #FF3B30 0%, #E11D48 100%)',
        border: 'rgba(255, 255, 255, 0.15)'
    },
    ocean: {
        primary: '#0284C7',
        secondary: '#0369A1',
        bgGradient: 'linear-gradient(135deg, #075985 0%, #0C4A6E 50%, #082F49 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(8, 47, 73, 0.90)',
        textColor: '#FFFFFF',
        mutedText: '#BAE6FD',
        accent: '#38BDF8',
        badgeBg: 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)',
        border: 'rgba(186, 230, 253, 0.2)'
    },
    emerald: {
        primary: '#10B981',
        secondary: '#059669',
        bgGradient: 'linear-gradient(135deg, #064E3B 0%, #065F46 50%, #022C22 100%)',
        cardBg: 'rgba(255, 255, 255, 0.07)',
        infoBg: 'rgba(2, 44, 34, 0.90)',
        textColor: '#FFFFFF',
        mutedText: '#A7F3D0',
        accent: '#34D399',
        badgeBg: 'linear-gradient(135deg, #F43F5E 0%, #E11D48 100%)',
        border: 'rgba(167, 243, 208, 0.2)'
    },
    purple: {
        primary: '#8B5CF6',
        secondary: '#7C3AED',
        bgGradient: 'linear-gradient(135deg, #2E1065 0%, #4C1D95 50%, #1E1B4B 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(30, 27, 75, 0.90)',
        textColor: '#FFFFFF',
        mutedText: '#DDD6FE',
        accent: '#A78BFA',
        badgeBg: 'linear-gradient(135deg, #EC4899 0%, #D946EF 100%)',
        border: 'rgba(221, 214, 254, 0.2)'
    },
    sunset: {
        primary: '#F97316',
        secondary: '#EA580C',
        bgGradient: 'linear-gradient(135deg, #431407 0%, #7C2D12 50%, #2A0800 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(42, 8, 0, 0.90)',
        textColor: '#FFFFFF',
        mutedText: '#FFEDD5',
        accent: '#FB923C',
        badgeBg: 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)',
        border: 'rgba(255, 237, 213, 0.2)'
    },
    rose: {
        primary: '#F43F5E',
        secondary: '#E11D48',
        bgGradient: 'linear-gradient(135deg, #4C0519 0%, #881337 50%, #2B000D 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(43, 0, 13, 0.90)',
        textColor: '#FFFFFF',
        mutedText: '#FFE4E6',
        accent: '#FB7185',
        badgeBg: 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)',
        border: 'rgba(255, 228, 230, 0.2)'
    },
    gold: {
        primary: '#F59E0B',
        secondary: '#D97706',
        bgGradient: 'linear-gradient(135deg, #451A03 0%, #78350F 50%, #1C0A00 100%)',
        cardBg: 'rgba(255, 255, 255, 0.08)',
        infoBg: 'rgba(28, 10, 0, 0.90)',
        textColor: '#FFFFFF',
        mutedText: '#FEF3C7',
        accent: '#FBBF24',
        badgeBg: 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)',
        border: 'rgba(254, 243, 199, 0.2)'
    }
};

function resolvePalette(corTema, loja) {
    if (COLOR_PALETTES[corTema]) {
        return COLOR_PALETTES[corTema];
    }

    // Custom hex ou fallback loja
    const primary = (corTema && corTema.startsWith('#')) ? corTema : (loja.corPrimaria || '#0E8CE9');
    const secondary = loja.corSecundaria || primary;

    return {
        primary: primary,
        secondary: secondary,
        bgGradient: `linear-gradient(135deg, #0F172A 0%, ${primary}22 50%, #0F172A 100%)`,
        cardBg: 'rgba(255, 255, 255, 0.06)',
        infoBg: 'rgba(15, 23, 42, 0.88)',
        textColor: '#FFFFFF',
        mutedText: '#94A3B8',
        accent: primary,
        badgeBg: 'linear-gradient(135deg, #FF3B30 0%, #E11D48 100%)',
        border: 'rgba(255, 255, 255, 0.15)'
    };
}

function resolveBackgroundCss(fundoEstilo, palette, imagemFundoBase64) {
    if (imagemFundoBase64) {
        return `background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.85)), url("${imagemFundoBase64}") center/cover no-repeat;`;
    }

    switch (fundoEstilo) {
        case 'mesh':
            return `background: radial-gradient(at 0% 0%, ${palette.primary}AA 0px, transparent 50%),
                        radial-gradient(at 100% 0%, ${palette.secondary}AA 0px, transparent 50%),
                        radial-gradient(at 100% 100%, ${palette.primary}44 0px, transparent 50%),
                        radial-gradient(at 0% 100%, #0F172A 0px, transparent 50%), #0F172A;`;
        case 'geometric':
            return `background: ${palette.bgGradient};
                    background-image: radial-gradient(${palette.primary}33 1px, transparent 1px), radial-gradient(${palette.secondary}33 1px, #0F172A 1px);
                    background-size: 40px 40px; background-position: 0 0, 20px 20px;`;
        case 'dots':
            return `background: ${palette.bgGradient};
                    background-image: radial-gradient(rgba(255, 255, 255, 0.15) 1.5px, transparent 1.5px);
                    background-size: 24px 24px;`;
        case 'gradient':
        default:
            return `background: ${palette.bgGradient};`;
    }
}

function generateHtmlTemplate(data, isStories) {
    const templateName = (data.template || 'modern_dark').toLowerCase();
    const corTema = (data.corTema || 'dark').toLowerCase();
    const fundoEstilo = (data.fundoEstilo || 'gradient').toLowerCase();
    const imagemFundoBase64 = data.imagemFundoBase64 || null;

    const p = data.produto || {};
    const l = data.loja || {};
    const palette = resolvePalette(corTema, l);
    const bgCss = resolveBackgroundCss(fundoEstilo, palette, imagemFundoBase64);

    const nomeProduto = escapeHtml(p.nome || 'PRODUTO EM OFERTA');
    const marca = escapeHtml(p.marca || '');
    const precoOriginal = p.precoOriginal || '';
    const precoPromocional = p.precoPromocional || p.precoVenda || 'R$ 0,00';
    const emPromocao = !!p.emPromocao;
    const descontoPercentual = p.descontoPercentual || '';
    const badgeTexto = p.badgeTexto || (emPromocao ? `-${descontoPercentual}` : 'OFERTA ESPECIAL');
    const parcelamento = escapeHtml(p.parcelamento || '');
    const imagemProduto = p.imagemBase64 || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 24 24" fill="none" stroke="%23cccccc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';

    const nomeLoja = escapeHtml(l.nome || 'PULSE STORE');
    const logoLoja = l.logoBase64 || '';
    const telefone = escapeHtml(l.telefone || '');
    const site = escapeHtml(l.site || '');

    // Renders specific template style
    switch (templateName) {
        case 'vibrant_gradient':
            return renderVibrantGradientTemplate({ isStories, palette, bgCss, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site });
        case 'minimalist_light':
            return renderMinimalistLightTemplate({ isStories, palette, bgCss, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site });
        case 'neon_promo':
            return renderNeonPromoTemplate({ isStories, palette, bgCss, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site });
        case 'bold_banner':
            return renderBoldBannerTemplate({ isStories, palette, bgCss, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site });
        case 'modern_dark':
        default:
            return renderModernDarkTemplate({ isStories, palette, bgCss, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site });
    }
}

/* =========================================================================================================
 * TEMPLATE 1: MODERN DARK (Glassmorphism Padrão)
 * ========================================================================================================= */
function renderModernDarkTemplate(ctx) {
    const { isStories, palette, bgCss, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site } = ctx;
    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: ${isStories ? '1920px' : '1080px'};
            font-family: 'Inter', sans-serif; ${bgCss}
            color: ${palette.textColor}; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; position: relative;
        }
        .bg-glow-1 { position: absolute; top: -150px; right: -150px; width: 600px; height: 600px; background: radial-gradient(circle, ${palette.primary}66 0%, rgba(0,0,0,0) 70%); border-radius: 50%; filter: blur(60px); z-index: 1; }
        .bg-glow-2 { position: absolute; bottom: -150px; left: -150px; width: 700px; height: 700px; background: radial-gradient(circle, ${palette.secondary}55 0%, rgba(0,0,0,0) 70%); border-radius: 50%; filter: blur(80px); z-index: 1; }
        .card-container { position: relative; z-index: 2; width: 100%; height: 100%; padding: ${isStories ? '80px 60px 70px 60px' : '50px 60px'}; display: flex; flex-direction: column; justify-content: space-between; }
        .header { display: flex; align-items: center; justify-content: space-between; width: 100%; height: ${isStories ? '110px' : '90px'}; padding-bottom: 20px; border-bottom: 1px solid ${palette.border}; }
        .store-brand { display: flex; align-items: center; gap: 20px; }
        .store-logo { max-height: ${isStories ? '75px' : '65px'}; max-width: 220px; object-fit: contain; }
        .store-name { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '34px' : '30px'}; font-weight: 800; text-transform: uppercase; color: #FFFFFF; }
        .promo-badge { background: ${palette.badgeBg}; color: #FFFFFF; font-family: 'Outfit', sans-serif; font-weight: 900; font-size: ${isStories ? '28px' : '24px'}; padding: 10px 24px; border-radius: 50px; box-shadow: 0 8px 20px rgba(0,0,0,0.4); text-transform: uppercase; }
        .product-stage { position: relative; flex: 1; display: flex; align-items: center; justify-content: center; margin: ${isStories ? '40px 0' : '30px 0'}; }
        .image-card { position: relative; width: 100%; height: ${isStories ? '920px' : '520px'}; background: ${palette.cardBg}; backdrop-filter: blur(20px); border: 1px solid ${palette.border}; border-radius: 36px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; padding: 35px; overflow: hidden; }
        .product-image { max-width: 90%; max-height: 90%; object-fit: contain; filter: drop-shadow(0 15px 25px rgba(0,0,0,0.5)); }
        .brand-tag { position: absolute; top: 24px; left: 24px; background: rgba(15, 23, 42, 0.75); border: 1px solid ${palette.border}; color: ${palette.mutedText}; font-size: 20px; font-weight: 700; padding: 8px 20px; border-radius: 12px; text-transform: uppercase; }
        .info-card { background: ${palette.infoBg}; backdrop-filter: blur(25px); border: 1px solid ${palette.border}; border-radius: 32px; padding: ${isStories ? '36px 40px' : '30px 40px'}; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); display: flex; flex-direction: column; gap: 16px; }
        .product-title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '42px' : '36px'}; font-weight: 800; line-height: 1.25; color: #FFFFFF; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .price-section { display: flex; align-items: flex-end; justify-content: space-between; margin-top: 6px; }
        .original-price { font-size: ${isStories ? '26px' : '24px'}; color: ${palette.mutedText}; text-decoration: line-through; font-weight: 600; }
        .current-price-label { font-size: 18px; font-weight: 700; color: ${palette.accent}; text-transform: uppercase; letter-spacing: 1px; }
        .current-price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '64px' : '54px'}; font-weight: 900; color: ${palette.accent}; line-height: 1; letter-spacing: -1px; }
        .installment-badge { background: rgba(255,255,255,0.08); border: 1px solid ${palette.border}; color: ${palette.accent}; font-size: ${isStories ? '22px' : '20px'}; font-weight: 700; padding: 12px 24px; border-radius: 16px; align-self: flex-end; }
        .footer { display: flex; align-items: center; justify-content: space-between; margin-top: ${isStories ? '25px' : '15px'}; padding-top: 15px; border-top: 1px solid ${palette.border}; font-size: ${isStories ? '22px' : '20px'}; color: ${palette.mutedText}; font-weight: 600; }
        .cta-pill { background: ${palette.primary}; color: #FFFFFF; font-family: 'Outfit', sans-serif; font-size: ${isStories ? '22px' : '19px'}; font-weight: 800; padding: 10px 22px; border-radius: 12px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>
    <div class="card-container">
        <div class="header">
            <div class="store-brand">
                ${logoLoja ? `<img class="store-logo" src="${logoLoja}">` : ''}
                <div class="store-name">${nomeLoja}</div>
            </div>
            ${badgeTexto ? `<div class="promo-badge">${badgeTexto}</div>` : ''}
        </div>
        <div class="product-stage">
            <div class="image-card">
                ${marca ? `<div class="brand-tag">${marca}</div>` : ''}
                <img class="product-image" src="${imagemProduto}">
            </div>
        </div>
        <div class="info-card">
            <div class="product-title">${nomeProduto}</div>
            <div class="price-section">
                <div>
                    ${emPromocao && precoOriginal ? `<div class="original-price">De: ${precoOriginal}</div>` : ''}
                    <div class="current-price-label">${emPromocao ? 'Por apenas' : 'Preço de venda'}</div>
                    <div class="current-price">${precoPromocional}</div>
                </div>
                ${parcelamento ? `<div class="installment-badge">${parcelamento}</div>` : ''}
            </div>
        </div>
        <div class="footer">
            <div>📞 ${telefone || 'Consulte pelo WhatsApp'}</div>
            ${site ? `<div>🌐 ${site}</div>` : '<div class="cta-pill">PEÇA JÁ</div>'}
        </div>
    </div>
</body>
</html>`;
}

/* =========================================================================================================
 * TEMPLATE 2: VIBRANT GRADIENT
 * ========================================================================================================= */
function renderVibrantGradientTemplate(ctx) {
    const { isStories, palette, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site } = ctx;
    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;800;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: ${isStories ? '1920px' : '1080px'};
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, ${palette.primary} 0%, ${palette.secondary} 50%, #0F172A 100%);
            color: #FFFFFF; display: flex; flex-direction: column; justify-content: space-between; padding: ${isStories ? '70px 50px' : '45px 50px'}; position: relative; overflow: hidden;
        }
        .header { display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.25); backdrop-filter: blur(15px); padding: 20px 30px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.2); }
        .store-logo { max-height: 60px; }
        .store-name { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; text-transform: uppercase; }
        .badge { background: #FFD700; color: #000; font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 24px; padding: 8px 22px; border-radius: 40px; text-transform: uppercase; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .stage { flex: 1; display: flex; align-items: center; justify-content: center; margin: 25px 0; }
        .img-box { width: 100%; height: ${isStories ? '950px' : '530px'}; background: #FFFFFF; border-radius: 36px; display: flex; align-items: center; justify-content: center; padding: 40px; box-shadow: 0 30px 60px rgba(0,0,0,0.4); position: relative; }
        .img-box img { max-width: 90%; max-height: 90%; object-fit: contain; }
        .brand-badge { position: absolute; top: 20px; left: 20px; background: ${palette.primary}; color: #FFF; font-weight: 800; padding: 6px 16px; border-radius: 10px; font-size: 18px; text-transform: uppercase; }
        .footer-card { background: #FFFFFF; color: #0F172A; border-radius: 32px; padding: 35px 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); display: flex; flex-direction: column; gap: 15px; }
        .title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '40px' : '34px'}; font-weight: 900; line-height: 1.2; text-transform: uppercase; color: #0F172A; }
        .price-row { display: flex; align-items: flex-end; justify-content: space-between; }
        .price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '60px' : '52px'}; font-weight: 900; color: ${palette.secondary}; line-height: 1; }
        .orig-price { font-size: 22px; color: #64748B; text-decoration: line-through; font-weight: 600; }
        .installment { background: #F1F5F9; color: #334155; font-size: 18px; font-weight: 700; padding: 10px 20px; border-radius: 14px; }
        .contacts { display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.9); padding-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display:flex; align-items:center; gap:15px;">
            ${logoLoja ? `<img class="store-logo" src="${logoLoja}">` : ''}
            <div class="store-name">${nomeLoja}</div>
        </div>
        ${badgeTexto ? `<div class="badge">${badgeTexto}</div>` : ''}
    </div>
    <div class="stage">
        <div class="img-box">
            ${marca ? `<div class="brand-badge">${marca}</div>` : ''}
            <img src="${imagemProduto}">
        </div>
    </div>
    <div class="footer-card">
        <div class="title">${nomeProduto}</div>
        <div class="price-row">
            <div>
                ${emPromocao && precoOriginal ? `<div class="orig-price">De: ${precoOriginal}</div>` : ''}
                <div class="price">${precoPromocional}</div>
            </div>
            ${parcelamento ? `<div class="installment">${parcelamento}</div>` : ''}
        </div>
    </div>
    <div class="contacts">
        <div>📞 ${telefone || 'Atendimento WhatsApp'}</div>
        <div>🌐 ${site || 'Peça Online'}</div>
    </div>
</body>
</html>`;
}

/* =========================================================================================================
 * TEMPLATE 3: MINIMALIST LIGHT
 * ========================================================================================================= */
function renderMinimalistLightTemplate(ctx) {
    const { isStories, palette, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site } = ctx;
    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: ${isStories ? '1920px' : '1080px'};
            font-family: 'Inter', sans-serif; background: #F8FAFC; color: #0F172A;
            display: flex; flex-direction: column; justify-content: space-between; padding: ${isStories ? '80px 60px' : '50px 60px'};
        }
        .header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 25px; border-bottom: 2px solid #E2E8F0; }
        .store-name { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 800; letter-spacing: -0.5px; color: #0F172A; text-transform: uppercase; }
        .badge { background: #0F172A; color: #FFFFFF; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 22px; padding: 8px 20px; border-radius: 8px; text-transform: uppercase; }
        .stage { flex: 1; display: flex; align-items: center; justify-content: center; margin: 30px 0; }
        .img-card { width: 100%; height: ${isStories ? '940px' : '540px'}; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 24px; display: flex; align-items: center; justify-content: center; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); position: relative; }
        .img-card img { max-width: 90%; max-height: 90%; object-fit: contain; }
        .brand { position: absolute; top: 20px; left: 20px; color: #64748B; font-weight: 700; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
        .details { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 24px; padding: 35px 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 15px; }
        .title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '42px' : '36px'}; font-weight: 800; color: #0F172A; line-height: 1.25; }
        .price-section { display: flex; align-items: flex-end; justify-content: space-between; }
        .price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '64px' : '54px'}; font-weight: 800; color: ${palette.primary}; line-height: 1; }
        .orig-price { font-size: 24px; color: #94A3B8; text-decoration: line-through; }
        .installment { font-size: 20px; font-weight: 600; color: #475569; background: #F1F5F9; padding: 10px 20px; border-radius: 12px; }
        .footer { display: flex; justify-content: space-between; font-size: 20px; color: #64748B; font-weight: 600; border-top: 1px solid #E2E8F0; pt: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display:flex; align-items:center; gap:20px;">
            ${logoLoja ? `<img style="max-height:60px;" src="${logoLoja}">` : ''}
            <div class="store-name">${nomeLoja}</div>
        </div>
        ${badgeTexto ? `<div class="badge">${badgeTexto}</div>` : ''}
    </div>
    <div class="stage">
        <div class="img-card">
            ${marca ? `<div class="brand">${marca}</div>` : ''}
            <img src="${imagemProduto}">
        </div>
    </div>
    <div class="details">
        <div class="title">${nomeProduto}</div>
        <div class="price-section">
            <div>
                ${emPromocao && precoOriginal ? `<div class="orig-price">De: ${precoOriginal}</div>` : ''}
                <div class="price">${precoPromocional}</div>
            </div>
            ${parcelamento ? `<div class="installment">${parcelamento}</div>` : ''}
        </div>
    </div>
    <div class="footer">
        <div>📱 ${telefone || 'WhatsApp'}</div>
        <div>🌐 ${site || 'Loja Online'}</div>
    </div>
</body>
</html>`;
}

/* =========================================================================================================
 * TEMPLATE 4: NEON PROMO
 * ========================================================================================================= */
function renderNeonPromoTemplate(ctx) {
    const { isStories, palette, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site } = ctx;
    const neonColor = palette.accent || '#00FF66';
    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;900&family=Inter:wght@600;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: ${isStories ? '1920px' : '1080px'};
            font-family: 'Inter', sans-serif; background: #050811; color: #FFFFFF;
            display: flex; flex-direction: column; justify-content: space-between; padding: ${isStories ? '70px 50px' : '45px 50px'}; position: relative;
        }
        .neon-border { position: absolute; inset: 20px; border: 2px solid ${neonColor}; border-radius: 30px; box-shadow: 0 0 25px ${neonColor}66, inset 0 0 15px ${neonColor}33; pointer-events: none; }
        .header { display: flex; justify-content: space-between; align-items: center; z-index: 2; padding: 20px 30px; }
        .store-name { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 900; text-transform: uppercase; color: #FFF; text-shadow: 0 0 10px ${neonColor}; }
        .badge { background: ${neonColor}; color: #000; font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 26px; padding: 10px 26px; border-radius: 50px; box-shadow: 0 0 20px ${neonColor}; text-transform: uppercase; }
        .stage { flex: 1; display: flex; align-items: center; justify-content: center; margin: 20px 0; z-index: 2; }
        .img-box { width: 100%; height: ${isStories ? '920px' : '520px'}; background: rgba(15, 23, 42, 0.9); border: 2px solid ${neonColor}88; border-radius: 32px; display: flex; align-items: center; justify-content: center; padding: 40px; box-shadow: 0 0 40px ${neonColor}33; position: relative; }
        .img-box img { max-width: 90%; max-height: 90%; object-fit: contain; filter: drop-shadow(0 0 20px rgba(255,255,255,0.2)); }
        .info-card { background: rgba(10, 15, 30, 0.95); border: 2px solid ${neonColor}; border-radius: 28px; padding: 30px 40px; z-index: 2; box-shadow: 0 0 30px ${neonColor}44; display: flex; flex-direction: column; gap: 15px; }
        .title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '42px' : '36px'}; font-weight: 900; text-transform: uppercase; line-height: 1.2; color: #FFF; }
        .price-row { display: flex; justify-content: space-between; align-items: flex-end; }
        .price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '66px' : '56px'}; font-weight: 900; color: ${neonColor}; text-shadow: 0 0 20px ${neonColor}AA; line-height: 1; }
        .orig-price { font-size: 24px; color: #94A3B8; text-decoration: line-through; }
        .installment { color: #FFF; font-size: 20px; font-weight: 700; border: 1px solid ${neonColor}; padding: 10px 20px; border-radius: 12px; background: ${neonColor}22; }
        .footer { display: flex; justify-content: space-between; z-index: 2; font-size: 20px; font-weight: 700; color: #94A3B8; padding: 0 20px; }
    </style>
</head>
<body>
    <div class="neon-border"></div>
    <div class="header">
        <div style="display:flex; align-items:center; gap:15px;">
            ${logoLoja ? `<img style="max-height:55px;" src="${logoLoja}">` : ''}
            <div class="store-name">${nomeLoja}</div>
        </div>
        ${badgeTexto ? `<div class="badge">${badgeTexto}</div>` : ''}
    </div>
    <div class="stage">
        <div class="img-box">
            <img src="${imagemProduto}">
        </div>
    </div>
    <div class="info-card">
        <div class="title">${nomeProduto}</div>
        <div class="price-row">
            <div>
                ${emPromocao && precoOriginal ? `<div class="orig-price">De: ${precoOriginal}</div>` : ''}
                <div class="price">${precoPromocional}</div>
            </div>
            ${parcelamento ? `<div class="installment">${parcelamento}</div>` : ''}
        </div>
    </div>
    <div class="footer">
        <div>🔥 ${telefone || 'PEDIDOS NO WHATSAPP'}</div>
        <div>🌐 ${site || 'OFERTA POR TEMPO LIMITADO'}</div>
    </div>
</body>
</html>`;
}

/* =========================================================================================================
 * TEMPLATE 5: BOLD BANNER
 * ========================================================================================================= */
function renderBoldBannerTemplate(ctx) {
    const { isStories, palette, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site } = ctx;
    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@800;900&family=Inter:wght@600;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px; height: ${isStories ? '1920px' : '1080px'};
            font-family: 'Inter', sans-serif; background: #0F172A; color: #FFFFFF;
            display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;
        }
        .top-banner { background: ${palette.primary}; padding: ${isStories ? '60px 50px 30px' : '35px 50px'}; display: flex; justify-content: space-between; align-items: center; }
        .store-title { font-family: 'Outfit', sans-serif; font-size: 34px; font-weight: 900; text-transform: uppercase; color: #FFF; }
        .badge { background: #FFF; color: ${palette.primary}; font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 26px; padding: 8px 24px; border-radius: 40px; text-transform: uppercase; }
        .stage { flex: 1; padding: 40px 60px; display: flex; items-center; justify-content: center; }
        .img-box { width: 100%; height: 100%; background: #FFF; border-radius: 32px; display: flex; align-items: center; justify-content: center; padding: 40px; position: relative; }
        .img-box img { max-width: 90%; max-height: 90%; object-fit: contain; }
        .bottom-banner { background: #FFFFFF; color: #0F172A; padding: ${isStories ? '50px 60px 70px' : '40px 60px'}; display: flex; flex-direction: column; gap: 15px; }
        .title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '44px' : '36px'}; font-weight: 900; text-transform: uppercase; line-height: 1.2; }
        .price-bar { display: flex; justify-content: space-between; align-items: center; background: #F1F5F9; padding: 20px 30px; border-radius: 20px; margin-top: 10px; }
        .price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '62px' : '52px'}; font-weight: 900; color: ${palette.secondary}; }
        .orig-price { font-size: 22px; color: #64748B; text-decoration: line-through; }
        .installment { font-size: 20px; font-weight: 700; color: #334155; }
        .contact-bar { background: #0F172A; color: #FFF; padding: 20px 60px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; }
    </style>
</head>
<body>
    <div class="top-banner">
        <div style="display:flex; align-items:center; gap:20px;">
            ${logoLoja ? `<img style="max-height:60px;" src="${logoLoja}">` : ''}
            <div class="store-title">${nomeLoja}</div>
        </div>
        ${badgeTexto ? `<div class="badge">${badgeTexto}</div>` : ''}
    </div>
    <div class="stage">
        <div class="img-box">
            <img src="${imagemProduto}">
        </div>
    </div>
    <div class="bottom-banner">
        <div class="title">${nomeProduto}</div>
        <div class="price-bar">
            <div>
                ${emPromocao && precoOriginal ? `<div class="orig-price">De: ${precoOriginal}</div>` : ''}
                <div class="price">${precoPromocional}</div>
            </div>
            ${parcelamento ? `<div class="installment">${parcelamento}</div>` : ''}
        </div>
    </div>
    <div class="contact-bar">
        <div>📞 ${telefone || 'CONSULTE NO WHATSAPP'}</div>
        <div>🌐 ${site || 'PEÇA AGORA'}</div>
    </div>
</body>
</html>`;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

main();
