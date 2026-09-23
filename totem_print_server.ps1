<#
.SYNOPSIS
    Server di stampa RAW TCP per il totem.
    Da eseguire sul computer totem COME AMMINISTRATORE.

.DESCRIPTION
    Ascolta su una porta TCP (default 9100) e ogni volta che riceve dati
    li invia DIRETTAMENTE alla stampante locale via winspool.drv (byte RAW).
    Non serve XAMPP, non serve nessun driver aggiuntivo.

.PARAMETER Port
    Porta TCP su cui ascoltare (default: 9100).

.PARAMETER PrinterName
    Nome esatto della stampante locale (es. "NPI Integration Driver (Copia 1)").
    Se omesso, viene rilevata automaticamente la prima stampante locale.

.PARAMETER LogFile
    Path del file di log (default: cartella script\print_server.log).

.EXAMPLE
    # Avvio semplice (rileva stampante automaticamente):
    powershell -ExecutionPolicy Bypass -File totem_print_server.ps1

    # Avvio con stampante esplicita:
    powershell -ExecutionPolicy Bypass -File totem_print_server.ps1 -PrinterName "NPI Integration Driver (Copia 1)"

    # Per aprire la porta nel firewall (eseguire UNA VOLTA come admin):
    netsh advfirewall firewall add rule name="Totem Print Server" dir=in action=allow protocol=TCP localport=9100
#>
param(
    [int]$Port        = 9100,
    [string]$PrinterName = "",
    [string]$LogFile  = ""
)

# ── Inizializzazione log ──────────────────────────────────────────────────────
if ($LogFile -eq "") {
    $LogFile = Join-Path $PSScriptRoot "print_server.log"
}
function Write-Log {
    param([string]$Msg, [string]$Level = "INFO")
    $ts  = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $row = "[$ts][$Level] $Msg"
    Write-Host $row
    Add-Content -Path $LogFile -Value $row -ErrorAction SilentlyContinue
}

# ── Classi C# per stampa RAW ──────────────────────────────────────────────────
# DirectPortHelper: scrittura diretta sulla porta USB (\\.\USB001 ecc.)
#   → bypasssa completamente il driver GDI, i byte ESC/POS arrivano grezzi
# RawPrinterHelper: fallback via winspool.drv (usato se la porta non è USB)
$source = @"
using System;
using System.Runtime.InteropServices;

// ── Scrittura diretta sulla porta USB (bypassa driver GDI) ───────────────────
public class DirectPortHelper {
    [DllImport("kernel32.dll", CharSet=CharSet.Auto, SetLastError=true)]
    public static extern IntPtr CreateFile(
        string lpFileName, uint dwDesiredAccess, uint dwShareMode,
        IntPtr lpSecurityAttributes, uint dwCreationDisposition,
        uint dwFlagsAndAttributes, IntPtr hTemplateFile);

    [DllImport("kernel32.dll", SetLastError=true)]
    public static extern bool WriteFile(
        IntPtr hFile, byte[] lpBuffer, uint nBytesToWrite,
        out uint lpNumberOfBytesWritten, IntPtr lpOverlapped);

    [DllImport("kernel32.dll", SetLastError=true)]
    public static extern bool CloseHandle(IntPtr hObject);

    private const uint GENERIC_WRITE   = 0x40000000;
    private const uint FILE_SHARE_READ = 0x00000001;
    private const uint FILE_SHARE_WRITE= 0x00000002;
    private const uint OPEN_EXISTING   = 3;

    public static int WriteToPort(string portName, byte[] bytes) {
        // portName: "USB001", "USB002", ecc.
        string path = @"\\.\" + portName;
        IntPtr h = CreateFile(path, GENERIC_WRITE, FILE_SHARE_READ | FILE_SHARE_WRITE,
                              IntPtr.Zero, OPEN_EXISTING, 0, IntPtr.Zero);
        if (h == new IntPtr(-1)) {
            return Marshal.GetLastWin32Error();
        }
        uint written = 0;
        bool ok = WriteFile(h, bytes, (uint)bytes.Length, out written, IntPtr.Zero);
        int err = ok ? 0 : Marshal.GetLastWin32Error();
        CloseHandle(h);
        return err;
    }
}

// ── Fallback: stampa via winspool.drv (RAW) ──────────────────────────────────
public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }

    [DllImport("winspool.drv", EntryPoint="OpenPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
    public static extern bool OpenPrinter(
        [MarshalAs(UnmanagedType.LPStr)] string szPrinter,
        out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.drv", EntryPoint="ClosePrinter")]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint="StartDocPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
    public static extern Int32 StartDocPrinter(
        IntPtr hPrinter, Int32 level,
        [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);

    [DllImport("winspool.drv", EntryPoint="EndDocPrinter")]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint="StartPagePrinter")]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint="EndPagePrinter")]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint="WritePrinter", SetLastError=true)]
    public static extern bool WritePrinter(
        IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

    public static int SendBytesToPrinter(string szPrinterName, byte[] bytes) {
        IntPtr hPrinter = IntPtr.Zero;
        DOCINFOA di = new DOCINFOA();
        di.pDocName  = "Biglietto Totem";
        di.pDataType = "RAW";

        if (!OpenPrinter(szPrinterName, out hPrinter, IntPtr.Zero)) {
            return Marshal.GetLastWin32Error();
        }
        if (StartDocPrinter(hPrinter, 1, di) <= 0) {
            int e = Marshal.GetLastWin32Error();
            ClosePrinter(hPrinter);
            return e;
        }
        if (!StartPagePrinter(hPrinter)) {
            int e = Marshal.GetLastWin32Error();
            EndDocPrinter(hPrinter);
            ClosePrinter(hPrinter);
            return e;
        }
        IntPtr p = Marshal.AllocCoTaskMem(bytes.Length);
        Marshal.Copy(bytes, 0, p, bytes.Length);
        Int32 written = 0;
        bool ok = WritePrinter(hPrinter, p, bytes.Length, out written);
        Marshal.FreeCoTaskMem(p);
        EndPagePrinter(hPrinter);
        EndDocPrinter(hPrinter);
        ClosePrinter(hPrinter);
        return ok ? 0 : Marshal.GetLastWin32Error();
    }
}
"@

