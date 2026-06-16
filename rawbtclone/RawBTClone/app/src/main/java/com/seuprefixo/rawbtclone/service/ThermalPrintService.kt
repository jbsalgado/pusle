package com.seuprefixo.rawbtclone.service

import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.pdf.PdfRenderer
import android.os.CancellationSignal
import android.os.ParcelFileDescriptor
import android.print.PrintAttributes
import android.print.PrintDocumentAdapter
import android.print.PrintDocumentInfo
import android.printservice.PrintDocument
import android.printservice.PrintJob
import android.printservice.PrintService
import android.printservice.PrinterDiscoverySession
import android.util.Log
import com.seuprefixo.rawbtclone.bluetooth.BluetoothPrinterManager
import com.seuprefixo.rawbtclone.escpos.EscPosBuilder
import com.seuprefixo.rawbtclone.utils.PrinterSettings
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.first

/**
 * Serviço de impressão nativo do Android.
 *
 * Ao ser habilitado em Configurações → Impressão, este serviço
 * registra a impressora Bluetooth configurada como impressora
 * disponível em todo o sistema (Gmail, Chrome, Word, etc.).
 */
class ThermalPrintService : PrintService() {

    private val tag = "ThermalPrintService"
    private val serviceScope = CoroutineScope(Dispatchers.IO + SupervisorJob())
    private lateinit var btManager: BluetoothPrinterManager
    private lateinit var settings: PrinterSettings

    override fun onCreate() {
        super.onCreate()
        btManager = BluetoothPrinterManager(applicationContext)
        settings  = PrinterSettings(applicationContext)
    }

    override fun onCreatePrinterDiscoverySession(): PrinterDiscoverySession {
        return ThermalPrinterDiscoverySession(this, btManager, settings)
    }

    override fun onRequestCancelPrintJob(printJob: PrintJob) {
        printJob.cancel()
    }

    override fun onPrintJobQueued(printJob: PrintJob) {
        serviceScope.launch {
            processPrintJob(printJob)
        }
    }

    private suspend fun processPrintJob(printJob: PrintJob) {
        try {
            val address = settings.deviceAddress.first()
            if (address.isNullOrBlank()) {
                Log.e(tag, "Nenhuma impressora configurada")
                printJob.fail("Configure uma impressora no app RawBT Clone")
                return
            }

            val device = btManager.getPairedPrinters()
                .firstOrNull { it.address == address }
                ?: run {
                    printJob.fail("Impressora não encontrada nos pareados")
                    return
                }

            val connectResult = btManager.connect(device)
            if (connectResult.isFailure) {
                printJob.fail("Falha ao conectar: ${connectResult.exceptionOrNull()?.message}")
                return
            }

            printJob.start()

            val document = printJob.document
            val widthMm  = settings.paperWidthMm.first()
            val widthDots = settings.mmToDots(widthMm)
            val autoCut  = settings.autoCut.first()

            // Renderiza cada página do PDF e envia como imagem raster
            val fd = document.data ?: run {
                printJob.fail("Documento vazio")
                return
            }

            val bytes = renderPdfToEscPos(fd, widthDots, autoCut)
            val sendResult = btManager.sendBytes(bytes)

            if (sendResult.isSuccess) {
                printJob.complete()
            } else {
                printJob.fail("Erro ao enviar: ${sendResult.exceptionOrNull()?.message}")
            }

        } catch (e: Exception) {
            Log.e(tag, "Erro ao processar job de impressão", e)
            printJob.fail(e.message)
        } finally {
            btManager.disconnect()
        }
    }

    private fun renderPdfToEscPos(
        fd: ParcelFileDescriptor,
        widthDots: Int,
        autoCut: Boolean
    ): ByteArray {
        val builder = EscPosBuilder().initialize()
        val renderer = PdfRenderer(fd)

        for (i in 0 until renderer.pageCount) {
            val page = renderer.openPage(i)

            // Calcula altura proporcional
            val scale = widthDots.toFloat() / page.width
            val bmpHeight = (page.height * scale).toInt()

            val bitmap = Bitmap.createBitmap(widthDots, bmpHeight, Bitmap.Config.ARGB_8888)
            val canvas = Canvas(bitmap)
            canvas.drawColor(Color.WHITE)

            page.render(bitmap, null, null, PdfRenderer.Page.RENDER_MODE_FOR_PRINT)
            page.close()

            builder.image(bitmap, widthDots)
            bitmap.recycle()
        }

        renderer.close()
        fd.close()

        if (autoCut) builder.feedAndCut()
        return builder.build()
    }

    override fun onDestroy() {
        super.onDestroy()
        serviceScope.cancel()
        btManager.disconnect()
    }
}
