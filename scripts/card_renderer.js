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
    const outputPath = args[1] || data.outputPath || path.join(__dirname, `card_${Date.now()}.webp`);

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
                '--disable-background-networking',
                '--disable-default-apps',
                '--disable-sync',
                '--disable-extensions',
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

        if (!executablePath) {
            const systemExecutables = [
                '/usr/bin/google-chrome-stable',
                '/usr/bin/google-chrome',
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
                '/snap/bin/chromium'
            ];

            for (const sysPath of systemExecutables) {
                if (fs.existsSync(sysPath)) {
                    executablePath = sysPath;
                    break;
                }
            }
        }

        if (executablePath) {
            launchOptions.executablePath = executablePath;
        }

        browser = await puppeteer.launch(launchOptions);
        const page = await browser.newPage();
        page.setDefaultTimeout(15000);

        const scaleFactor = data.scaleFactor !== undefined ? parseFloat(data.scaleFactor) : 1.0;

        await page.setViewport({
            width: width,
            height: height,
            deviceScaleFactor: scaleFactor
        });

        await page.setContent(htmlContent, { waitUntil: 'domcontentloaded', timeout: 10000 });

        // Aguarda fontes com timeout máximo de 2 segundos para nunca travar
        await Promise.race([
            page.evaluate(async () => {
                if (document.fonts) {
                    await document.fonts.ready;
                }
            }),
            new Promise(resolve => setTimeout(resolve, 2000))
        ]);

        const outputDir = path.dirname(outputPath);
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        const webpQuality = data.quality !== undefined ? parseInt(data.quality, 10) : 80;

        await page.screenshot({
            path: outputPath,
            type: 'webp',
            quality: webpQuality,
            fullPage: false,
            omitBackground: false
        });

        console.log(JSON.stringify({
            success: true,
            outputPath: outputPath,
            formato: formato,
            template: data.template || 'modern_dark',
            corTema: data.corTema || 'dark',
            width: Math.round(width * scaleFactor),
            height: Math.round(height * scaleFactor)
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

function detectImageDimensions(imgDataOrPath) {
    if (!imgDataOrPath || typeof imgDataOrPath !== 'string') return null;
    let buffer = null;
    try {
        if (imgDataOrPath.startsWith('data:image/')) {
            const base64Index = imgDataOrPath.indexOf(';base64,');
            if (base64Index !== -1) {
                buffer = Buffer.from(imgDataOrPath.substring(base64Index + 8), 'base64');
            }
        } else if (fs.existsSync(imgDataOrPath)) {
            buffer = fs.readFileSync(imgDataOrPath);
        }
    } catch (e) {
        return null;
    }
    if (!buffer || buffer.length < 24) return null;

    // JPEG detection
    if (buffer[0] === 0xFF && buffer[1] === 0xD8) {
        let i = 2;
        while (i < buffer.length - 8) {
            if (buffer[i] === 0xFF) {
                const marker = buffer[i + 1];
                if ((marker >= 0xC0 && marker <= 0xC3) || (marker >= 0xC9 && marker <= 0xCB)) {
                    const height = buffer.readUInt16BE(i + 5);
                    const width = buffer.readUInt16BE(i + 7);
                    return { width, height, aspectRatio: width / height };
                }
                const length = buffer.readUInt16BE(i + 2);
                i += 2 + length;
            } else {
                i++;
            }
        }
    }

    // PNG detection
    if (buffer[0] === 0x89 && buffer[1] === 0x50 && buffer[2] === 0x4E && buffer[3] === 0x47) {
        const width = buffer.readUInt32BE(16);
        const height = buffer.readUInt32BE(20);
        return { width, height, aspectRatio: width / height };
    }

    // WebP detection
    if (buffer.toString('ascii', 0, 4) === 'RIFF' && buffer.toString('ascii', 8, 12) === 'WEBP') {
        if (buffer.toString('ascii', 12, 16) === 'VP8 ') {
            const width = buffer.readUInt16LE(26) & 0x3fff;
            const height = buffer.readUInt16LE(28) & 0x3fff;
            return { width, height, aspectRatio: width / height };
        } else if (buffer.toString('ascii', 12, 16) === 'VP8L') {
            const b1 = buffer[21];
            const b2 = buffer[22];
            const b3 = buffer[23];
            const b4 = buffer[24];
            const width = 1 + (((b2 & 0x3f) << 8) | b1);
            const height = 1 + (((b4 & 0xf) << 10) | (b3 << 2) | ((b2 & 0xc0) >> 6));
            return { width, height, aspectRatio: width / height };
        }
    }

    return null;
}

function resolveProductImageStyles(opts) {
    const { enquadramentoFoto = 'auto', rotacaoFoto = 'auto', isStories, hasGrade, hasPromoCallout, imgDimensions } = opts;

    const stageWidth = 980;
    const stageHeight = isStories 
        ? (hasGrade ? (hasPromoCallout ? 820 : 850) : (hasPromoCallout ? 910 : 950)) 
        : (hasGrade ? (hasPromoCallout ? 430 : 460) : (hasPromoCallout ? 500 : 530));

    let rotation = 0;
    if (rotacaoFoto && rotacaoFoto !== 'auto') {
        const parsed = parseInt(rotacaoFoto, 10);
        rotation = parsed === 270 ? -90 : (isNaN(parsed) ? 0 : parsed);
    } else {
        // Auto / Inteligente:
        // Se o palco for horizontal (Feed: !isStories) e a foto for vertical (aspect ratio < 0.85)
        if (!isStories && imgDimensions && imgDimensions.aspectRatio < 0.85) {
            rotation = -90; // Deita calçados e produtos verticais
        }
    }

    let imgStyle = '';
    const isRotated = (rotation === 90 || rotation === -90 || rotation === 270);

    if (isRotated) {
        const fit = (enquadramentoFoto === 'cover') ? 'cover' : 'contain';
        imgStyle = `width:${stageHeight}px; height:${stageWidth}px; object-fit:${fit}; transform:rotate(${rotation}deg); transform-origin:center center; flex-shrink:0;`;
    } else if (rotation === 180) {
        const fit = (enquadramentoFoto === 'cover') ? 'cover' : 'contain';
        const w = (enquadramentoFoto === 'cover') ? '100%' : '90%';
        const h = (enquadramentoFoto === 'cover') ? '100%' : '90%';
        imgStyle = `width:${w}; height:${h}; object-fit:${fit}; transform:rotate(180deg); transform-origin:center center;`;
    } else {
        // rotation === 0
        if (enquadramentoFoto === 'cover') {
            imgStyle = `width:100%; height:100%; object-fit:cover;`;
        } else {
            // contain
            imgStyle = `max-width:90%; max-height:90%; object-fit:contain;`;
        }
    }

    return {
        rotation,
        isRotated,
        imgStyle,
        stageHeight
    };
}

/**
 * Renderiza a pílula / faixa da mensagem promocional customizada
 */
function renderPromoCallout(mensagemCard, templateName, palette, isStories) {
    if (!mensagemCard) return '';

    const fontSize = isStories ? '22px' : '16px';
    const padding = isStories ? '8px 22px' : '5px 16px';
    const iconSize = isStories ? '20px' : '15px';

    const hasLeadingEmoji = /^\p{Extended_Pictographic}/u.test(mensagemCard);
    const iconPrefix = hasLeadingEmoji ? '' : `<span style="font-size:${iconSize}; line-height:1;">⚡</span>`;

    switch (templateName) {
        case 'modern_dark':
            return `
            <div style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg, rgba(245,158,11,0.25) 0%, rgba(239,68,68,0.2) 100%); border:1.5px solid rgba(245,158,11,0.55); border-radius:50px; padding:${padding}; font-family:'Outfit',sans-serif; font-size:${fontSize}; font-weight:900; color:#FDE68A; text-transform:uppercase; letter-spacing:0.8px; box-shadow:0 4px 15px rgba(245,158,11,0.2); width:fit-content; margin-bottom:6px;">
                ${iconPrefix}<span>${mensagemCard}</span>
            </div>`;

        case 'vibrant_gradient':
            return `
            <div style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border:1.5px solid #F59E0B; border-radius:50px; padding:${padding}; font-family:'Outfit',sans-serif; font-size:${fontSize}; font-weight:900; color:#92400E; text-transform:uppercase; letter-spacing:0.5px; box-shadow:0 4px 12px rgba(245,158,11,0.25); width:fit-content; margin-bottom:6px;">
                ${iconPrefix}<span>${mensagemCard}</span>
            </div>`;

        case 'minimalist_light':
            return `
            <div style="display:inline-flex; align-items:center; gap:8px; background:#0F172A; border:1px solid #1E293B; border-radius:8px; padding:${padding}; font-family:'Outfit',sans-serif; font-size:${fontSize}; font-weight:800; color:#FFFFFF; text-transform:uppercase; letter-spacing:1px; box-shadow:0 4px 10px rgba(15,23,42,0.15); width:fit-content; margin-bottom:6px;">
                ${iconPrefix}<span>${mensagemCard}</span>
            </div>`;

        case 'neon_promo':
            const neonColor = palette.accent || '#00FF66';
            return `
            <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(0,0,0,0.75); border:1.5px solid ${neonColor}; border-radius:50px; padding:${padding}; font-family:'Outfit',sans-serif; font-size:${fontSize}; font-weight:900; color:#FFFFFF; text-transform:uppercase; letter-spacing:1px; text-shadow:0 0 8px ${neonColor}; box-shadow:0 0 15px ${neonColor}55; width:fit-content; margin-bottom:6px;">
                ${iconPrefix}<span>${mensagemCard}</span>
            </div>`;

        case 'bold_banner':
            return `
            <div style="display:inline-flex; align-items:center; gap:8px; background:${palette.secondary || '#EF4444'}; border-radius:8px; padding:${padding}; font-family:'Outfit',sans-serif; font-size:${fontSize}; font-weight:900; color:#FFFFFF; text-transform:uppercase; letter-spacing:0.8px; width:fit-content; margin-bottom:6px;">
                ${iconPrefix}<span>${mensagemCard}</span>
            </div>`;

        case 'full_bleed_banner':
        case 'full_bleed':
        default:
            return `
            <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(0,0,0,0.65); border:1.5px solid rgba(255,255,255,0.4); border-radius:50px; padding:${padding}; font-family:'Outfit',sans-serif; font-size:${fontSize}; font-weight:900; color:#FFFFFF; text-transform:uppercase; letter-spacing:0.8px; width:fit-content; margin-bottom:6px;">
                ${iconPrefix}<span>${mensagemCard}</span>
            </div>`;
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

    const gradeTamanhos = p.gradeTamanhos || [];
    const mesmoPreco = p.mesmoPreco !== undefined ? !!p.mesmoPreco : true;
    const hasGrade = Array.isArray(gradeTamanhos) && gradeTamanhos.length > 0;
    const priceLabel = p.priceLabel || (hasGrade && !mesmoPreco ? 'A partir de' : (emPromocao ? 'Por apenas' : 'Preço de venda'));

    const enquadramentoFoto = p.enquadramentoFoto || p.enquadramento_foto || data.enquadramentoFoto || data.enquadramento_foto || 'auto';
    const rotacaoFoto = p.rotacaoFoto || p.rotacao_foto || data.rotacaoFoto || data.rotacao_foto || 'auto';
    const mensagemCard = escapeHtml(String(p.mensagemCard || p.mensagem_card || data.mensagemCard || data.mensagem_card || '').trim());
    const hasPromoCallout = !!mensagemCard;
    const promoCalloutHtml = renderPromoCallout(mensagemCard, templateName, palette, isStories);

    const imgDimensions = detectImageDimensions(imagemProduto);
    const imgStyles = resolveProductImageStyles({
        enquadramentoFoto,
        rotacaoFoto,
        isStories,
        hasGrade,
        hasPromoCallout,
        imgDimensions
    });

    const ctx = {
        isStories,
        palette,
        bgCss,
        nomeProduto,
        marca,
        precoOriginal,
        precoPromocional,
        emPromocao,
        badgeTexto,
        parcelamento,
        imagemProduto,
        nomeLoja,
        logoLoja,
        telefone,
        site,
        gradeTamanhos,
        mesmoPreco,
        hasGrade,
        priceLabel,
        imgStyles,
        mensagemCard,
        hasPromoCallout,
        promoCalloutHtml
    };

    // Renders specific template style
    switch (templateName) {
        case 'full_bleed_banner':
        case 'full_bleed':
            return renderFullBleedBannerTemplate(ctx);
        case 'vibrant_gradient':
            return renderVibrantGradientTemplate(ctx);
        case 'minimalist_light':
            return renderMinimalistLightTemplate(ctx);
        case 'neon_promo':
            return renderNeonPromoTemplate(ctx);
        case 'bold_banner':
            return renderBoldBannerTemplate(ctx);
        case 'modern_dark':
        default:
            return renderModernDarkTemplate(ctx);
    }
}

/**
 * Renderiza bloco visual de Grade de Tamanhos e Preços da Matriz
 */
function renderTamanhosGradeSection(gradeTamanhos, mesmoPreco, palette, isStories, isLight = false) {
    if (!gradeTamanhos || !Array.isArray(gradeTamanhos) || gradeTamanhos.length === 0) {
        return '';
    }

    const maxExibir = isStories ? 20 : 14;
    const itensExibidos = gradeTamanhos.slice(0, maxExibir);
    const restantes = gradeTamanhos.length - maxExibir;

    const label = mesmoPreco 
        ? (itensExibidos.length === 1 ? 'Tamanho Disponível' : 'Tamanhos Disponíveis') 
        : (itensExibidos.length === 1 ? 'Opção & Preço' : 'Opções & Preços');

    // Para cartões claros (isLight = true), usamos fundo escuro (#0F172A) de altíssimo contraste com texto branco
    // Para cartões escuros (isLight = false), usamos glassmorphism translúcido suave com texto branco
    const pillBg = isLight ? '#0F172A' : 'rgba(255, 255, 255, 0.14)';
    const pillBorder = isLight ? '#1E293B' : 'rgba(255, 255, 255, 0.25)';
    const pillColor = '#FFFFFF';
    const accentColor = palette.accent || palette.primary || '#38BDF8';
    const labelColor = isLight ? '#475569' : accentColor;

    const pillsHtml = itensExibidos.map(item => {
        const tam = escapeHtml(String(item.tamanho || ''));
        if (mesmoPreco) {
            return `<span style="background:${pillBg}; border:1px solid ${pillBorder}; color:${pillColor}; font-weight:800; font-size:${isStories ? '20px' : '16px'}; padding:4px 12px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.15);">${tam}</span>`;
        } else {
            const preco = escapeHtml(String(item.preco_formatado || (`R$ ${parseFloat(item.preco || 0).toFixed(2).replace('.', ',')}`)));
            if (isLight) {
                const varColor = palette.secondary || palette.primary || '#0F172A';
                return `<span style="display:inline-flex; align-items:center; gap:6px; background:#F8FAFC; border:1.5px solid #CBD5E1; color:#0F172A; font-size:${isStories ? '18px' : '15px'}; padding:4px 10px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.08);"><b style="color:${varColor}; font-weight:900;">${tam}:</b> <span style="font-weight:800; color:#0F172A;">${preco}</span></span>`;
            } else {
                return `<span style="display:inline-flex; align-items:center; gap:6px; background:${pillBg}; border:1px solid ${accentColor}; color:${pillColor}; font-size:${isStories ? '18px' : '15px'}; padding:4px 10px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.15);"><b style="color:${accentColor}; font-weight:900;">${tam}:</b> <span style="font-weight:700;">${preco}</span></span>`;
            }
        }
    }).join('');

    const maisHtml = restantes > 0 ? `<span style="color:${isLight ? '#64748B' : (palette.mutedText || '#94A3B8')}; font-weight:800; font-size:14px; padding:4px 6px;">+${restantes}</span>` : '';

    return `
    <div style="margin: ${isStories ? '10px 0' : '5px 0'}; display: flex; flex-direction: column; gap: 5px;">
        <div style="font-size: ${isStories ? '16px' : '13px'}; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: ${labelColor};">📏 ${label}</div>
        <div style="display: flex; flex-wrap: wrap; gap: 6px 8px; align-items: center; max-height: ${isStories ? '130px' : '75px'}; overflow: hidden;">
            ${pillsHtml}
            ${maisHtml}
        </div>
    </div>`;
}

/* =========================================================================================================
 * TEMPLATE 1: MODERN DARK (Glassmorphism Padrão)
 * ========================================================================================================= */
function renderModernDarkTemplate(ctx) {
    const { isStories, palette, bgCss, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site, gradeTamanhos, mesmoPreco, hasGrade, priceLabel, imgStyles, promoCalloutHtml } = ctx;
    const tamanhosGradeHtml = renderTamanhosGradeSection(gradeTamanhos, mesmoPreco, palette, isStories, false);

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
        .product-stage { position: relative; flex: 1; display: flex; align-items: center; justify-content: center; margin: ${isStories ? '40px 0' : (hasGrade ? '20px 0' : '30px 0')}; }
        .image-card { position: relative; width: 100%; height: ${imgStyles ? imgStyles.stageHeight : (isStories ? 920 : (hasGrade ? 460 : 520))}px; background: ${palette.cardBg}; backdrop-filter: blur(20px); border: 1px solid ${palette.border}; border-radius: 36px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; padding: 0; overflow: hidden; }
        .image-card-blur-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: blur(30px) opacity(0.4); transform: scale(1.2); pointer-events: none; }
        .product-image { position: relative; z-index: 2; ${imgStyles ? imgStyles.imgStyle : 'width: 98%; height: 98%; object-fit: contain;'} filter: drop-shadow(0 15px 25px rgba(0,0,0,0.5)); }
        .brand-tag { position: absolute; top: 24px; left: 24px; z-index: 3; background: rgba(15, 23, 42, 0.85); border: 1px solid ${palette.border}; color: ${palette.mutedText}; font-size: 20px; font-weight: 700; padding: 8px 20px; border-radius: 12px; text-transform: uppercase; }
        .info-card { background: ${palette.infoBg}; backdrop-filter: blur(25px); border: 1px solid ${palette.border}; border-radius: 32px; padding: ${isStories ? '36px 40px' : '26px 40px'}; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); display: flex; flex-direction: column; gap: 10px; }
        .product-title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '42px' : '34px'}; font-weight: 800; line-height: 1.25; color: #FFFFFF; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .price-section { display: flex; align-items: flex-end; justify-content: space-between; margin-top: 4px; }
        .original-price { font-size: ${isStories ? '26px' : '22px'}; color: ${palette.mutedText}; text-decoration: line-through; font-weight: 600; }
        .current-price-label { font-size: 16px; font-weight: 700; color: ${palette.accent}; text-transform: uppercase; letter-spacing: 1px; }
        .current-price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '64px' : '52px'}; font-weight: 900; color: ${palette.accent}; line-height: 1; letter-spacing: -1px; }
        .installment-badge { background: rgba(255,255,255,0.08); border: 1px solid ${palette.border}; color: ${palette.accent}; font-size: ${isStories ? '22px' : '18px'}; font-weight: 700; padding: 10px 20px; border-radius: 16px; align-self: flex-end; }
        .footer { display: flex; align-items: center; justify-content: space-between; margin-top: ${isStories ? '25px' : '12px'}; padding-top: 12px; border-top: 1px solid ${palette.border}; font-size: ${isStories ? '22px' : '18px'}; color: ${palette.mutedText}; font-weight: 600; }
        .cta-pill { background: ${palette.primary}; color: #FFFFFF; font-family: 'Outfit', sans-serif; font-size: ${isStories ? '22px' : '18px'}; font-weight: 800; padding: 8px 20px; border-radius: 12px; text-transform: uppercase; }
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
                ${imagemProduto ? `<img class="image-card-blur-bg" src="${imagemProduto}">` : ''}
                ${marca ? `<div class="brand-tag">${marca}</div>` : ''}
                <img class="product-image" src="${imagemProduto}">
            </div>
        </div>
        <div class="info-card">
            ${promoCalloutHtml ? `<div>${promoCalloutHtml}</div>` : ''}
            <div class="product-title">${nomeProduto}</div>
            ${tamanhosGradeHtml}
            <div class="price-section">
                <div>
                    ${emPromocao && precoOriginal ? `<div class="original-price">De: ${precoOriginal}</div>` : ''}
                    <div class="current-price-label">${priceLabel}</div>
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
    const { isStories, palette, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site, gradeTamanhos, mesmoPreco, hasGrade, priceLabel, imgStyles, promoCalloutHtml } = ctx;
    const tamanhosGradeHtml = renderTamanhosGradeSection(gradeTamanhos, mesmoPreco, palette, isStories, true);

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
        .stage { flex: 1; display: flex; align-items: center; justify-content: center; margin: ${isStories ? '25px 0' : (hasGrade ? '15px 0' : '25px 0')}; }
        .img-box { width: 100%; height: ${imgStyles ? imgStyles.stageHeight : (isStories ? 950 : (hasGrade ? 460 : 530))}px; background: #FFFFFF; border-radius: 36px; display: flex; align-items: center; justify-content: center; padding: 0; box-shadow: 0 30px 60px rgba(0,0,0,0.4); position: relative; overflow: hidden; }
        .img-box img { ${imgStyles ? imgStyles.imgStyle : 'max-width: 90%; max-height: 90%; object-fit: contain;'} }
        .brand-badge { position: absolute; top: 20px; left: 20px; z-index: 10; background: ${palette.primary}; color: #FFF; font-weight: 800; padding: 6px 16px; border-radius: 10px; font-size: 18px; text-transform: uppercase; }
        .footer-card { background: #FFFFFF; color: #0F172A; border-radius: 32px; padding: ${isStories ? '35px 40px' : '25px 40px'}; box-shadow: 0 20px 50px rgba(0,0,0,0.3); display: flex; flex-direction: column; gap: 8px; }
        .title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '40px' : '32px'}; font-weight: 900; line-height: 1.2; text-transform: uppercase; color: #0F172A; }
        .price-row { display: flex; align-items: flex-end; justify-content: space-between; }
        .price-label { font-size: 15px; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }
        .price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '60px' : '50px'}; font-weight: 900; color: ${palette.secondary}; line-height: 1; }
        .orig-price { font-size: 20px; color: #64748B; text-decoration: line-through; font-weight: 600; }
        .installment { background: #F1F5F9; color: #334155; font-size: 18px; font-weight: 700; padding: 8px 18px; border-radius: 14px; }
        .contacts { display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; color: rgba(255,255,255,0.9); padding-top: 12px; }
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
        ${promoCalloutHtml ? `<div>${promoCalloutHtml}</div>` : ''}
        <div class="title">${nomeProduto}</div>
        ${tamanhosGradeHtml}
        <div class="price-row">
            <div>
                ${emPromocao && precoOriginal ? `<div class="orig-price">De: ${precoOriginal}</div>` : ''}
                <div class="price-label">${priceLabel}</div>
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
    const { isStories, palette, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site, gradeTamanhos, mesmoPreco, hasGrade, priceLabel, imgStyles, promoCalloutHtml } = ctx;
    const tamanhosGradeHtml = renderTamanhosGradeSection(gradeTamanhos, mesmoPreco, palette, isStories, true);

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
        .stage { flex: 1; display: flex; align-items: center; justify-content: center; margin: ${isStories ? '30px 0' : (hasGrade ? '15px 0' : '30px 0')}; }
        .img-card { position: relative; width: 100%; height: ${imgStyles ? imgStyles.stageHeight : (isStories ? 940 : (hasGrade ? 460 : 540))}px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 24px; display: flex; align-items: center; justify-content: center; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
        .img-card-blur-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: blur(30px) opacity(0.35); transform: scale(1.2); pointer-events: none; }
        .img-card img.product-img-main { position: relative; z-index: 2; ${imgStyles ? imgStyles.imgStyle : 'width: 98%; height: 98%; object-fit: contain;'} filter: drop-shadow(0 15px 25px rgba(0,0,0,0.15)); }
        .brand { position: absolute; top: 20px; left: 20px; z-index: 10; color: #64748B; font-weight: 700; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; background: rgba(255,255,255,0.85); padding: 4px 12px; border-radius: 8px; }
        .details { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 24px; padding: ${isStories ? '35px 40px' : '25px 40px'}; box-shadow: 0 10px 30px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 8px; }
        .title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '42px' : '34px'}; font-weight: 800; color: #0F172A; line-height: 1.25; }
        .price-section { display: flex; align-items: flex-end; justify-content: space-between; }
        .price-label { font-size: 15px; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }
        .price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '64px' : '52px'}; font-weight: 800; color: ${palette.primary}; line-height: 1; }
        .orig-price { font-size: 20px; color: #94A3B8; text-decoration: line-through; }
        .installment { font-size: 18px; font-weight: 600; color: #475569; background: #F1F5F9; padding: 8px 18px; border-radius: 12px; }
        .footer { display: flex; justify-content: space-between; font-size: 20px; color: #64748B; font-weight: 600; border-top: 1px solid #E2E8F0; padding-top: 12px; }
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
            ${imagemProduto ? `<img class="img-card-blur-bg" src="${imagemProduto}">` : ''}
            ${marca ? `<div class="brand">${marca}</div>` : ''}
            <img class="product-img-main" src="${imagemProduto}">
        </div>
    </div>
    <div class="details">
        ${promoCalloutHtml ? `<div>${promoCalloutHtml}</div>` : ''}
        <div class="title">${nomeProduto}</div>
        ${tamanhosGradeHtml}
        <div class="price-section">
            <div>
                ${emPromocao && precoOriginal ? `<div class="orig-price">De: ${precoOriginal}</div>` : ''}
                <div class="price-label">${priceLabel}</div>
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
    const { isStories, palette, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site, gradeTamanhos, mesmoPreco, hasGrade, priceLabel, imgStyles, promoCalloutHtml } = ctx;
    const neonColor = palette.accent || '#00FF66';
    const tamanhosGradeHtml = renderTamanhosGradeSection(gradeTamanhos, mesmoPreco, palette, isStories, false);

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
        .stage { flex: 1; display: flex; align-items: center; justify-content: center; margin: ${isStories ? '20px 0' : (hasGrade ? '10px 0' : '20px 0')}; z-index: 2; }
        .img-box { width: 100%; height: ${imgStyles ? imgStyles.stageHeight : (isStories ? 920 : (hasGrade ? 460 : 520))}px; background: rgba(15, 23, 42, 0.9); border: 2px solid ${neonColor}88; border-radius: 32px; display: flex; align-items: center; justify-content: center; padding: 0; box-shadow: 0 0 40px ${neonColor}33; position: relative; overflow: hidden; }
        .img-box img { ${imgStyles ? imgStyles.imgStyle : 'max-width: 90%; max-height: 90%; object-fit: contain;'} filter: drop-shadow(0 0 20px rgba(255,255,255,0.2)); }
        .info-card { background: rgba(10, 15, 30, 0.95); border: 2px solid ${neonColor}; border-radius: 28px; padding: ${isStories ? '30px 40px' : '22px 40px'}; z-index: 2; box-shadow: 0 0 30px ${neonColor}44; display: flex; flex-direction: column; gap: 8px; }
        .title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '42px' : '34px'}; font-weight: 900; text-transform: uppercase; line-height: 1.2; color: #FFF; }
        .price-row { display: flex; justify-content: space-between; align-items: flex-end; }
        .price-label { font-size: 15px; font-weight: 800; color: ${neonColor}; text-transform: uppercase; letter-spacing: 0.5px; }
        .price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '66px' : '52px'}; font-weight: 900; color: ${neonColor}; text-shadow: 0 0 20px ${neonColor}AA; line-height: 1; }
        .orig-price { font-size: 20px; color: #94A3B8; text-decoration: line-through; }
        .installment { color: #FFF; font-size: 18px; font-weight: 700; border: 1px solid ${neonColor}; padding: 8px 18px; border-radius: 12px; background: ${neonColor}22; }
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
        ${promoCalloutHtml ? `<div>${promoCalloutHtml}</div>` : ''}
        <div class="title">${nomeProduto}</div>
        ${tamanhosGradeHtml}
        <div class="price-row">
            <div>
                ${emPromocao && precoOriginal ? `<div class="orig-price">De: ${precoOriginal}</div>` : ''}
                <div class="price-label">${priceLabel}</div>
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
    const { isStories, palette, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site, gradeTamanhos, mesmoPreco, hasGrade, priceLabel, imgStyles, promoCalloutHtml } = ctx;
    const tamanhosGradeHtml = renderTamanhosGradeSection(gradeTamanhos, mesmoPreco, palette, isStories, true);

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
        .top-banner { background: ${palette.primary}; padding: ${isStories ? '60px 50px 30px' : '30px 50px'}; display: flex; justify-content: space-between; align-items: center; }
        .store-title { font-family: 'Outfit', sans-serif; font-size: 34px; font-weight: 900; text-transform: uppercase; color: #FFF; }
        .badge { background: #FFF; color: ${palette.primary}; font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 26px; padding: 8px 24px; border-radius: 40px; text-transform: uppercase; }
        .stage { flex: 1; padding: ${isStories ? '40px 60px' : (hasGrade ? '20px 60px' : '40px 60px')}; display: flex; align-items: center; justify-content: center; }
        .img-box { width: 100%; height: 100%; background: #FFF; border-radius: 32px; display: flex; align-items: center; justify-content: center; padding: 0; position: relative; overflow: hidden; }
        .img-box img { ${imgStyles ? imgStyles.imgStyle : 'max-width: 90%; max-height: 90%; object-fit: contain;'} }
        .bottom-banner { background: #FFFFFF; color: #0F172A; padding: ${isStories ? '45px 60px 55px' : '25px 60px 30px'}; display: flex; flex-direction: column; gap: 8px; }
        .title { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '44px' : '34px'}; font-weight: 900; text-transform: uppercase; line-height: 1.2; }
        .price-bar { display: flex; justify-content: space-between; align-items: center; background: #F1F5F9; padding: 15px 30px; border-radius: 20px; margin-top: 5px; }
        .price-label { font-size: 15px; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }
        .price { font-family: 'Outfit', sans-serif; font-size: ${isStories ? '62px' : '50px'}; font-weight: 900; color: ${palette.secondary}; }
        .orig-price { font-size: 20px; color: #64748B; text-decoration: line-through; }
        .installment { font-size: 18px; font-weight: 700; color: #334155; }
        .contact-bar { background: #0F172A; color: #FFF; padding: 15px 60px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; }
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
        ${promoCalloutHtml ? `<div>${promoCalloutHtml}</div>` : ''}
        <div class="title">${nomeProduto}</div>
        ${tamanhosGradeHtml}
        <div class="price-bar">
            <div>
                ${emPromocao && precoOriginal ? `<div class="orig-price">De: ${precoOriginal}</div>` : ''}
                <div class="price-label">${priceLabel}</div>
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

/* =========================================================================================================
 * TEMPLATE 6: FULL BLEED BANNER (Foto em Tela Cheia com Banners Topo e Rodapé)
 * ========================================================================================================= */
function renderFullBleedBannerTemplate(ctx) {
    const { isStories, palette, bgCss, nomeProduto, marca, precoOriginal, precoPromocional, emPromocao, badgeTexto, parcelamento, imagemProduto, nomeLoja, logoLoja, telefone, site, gradeTamanhos, mesmoPreco, hasGrade, priceLabel, promoCalloutHtml } = ctx;
    const tamanhosGradeHtml = renderTamanhosGradeSection(gradeTamanhos, mesmoPreco, palette, isStories, false);

    const topBgColor = palette.primary || '#6b8e23';
    const bottomBgColor = palette.badgeBg ? palette.badgeBg : '#ff5722';

    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Inter:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1080px;
            height: ${isStories ? 1920 : 1080}px;
            font-family: 'Outfit', sans-serif;
            background: #000000;
            overflow: hidden;
            position: relative;
        }

        /* Background Full Screen Product Image */
        .full-bg-image {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 1;
        }

        /* Overlay Top Banner */
        .top-banner {
            position: absolute;
            top: 0; left: 0; right: 0;
            z-index: 10;
            background: ${topBgColor};
            color: #ffffff;
            padding: ${isStories ? '45px 40px 30px' : '25px 30px 20px'};
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        .top-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: ${isStories ? '28px' : '22px'};
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 6px;
        }

        .top-title {
            font-size: ${isStories ? '64px' : '48px'};
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ffffff;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Overlay Bottom Banner */
        .bottom-banner {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            z-index: 10;
            background: ${bottomBgColor};
            padding: ${isStories ? '35px 50px 40px' : '20px 40px'};
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.4);
        }

        .bottom-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-width: 65%;
        }

        .store-brand-name {
            font-size: ${isStories ? '36px' : '26px'};
            font-weight: 900;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .main-headline {
            font-size: ${isStories ? '42px' : '30px'};
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .price-badge-text {
            font-size: ${isStories ? '56px' : '42px'};
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
        }

        .whatsapp-contact-box {
            display: flex;
            align-items: center;
            gap: 16px;
            background: rgba(0, 0, 0, 0.15);
            padding: 12px 24px;
            border-radius: 60px;
        }

        .whatsapp-icon {
            width: ${isStories ? '64px' : '48px'};
            height: ${isStories ? '64px' : '48px'};
        }

        .phone-number {
            font-size: ${isStories ? '46px' : '34px'};
            font-weight: 900;
            color: #000000;
            font-family: 'Inter', sans-serif;
            letter-spacing: -1px;
        }
    </style>
</head>
<body>
    <img class="full-bg-image" src="${imagemProduto}" alt="Background">

    <div class="top-banner">
        ${promoCalloutHtml ? `<div style="margin-bottom:8px; display:flex; justify-content:center;">${promoCalloutHtml}</div>` : ''}
        <div class="top-subtitle">${nomeLoja}</div>
        <div class="top-title">${nomeProduto}</div>
    </div>

    <div class="bottom-banner">
        <div class="bottom-info">
            <div class="store-brand-name">${nomeLoja}</div>
            ${tamanhosGradeHtml}
            <div class="main-headline">${priceLabel}</div>
            <div class="price-badge-text">${precoPromocional}</div>
        </div>

        <div class="whatsapp-contact-box">
            <svg class="whatsapp-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.982-1.385A9.956 9.956 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" fill="#25D366"/>
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.124-.272-.198-.57-.346z" fill="#FFFFFF"/>
            </svg>
            <span class="phone-number">${telefone || '(81) 9386-1026'}</span>
        </div>
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
