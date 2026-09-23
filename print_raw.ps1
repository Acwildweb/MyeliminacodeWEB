param(
    [Parameter(Mandatory=$true)]
    [string]$PrinterName,
    [Parameter(Mandatory=$true)]
    [string]$FilePath,
    [int]$RawPort = 9100
)

# Codice C# per la stampa RAW diretta via Windows API (winspool.drv)
$source = @"
using System;
using System.Runtime.InteropServices;

public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }

    [DllImport("winspool.drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi)]
    public static extern bool OpenPrinter(
        [MarshalAs(UnmanagedType.LPStr)] string szPrinter,
        out IntPtr hPrinter,
        IntPtr pd);

    [DllImport("winspool.drv", EntryPoint = "ClosePrinter", SetLastError = true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi)]
    public static extern Int32 StartDocPrinter(
        IntPtr hPrinter,
        Int32 level,
        [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);

    [DllImport("winspool.drv", EntryPoint = "EndDocPrinter", SetLastError = true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint = "StartPagePrinter", SetLastError = true)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint = "EndPagePrinter", SetLastError = true)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.drv", EntryPoint = "WritePrinter", SetLastError = true)]
    public static extern bool WritePrinter(
        IntPtr hPrinter,
        IntPtr pBytes,
        Int32 dwCount,
        out Int32 dwWritten);

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
        IntPtr pUnmanagedBytes = Marshal.AllocCoTaskMem(bytes.Length);
        Marshal.Copy(bytes, 0, pUnmanagedBytes, bytes.Length);
        Int32 nWritten = 0;
        bool bSuccess = WritePrinter(hPrinter, pUnmanagedBytes, bytes.Length, out nWritten);
        int errCode = bSuccess ? 0 : Marshal.GetLastWin32Error();
        Marshal.FreeCoTaskMem(pUnmanagedBytes);
        EndPagePrinter(hPrinter);
        EndDocPrinter(hPrinter);
        ClosePrinter(hPrinter);
        return errCode;
    }
}
"@

try {
    Add-Type -TypeDefinition $source -Language CSharp -ErrorAction Stop
} catch {
    Write-Output "ERRORE_COMPILE: $($_.Exception.Message)"
    exit 2
}

if (-not (Test-Path $FilePath)) {
    Write-Output "ERRORE_FILE: File non trovato: $FilePath"
    exit 3
}

# ── Tentativo 1: OpenPrinter via winspool.drv ────────────────────────────────
# Funziona sia per stampanti locali sia per stampanti di rete installate
# tramite "Aggiungi stampante" (\\HOST\share o nome Windows).
$bytes = [System.IO.File]::ReadAllBytes($FilePath)
$errCode = [RawPrinterHelper]::SendBytesToPrinter($PrinterName, $bytes)
if ($errCode -eq 0) {
    Write-Output "OK"
    exit 0
}

# ── Tentativo 2: TCP socket diretto (se UNC e stampante NON installata) ───────
# Solo se OpenPrinter fallisce con 1801 (ERROR_INVALID_PRINTER_NAME):
# la stampante non è installata localmente. In questo caso tentiamo la
# connessione TCP diretta al totem_print_server.ps1 sulla porta $RawPort.
if ($errCode -eq 1801 -and $PrinterName -like '\\*') {
    $PrinterName = '\\' + $PrinterName.TrimStart('\')
    $hostPart = ($PrinterName -replace '^\\\\([^\\]+)\\.*', '$1')
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $client.SendTimeout    = 10000
        $client.ReceiveTimeout = 10000
        $client.Connect($hostPart, $RawPort)
        $stream = $client.GetStream()
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Flush()
        $client.Close()
        Write-Output "OK"
        exit 0
    } catch {
        Write-Output "ERRORE_TCP: $($_.Exception.Message) (host=$hostPart porta=$RawPort)"
        exit 1
    }
}

# OpenPrinter fallito con errore diverso da 1801
Write-Output "ERRORE_STAMPA: Win32 error code $errCode"
exit 1

# (blocco catch per errori imprevisti del ReadAllBytes ecc.)
try { } catch {
    Write-Output "ERRORE_ECCEZIONE: $($_.Exception.Message)"
    exit 4
}
