#!/usr/bin/env python3
"""
RIMS POS - Raw Print Server (JetDirect Port 9100)
Menerima raw ESC/POS byte stream via TCP port 9100 dan meneruskannya
langsung ke printer Windows (RAW Spooler) tanpa perlu driver khusus.
"""

import sys
import os
import socket
import argparse
import logging
import threading
from datetime import datetime

# Windows Ctypes API untuk Winspool
import ctypes
from ctypes import wintypes

# Konfigurasi Logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] %(levelname)s: %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger("RawPrintServer")


class DOC_INFO_1(ctypes.Structure):
    _fields_ = [
        ("pDocName", wintypes.LPWSTR),
        ("pOutputFile", wintypes.LPWSTR),
        ("pDatatype", wintypes.LPWSTR),
    ]


class WindowsRawPrinter:
    """Mengirim byte mentah (RAW) langsung ke Windows Print Spooler."""
    def __init__(self, printer_name: str):
        self.target_name = printer_name
        self.winspool = ctypes.WinDLL("winspool.drv")
        self.resolved_name = self._resolve_printer_name(printer_name)

    def _resolve_printer_name(self, name: str) -> str:
        installed = self.list_printers()
        if not installed:
            logger.warning("Tidak ditemukan printer yang terpasang di Windows!")
            return name

        # 1. Exact match
        if name in installed:
            return name

        # 2. Case-insensitive match
        for p in installed:
            if p.lower() == name.lower():
                return p

        # 3. Match ignoring hyphens and spaces (e.g. POS80-Printer vs POS80 Printer vs POS80)
        clean_target = name.lower().replace("-", "").replace(" ", "").replace("_", "")
        for p in installed:
            clean_p = p.lower().replace("-", "").replace(" ", "").replace("_", "")
            if clean_target in clean_p or clean_p in clean_target:
                logger.info(f"Printer '{name}' dicocokkan otomatis dengan printer Windows: '{p}'")
                return p

        logger.warning(f"Printer '{name}' tidak ditemukan di daftar printer Windows. Tetap mencoba dengan nama tersebut.")
        return name

    @staticmethod
    def list_printers():
        """Mendapatkan daftar semua printer Windows yang terpasang."""
        printers = []
        try:
            winspool = ctypes.WinDLL("winspool.drv")
            PRINTER_ENUM_LOCAL = 0x00000002
            PRINTER_ENUM_CONNECTIONS = 0x00000004
            flags = PRINTER_ENUM_LOCAL | PRINTER_ENUM_CONNECTIONS

            needed = wintypes.DWORD(0)
            returned = wintypes.DWORD(0)
            winspool.EnumPrintersW(flags, None, 2, None, 0, ctypes.byref(needed), ctypes.byref(returned))

            if needed.value > 0:
                buffer = (ctypes.c_byte * needed.value)()
                res = winspool.EnumPrintersW(flags, None, 2, buffer, needed.value, ctypes.byref(needed), ctypes.byref(returned))
                if res:
                    class PRINTER_INFO_2(ctypes.Structure):
                        _fields_ = [
                            ("pServerName", wintypes.LPWSTR),
                            ("pPrinterName", wintypes.LPWSTR),
                            ("pShareName", wintypes.LPWSTR),
                            ("pPortName", wintypes.LPWSTR),
                            ("pDriverName", wintypes.LPWSTR),
                            ("pComment", wintypes.LPWSTR),
                            ("pLocation", wintypes.LPWSTR),
                            ("pDevMode", ctypes.c_void_p),
                            ("pSepFile", wintypes.LPWSTR),
                            ("pPrintProcessor", wintypes.LPWSTR),
                            ("pDatatype", wintypes.LPWSTR),
                            ("pParameters", wintypes.LPWSTR),
                            ("pSecurityDescriptor", ctypes.c_void_p),
                            ("Attributes", wintypes.DWORD),
                            ("Priority", wintypes.DWORD),
                            ("DefaultPriority", wintypes.DWORD),
                            ("StartTime", wintypes.DWORD),
                            ("UntilTime", wintypes.DWORD),
                            ("Status", wintypes.DWORD),
                            ("cJobs", wintypes.DWORD),
                            ("AveragePPM", wintypes.DWORD),
                        ]
                    p_info_array = ctypes.cast(buffer, ctypes.POINTER(PRINTER_INFO_2))
                    for i in range(returned.value):
                        p_name = p_info_array[i].pPrinterName
                        if p_name:
                            printers.append(p_name)
        except Exception as e:
            logger.debug(f"EnumPrinters error: {e}")

        # Fallback via PowerShell jika EnumPrintersW kosong
        if not printers:
            try:
                import subprocess
                cmd = 'powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name"'
                out = subprocess.check_output(cmd, shell=True, text=True)
                printers = [line.strip() for line in out.splitlines() if line.strip()]
            except Exception:
                pass

        return printers

    def print_raw(self, data: bytes, doc_name: str = "RIMS POS Raw Print Job") -> bool:
        """Mengirim data byte langsung ke printer Windows menggunakan RAW datatype."""
        if not data:
            return True

        hPrinter = wintypes.HANDLE()
        printer_name = self.resolved_name

        # 1. Open Printer
        res = self.winspool.OpenPrinterW(printer_name, ctypes.byref(hPrinter), None)
        if not res or not hPrinter.value:
            err = ctypes.GetLastError()
            logger.error(f"Gagal membuka printer '{printer_name}' (Win32 Error: {err})")
            return False

        try:
            # 2. Start Doc Printer
            doc_info = DOC_INFO_1()
            doc_info.pDocName = doc_name
            doc_info.pOutputFile = None
            doc_info.pDatatype = "RAW"

            job_id = self.winspool.StartDocPrinterW(hPrinter, 1, ctypes.byref(doc_info))
            if job_id == 0:
                err = ctypes.GetLastError()
                logger.error(f"StartDocPrinter gagal (Win32 Error: {err})")
                return False

            try:
                # 3. Start Page Printer
                if not self.winspool.StartPagePrinter(hPrinter):
                    err = ctypes.GetLastError()
                    logger.error(f"StartPagePrinter gagal (Win32 Error: {err})")
                    return False

                try:
                    # 4. Write Printer
                    written = wintypes.DWORD(0)
                    chunk_size = 8192
                    total_written = 0
                    for i in range(0, len(data), chunk_size):
                        chunk = data[i:i + chunk_size]
                        written.value = 0
                        ok = self.winspool.WritePrinter(
                            hPrinter,
                            chunk,
                            len(chunk),
                            ctypes.byref(written)
                        )
                        if not ok:
                            err = ctypes.GetLastError()
                            logger.error(f"WritePrinter gagal pada byte {i} (Win32 Error: {err})")
                            return False
                        total_written += written.value

                    logger.info(f"Berhasil menulis {total_written} bytes ke spooler '{printer_name}' (Job ID: {job_id})")
                    return True

                finally:
                    # End Page
                    self.winspool.EndPagePrinter(hPrinter)
            finally:
                # End Doc
                self.winspool.EndDocPrinter(hPrinter)
        finally:
            # Close Printer
            self.winspool.ClosePrinter(hPrinter)


