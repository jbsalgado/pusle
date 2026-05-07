package com.seuprefixo.rawbtclone.ui.main

import android.app.Application
import android.bluetooth.BluetoothDevice
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.seuprefixo.rawbtclone.bluetooth.BluetoothPrinterManager
import com.seuprefixo.rawbtclone.escpos.EscPosBuilder
import com.seuprefixo.rawbtclone.utils.PrinterSettings
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

data class MainUiState(
    val pairedDevices: List<BluetoothDevice> = emptyList(),
    val selectedDevice: BluetoothDevice? = null,
    val isConnected: Boolean = false,
    val isLoading: Boolean = false,
    val statusMessage: String = "Desconectado",
    val savedDeviceName: String? = null
)

class MainViewModel(application: Application) : AndroidViewModel(application) {

    val btManager = BluetoothPrinterManager(application)
    val settings  = PrinterSettings(application)

    private val _uiState = MutableStateFlow(MainUiState())
    val uiState: StateFlow<MainUiState> = _uiState.asStateFlow()

    init {
        loadSavedDevice()
        refreshDeviceList()
    }

    private fun loadSavedDevice() {
        viewModelScope.launch {
            settings.deviceName.collect { name ->
                _uiState.update { it.copy(savedDeviceName = name) }
            }
        }
    }

    fun refreshDeviceList() {
        val devices = btManager.getPairedPrinters()
        _uiState.update { it.copy(pairedDevices = devices) }
    }

    fun selectDevice(device: BluetoothDevice) {
        _uiState.update { it.copy(selectedDevice = device) }
    }

    fun connect() {
        val device = _uiState.value.selectedDevice ?: return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, statusMessage = "Conectando...") }
            val result = btManager.connect(device)
            if (result.isSuccess) {
                settings.savePrinter(device.address, device.name ?: device.address)
                _uiState.update {
                    it.copy(
                        isConnected = true,
                        isLoading = false,
                        statusMessage = "Conectado: ${device.name ?: device.address}"
                    )
                }
            } else {
                _uiState.update {
                    it.copy(
                        isConnected = false,
                        isLoading = false,
                        statusMessage = "Erro: ${result.exceptionOrNull()?.message}"
                    )
                }
            }
        }
    }

    fun disconnect() {
        btManager.disconnect()
        _uiState.update { it.copy(isConnected = false, statusMessage = "Desconectado") }
    }

    fun printTestPage() {
        viewModelScope.launch {
            val encoding = settings.encoding.first()
            val columns  = settings.columns.first()
            val autoCut  = settings.autoCut.first()

            val bytes = EscPosBuilder()
                .initialize()
                .align(EscPosBuilder.ALIGN_CENTER)
                .bold(true)
                .charSize(2, 2)
                .text("RawBT Clone\n", encoding)
                .charSize(1, 1)
                .bold(false)
                .text("Página de Teste\n", encoding)
                .divider('-', columns)
                .align(EscPosBuilder.ALIGN_LEFT)
                .text("Papel: ${settings.paperWidthMm.first()} mm\n", encoding)
                .text("Encoding: $encoding\n", encoding)
                .text("Colunas: $columns\n", encoding)
                .text("Corte auto: ${if (autoCut) "Sim" else "Não"}\n", encoding)
                .divider('-', columns)
                .align(EscPosBuilder.ALIGN_CENTER)
                .text("Impressão OK!\n", encoding)
                .apply { if (autoCut) feedAndCut() else feedLines(4) }
                .build()

            val result = btManager.sendBytes(bytes)
            _uiState.update {
                it.copy(
                    statusMessage = if (result.isSuccess) "Página de teste enviada!"
                    else "Erro: ${result.exceptionOrNull()?.message}"
                )
            }
        }
    }

    override fun onCleared() {
        super.onCleared()
        btManager.disconnect()
    }
}
