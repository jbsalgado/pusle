package com.thermalprintdriver

import java.io.ByteArrayOutputStream
import java.io.IOException
import java.nio.charset.Charset

class EscPosEngine {

    companion object {
        const val ESC: Byte = 0x1B
        const val GS: Byte = 0x1D
        const val LF: Byte = 0x0A
    }

    @Throws(IOException::class)
    fun getBytes(text: String): ByteArray {
        val stream = ByteArrayOutputStream()

        // Init printer
        stream.write(byteArrayOf(ESC, '@'.code.toByte()))

        // Process text - simplistic implementation assuming formatted text or just raw text
        // In a real app, we might parse tags like [b]bold[/b], but for now we interpret the whole string.
        // If the web app sends raw commands, we just pass them. 
        // If it sends plain text, we assume default formatting.
        
        // For this driver, we will support a simple protocol:
        // if text starts with "{CMD}", we treat it as special, otherwise plain text.
        // Actually, for simplicity and universality, let's assume the web app sends the content
        // and we just wrap it with init and cut, or expect the web app to format it?
        // The prompt says "convert Strings and PDFs into byte commands".
        // Let's implement basic text printing with charset support.

        val charset = Charset.forName("ISO-8859-1") // Common for thermal printers in Brazil (PC850/860 often used too)
        val textBytes = text.toByteArray(charset)
        stream.write(textBytes)

        // Feed paper
        stream.write(byteArrayOf(LF, LF, LF))

        // Cut paper (GS V 66 0)
        stream.write(byteArrayOf(GS, 'V'.code.toByte(), 66, 0))

        return stream.toByteArray()
    }
    
    // Auxiliary commands
    fun bold(on: Boolean): ByteArray {
        return byteArrayOf(ESC, 'E'.code.toByte(), if (on) 1 else 0)
    }

    fun align(align: Int): ByteArray {
        // 0: Left, 1: Center, 2: Right
        return byteArrayOf(ESC, 'a'.code.toByte(), align.toByte())
    }
}
