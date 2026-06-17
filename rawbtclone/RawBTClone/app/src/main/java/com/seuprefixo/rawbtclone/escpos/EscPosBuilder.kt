package com.seuprefixo.rawbtclone.escpos

import android.graphics.Bitmap
import java.io.ByteArrayOutputStream

/**
 * Constrói sequências de bytes ESC/POS para impressoras térmicas.
 *
 * Referência: https://reference.epson-biz.com/modules/ref_escpos/
 *
 * Uso:
 *   val bytes = EscPosBuilder()
 *       .initialize()
 *       .align(EscPosBuilder.ALIGN_CENTER)
 *       .bold(true)
 *       .text("Nota Fiscal\n")
 *       .bold(false)
 *       .text("Item 01 ............. R$ 10,00\n")
 *       .feedAndCut()
 *       .build()
 */
class EscPosBuilder {

    companion object {
        const val ALIGN_LEFT   = 0
        const val ALIGN_CENTER = 1
        const val ALIGN_RIGHT  = 2

        const val FONT_A = 0  // maior
        const val FONT_B = 1  // menor
    }

    private val buffer = ByteArrayOutputStream()

    /** ESC @ — inicializa a impressora (reseta configurações) */
    fun initialize(): EscPosBuilder {
        buffer.write(byteArrayOf(0x1B, 0x40))
        return this
    }

    /** Escreve texto com encoding configurável (padrão CP860 / Latin-1) */
    fun text(text: String, charset: String = "CP860"): EscPosBuilder {
        buffer.write(text.toByteArray(charset(charset)))
        return this
    }

    /** LF — avança uma linha */
    fun newLine(): EscPosBuilder {
        buffer.write(byteArrayOf(0x0A))
        return this
    }

    /** ESC a — alinhamento (0=esq, 1=centro, 2=dir) */
    fun align(alignment: Int): EscPosBuilder {
        buffer.write(byteArrayOf(0x1B, 0x61, alignment.toByte()))
        return this
    }

    /** ESC E — negrito */
    fun bold(enable: Boolean): EscPosBuilder {
        buffer.write(byteArrayOf(0x1B, 0x45, if (enable) 1 else 0))
        return this
    }

    /** ESC - — sublinhado */
    fun underline(enable: Boolean): EscPosBuilder {
        buffer.write(byteArrayOf(0x1B, 0x2D, if (enable) 1 else 0))
        return this
    }

    /** ESC M — fonte (0=A grande, 1=B pequena) */
    fun font(fontType: Int): EscPosBuilder {
        buffer.write(byteArrayOf(0x1B, 0x4D, fontType.toByte()))
        return this
    }

    /**
     * GS ! — tamanho do caractere.
     * widthMag e heightMag: multiplicadores 1–8
     */
    fun charSize(widthMag: Int = 1, heightMag: Int = 1): EscPosBuilder {
        val w = (widthMag - 1).coerceIn(0, 7)
        val h = (heightMag - 1).coerceIn(0, 7)
        buffer.write(byteArrayOf(0x1D, 0x21, ((w shl 4) or h).toByte()))
        return this
    }

    /** ESC d — avança N linhas */
    fun feedLines(n: Int): EscPosBuilder {
        buffer.write(byteArrayOf(0x1B, 0x64, n.toByte()))
        return this
    }

    /**
     * GS V — corte do papel.
     * type: 0=corte total, 1=corte parcial
     */
    fun cut(type: Int = 1): EscPosBuilder {
        buffer.write(byteArrayOf(0x1D, 0x56, type.toByte()))
        return this
    }

    /** Avança 4 linhas e corta — atalho comum */
    fun feedAndCut(): EscPosBuilder = feedLines(4).cut()

    /**
     * Imprime imagem Bitmap usando o comando GS v 0 (raster).
     * Suportado pela maioria das impressoras térmicas modernas.
     *
     * @param bitmap Bitmap em escala de cinza ou preto/branco
     * @param paperWidthDots Largura do papel em dots (58mm=384, 80mm=576)
     */
    fun image(bitmap: Bitmap, paperWidthDots: Int = 384): EscPosBuilder {
        val scaledWidth = minOf(bitmap.width, paperWidthDots)
        val scaledBitmap = if (bitmap.width > paperWidthDots) {
            val ratio = paperWidthDots.toFloat() / bitmap.width
            Bitmap.createScaledBitmap(
                bitmap,
                paperWidthDots,
                (bitmap.height * ratio).toInt(),
                true
            )
        } else bitmap

        val width = scaledBitmap.width
        val height = scaledBitmap.height
        val widthBytes = (width + 7) / 8

        // GS v 0 — raster bit image
        buffer.write(byteArrayOf(0x1D, 0x76, 0x30, 0x00))
        buffer.write(byteArrayOf(
            (widthBytes and 0xFF).toByte(),
            ((widthBytes shr 8) and 0xFF).toByte(),
            (height and 0xFF).toByte(),
            ((height shr 8) and 0xFF).toByte()
        ))

        val pixels = IntArray(width * height)
        scaledBitmap.getPixels(pixels, 0, width, 0, 0, width, height)

        for (y in 0 until height) {
            for (xByte in 0 until widthBytes) {
                var byte = 0
                for (bit in 0 until 8) {
                    val x = xByte * 8 + bit
                    if (x < width) {
                        val pixel = pixels[y * width + x]
                        val r = (pixel shr 16) and 0xFF
                        val g = (pixel shr 8) and 0xFF
                        val b = pixel and 0xFF
                        val luminance = (0.299 * r + 0.587 * g + 0.114 * b).toInt()
                        if (luminance < 128) {
                            byte = byte or (1 shl (7 - bit))
                        }
                    }
                }
                buffer.write(byte)
            }
        }
        return this
    }

    /** Linha divisória com caractere repetido */
    fun divider(char: Char = '-', columns: Int = 32): EscPosBuilder {
        return text(char.toString().repeat(columns) + "\n")
    }

    /** Retorna os bytes montados prontos para envio via Bluetooth */
    fun build(): ByteArray = buffer.toByteArray()
}
