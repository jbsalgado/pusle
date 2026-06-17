package com.thermalprintdriver

import android.annotation.SuppressLint
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothSocket
import android.content.Intent
import android.os.Build
import android.os.Handler
import android.os.IBinder
import android.os.Looper
import android.util.Log
import androidx.core.app.NotificationCompat
import java.io.IOException
import java.io.OutputStream
import java.util.UUID

class PrintService : Service() {

    companion object {
        const val CHANNEL_ID = "PrintServiceChannel"
        const val NOTIFICATION_ID = 1
        const val ACTION_CONNECT = "com.thermalprintdriver.CONNECT"
        const val ACTION_DISCONNECT = "com.thermalprintdriver.DISCONNECT"
        const val ACTION_PRINT = "com.thermalprintdriver.PRINT"
        const val EXTRA_DEVICE_ADDRESS = "device_address"
        const val EXTRA_PRINT_DATA = "print_data"
        
        // UUID for SPP (Serial Port Profile)
        private val SPP_UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")
        
        // Auto-disconnect timeout (e.g., 5 minutes)
        private const val TIMEOUT_MS = 5 * 60 * 1000L
    }

    private var bluetoothSocket: BluetoothSocket? = null
    private var outputStream: OutputStream? = null
    private val disconnectHandler = Handler(Looper.getMainLooper())
    private val disconnectRunnable = Runnable { disconnect() }
    
    // Status callback (simplistic approach via broadcast or static for demo)
    // For a robust app, use EventBus or LiveData repository.
    // Here we will log and maybe toast.

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val notification = createNotification("Serviço de Impressão Ativo")
        startForeground(NOTIFICATION_ID, notification)

        when (intent?.action) {
            ACTION_CONNECT -> {
                val address = intent.getStringExtra(EXTRA_DEVICE_ADDRESS)
                if (address != null) {
                    connectToDevice(address)
                }
            }
            ACTION_DISCONNECT -> {
                disconnect()
            }
            ACTION_PRINT -> {
                val data = intent.getStringExtra(EXTRA_PRINT_DATA) ?: ""
                printData(data)
            }
        }

        return START_NOT_STICKY
    }

    override fun onBind(intent: Intent?): IBinder? {
        return null
    }

    @SuppressLint("MissingPermission")
    private fun connectToDevice(address: String) {
        // Reset timer if already connected or connecting
        resetDisconnectTimer()

        // If already connected to this device, ignore? Or reconnect?
        if (bluetoothSocket?.isConnected == true) {
            Log.d("PrintService", "Already connected")
            return
        }

        Thread {
            try {
                val adapter = BluetoothAdapter.getDefaultAdapter()
                val device: BluetoothDevice = adapter.getRemoteDevice(address)
                
                // Cancel discovery mainly for performance
                adapter.cancelDiscovery()

                bluetoothSocket = device.createRfcommSocketToServiceRecord(SPP_UUID)
                bluetoothSocket?.connect()
                outputStream = bluetoothSocket?.outputStream

                Log.i("PrintService", "Connected to $address")
                
                // Send broadcast or update UI state here

            } catch (e: IOException) {
                Log.e("PrintService", "Connection failed", e)
                try {
                    bluetoothSocket?.close()
                } catch (closeException: IOException) {}
                bluetoothSocket = null
                outputStream = null
            }
        }.start()
    }

    private fun disconnect() {
        try {
            outputStream?.close()
            bluetoothSocket?.close()
            Log.i("PrintService", "Disconnected and resources released")
        } catch (e: IOException) {
            Log.e("PrintService", "Error closing socket", e)
        } finally {
            outputStream = null
            bluetoothSocket = null
            disconnectHandler.removeCallbacks(disconnectRunnable)
        }
    }

    private fun printData(text: String) {
        resetDisconnectTimer() // Reset timer on activity
        Thread {
            try {
                if (outputStream == null) {
                    Log.e("PrintService", "Not connected. Cannot print.")
                    return@Thread
                }
                
                val engine = EscPosEngine()
                val bytes = engine.getBytes(text)
                
                outputStream?.write(bytes)
                outputStream?.flush()
                Log.i("PrintService", "Data sent to printer")

            } catch (e: IOException) {
                Log.e("PrintService", "Error printing", e)
            }
        }.start()
    }

    private fun resetDisconnectTimer() {
        disconnectHandler.removeCallbacks(disconnectRunnable)
        disconnectHandler.postDelayed(disconnectRunnable, TIMEOUT_MS)
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val serviceChannel = NotificationChannel(
                CHANNEL_ID,
                "Thermal Print Service Channel",
                NotificationManager.IMPORTANCE_DEFAULT
            )
            val manager = getSystemService(NotificationManager::class.java)
            manager.createNotificationChannel(serviceChannel)
        }
    }

    private fun createNotification(contentText: String): Notification {
        val notificationIntent = Intent(this, MainActivity::class.java)
        val pendingIntent = PendingIntent.getActivity(
            this, 0, notificationIntent,
            PendingIntent.FLAG_IMMUTABLE
        )

        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle("Thermal Print Driver")
            .setContentText(contentText)
            .setSmallIcon(android.R.drawable.ic_menu_rotate) // Replace with app icon
            .setContentIntent(pendingIntent)
            .build()
    }

    override fun onDestroy() {
        disconnect()
        super.onDestroy()
    }
}