try {
    Add-Type -TypeDefinition $source -Language CSharp -ErrorAction Stop
    Write-Log "Classi DirectPortHelper e RawPrinterHelper caricate."
} catch {
    Write-Log "ERRORE compilazione C#: $($_.Exception.Message)" "ERROR"
    exit 1
}

# ── Rileva stampante locale se non specificata ────────────────────────────────
if ($PrinterName -eq "" -or $PrinterName -eq $null) {
    $local = Get-Printer | Where-Object { $_.Type -eq "Local" } | Select-Object -First 1
    if ($local) {
        $PrinterName = $local.Name
        Write-Log "Stampante rilevata automaticamente: '$PrinterName'"
    } else {
        Write-Log "ERRORE: nessuna stampante locale trovata. Usa -PrinterName 'Nome'." "ERROR"
        exit 1
    }
}

# ── Rileva la porta USB della stampante (per scrittura diretta) ───────────────
$UsbPortName = ""
try {
    $prWmi = Get-WmiObject -Query "SELECT PortName FROM Win32_Printer WHERE Name='$PrinterName'" -ErrorAction Stop
    if ($prWmi -and $prWmi.PortName -match "^USB\d+$") {
        $UsbPortName = $prWmi.PortName
        Write-Log "Porta USB rilevata: $UsbPortName (scrittura diretta attiva)"
    } else {
        Write-Log "Porta non USB ($($prWmi.PortName)): uso winspool fallback."
    }
} catch {
    Write-Log "Impossibile rilevare porta USB: $($_.Exception.Message)" "WARN"
}

# ── Avvia il listener TCP ─────────────────────────────────────────────────────
try {
    $endpoint = New-Object System.Net.IPEndPoint([System.Net.IPAddress]::Any, $Port)
    $listener = New-Object System.Net.Sockets.TcpListener($endpoint)
    $listener.Start()
} catch {
    Write-Log "ERRORE avvio listener su porta $Port : $($_.Exception.Message)" "ERROR"
    Write-Log "Suggerimento: assicurati che la porta non sia già in uso e di eseguire come Amministratore." "ERROR"
    exit 1
}

Write-Log "=========================================="
Write-Log "  Server stampa RAW in ascolto"
Write-Log "  Porta TCP : $Port"
Write-Log "  Stampante : $PrinterName"
if ($UsbPortName) {
Write-Log "  USB diretto: $UsbPortName (bypass driver GDI)"
} else {
Write-Log "  Modalità  : winspool fallback"
}
Write-Log "  Log       : $LogFile"
Write-Log "  Premi Ctrl+C per fermare."
Write-Log "=========================================="

# ── Loop principale ───────────────────────────────────────────────────────────
try {
    while ($true) {
        $client = $listener.AcceptTcpClient()
        $remote = $client.Client.RemoteEndPoint.ToString()
        Write-Log "Connessione da $remote"

        try {
            $stream = $client.GetStream()
            $ms     = New-Object System.IO.MemoryStream
            $buf    = New-Object byte[] 8192

            # Leggi tutto finché la connessione non viene chiusa (EOF)
            while ($true) {
                $n = $stream.Read($buf, 0, $buf.Length)
                if ($n -eq 0) { break }
                $ms.Write($buf, 0, $n)
            }

            $bytes = $ms.ToArray()
            $ms.Dispose()
            Write-Log "  Ricevuti $($bytes.Length) byte da $remote"

            if ($bytes.Length -gt 0) {
                # ── Tentativo 1: scrittura diretta sulla porta USB (bypass driver GDI) ──
                if ($UsbPortName) {
                    $errCode = [DirectPortHelper]::WriteToPort($UsbPortName, $bytes)
                    if ($errCode -eq 0) {
                        Write-Log "  Stampa OK (USB diretto $UsbPortName)"
                    } else {
                        Write-Log "  WriteToPort errore Win32: $errCode - fallback a winspool" "WARN"
                        # Fallback: winspool (potrebbe non funzionare con driver GDI, ma ci proviamo)
                        $errCode2 = [RawPrinterHelper]::SendBytesToPrinter($PrinterName, $bytes)
                        if ($errCode2 -eq 0) {
                            Write-Log "  Stampa OK (winspool fallback)"
                        } else {
                            Write-Log "  ERRORE winspool Win32: $errCode2" "ERROR"
                        }
                    }
                } else {
                    # Porta non USB: usa solo winspool
                    $errCode = [RawPrinterHelper]::SendBytesToPrinter($PrinterName, $bytes)
                    if ($errCode -eq 0) {
                        Write-Log "  Stampa OK (winspool)"
                    } else {
                        Write-Log "  ERRORE stampa Win32: $errCode" "ERROR"
                    }
                }
            } else {
                Write-Log "  Nessun dato ricevuto, connessione ignorata." "WARN"
            }
        } catch {
            Write-Log "  Errore gestione client: $($_.Exception.Message)" "ERROR"
        } finally {
            $client.Close()
        }
    }
} finally {
    $listener.Stop()
    Write-Log "Server fermato."
}