def list_bluetooth_com_ports():
    """Daftar port COM Bluetooth (SPP) beserta nama perangkat yang terhubung."""
    result = []
    try:
        import subprocess
        import json as _json
        ps = (
            "$devs = Get-CimInstance Win32_PnPEntity; "
            "$names = @{}; "
            "$devs | Where-Object { $_.DeviceID -match '^BTHENUM\\\\DEV_([0-9A-F]{12})' } | "
            "ForEach-Object { $names[$Matches[1]] = $_.Name }; "
            "$devs | Where-Object { $_.Name -match 'Bluetooth.*\\((COM\\d+)\\)' } | ForEach-Object { "
            "  $com = ($_.Name -replace '.*\\((COM\\d+)\\).*','$1'); "
            "  $mac = ''; if ($_.DeviceID -match '&([0-9A-F]{12})_') { $mac = $Matches[1] }; "
            "  [pscustomobject]@{ com = $com; mac = $mac; name = $names[$mac] } "
            "} | ConvertTo-Json -Compress"
        )
        out = subprocess.check_output(
            ["powershell", "-NoProfile", "-Command", ps], text=True
        ).strip()
        if out:
            data = _json.loads(out)
            if isinstance(data, dict):
                data = [data]
            for d in data:
                result.append((d.get("com"), d.get("mac") or "", d.get("name") or ""))
    except Exception as e:
        logger.debug(f"list_bluetooth_com_ports error: {e}")
    return result


