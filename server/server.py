#!/usr/bin/env python3
"""
Binary Webhook Server with PyQt5 UI
Real-time webhook data monitoring and testing
"""

import sys
import struct
import io
from datetime import datetime
from PyQt5.QtWidgets import (
    QApplication, QMainWindow, QWidget, QVBoxLayout, QHBoxLayout,
    QTabWidget, QTableWidget, QTableWidgetItem, QPushButton, QLabel,
    QLineEdit, QTextEdit, QGroupBox, QFormLayout, QListWidget, QListWidgetItem,
    QHeaderView, QMessageBox
)
from PyQt5.QtCore import Qt, QThread, pyqtSignal, QTimer
from PyQt5.QtGui import QColor, QFont
from flask import Flask, request, jsonify

# Constants
WEBHOOK_MAGIC_HEADER = 0x4A4F4D4A  # "JOMJ"
ALLOWED_API_KEYS = ["123", "456", "789"]
PORT = 5001

# Error code mapping
ERROR_CODES = {
    0: "no error",
    1: "Neg. Rate",
    2: "Rate too high",
    255: "unknown error"
}


class BinaryParser:
    """Helper class for binary parsing"""
    
    def __init__(self, data):
        self.buffer = io.BytesIO(data)
        self.offset = 0
    
    def read_byte(self):
        data = self.buffer.read(1)
        if not data:
            raise ValueError("Not enough data to read byte")
        return data[0]
    
    def read_uint16_be(self):
        data = self.buffer.read(2)
        if len(data) < 2:
            raise ValueError("Not enough data to read uint16")
        return struct.unpack('>H', data)[0]
    
    def read_uint32_be(self):
        data = self.buffer.read(4)
        if len(data) < 4:
            raise ValueError("Not enough data to read uint32")
        return struct.unpack('>I', data)[0]
    
    def read_uint64_be(self):
        data = self.buffer.read(8)
        if len(data) < 8:
            raise ValueError("Not enough data to read uint64")
        return struct.unpack('>Q', data)[0]
    
    def read_string(self, max_len=255):
        length = self.read_byte()
        if length > max_len:
            length = max_len
        
        data = self.buffer.read(length)
        if len(data) < length:
            raise ValueError("Not enough data to read string")
        return data.decode('utf-8', errors='replace')
    
    @staticmethod
    def calculate_crc16(data):
        crc = 0xFFFF
        for byte in data:
            crc ^= byte << 8
            for _ in range(8):
                if crc & 0x8000:
                    crc = (crc << 1) ^ 0x1021
                else:
                    crc = crc << 1
                crc &= 0xFFFF
        return crc
    
    @staticmethod
    def error_code_to_string(code):
        return ERROR_CODES.get(code, "unknown error")


