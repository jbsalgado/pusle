package com.seuprefixo.rawbtclone.service

import android.print.PrinterCapabilitiesInfo
import android.print.PrinterId
import android.print.PrinterInfo
import android.printservice.PrintService
import android.printservice.PrinterDiscoverySession
import com.seuprefixo.rawbtclone.bluetooth.BluetoothPrinterManager
import com.seuprefixo.rawbtclone.utils.PrinterSettings
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.first

/**
 * Descobre e registra a impressora Bluetooth configurada como
 * impressora disponível no sistema de impressão do Android.
 */
class ThermalPrinterDiscoverySession(
    private val printService: PrintService,
    private val btManager: BluetoothPrinterManager,
    private val settings: PrinterSettings
) : PrinterDiscoverySession() {

    private val scope = CoroutineScope(Dispatchers.IO + SupervisorJob())

    override fun onStartPrinterDiscovery(priorityList: MutableList<PrinterId>) {
        scope.launch {
            val address = settings.deviceAddress.first() ?: return@launch
            val name    = settings.deviceName.first() ?: "Impressora Térmica"

            val printerId = printService.generatePrinterId(address)
            val capabilities = PrinterCapabilitiesInfo.Builder(printerId)
                .setMinMargins(android.print.PrintAttributes.Margins.NO_MARGINS)
                .setColorModes(
                    PrintAttributes.COLOR_MODE_MONOCHROME,
                    PrintAttributes.COLOR_MODE_MONOCHROME
                )
                .addMediaSize(PrintAttributes.MediaSize.ISO_A4, true)
                .addResolution(
                    PrintAttributes.Resolution("r203", "203 dpi", 203, 203),
                    true
                )
                .build()

            val printerInfo = PrinterInfo.Builder(printerId, name, PrinterInfo.STATUS_IDLE)
                .setCapabilities(capabilities)
                .build()

            addPrinters(listOf(printerInfo))
        }
    }

    override fun onStopPrinterDiscovery() {
        scope.cancel()
    }

    override fun onValidatePrinters(printerIds: MutableList<PrinterId>) { }

    override fun onStartPrinterStateTracking(printerId: PrinterId) { }

    override fun onStopPrinterStateTracking(printerId: PrinterId) { }

    override fun onDestroy() {
        scope.cancel()
    }
}