class SerialRawPrinter:
    """Mengirim byte mentah ke printer Bluetooth via virtual COM port (SPP)."""
    def __init__(self, com_port: str, retries: int = 3):
        self.com_port = com_port.upper()
        self.resolved_name = f"{self.com_port} (Bluetooth)"
        self.retries = retries
        self._lock = threading.Lock()

    def print_raw(self, data: bytes, doc_name: str = "RIMS POS Raw Print Job") -> bool:
        if not data:
            return True
        import time
        path = r"\\.\%s" % self.com_port
        # Lock: port COM hanya bisa dibuka satu koneksi dalam satu waktu
        with self._lock:
            for attempt in range(1, self.retries + 1):
                try:
                    with open(path, "wb", buffering=0) as port:
                        chunk_size = 512
                        for i in range(0, len(data), chunk_size):
                            port.write(data[i:i + chunk_size])
                        port.flush()
                    # Beri waktu printer memproses buffer sebelum port ditutup/ dibuka lagi
                    time.sleep(0.3)
                    logger.info(f"Berhasil menulis {len(data)} bytes ke {self.resolved_name}")
                    return True
                except Exception as e:
                    logger.warning(f"Percobaan {attempt}/{self.retries} ke {self.com_port} gagal: {e}")
                    time.sleep(1.0)
        logger.error(f"Gagal mengirim ke {self.com_port}. Pastikan printer menyala & sudah di-pair.")
        return False


class BluetoothRfcommPrinter:
    """Mengirim byte mentah langsung ke printer Bluetooth via RFCOMM socket (tanpa driver/COM port)."""
    def __init__(self, mac_address: str, channel: int = 1, retries: int = 3):
        clean = mac_address.replace(":", "").replace("-", "").strip().upper()
        if len(clean) == 12:
            self.mac_address = ":".join(clean[i:i + 2] for i in range(0, 12, 2))
        else:
            self.mac_address = mac_address.strip()
        self.channel = channel
        self.retries = retries
        self.resolved_name = f"{self.mac_address} (Bluetooth RFCOMM ch:{channel})"
        self._lock = threading.Lock()

    def print_raw(self, data: bytes, doc_name: str = "RIMS POS Raw Print Job") -> bool:
        if not data:
            return True
        if not hasattr(socket, "AF_BLUETOOTH"):
            logger.error("Python di sistem ini tidak mendukung socket.AF_BLUETOOTH.")
            return False
        import time
        with self._lock:
            for attempt in range(1, self.retries + 1):
                sock = None
                try:
                    sock = socket.socket(socket.AF_BLUETOOTH, socket.SOCK_STREAM, socket.BTPROTO_RFCOMM)
                    sock.settimeout(8.0)
                    sock.connect((self.mac_address, self.channel))
                    sock.sendall(data)
                    time.sleep(0.3)
                    logger.info(f"Berhasil menulis {len(data)} bytes ke {self.resolved_name}")
                    return True
                except Exception as e:
                    logger.warning(f"Percobaan {attempt}/{self.retries} ke {self.mac_address} gagal: {e}")
                    time.sleep(1.0)
                finally:
                    if sock is not None:
                        try:
                            sock.close()
                        except Exception:
                            pass
        logger.error(f"Gagal mengirim ke {self.mac_address}. Pastikan printer menyala & sudah di-pair di Windows.")
        return False


