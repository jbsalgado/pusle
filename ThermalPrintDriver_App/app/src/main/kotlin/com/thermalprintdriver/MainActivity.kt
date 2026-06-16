package com.thermalprintdriver

import android.Manifest
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.Spinner
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat

class MainActivity : AppCompatActivity() {

    private lateinit var tvStatus: TextView
    private lateinit var spinnerDevices: Spinner
    private lateinit var btnConnect: Button
    private lateinit var btnDisconnect: Button
    
    private val bluetoothAdapter: BluetoothAdapter? = BluetoothAdapter.getDefaultAdapter()
    private val deviceList = ArrayList<BluetoothDevice>()

    companion object {
        private const val PERMISSION_REQUEST_CODE = 101
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        tvStatus = findViewById(R.id.tvStatus)
        spinnerDevices = findViewById(R.id.spinnerDevices)
        btnConnect = findViewById(R.id.btnConnect)
        btnDisconnect = findViewById(R.id.btnDisconnect)

        checkPermissions()

        btnConnect.setOnClickListener {
            val selectedDevice = if (spinnerDevices.selectedItemPosition >= 0) {
                deviceList[spinnerDevices.selectedItemPosition]
            } else null

            if (selectedDevice != null) {
                connectToDevice(selectedDevice.address)
            } else {
                Toast.makeText(this, "Selecione um dispositivo", Toast.LENGTH_SHORT).show()
            }
        }

        btnDisconnect.setOnClickListener {
            disconnect()
        }
        
        // Handle deep link if app started via one
        handleIntent(intent)
    }

    override fun onNewIntent(intent: Intent?) {
        super.onNewIntent(intent)
        setIntent(intent)
        handleIntent(intent)
    }

    private fun handleIntent(intent: Intent?) {
        val appLinkAction = intent?.action
        val appLinkData: Uri? = intent?.data

        if (Intent.ACTION_VIEW == appLinkAction && appLinkData != null) {
            val data = appLinkData.getQueryParameter("data")
            if (data != null) {
                // If we are just launching, we might need to be connected first.
                // In a real scenario, we might want to auto-connect to last device.
                // For now, assume connected or pass to service to handle queuing strictly.
                // We'll send to Service.
                val serviceIntent = Intent(this, PrintService::class.java).apply {
                    action = PrintService.ACTION_PRINT
                    putExtra(PrintService.EXTRA_PRINT_DATA, data)
                }
                startService(serviceIntent)
                Toast.makeText(this, "Imprimindo via Deep Link...", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun checkPermissions() {
        val permissions = mutableListOf<String>()
        
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED) {
                permissions.add(Manifest.permission.BLUETOOTH_CONNECT)
            }
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.BLUETOOTH_SCAN) != PackageManager.PERMISSION_GRANTED) {
                permissions.add(Manifest.permission.BLUETOOTH_SCAN)
            }
        } else {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.BLUETOOTH) != PackageManager.PERMISSION_GRANTED) {
                permissions.add(Manifest.permission.BLUETOOTH)
            }
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.BLUETOOTH_ADMIN) != PackageManager.PERMISSION_GRANTED) {
                permissions.add(Manifest.permission.BLUETOOTH_ADMIN)
            }
        }
        
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
             if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                permissions.add(Manifest.permission.POST_NOTIFICATIONS)
            }
        }

        if (permissions.isNotEmpty()) {
            ActivityCompat.requestPermissions(this, permissions.toTypedArray(), PERMISSION_REQUEST_CODE)
        } else {
            loadPairedDevices()
        }
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == PERMISSION_REQUEST_CODE) {
            if (grantResults.isNotEmpty() && grantResults.all { it == PackageManager.PERMISSION_GRANTED }) {
                loadPairedDevices()
            } else {
                Toast.makeText(this, "Permissões necessárias para Bluetooth não concedidas", Toast.LENGTH_LONG).show()
            }
        }
    }

    private fun loadPairedDevices() {
        if (ActivityCompat.checkSelfPermission(this, Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED 
            && Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            return
        }

        val pairedDevices = bluetoothAdapter?.bondedDevices
        val deviceNames = ArrayList<String>()
        deviceList.clear()

        pairedDevices?.forEach { device ->
            deviceNames.add("${device.name} \n${device.address}")
            deviceList.add(device)
        }

        val adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, deviceNames)
        spinnerDevices.adapter = adapter
    }

    private fun connectToDevice(address: String) {
        val intent = Intent(this, PrintService::class.java).apply {
            action = PrintService.ACTION_CONNECT
            putExtra(PrintService.EXTRA_DEVICE_ADDRESS, address)
        }
        startForegroundServiceCompat(intent)
        tvStatus.text = "Status: Conectando..."
    }

    private fun disconnect() {
        val intent = Intent(this, PrintService::class.java).apply {
            action = PrintService.ACTION_DISCONNECT
        }
        startService(intent) // Service handles stopping itself or just disconnecting
        tvStatus.text = "Status: Desconectado"
    }

    private fun startForegroundServiceCompat(intent: Intent) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(intent)
        } else {
            startService(intent)
        }
    }
}
