package com.seuprefixo.rawbtclone.bluetooth

import android.Manifest
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothManager
import android.bluetooth.BluetoothSocket
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.content.ContextCompat
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.IOException
import java.io.OutputStream
import java.util.UUID

/**
 * Gerencia a descoberta, conexão e envio de dados para impressoras Bluetooth.
 * Usa o perfil SPP (Serial Port Profile) — padrão para impressoras ESC/POS.
 */
class BluetoothPrinterManager(private val context: Context) {

    companion object {
        // UUID padrão do perfil SPP — usado por 99% das impressoras térmicas BT
        val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")
    }

    private val bluetoothAdapter: BluetoothAdapter? by lazy {
        val manager = context.getSystemService(Context.BLUETOOTH_SERVICE) as BluetoothManager
        manager.adapter
    }

    private var activeSocket: BluetoothSocket? = null
    private var outputStream: OutputStream? = null

    /** Verifica se o Bluetooth está disponível e ativado */
    fun isBluetoothAvailable(): Boolean = bluetoothAdapter != null

    fun isBluetoothEnabled(): Boolean = bluetoothAdapter?.isEnabled == true

    /**
     * Retorna a lista de dispositivos Bluetooth já pareados no sistema.
     * Filtra apenas impressoras (class = 1664 = Printer Major Class).
     * Se nenhuma impressora for detectada, retorna todos os pareados.
     */
    fun getPairedPrinters(): List<BluetoothDevice> {
        if (!hasPermission()) return emptyList()
        val bonded = bluetoothAdapter?.bondedDevices ?: return emptyList()
        // Classe Bluetooth para impressoras: 0x680 (1664)
        val printers = bonded.filter { it.bluetoothClass?.majorDeviceClass == 0x680 }
        return printers.ifEmpty { bonded.toList() }
    }

    /**
     * Conecta ao dispositivo via RFCOMM (SPP).
     * Deve ser chamado em uma coroutine (Dispatchers.IO).
     */
    suspend fun connect(device: BluetoothDevice): Result<Unit> = withContext(Dispatchers.IO) {
        try {
            disconnect()
            val socket = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                device.createRfcommSocketToServiceRecord(SPP_UUID)
            } else {
                @Suppress("DEPRECATION")
                device.createRfcommSocketToServiceRecord(SPP_UUID)
            }
            socket.connect()
            activeSocket = socket
            outputStream = socket.outputStream
            Result.success(Unit)
        } catch (e: IOException) {
            Result.failure(e)
        }
    }

    /**
     * Envia bytes brutos para a impressora conectada.
     * Use EscPosBuilder para montar os bytes antes de chamar este método.
     */
    suspend fun sendBytes(data: ByteArray): Result<Unit> = withContext(Dispatchers.IO) {
        try {
            val stream = outputStream ?: return@withContext Result.failure(
                IOException("Nenhuma impressora conectada")
            )
            stream.write(data)
            stream.flush()
            Result.success(Unit)
        } catch (e: IOException) {
            Result.failure(e)
        }
    }

    /** Fecha a conexão com a impressora */
    fun disconnect() {
        try {
            outputStream?.close()
            activeSocket?.close()
        } catch (_: IOException) { }
        outputStream = null
        activeSocket = null
    }

    fun isConnected(): Boolean = activeSocket?.isConnected == true

    private fun hasPermission(): Boolean {
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            ContextCompat.checkSelfPermission(
                context, Manifest.permission.BLUETOOTH_CONNECT
            ) == PackageManager.PERMISSION_GRANTED
        } else {
            true
        }
    }
}