def get_local_ip_addresses():
    """Mendeteksi semua alamat IP lokal komputer ini."""
    ips = set()
    try:
        # Trik socket UDP untuk mengetahui IP interface aktif
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.settimeout(0.5)
        s.connect(("8.8.8.8", 80))
        ips.add(s.getsockname()[0])
        s.close()
    except Exception:
        pass

    try:
        hostname = socket.gethostname()
        for ip in socket.gethostbyname_ex(hostname)[2]:
            if not ip.startswith("127."):
                ips.add(ip)
    except Exception:
        pass

    return sorted(list(ips)) if ips else ["127.0.0.1"]


def handle_client(client_socket, client_address, raw_printer, timeout: float = 3.0):
    """Menangani satu sesi koneksi cetak RAW dari aplikasi POS."""
    client_ip, client_port = client_address
    logger.info(f"Koneksi masuk dari {client_ip}:{client_port}")

    buffer = bytearray()
    client_socket.settimeout(timeout)

    try:
        while True:
            try:
                data = client_socket.recv(4096)
                if not data:
                    break
                buffer.extend(data)
            except socket.timeout:
                # Timeout idle menandakan aplikasi selesai mengirim byte stream
                break
    except Exception as e:
        logger.warning(f"Error membaca socket dari {client_ip}: {e}")
    finally:
        try:
            client_socket.close()
        except Exception:
            pass

    total_bytes = len(buffer)
    if total_bytes == 0:
        logger.warning(f"Koneksi dari {client_ip} ditutup tanpa mengirim data cetak.")
        return

    logger.info(f"Menerima {total_bytes} bytes dari {client_ip}. Mengirim ke printer '{raw_printer.resolved_name}'...")
    success = raw_printer.print_raw(bytes(buffer))
    if success:
        logger.info(f"SUKSES: Dokumen {total_bytes} bytes selesai dikirim ke '{raw_printer.resolved_name}'")
    else:
        logger.error(f"GAGAL: Tidak dapat mencetak {total_bytes} bytes ke '{raw_printer.resolved_name}'")


def build_printer(printer_name: str, com_port: str = None, bt_mac: str = None, bt_channel: int = 1):
    if bt_mac:
        return BluetoothRfcommPrinter(bt_mac, channel=bt_channel)
    if com_port:
        return SerialRawPrinter(com_port)
    return WindowsRawPrinter(printer_name)


def start_server(host: str, port: int, printer_name: str, client_timeout: float, com_port: str = None, bt_mac: str = None, bt_channel: int = 1):
    raw_printer = build_printer(printer_name, com_port, bt_mac, bt_channel)

    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

    try:
        server.bind((host, port))
        server.listen(10)
    except Exception as e:
        logger.critical(f"Gagal membuka port {port} pada {host}: {e}")
        logger.info("Pastikan port 9100 tidak sedang digunakan oleh aplikasi lain.")
        sys.exit(1)

    local_ips = get_local_ip_addresses()

    print("\n" + "=" * 65)
    print("  RIMS POS - RAW PRINT SERVER (JETDIRECT 9100)")
    print("=" * 65)
    print(f"  Target Windows Printer : {raw_printer.resolved_name}")
    print(f"  Listening Socket       : {host}:{port}")
    print("  Alamat IP Komputer ini :")
    for ip in local_ips:
        print(f"    -> http://{ip}:{port} atau set IP LAN di POS: {ip} port: {port}")
    print("=" * 65)
    print("  Server siap menerima job cetak! Tekan Ctrl+C untuk berhenti.\n")

    try:
        while True:
            client_sock, client_addr = server.accept()
            client_thread = threading.Thread(
                target=handle_client,
                args=(client_sock, client_addr, raw_printer, client_timeout),
                daemon=True
            )
            client_thread.start()
    except KeyboardInterrupt:
        print("\n[Server Dimatikan] Menutup socket...")
    finally:
        server.close()
        logger.info("Server berhenti.")