class WebhookServer(QThread):
    """Flask server running in separate thread"""
    webhook_received = pyqtSignal(dict)
    
    def __init__(self):
        super().__init__()
        self.app = Flask(__name__)
        self.setup_routes()
    
    def setup_routes(self):
        @self.app.route('/webhook', methods=['POST'])
        def handle_webhook():
            try:
                api_key = request.headers.get('APIKEY')
                if not api_key or api_key not in ALLOWED_API_KEYS:
                    return jsonify({"status": "error", "message": "Invalid API key"}), 403
                
                content_type = request.headers.get('Content-Type', '')
                if content_type != 'application/octet-stream':
                    return jsonify({"status": "error"}), 400
                
                body = request.get_data()
                if not body or len(body) < 8:
                    return jsonify({"status": "error"}), 400
                
                parsed = self.parse_binary_packet(body)
                self.webhook_received.emit(parsed)
                
                return jsonify({"status": "success"}), 200
            except Exception as e:
                return jsonify({"status": "error", "message": str(e)}), 500
        
        @self.app.route('/health', methods=['GET'])
        def health():
            return jsonify({"status": "ok", "server": "Webhook Server"}), 200
    
    def parse_binary_packet(self, data):
        if len(data) < 8:
            raise ValueError("Packet too small")
        
        parser = BinaryParser(data)
        magic = parser.read_uint32_be()
        if magic != WEBHOOK_MAGIC_HEADER:
            raise ValueError(f"Invalid magic header")
        
        data_length = parser.read_uint16_be()
        expected_total = 4 + 2 + data_length + 2
        if len(data) != expected_total:
            raise ValueError("Packet length mismatch")
        
        data_section = data[6:6+data_length]
        received_crc = struct.unpack('>H', data[6+data_length:6+data_length+2])[0]
        calculated_crc = BinaryParser.calculate_crc16(data[:6+data_length])
        
        if received_crc != calculated_crc:
            raise ValueError("CRC16 mismatch")
        
        parser = BinaryParser(data_section)
        num_items = parser.read_byte()
        
        items = []
        for i in range(num_items):
            item = {
                "index": i + 1,
                "name": parser.read_string(255),
                "timestamp": parser.read_uint64_be(),
                "rawValue": parser.read_string(255),
                "value": parser.read_string(255),
                "preValue": parser.read_string(255),
                "rate": parser.read_string(255),
                "changeAbsolute": parser.read_string(255),
                "errorCode": parser.read_byte(),
                "error": ""
            }
            item["error"] = BinaryParser.error_code_to_string(item["errorCode"])
            items.append(item)
        
        return {
            "receivedAt": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "magic": f"0x{magic:08X}",
            "dataLength": data_length,
            "numItems": num_items,
            "checksum": f"0x{received_crc:04X}",
            "packetSize": len(data),
            "items": items
        }
    
    def run(self):
        self.app.run(host='0.0.0.0', port=PORT, debug=False, use_reloader=False)


