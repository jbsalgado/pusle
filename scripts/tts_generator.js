#!/usr/bin/env node
/**
 * tts_generator.js
 * Utilitário de Síntese de Voz Neural (Text-to-Speech) para o Pulse Vídeo Studio
 * 100% nativo em Node.js para Linux (Arch Linux)
 * 
 * Uso via CLI:
 *   node scripts/tts_generator.js --text "Texto da oferta" --voice "pt-BR-FranciscaNeural" --output "/caminho/saida.mp3" [--rate "+0%"]
 */

const fs = require('fs');
const path = require('path');
const { MsEdgeTTS, OUTPUT_FORMAT } = require('msedge-tts');

// Parse argumentos CLI
const args = process.argv.slice(2);
let text = '';
let voice = 'pt-BR-FranciscaNeural';
let output = '';
let rate = '+0%';
let pitch = '+0Hz';

for (let i = 0; i < args.length; i++) {
    if (args[i] === '--text' && args[i + 1]) {
        text = args[++i];
    } else if (args[i] === '--voice' && args[i + 1]) {
        voice = args[++i];
    } else if (args[i] === '--output' && args[i + 1]) {
        output = args[++i];
    } else if (args[i] === '--rate' && args[i + 1]) {
        rate = args[++i];
    } else if (args[i] === '--pitch' && args[i + 1]) {
        pitch = args[++i];
    }
}

if (!text) {
    console.error(JSON.stringify({ success: false, error: 'Parâmetro --text é obrigatório.' }));
    process.exit(1);
}

if (!output) {
    output = path.join('/tmp', `tts_${Date.now()}_${Math.random().toString(36).substring(7)}.mp3`);
}

// Garante que o diretório de destino existe
const outDir = path.dirname(output);
if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
}

async function generateTTS() {
    const tts = new MsEdgeTTS();
    try {
        await tts.setMetadata(voice, OUTPUT_FORMAT.AUDIO_24KHZ_48KBITRATE_MONO_MP3);
        
        const { audioStream } = tts.toStream(text, {
            rate: rate,
            pitch: pitch
        });

        const writeStream = fs.createWriteStream(output);
        audioStream.pipe(writeStream);

        await new Promise((resolve, reject) => {
            writeStream.on('finish', resolve);
            writeStream.on('error', reject);
            audioStream.on('error', reject);
        });

        tts.close();

        const stats = fs.statSync(output);
        console.log(JSON.stringify({
            success: true,
            voice: voice,
            output: output,
            size: stats.size
        }));
    } catch (err) {
        if (tts) {
            try { tts.close(); } catch (e) {}
        }
        console.error(JSON.stringify({
            success: false,
            error: err.message || String(err)
        }));
        process.exit(1);
    }
}

generateTTS();