def main():
    parser = argparse.ArgumentParser(description="RIMS POS Raw Print Server (JetDirect Port 9100)")
    parser.add_argument("--host", default="0.0.0.0", help="Host IP listen (default: 0.0.0.0)")
    parser.add_argument("--port", type=int, default=9100, help="Port listen (default: 9100)")
    parser.add_argument("--printer", default="POS80-Printer", help="Nama printer Windows (default: POS80-Printer)")
    parser.add_argument("--com", default=None, help="Port COM printer Bluetooth (mis. COM5). Jika diisi, --printer diabaikan")
    parser.add_argument("--bt", default=None, help="MAC address Bluetooth printer (mis. 86:67:7A:38:5B:5C) via RFCOMM langsung tanpa driver")
    parser.add_argument("--bt-channel", type=int, default=1, help="Channel RFCOMM Bluetooth (default: 1)")
    parser.add_argument("--timeout", type=float, default=2.5, help="Timeout idle baca socket dalam detik (default: 2.5)")
    parser.add_argument("--list-printers", action="store_true", help="Tampilkan semua printer yang terpasang lalu keluar")
    parser.add_argument("--test", action="store_true", help="Kirim tes cetak singkat ke printer lalu keluar")

    args = parser.parse_args()

    if args.list_printers:
        print("Daftar Printer Windows yang Terpasang:")
        printers = WindowsRawPrinter.list_printers()
        if not printers:
            print("  (Tidak ditemukan printer)")
        else:
            for i, p in enumerate(printers, 1):
                print(f"  {i}. {p}")
        print("\nPort COM Bluetooth (gunakan dengan --com):")
        bt_ports = list_bluetooth_com_ports()
        if not bt_ports:
            print("  (Tidak ditemukan)")
        else:
            for com, mac, name in bt_ports:
                label = name or ("(port incoming / tanpa perangkat)" if mac in ("", "000000000000") else "?")
                print(f"  {com:<6} {mac:<12} {label}")
        return

    if args.test:
        raw_printer = build_printer(args.printer, args.com, args.bt, args.bt_channel)
        print(f"Mengirim tes cetak ke printer: '{raw_printer.resolved_name}'...")
        # ESC/POS Initialize, Text, Feed & Cut
        test_bytes = (
            b"\x1b\x40"                      # ESC @ (Initialize)
            b"\x1b\x61\x01"                  # ESC a 1 (Center align)
            b"================================\n"
            b"      RIMS POS PRINT SERVER     \n"
            b"================================\n"
            b"Printer : " + raw_printer.resolved_name.encode('ascii', 'ignore') + b"\n"
            b"Waktu   : " + datetime.now().strftime('%d-%m-%Y %H:%M:%S').encode('ascii') + b"\n"
            b"Status  : KONEKSI RAW BERHASIL!\n"
            b"================================\n"
            b"\n\n\n"
            b"\x1d\x56\x41\x03"              # GS V A 3 (Paper Cut)
        )
        ok = raw_printer.print_raw(test_bytes, doc_name="RIMS POS Test Print")
        if ok:
            print("Tes cetak BERHASIL dikirim ke printer!")
        else:
            print("Tes cetak GAGAL! Periksa apakah printer menyala dan kabel terhubung.")
        return

    start_server(args.host, args.port, args.printer, args.timeout, args.com, args.bt, args.bt_channel)


if __name__ == "__main__":
    main()