class WebhookUI(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("Binary Webhook Server - ESP32 Monitor")
        self.setGeometry(100, 100, 1400, 850)
        self.setStyleSheet(self.get_stylesheet())
        
        self.webhook_data = None
        self.history = []
        
        self.init_ui()
        self.start_server()
    
    def get_stylesheet(self):
        return """
        QMainWindow {
            background-color: #f0f0f0;
        }
        QGroupBox {
            font-weight: bold;
            border: 1px solid #cccccc;
            border-radius: 5px;
            margin-top: 10px;
            padding-top: 10px;
        }
        QGroupBox::title {
            subcontrol-origin: margin;
            left: 10px;
            padding: 0 3px 0 3px;
        }
        QPushButton {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-weight: bold;
        }
        QPushButton:hover {
            background-color: #45a049;
        }
        QPushButton:pressed {
            background-color: #3d8b40;
        }
        QPushButton#deleteBtn {
            background-color: #f44336;
        }
        QPushButton#deleteBtn:hover {
            background-color: #da190b;
        }
        QTableWidget {
            border: 1px solid #cccccc;
            gridline-color: #cccccc;
            background-color: white;
        }
        QHeaderView::section {
            background-color: #2196F3;
            color: white;
            padding: 5px;
            border: 1px solid #1976D2;
            font-weight: bold;
        }
        QLineEdit, QTextEdit {
            border: 1px solid #cccccc;
            border-radius: 3px;
            padding: 5px;
            background-color: white;
        }
        QTabWidget::pane {
            border: 1px solid #cccccc;
        }
        QTabBar::tab {
            background-color: #efefef;
            border: 1px solid #cccccc;
            padding: 8px 20px;
        }
        QTabBar::tab:selected {
            background-color: white;
            border-bottom: 2px solid #2196F3;
        }
        """
    
    def init_ui(self):
        central_widget = QWidget()
        self.setCentralWidget(central_widget)
        main_layout = QVBoxLayout(central_widget)
        
        # Header
        header_layout = QHBoxLayout()
        header_label = QLabel("🔌 Binary Webhook Server - ESP32 Monitor")
        header_font = QFont()
        header_font.setPointSize(14)
        header_font.setBold(True)
        header_label.setFont(header_font)
        header_layout.addWidget(header_label)
        header_layout.addStretch()
        
        self.status_indicator = QLabel("● Offline")
        status_font = QFont()
        status_font.setPointSize(11)
        status_font.setBold(True)
        self.status_indicator.setFont(status_font)
        self.status_indicator.setStyleSheet("color: red;")
        header_layout.addWidget(self.status_indicator)
        
        main_layout.addLayout(header_layout)
        main_layout.addSpacing(10)
        
        # Tabs
        tabs = QTabWidget()
        
        # Tab 1: Latest Data
        self.latest_tab = QWidget()
        self.setup_latest_tab()
        tabs.addTab(self.latest_tab, "📊 Latest Webhook")
        
        # Tab 2: History
        self.history_tab = QWidget()
        self.setup_history_tab()
        tabs.addTab(self.history_tab, "📜 History")
        
        # Tab 3: Settings
        self.settings_tab = QWidget()
        self.setup_settings_tab()
        tabs.addTab(self.settings_tab, "⚙️ Settings")
        
        main_layout.addWidget(tabs)
        
        # Status bar
        self.statusBar().showMessage("Server starting...")
    
    def setup_latest_tab(self):
        layout = QVBoxLayout(self.latest_tab)
        
        # Packet info
        packet_group = QGroupBox("📦 Packet Information")
        packet_layout = QFormLayout()
        
        self.info_magic = QLineEdit()
        self.info_magic.setReadOnly(True)
        packet_layout.addRow("Magic Header:", self.info_magic)
        
        self.info_size = QLineEdit()
        self.info_size.setReadOnly(True)
        packet_layout.addRow("Packet Size:", self.info_size)
        
        self.info_checksum = QLineEdit()
        self.info_checksum.setReadOnly(True)
        packet_layout.addRow("CRC16 Checksum:", self.info_checksum)
        
        self.info_time = QLineEdit()
        self.info_time.setReadOnly(True)
        packet_layout.addRow("Received At:", self.info_time)
        
        packet_group.setLayout(packet_layout)
        layout.addWidget(packet_group)
        
        # Data table
        table_group = QGroupBox("📋 Data Items")
        table_layout = QVBoxLayout()
        
        self.data_table = QTableWidget()
        self.data_table.setColumnCount(9)
        self.data_table.setHorizontalHeaderLabels([
            "Name", "Timestamp", "Raw Value", "Value", "Pre Value",
            "Rate", "Change Abs", "Error Code", "Error"
        ])
        self.data_table.horizontalHeader().setSectionResizeMode(QHeaderView.Stretch)
        self.data_table.setAlternatingRowColors(True)
        self.data_table.setStyleSheet("""
            QTableWidget {
                gridline-color: #e0e0e0;
            }
            QTableWidget::item:alternate {
                background-color: #f9f9f9;
            }
        """)
        table_layout.addWidget(self.data_table)
        table_group.setLayout(table_layout)
        layout.addWidget(table_group)
    
    def setup_history_tab(self):
        layout = QVBoxLayout(self.history_tab)
        
        button_layout = QHBoxLayout()
        
        clear_btn = QPushButton("🗑️  Clear History")
        clear_btn.setObjectName("deleteBtn")
        clear_btn.clicked.connect(self.clear_history)
        button_layout.addWidget(clear_btn)
        button_layout.addStretch()
        layout.addLayout(button_layout)
        
        self.history_list = QListWidget()
        layout.addWidget(self.history_list)
    
    def setup_settings_tab(self):
        layout = QVBoxLayout(self.settings_tab)
        
        settings_group = QGroupBox("⚙️ Server Settings")
        settings_layout = QFormLayout()
        
        host_input = QLineEdit("0.0.0.0")
        host_input.setReadOnly(True)
        settings_layout.addRow("Host:", host_input)
        
        port_input = QLineEdit(str(PORT))
        port_input.setReadOnly(True)
        settings_layout.addRow("Port:", port_input)
        
        api_keys_input = QLineEdit(", ".join(ALLOWED_API_KEYS))
        api_keys_input.setReadOnly(True)
        settings_layout.addRow("API Keys:", api_keys_input)
        
        settings_group.setLayout(settings_layout)
        layout.addWidget(settings_group)
        
        # Instructions
        instructions_group = QGroupBox("📖 Documentation")
        instructions_layout = QVBoxLayout()
        
        instructions = QTextEdit()
        instructions.setReadOnly(True)
        instructions.setHtml("""
<h3>How to send webhook data:</h3>

<b>cURL example:</b><br>
<code>curl -X POST http://localhost:5001/webhook \\<br>
&nbsp;&nbsp;-H "APIKEY: 123" \\<br>
&nbsp;&nbsp;-H "Content-Type: application/octet-stream" \\<br>
&nbsp;&nbsp;--data-binary @packet.bin</code>

<h3>Binary Packet Format:</h3>
<ul>
<li><b>Magic Header:</b> 0x4A4F4D4A (4 bytes)</li>
<li><b>Data Length:</b> uint16_t (2 bytes)</li>
<li><b>Data:</b> variable length</li>
<li><b>CRC16:</b> uint16_t (2 bytes)</li>
</ul>

<h3>Error Codes:</h3>
<ul>
<li><b>0x00:</b> no error</li>
<li><b>0x01:</b> Neg. Rate</li>
<li><b>0x02:</b> Rate too high</li>
<li><b>0xFF:</b> unknown error</li>
</ul>

<h3>Allowed API Keys:</h3>
<code>""" + ", ".join(ALLOWED_API_KEYS) + """</code>
        """)
        instructions_layout.addWidget(instructions)
        instructions_group.setLayout(instructions_layout)
        layout.addWidget(instructions_group)
    
    def start_server(self):
        self.server_thread = WebhookServer()
        self.server_thread.webhook_received.connect(self.on_webhook_received)
        self.server_thread.start()
        
        # Update status after delay
        QTimer.singleShot(1500, self.update_server_status)
    
    def update_server_status(self):
        self.status_indicator.setText("● Online")
        self.status_indicator.setStyleSheet("color: green; font-weight: bold;")
        self.statusBar().showMessage(f"✅ Server running on 0.0.0.0:{PORT}")
    
    def on_webhook_received(self, data):
        self.webhook_data = data
        self.history.append(data)
        self.update_display()
        
        # Add to history list
        history_text = f"[{data['receivedAt']}] {data['numItems']} items | Size: {data['packetSize']} bytes | CRC: {data['checksum']}"
        self.history_list.insertItem(0, QListWidgetItem(history_text))
        
        print(f"\n✅ Webhook received at {data['receivedAt']}")
        print(f"   Items: {data['numItems']}, Size: {data['packetSize']} bytes, CRC: {data['checksum']}")
    
    def update_display(self):
        if not self.webhook_data:
            return
        
        data = self.webhook_data
        
        # Update packet info
        self.info_magic.setText(data['magic'])
        self.info_size.setText(f"{data['packetSize']} bytes")
        self.info_checksum.setText(data['checksum'])
        self.info_time.setText(data['receivedAt'])
        
        # Update data table
        self.data_table.setRowCount(len(data['items']))
        
        for row, item in enumerate(data['items']):
            self.data_table.setItem(row, 0, QTableWidgetItem(item['name']))
            self.data_table.setItem(row, 1, QTableWidgetItem(str(item['timestamp'])))
            self.data_table.setItem(row, 2, QTableWidgetItem(item['rawValue']))
            self.data_table.setItem(row, 3, QTableWidgetItem(item['value']))
            self.data_table.setItem(row, 4, QTableWidgetItem(item['preValue']))
            self.data_table.setItem(row, 5, QTableWidgetItem(item['rate']))
            self.data_table.setItem(row, 6, QTableWidgetItem(item['changeAbsolute']))
            
            error_code_item = QTableWidgetItem(str(item['errorCode']))
            if item['errorCode'] != 0:
                error_code_item.setBackground(QColor(255, 100, 100))
                error_code_item.setForeground(QColor(255, 255, 255))
            self.data_table.setItem(row, 7, error_code_item)
            
            error_item = QTableWidgetItem(item['error'])
            if item['error'] and item['error'] != "no error":
                error_item.setBackground(QColor(255, 150, 150))
            self.data_table.setItem(row, 8, error_item)
    
    def clear_history(self):
        self.history_list.clear()
        self.history.clear()
        QMessageBox.information(self, "✅ Success", "History cleared successfully")
    
    def closeEvent(self, event):
        self.server_thread.quit()
        self.server_thread.wait()
        event.accept()


def main():
    app = QApplication(sys.argv)
    window = WebhookUI()
    window.show()
    sys.exit(app.exec_())


if __name__ == '__main__':
    main()
