Imports System.Net.Sockets
Imports System.Windows.Forms.VisualStyles.VisualStyleElement.TrayNotify
Imports System.IO

Public Module Globali
    Public CIDb As New CDb
    Public DbConnection As New DbParser
    Private PF As Server
    Public DatiDb As CDb.Tdb
    Public LocalTcpPort As String
    Public ilmonitor As Monitor2
    Public WebMonitor As Boolean = False
    Public PercorsoWeb As String = ""
    Public Azzerati = True


    'VARIABILI DI REGISTRO
    Public TipoMonitor As String = "" '0 di sala - 1 di reparto
    Public Avanzamento As String = "" '0 tramite UDP - 1 tramite mouse
    Public LetturaNumeri As String = "" '0 su registro - 1 su file
    Public PathFileContatori As String = "" 'Percorso su cui vengono scritti i files dei contatori reparti
    Public MonitorAttivo As String = "" '0 non attivo - 1 attivo
    Public UrlMonitorAttivo As String = ""
    Public Spegnimento As String = ""
    Public Riavvio As String = ""
    Public SpegnimentoOra As String = ""
    Public Ibernazione As String = ""
    Public IbernazioneOra As String = ""
    Public MaxIndiceCall As Integer = 99
    Public NonChiamareNumeri As Boolean = False
    Public BasatoXML As String = ""

    Public IdCliente As String = ""
    Public UrlPing As String = "" 'URL richiamato per indicare monitor attivo
    Public UrlDati As String = "" 'URL richiamato per registrare su web i numeri chiamati
    Public CSVRAttivo As String = "" '1 Contact server Attivo - 0 Contact server non Attivo
    Public ImgVidTimer As String = "" 'Timer ciclo immagini e video
    Public MeteoTimer As String = "" 'Timer aggiornamento meteo
    Public NewsTimer As String = "" 'Timer aggiornamento news
    Public ClienteAttivo As String = "" '0 non attivo - 1 attivo
    Public ClienteAttivoTimer As String = "" 'Timer controllo cliente attivo
    Public ContactSVRTimer As String = "" 'Timer ping su contactsvr
    Public NoControllo As String = "" 'se valorizzato indica la data fino a quando non controllare
    Public UrlAggiornamenti As String = "https://myeliminacode.acwild.eu/appOffline" 'url per scaricare aggiornamento
    Public ScaricoFilesTimer As String = "" 'Timer ciclo scaricamento immagini e video
    Public RepartoImmagini As String = "" 'Reparto indicato nelle immagini da scaricare se presente
    Public EReparti As String = ""
    Public vReparti() As String
    Public fReparti() As String
    Public dimFontReparti() As Integer
    Public xReparti() As Integer
    Public yReparti() As Integer
    Public BoldReparti() As String
    Public ColorReparti() As String
    Public nReparti As Integer = 0

    Public vLblTurni() As Label
    Public vLblPostazioniTurni() As Label
    Public vLblContatoriTurni() As Label
    Public nTurni As Integer = 0

    Public SincroAttiva As Boolean = False

    Public BoxVideoPresente As String = ""
    Public BoxImgPresente As String = ""
    Public BoxMeteoPresente As String = ""
    Public LinkUrlMeteo As String = ""
    Public BoxNewsPresente As String = ""
    Public LinkUrlNews As String = ""
    Public BoxChiamatiPresente As String = ""
    Public VideoDaMostrare As String = ""

    Public EMp3 As String = ""
    Public vMP3() As String

    Public WebRadio As String = ""
    Public WebRadioUrl As String = ""
    Public startInfo As Process

    Public RepartoSel As Integer = -1

    Public WBoxVid As Integer
    Public HBoxVid As Integer
    Public XBoxVid As Integer
    Public YBoxVid As Integer

    Public WBoxImg As Integer
    Public HBoxImg As Integer
    Public XBoxImg As Integer
    Public YBoxImg As Integer

    Public WBoxMeteo As Integer
    Public HBoxMeteo As Integer
    Public XBoxMeteo As Integer
    Public YBoxMeteo As Integer

    Public WBoxNews As Integer
    Public HBoxNews As Integer
    Public XBoxNews As Integer
    Public YBoxNews As Integer

    Public WBoxChiamati As Integer
    Public HBoxChiamati As Integer
    Public XBoxChiamati As Integer
    Public YBoxChiamati As Integer

    Public vChiamati() As String
    Public maxSizeChiamati As Integer = 8

    'VARIABILI DI FUNZIONAMENTO
    Public PathContatori As String = ""
    Public pathImmagini As String = "c:\users\public\immagini\"
    Public pathVideo As String = "c:\users\public\video\"
    Public pathservizio As String = "c:\users\public\servizio\servizio.png"
    Public pathcliente As String = "c:\users\public\cliente\"
    Public pathVideocliente As String = "c:\users\public\videocliente\"
    Public PathAudio As String = ""
    'Public UrlJson As String = "https://localhost/myeliminacode/api/get_json_config.php?idmonitor=Monitor-xzK0K"
    Public UrlJson As String = "https://myeliminacode.acwild.eu/api/get_json_config.php?idmonitor="
    Public UrlContact As String = "https://myeliminacode.acwild.eu/appOffline/contactsvr/contactsvr/"

    Public Lfiles() As String
    Public Lvideo() As String
    Public rImgVideo As Integer = 6
    Public N As Integer = -1
    Public M As Integer = -1
    Public NumImmagini As Integer = -1
    Public NumVideo As Integer = -1
    Dim NumGiroImg As Integer = 0

    'CONFIGURAZIONE MONITOR
    Public SfondoMonitor As String = ""
    Public WSfondoMonitor As Integer = 0
    Public HSfondoMonitor As Integer = 0
    Public SfondoNumeroTuttoSchermo As String = ""
    Public FontNumeroTuttoSchermo As String = ""
    Public DimensioneFontNumeroTuttoSchermo As String = ""
    Public GrassettoFontNumeroTuttoSchermo As String = ""
    Public FontTurnoTuttoSchermo As String = ""
    Public DimensioneFontTurnoTuttoSchermo As String = ""
    Public GrassettoFontTurnoTuttoSchermo As String = ""
    Public FontNumeroTuttoSchermoRep As String = ""
    Public DimensioneFontNumeroTuttoSchermoRep As String = ""
    Public GrassettoFontNumeroTuttoSchermoRep As String = ""
    Public NumeroTuttoSchermoAttivo As String = ""
    Public FontBoxChiamati As String = ""
    Public GrassettoBoxChiamati As String = ""

    Public InConfigurazione As Boolean = False
    Public InScaricamento As Boolean = False
    Public InEsecuzioneVid As Boolean = False
    Public InEsecuzioneAud As Boolean = False


    Public SfondoTotem As String = ""

    'FORMS
    Public frmMonitor As Monitor2

    Dim cBackGround As Background

    'GESTIONE RICEZIONE MESSAGGI UDP
    Public receivingUdpClient As UdpClient
    Public RemoteIpEndPoint As New System.Net.IPEndPoint(System.Net.IPAddress.Any, 0)
    Public ThreadReceive As System.Threading.Thread
    Public SocketNO As Integer
    Public vChiamate(2, 0) As String
    Public nChiamate As Integer = 0

    Public NoServer As Boolean = False

    Public chiediAzzeramento As Boolean = False

    Public LoggaSql As Boolean = False

    Public Sub Main()
        Dim Processo As New System.Diagnostics.Process
        Dim LRet As String = ""

        LRet = Process.GetCurrentProcess.ProcessName
        If Process.GetProcessesByName(LRet).GetUpperBound(0) > 0 Then
            MsgBox("Applicazione già in uso!")
            Application.ExitThread()
            Application.Exit()
            End
        End If
        DatiDb = CIDb.DatiDb
        If DatiDb.TipoDb = "" Then
            Dim FImpostazioniDb As New FrmImpostazioniDb
            FImpostazioniDb.ShowDialog()
            End
        Else
            Select Case DatiDb.TipoDb
                Case "0"
                    DbConnection.TipoDb = "0"
                    DbConnection.NomeODBC = DatiDb.NomeODBC
                Case "1"
                    DbConnection.TipoDb = "1"
                    DbConnection.FilePath = DatiDb.PathAccess
                    DbConnection.PwdAccesso = DatiDb.Pwd
                Case "2", "3", "4"
                    DbConnection.TipoDb = DatiDb.TipoDb
                    DbConnection.NomeServer = DatiDb.NomeServer
                    DbConnection.NomeDatabase = DatiDb.NomeDb
                    DbConnection.UseridAccesso = DatiDb.Uid
                    DbConnection.PwdAccesso = DatiDb.Pwd
            End Select
            If Not DbConnection.Connetti Then
                If MsgBox("Connessione non riuscita!" & vbCrLf & "Inserire i dati manualmente?", MsgBoxStyle.YesNo, "Errore di connessione") = MsgBoxResult.Yes Then
                    Dim FImpostazioniDb As New FrmImpostazioniDb
                    FImpostazioniDb.ShowDialog()
                End If
                End
            End If
        End If

        verdb()

        Dim Regi As New Registro
        Dim sPort As String
        Dim testo As String

        sPort = Regi.Leggi("SERVERMEC", "SERVERPORT")
        IdCliente = Regi.Leggi("ContactSVR", "IdCliente")
        WebMonitor = IIf(Regi.Leggi("ContactSVR", "WebMonitor") = "1", True, False)
        PercorsoWeb = Regi.Leggi("ContactSVR", "PercorsoWeb")

        testo = Regi.Leggi("ContactSVR", "Logging")
        If testo = "SI" Then
            LoggaSql = True
        End If

        If sPort = "" Or IdCliente = "" Then
            Server.ShowDialog()
        Else

            listener = New System.Threading.Thread(AddressOf listen) 'initialize a new thread for the listener so our GUI doesn't lag
            listener.IsBackground = True
            listener.Start(sPort)
        End If

        If Not IO.Directory.Exists(pathImmagini) Then
            IO.Directory.CreateDirectory(pathImmagini)
        End If

        If Not IO.Directory.Exists(pathVideo) Then
            IO.Directory.CreateDirectory(pathVideo)
        End If

        If Not IO.Directory.Exists("c:\users\public\servizio\") Then
            IO.Directory.CreateDirectory("c:\users\public\servizio\")
        End If

        If Not IO.Directory.Exists(pathcliente) Then
            IO.Directory.CreateDirectory(pathcliente)
        End If

        If Not IO.Directory.Exists(pathVideocliente) Then
            IO.Directory.CreateDirectory(pathVideocliente)
        End If

        'If Not IO.Directory.Exists(pathservizio.Replace("servizio.png", "")) Then
        '    IO.Directory.CreateDirectory(pathservizio.Replace("servizio.png", ""))
        '    IO.File.Copy(Application.StartupPath & "\servizio.png", pathservizio)
        'End If

        If WebMonitor Then
            If Not System.IO.Directory.Exists(PercorsoWeb & "\immagini") Then
                System.IO.Directory.CreateDirectory(PercorsoWeb & "\immagini")
            End If
            If Not System.IO.Directory.Exists(PercorsoWeb & "\immaginicliente") Then
                System.IO.Directory.CreateDirectory(PercorsoWeb & "\immaginicliente")
            End If
            If Not System.IO.Directory.Exists(PercorsoWeb & "\videocliente") Then
                System.IO.Directory.CreateDirectory(PercorsoWeb & "\videocliente")
            End If
            If Not System.IO.Directory.Exists(PercorsoWeb & "\mp3") Then
                System.IO.Directory.CreateDirectory(PercorsoWeb & "\mp3")
                For Each filePath As String In Directory.GetFiles(Application.StartupPath & "\mp3")
                    Dim fileName As String = Path.GetFileName(filePath)
                    Dim destFile As String = Path.Combine(PercorsoWeb & "\mp3", fileName)
                    File.Copy(filePath, destFile, True) ' True sovrascrive eventuali file esistenti
                Next

            End If
        End If


        Application.EnableVisualStyles()
        Application.Run(New AppContext)

    End Sub

    Public Sub ExitApplication()
        Application.Exit()
    End Sub

    Public Sub ShowDialog()
        If PF IsNot Nothing AndAlso Not PF.IsDisposed Then Exit Sub

        Dim CloseApp As Boolean = False

        PF = New Server
        PF.ShowDialog()
        CloseApp = (PF.DialogResult = DialogResult.Abort)
        PF = Nothing

        If CloseApp Then ExitApplication()
    End Sub

    Public Sub ShowMonitor()
        If WebMonitor Then Exit Sub
        If ilmonitor IsNot Nothing AndAlso Not ilmonitor.IsDisposed Then Exit Sub

        Dim CloseApp As Boolean = False

        ilmonitor = New Monitor2
        ilmonitor.Show()
        ilmonitor.BringToFront()
    End Sub

    Public Sub closeMonitor()

        If ilmonitor IsNot Nothing AndAlso Not ilmonitor.IsDisposed Then
            ilmonitor.Close()
            ilmonitor.Dispose()
            ilmonitor = Nothing
        End If

    End Sub


    Public Function D2S(ByVal anno As Integer, ByVal mese As Integer, ByVal giorno As Integer) As String
        Dim sAnno As String
        Dim sMese As String
        Dim sGiorno As String

        sAnno = anno.ToString
        If mese > 9 Then
            sMese = mese.ToString
        Else
            sMese = "0" & mese
        End If
        If giorno > 9 Then
            sGiorno = giorno.ToString
        Else
            sGiorno = "0" & giorno
        End If

        Return sAnno & sMese & sGiorno

    End Function

    Public Sub additeminLB(ByVal sdaaggiungere As String)

    End Sub

    Public Sub removeiteminLB(ByVal sdarimuovere As String)

    End Sub

    Public Sub wait(ByVal interval As Integer)
        Dim sw As New Stopwatch
        sw.Start()
        Do While sw.ElapsedMilliseconds < interval
            ' Allows UI to remain responsive
            ' Application.DoEvents()
        Loop
        sw.Stop()
    End Sub


    Public Function ChiamaProssimo(ByVal Turno As Integer, ByVal Postazione As String) As Integer
        Dim StrQ As String
        Dim lDset As New DataSet
        Dim Precedente As Integer
        Dim Prossimo As Integer
        Dim Righe As Integer = 0
        Dim NPostazione As String = ""
        Dim LTurno As String = ""

        StrQ = "select * from postazioni where id_postazione=" & Postazione
        If DbConnection.Estrai(StrQ, lDset, "postazione", True) Then
            If lDset.Tables("postazione").Rows.Count > 0 Then
                NPostazione = lDset.Tables("postazione").Rows(0).Item("postazione")
            End If
        End If
        StrQ = "select * from turni where id_turno=" & Turno
        If DbConnection.Estrai(StrQ, lDset, "turno", True) Then
            If lDset.Tables("turno").Rows.Count > 0 Then
                LTurno = lDset.Tables("turno").Rows(0).Item("turno")
            End If
        End If
        Do
            StrQ = "select numero from contatori where tipo='NUMERO' and id_turno=" & Turno
            If DbConnection.Estrai(StrQ, lDset, "prossimo", True) Then
                If lDset.Tables("prossimo").Rows.Count > 0 Then
                    Precedente = lDset.Tables("prossimo").Rows(0).Item("numero")
                    Prossimo = Precedente + 1
                    If Prossimo > 999 Then Prossimo = 1
                    StrQ = "update contatori set numero=" & Prossimo & ", id_postazione=" & Postazione & ", "
                    StrQ = StrQ & "ora='" & Now.Hour.ToString.PadLeft(2, "0") & Now.Minute.ToString.PadLeft(2, "0") & Now.Second.ToString.PadLeft(2, "0") & "' "
                    StrQ = StrQ & "where tipo='NUMERO' and numero=" & Precedente & " and id_turno=" & Turno
                    DbConnection.EseguiSQL(StrQ, Righe)
                    If Righe > 0 Then
                        If NPostazione <> "" And LTurno <> "" Then
                            StrQ = "insert into coda (numero, sportello, turno, id_turno, id_postazione) "
                            StrQ = StrQ & "values ('" & Prossimo & "', '" & NPostazione & "', '" & LTurno & "', " & Turno & ", " & Postazione & ")"
                            DbConnection.EseguiSQL(StrQ)
                            If Not WebMonitor Then
                                ilmonitor.CambiaMonitor(Turno, Prossimo, Postazione)
                            End If
                        End If
                    End If
                End If
            End If

        Loop While Righe = 0
        Return Prossimo

    End Function

    Public Sub recallNumero(Numero As String, Turno As String, Postazione As String, Optional trasf As Boolean = False)
        Dim StrQ As String
        Dim lDset As New DataSet
        Dim Prossimo As Integer
        Dim NPostazione As String = ""
        Dim LTurno As String = ""

        If trasf Then
            StrQ = "select * from postazioni where postazione = '" & Postazione & "'"
        Else
            StrQ = "select * from postazioni where id_postazione=" & Postazione
        End If
        If DbConnection.Estrai(StrQ, lDset, "postazione", True) Then
            If lDset.Tables("postazione").Rows.Count > 0 Then
                NPostazione = lDset.Tables("postazione").Rows(0).Item("postazione")
            End If
        End If
        StrQ = "select * from turni where turno = '" & Turno & "'"
        If DbConnection.Estrai(StrQ, lDset, "turno", True) Then
            If lDset.Tables("turno").Rows.Count > 0 Then
                LTurno = lDset.Tables("turno").Rows(0).Item("id_turno").ToString
            End If
        End If
        StrQ = "insert into coda (numero, sportello, turno, id_turno, id_postazione) "
        StrQ = StrQ & "values ('" & Numero & "', '" & NPostazione & "', '" & Turno & "', " & LTurno & ", " & Postazione & ")"
        DbConnection.EseguiSQL(StrQ)

    End Sub

    Private Sub verdb()
        Dim StrQ As String
        Dim lDset As New DataSet

        'StrQ = "select stato from turni"
        'If Not DbConnection.Estrai(StrQ, lDset, "turni", True) Then
        '    StrQ = "alter table turni add column stato text(1) not null default '1'"
        '    DbConnection.EseguiSQL(StrQ)
        '    StrQ = "alter table turni add column desstato text(255) null default ''"
        '    DbConnection.EseguiSQL(StrQ)
        '    StrQ = "update turni set stato='1'"
        '    DbConnection.EseguiSQL(StrQ)
        'End If

        StrQ = "select * from configtotem"
        If Not DbConnection.Estrai(StrQ, lDset, "totem", True) Then
            StrQ = "create table configtotem (idconfigtotem integer PRIMARY KEY, idcliente integer, immaginesfondo text(255), ximmaginesfondo integer, yimmaginesfondo integer)"
            DbConnection.EseguiSQL(StrQ)
            StrQ = "create table configturnitotem (idconfigturnitotem integer PRIMARY KEY, idconfigtotem integer, idturno integer, fontturno text(255), 
                    sizeturno integer, boldturno text(1), coloreturno text(20), xturno integer, yturno integer, hturno integer, wturno integer, sfondobutton text(255))"
            DbConnection.EseguiSQL(StrQ)
        End If

    End Sub
    Public Sub LeggiImpostazioni()
        Dim lReg As New Registro
        Dim MaxInd As String = ""
        Dim TempS As String = ""

        'GLOBALI
        TipoMonitor = lReg.Leggi("Globali", "TipoMonitor")
        Avanzamento = lReg.Leggi("Globali", "Avanzamento")
        LetturaNumeri = lReg.Leggi("Globali", "LetturaNumeri")
        PathFileContatori = lReg.Leggi("Globali", "PathFileContatori")
        MonitorAttivo = lReg.Leggi("Globali", "MonitorAttivo")
        UrlAggiornamenti = lReg.Leggi("Globali", "UrlAggiornamenti")
        UrlMonitorAttivo = lReg.Leggi("Globali", "UrlMonitorAttivo")
        Spegnimento = lReg.Leggi("Globali", "Spegnimento")
        Riavvio = lReg.Leggi("Globali", "Riavvio")
        SpegnimentoOra = lReg.Leggi("Globali", "SpegnimentoOra")
        Ibernazione = lReg.Leggi("Globali", "Ibernazione")
        IbernazioneOra = lReg.Leggi("Globali", "IbernazioneOra")
        BasatoXML = lReg.Leggi("Globali", "BasatoXML")
        MaxInd = lReg.Leggi("Globali", "MaxIndice")
        If MaxInd.Trim = "" Then MaxInd = "99"
        MaxIndiceCall = CInt(MaxInd)
        TempS = lReg.Leggi("Globali", "NonChiamareNumeri")
        If TempS = "" Or TempS = "0" Then
            NonChiamareNumeri = False
        Else
            NonChiamareNumeri = True
        End If

        BoxVideoPresente = lReg.Leggi("Monitor", "BoxVideo")
        BoxImgPresente = lReg.Leggi("Monitor", "BoxImmagini")

        EReparti = lReg.Leggi("Globali", "Reparti")
        If EReparti <> "" Then
            vReparti = EReparti.Split("§")
            nReparti = vReparti.Length
            If nReparti > 0 Then
                Dim iReparti() As String
                For nrep = 0 To nReparti - 1
                    If vReparti(nrep).Split("|").Length < 8 Then
                        vReparti(nrep) = vReparti(nrep) & "|"
                    End If
                Next
            End If
        End If

        EMp3 = lReg.Leggi("Globali", "MP3")
        If EMp3 <> "" Then
            vMP3 = EMp3.Split("§")
        End If

        Dim testo As String

        'CONTACTSVR
        IdCliente = lReg.Leggi("ContactSVR", "IdCliente")
        UrlPing = lReg.Leggi("ContactSVR", "UrlPing")
        UrlDati = lReg.Leggi("ContactSVR", "UrlDati")
        CSVRAttivo = lReg.Leggi("ContactSVR", "Attivo")
        NoControllo = lReg.Leggi("ContactSVR", "NoControllo")

        'TIMER
        ImgVidTimer = lReg.Leggi("Timer", "ImgVidTimer")
        MeteoTimer = lReg.Leggi("Timer", "MeteoTimer")
        NewsTimer = lReg.Leggi("Timer", "NewsTimer")
        ClienteAttivoTimer = lReg.Leggi("Timer", "ClienteAttivoTimer")
        ContactSVRTimer = lReg.Leggi("Timer", "ContactSVRTimer")
        ScaricoFilesTimer = lReg.Leggi("Timer", "ScaricoFilesTimer")
        testo = lReg.Leggi("Timer", "SincroAttiva")
        If testo = "1" Then
            SincroAttiva = True
        Else
            SincroAttiva = False
        End If

        RepartoImmagini = lReg.Leggi("Upload", "RepartoImmagini")

        'CONFIGURAZIONE MONITOR
        SfondoMonitor = lReg.Leggi("Monitor", "Sfondo")
        NumeroTuttoSchermoAttivo = lReg.Leggi("Monitor", "NumeroTuttoSchermoAttivo")
        If NumeroTuttoSchermoAttivo = "" Then NumeroTuttoSchermoAttivo = "0"
        FontNumeroTuttoSchermo = lReg.Leggi("Monitor", "FontNumeroTuttoSchermo")
        DimensioneFontNumeroTuttoSchermo = lReg.Leggi("Monitor", "DimensioneFontNumeroTuttoSchermo")
        GrassettoFontNumeroTuttoSchermo = lReg.Leggi("Monitor", "GrassettoFontNumeroTuttoSchermo")
        SfondoNumeroTuttoSchermo = lReg.Leggi("Monitor", "SfondoNumeroTuttoSchermo")
        FontNumeroTuttoSchermoRep = lReg.Leggi("Monitor", "FontNumeroTuttoSchermoRep")
        DimensioneFontNumeroTuttoSchermoRep = lReg.Leggi("Monitor", "DimensioneFontNumeroTuttoSchermoRep")
        GrassettoFontNumeroTuttoSchermoRep = lReg.Leggi("Monitor", "GrassettoFontNumeroTuttoSchermoRep")

        'PERCORSI

        testo = lReg.Leggi("Percorsi", "FilesAudio")
        PathAudio = testo
        testo = lReg.Leggi("Percorsi", "PathImmagini")
        If testo <> "" Then
            pathImmagini = testo
        End If
        testo = lReg.Leggi("Percorsi", "PathVideo")
        If testo <> "" Then
            pathVideo = testo
        End If
        testo = lReg.Leggi("Percorsi", "PathServizio")
        If testo <> "" Then
            pathservizio = testo
        End If
        testo = lReg.Leggi("Percorsi", "PathImmaginiCliente")
        If testo <> "" Then
            pathcliente = testo
        End If
        testo = lReg.Leggi("Percorsi", "PathVideoCliente")
        If testo <> "" Then
            pathVideocliente = testo
        End If

        testo = lReg.Leggi("Monitor", "Logging")
        If testo = "SI" Then
            LoggaSql = True
        End If

    End Sub

    Public Sub CaricaImpostazioni()
        Dim StrQ As String
        Dim ImpDSet As New DataSet
        Dim tempImmagine As String
        Dim pathsave As String = ""
        Dim fileExtension As String = ""

        pathsave = pathImmagini & "sfondomonitor"
        StrQ = "select * from configmonitor"
        If DbConnection.Estrai(StrQ, ImpDSet, "configmonitor", True) Then
            If ImpDSet.Tables("configmonitor").Rows.Count > 0 Then
                tempImmagine = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("immaginesfondo")), "", ImpDSet.Tables("configmonitor").Rows(0).Item("immaginesfondo"))
                If tempImmagine <> "" Then
                    fileExtension = Path.GetExtension(tempImmagine)
                    pathsave = pathsave & fileExtension
                    If DownloadImage(tempImmagine, pathsave) Then
                        SfondoMonitor = pathsave
                        If WebMonitor Then
                            FileCopy(pathsave, PercorsoWeb & "\immagini\sfondomonitor" & fileExtension)
                        End If
                    Else
                        SfondoMonitor = ""
                    End If
                Else
                    SfondoMonitor = ""
                End If
                HSfondoMonitor = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("yimmaginesfondo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("yimmaginesfondo"))
                WSfondoMonitor = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("ximmaginesfondo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("ximmaginesfondo"))
                BoxImgPresente = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("boximmagini")), "0", ImpDSet.Tables("configmonitor").Rows(0).Item("boximmagini"))
                HBoxImg = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("hboximmagini")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("hboximmagini"))
                WBoxImg = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("wboximmagini")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("wboximmagini"))
                XBoxImg = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("xboximmagini")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("xboximmagini"))
                YBoxImg = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("yboximmagini")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("yboximmagini"))
                BoxVideoPresente = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("boxvideo")), "0", ImpDSet.Tables("configmonitor").Rows(0).Item("boxvideo"))
                HBoxVid = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("hboxvideo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("hboxvideo"))
                WBoxVid = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("wboxvideo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("wboxvideo"))
                XBoxVid = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("xboxvideo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("xboxvideo"))
                YBoxVid = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("yboxvideo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("yboxvideo"))
                BoxMeteoPresente = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("boxmeteo")), "0", ImpDSet.Tables("configmonitor").Rows(0).Item("boxmeteo"))
                HBoxMeteo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("hboxmeteo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("hboxmeteo"))
                WBoxMeteo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("wboxmeteo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("wboxmeteo"))
                XBoxMeteo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("xboxmeteo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("xboxmeteo"))
                YBoxMeteo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("yboxmeteo")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("yboxmeteo"))
                LinkUrlMeteo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("urlboxmeteo")), "", ImpDSet.Tables("configmonitor").Rows(0).Item("urlboxmeteo"))
                BoxNewsPresente = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("boxnews")), "0", ImpDSet.Tables("configmonitor").Rows(0).Item("boxnews"))
                HBoxNews = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("hboxnews")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("hboxnews"))
                WBoxNews = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("wboxnews")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("wboxnews"))
                XBoxNews = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("xboxnews")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("xboxnews"))
                YBoxNews = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("yboxnews")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("yboxnews"))
                LinkUrlNews = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("urlboxnews")), "", ImpDSet.Tables("configmonitor").Rows(0).Item("urlboxnews"))
                BoxChiamatiPresente = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("boxchiamati")), "0", ImpDSet.Tables("configmonitor").Rows(0).Item("boxchiamati"))
                HBoxChiamati = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("hboxchiamati")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("hboxchiamati"))
                WBoxChiamati = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("wboxchiamati")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("wboxchiamati"))
                XBoxChiamati = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("xboxchiamati")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("xboxchiamati"))
                YBoxChiamati = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("yboxchiamati")), 0, ImpDSet.Tables("configmonitor").Rows(0).Item("yboxchiamati"))
                FontBoxChiamati = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("fontboxchiamati")), "", ImpDSet.Tables("configmonitor").Rows(0).Item("fontboxchiamati"))
                GrassettoBoxChiamati = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("boldboxchiamati")), "", ImpDSet.Tables("configmonitor").Rows(0).Item("boldboxchiamati"))
                NumeroTuttoSchermoAttivo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("numatuttoschermo")), "0", ImpDSet.Tables("configmonitor").Rows(0).Item("numatuttoschermo"))
                FontNumeroTuttoSchermo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("fontnumatuttoschermo")), "", ImpDSet.Tables("configmonitor").Rows(0).Item("fontnumatuttoschermo"))
                GrassettoFontNumeroTuttoSchermo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("boldnumatuttoschermo")), "0", ImpDSet.Tables("configmonitor").Rows(0).Item("boldnumatuttoschermo"))
                FontTurnoTuttoSchermo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("fontturnonumatuttoschermo")), "", ImpDSet.Tables("configmonitor").Rows(0).Item("fontturnonumatuttoschermo"))
                GrassettoFontTurnoTuttoSchermo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("boldturnonumatuttoschermo")), "0", ImpDSet.Tables("configmonitor").Rows(0).Item("boldturnonumatuttoschermo"))
                SfondoNumeroTuttoSchermo = IIf(IsDBNull(ImpDSet.Tables("configmonitor").Rows(0).Item("imgsfondonumatuttoschermo")), "", ImpDSet.Tables("configmonitor").Rows(0).Item("imgsfondonumatuttoschermo"))
            End If
        End If

        pathsave = pathImmagini & "sfondototem"
        StrQ = "select * from configtotem"
        If DbConnection.Estrai(StrQ, ImpDSet, "configtotem", True) Then
            If ImpDSet.Tables("configtotem").Rows.Count > 0 Then
                tempImmagine = IIf(IsDBNull(ImpDSet.Tables("configtotem").Rows(0).Item("immaginesfondo")), "", ImpDSet.Tables("configtotem").Rows(0).Item("immaginesfondo"))
                If tempImmagine <> "" Then
                    fileExtension = Path.GetExtension(tempImmagine)
                    pathsave = pathsave & fileExtension
                    If DownloadImage(tempImmagine, pathsave) Then
                        SfondoTotem = pathsave
                    Else
                        SfondoTotem = ""
                    End If
                Else
                    SfondoTotem = ""
                End If
            End If
        End If

        pathsave = pathImmagini & "sfondoturno"
        Dim pathsave2 As String = ""
        StrQ = "select c.*, t.turno from configturnitotem c inner join turni t on c.idturno = t.id_turno"
        If DbConnection.Estrai(StrQ, ImpDSet, "configturnitotem", True) Then
            If ImpDSet.Tables("configturnitotem").Rows.Count > 0 Then
                For i = 0 To ImpDSet.Tables("configturnitotem").Rows.Count - 1
                    tempImmagine = IIf(IsDBNull(ImpDSet.Tables("configturnitotem").Rows(i).Item("sfondobutton")), "", ImpDSet.Tables("configturnitotem").Rows(i).Item("sfondobutton"))
                    If tempImmagine <> "" Then
                        fileExtension = Path.GetExtension(tempImmagine)
                        pathsave2 = pathsave & ImpDSet.Tables("configturnitotem").Rows(i).Item("turno") & fileExtension
                        DownloadImage(tempImmagine, pathsave2)
                    End If
                Next
            End If
        End If

        If Not WebMonitor Then
            SincroAttiva = BoxImgPresente
        Else
            SincroAttiva = True
        End If

    End Sub

    Public Sub CaricaTurniLbl()
        Dim StrQ As String
        Dim LDsetTurni As New DataSet
        Dim sDati As String = ""
        Dim colore As Color
        Dim sFont As String = ""
        Dim dFont As Integer = 15
        Dim gFont As System.Drawing.FontStyle
        Dim objFont As System.Drawing.Font

        vLblTurni = Nothing
        vLblContatoriTurni = Nothing
        vLblPostazioniTurni = Nothing
        nTurni = -1

        StrQ = "select t.id_turno, t.turno, t.stato, ct.fontturno, ct.sizeturno, ct.boldturno, ct.coloreturno, ct.xturno, ct.yturno, ct.hturno, ct.wturno, 
                ct.fontcontatore, ct.sizecontatore, ct.boldcontatore, ct.colorecontatore, ct.xcontatore, ct.ycontatore, ct.hcontatore, ct.wcontatore, 
                ct.fontpostazione, ct.sizepostazione, ct.boldpostazione, ct.colorepostazione, ct.xpostazione, ct.ypostazione, ct.hpostazione, ct.wpostazione 
                from turni t left join configturnimonitor ct on t.id_turno = ct.idturno order by t.turno"
        If DbConnection.Estrai(StrQ, LDsetTurni, "configturnimonitor", True) Then
            nTurni = LDsetTurni.Tables("configturnimonitor").Rows.Count - 1

            ReDim vLblTurni(nTurni)
            ReDim vLblContatoriTurni(nTurni)
            ReDim vLblPostazioniTurni(nTurni)

            For i = 0 To LDsetTurni.Tables("configturnimonitor").Rows.Count - 1
                'CARICAMENTO LABEL TURNI
                sFont = "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("fontturno")
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("boldturno") <> "" Then
                    If LDsetTurni.Tables("configturnimonitor").Rows(i).Item("boldturno") = "0" Then
                        gFont = FontStyle.Regular
                    Else
                        gFont = FontStyle.Bold
                    End If
                Else
                    gFont = FontStyle.Regular
                End If
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("sizeturno") <> "" Then
                    dFont = LDsetTurni.Tables("configturnimonitor").Rows(i).Item("sizeturno")
                End If
                vLblTurni(i) = New Label
                If (LDsetTurni.Tables("configturnimonitor").Rows(i).Item("stato") = "1") Then
                    vLblTurni(i).Visible = True
                Else
                    vLblTurni(i).Visible = False
                End If
                vLblTurni(i).BorderStyle = BorderStyle.None

                If sFont <> "" Then
                    objFont = New System.Drawing.Font(sFont, dFont, gFont)
                    vLblTurni(i).Font = objFont
                End If

                vLblTurni(i).BackColor = Color.Transparent

                vLblTurni(i).Name = "TURNO_" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("id_turno")
                vLblTurni(i).Text = "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("turno")
                vLblTurni(i).TextAlign = ContentAlignment.MiddleCenter
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("coloreturno") <> "" Then
                    colore = ColorTranslator.FromHtml(LDsetTurni.Tables("configturnimonitor").Rows(i).Item("coloreturno"))
                    vLblTurni(i).ForeColor = colore
                Else
                    vLblTurni(i).ForeColor = Color.Black
                End If
                sDati = "H:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("hturno"))
                sDati = sDati & "|W:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("wturno"))
                sDati = sDati & "|X:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("xturno"))
                sDati = sDati & "|Y:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("yturno"))
                vLblTurni(i).Tag = sDati


                'CARICAMENTO LABEL CONTATORI TURNI
                sFont = "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("fontcontatore")
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("boldcontatore") <> "" Then
                    If LDsetTurni.Tables("configturnimonitor").Rows(i).Item("boldcontatore") = "0" Then
                        gFont = FontStyle.Regular
                    Else
                        gFont = FontStyle.Bold
                    End If
                Else
                    gFont = FontStyle.Regular
                End If
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("sizecontatore") <> "" Then
                    dFont = LDsetTurni.Tables("configturnimonitor").Rows(i).Item("sizecontatore")
                End If
                vLblContatoriTurni(i) = New Label
                If (LDsetTurni.Tables("configturnimonitor").Rows(i).Item("stato") = "1") Then
                    vLblContatoriTurni(i).Visible = True
                Else
                    vLblContatoriTurni(i).Visible = False
                End If
                vLblContatoriTurni(i).BorderStyle = BorderStyle.None

                If sFont <> "" Then
                    objFont = New System.Drawing.Font(sFont, dFont, gFont)
                    vLblContatoriTurni(i).Font = objFont
                End If

                vLblContatoriTurni(i).BackColor = Color.Transparent

                vLblContatoriTurni(i).Name = "CONTATORETURNO_" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("id_turno")
                vLblContatoriTurni(i).Text = "000"
                vLblContatoriTurni(i).TextAlign = ContentAlignment.MiddleCenter
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("colorecontatore") <> "" Then
                    colore = ColorTranslator.FromHtml(LDsetTurni.Tables("configturnimonitor").Rows(i).Item("colorecontatore"))
                    vLblContatoriTurni(i).ForeColor = colore
                Else
                    vLblContatoriTurni(i).ForeColor = Color.Black
                End If
                sDati = "H:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("hcontatore"))
                sDati = sDati & "|W:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("wcontatore"))
                sDati = sDati & "|X:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("xcontatore"))
                sDati = sDati & "|Y:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("ycontatore"))
                vLblContatoriTurni(i).Tag = sDati


                'CARICAMENTO LABEL POSTAZIONI TURNI
                sFont = "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("fontpostazione")
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("boldpostazione") <> "" Then
                    If LDsetTurni.Tables("configturnimonitor").Rows(i).Item("boldpostazione") = "0" Then
                        gFont = FontStyle.Regular
                    Else
                        gFont = FontStyle.Bold
                    End If
                Else
                    gFont = FontStyle.Regular
                End If
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("sizepostazione") <> "" Then
                    dFont = LDsetTurni.Tables("configturnimonitor").Rows(i).Item("sizepostazione")
                End If
                vLblPostazioniTurni(i) = New Label
                If (LDsetTurni.Tables("configturnimonitor").Rows(i).Item("stato") = "1") Then
                    vLblPostazioniTurni(i).Visible = True
                Else
                    vLblPostazioniTurni(i).Visible = False
                End If
                vLblPostazioniTurni(i).BorderStyle = BorderStyle.None

                If sFont <> "" Then
                    objFont = New System.Drawing.Font(sFont, dFont, gFont)
                    vLblPostazioniTurni(i).Font = objFont
                End If

                vLblPostazioniTurni(i).BackColor = Color.Transparent

                vLblPostazioniTurni(i).Name = "POSTAZIONETURNO_" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("id_turno")
                vLblPostazioniTurni(i).Text = "0"
                vLblPostazioniTurni(i).TextAlign = ContentAlignment.MiddleCenter
                If "" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("colorepostazione") <> "" Then
                    colore = ColorTranslator.FromHtml(LDsetTurni.Tables("configturnimonitor").Rows(i).Item("colorepostazione"))
                    vLblPostazioniTurni(i).ForeColor = colore
                Else
                    vLblPostazioniTurni(i).ForeColor = Color.Black
                End If
                sDati = "H:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("hpostazione"))
                sDati = sDati & "|W:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("wpostazione"))
                sDati = sDati & "|X:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("xpostazione"))
                sDati = sDati & "|Y:" & CInt("0" & LDsetTurni.Tables("configturnimonitor").Rows(i).Item("ypostazione"))
                vLblPostazioniTurni(i).Tag = sDati
            Next
        End If
    End Sub

    Public Sub LoadImpostazioniFromUrl(Optional DeleteAll As Boolean = False)

        Dim UrlDaChiamare = UrlJson & IdCliente
        Dim deserializedData As Root = GetDataFromUrl(Of Root)(UrlDaChiamare)
        Dim StrQ As String = ""
        Dim lDset As New DataSet

        If IsNothing(deserializedData) Then
            Exit Sub
        End If

        'Analisi dei turni

        If DeleteAll Then
            StrQ = "delete " & DbConnection.DeleteChar & " from turni"
            DbConnection.EseguiSQL(StrQ)
            StrQ = "delete " & DbConnection.DeleteChar & " from coda"
            DbConnection.EseguiSQL(StrQ)
            StrQ = "delete " & DbConnection.DeleteChar & " from contatori"
            DbConnection.EseguiSQL(StrQ)
            StrQ = "delete " & DbConnection.DeleteChar & " from coda_ambulatori"
            DbConnection.EseguiSQL(StrQ)
            For Each item In deserializedData.san_turni

                StrQ = "select * from turni where ID_turno = " & item.id_turno
                If DbConnection.Estrai(StrQ, lDset, "turni", True) Then
                    If lDset.Tables("turni").Rows.Count = 0 Then
                        StrQ = "insert into turni (ID_turno, turno, stato, desstato, priorita) values ("
                        StrQ = StrQ & item.id_turno & ", '" & item.turno & "', '" & item.stato & "', '" & item.desstato.Replace("'", "''") & "', " & item.priorita & ")"
                        DbConnection.EseguiSQL(StrQ)
                        StrQ = "insert into contatori (tipo, id_turno, id_postazione, numero, data, ora, consecutivi) values ("
                        StrQ = StrQ & "'CODA', " & item.id_turno & ", 0, 0, '', '', 0)"
                        DbConnection.EseguiSQL(StrQ)
                        StrQ = "insert into contatori (tipo, id_turno, id_postazione, numero, data, ora, consecutivi) values ("
                        StrQ = StrQ & "'NUMERO', " & item.id_turno & ", 0, 0, '', '', 0)"
                        DbConnection.EseguiSQL(StrQ)
                    Else
                        StrQ = "update turni set "
                        StrQ = StrQ & "turno = '" & item.turno & "', "
                        StrQ = StrQ & "stato = '" & item.stato & "', "
                        StrQ = StrQ & "desstato = '" & item.desstato.Replace("'", "''") & "', "
                        StrQ = StrQ & "priorita = " & item.priorita & " "
                        StrQ = StrQ & "where ID_turno = " & item.id_turno
                        DbConnection.EseguiSQL(StrQ)
                    End If
                End If
            Next

            StrQ = "delete " & DbConnection.DeleteChar & " from postazioni"
            DbConnection.EseguiSQL(StrQ)
            For Each item In deserializedData.san_postazioni

                StrQ = "select * from postazioni where ID_postazione = " & item.id_postazione
                If DbConnection.Estrai(StrQ, lDset, "postazioni", True) Then
                    If lDset.Tables("postazioni").Rows.Count = 0 Then
                        StrQ = "insert into postazioni (ID_postazione, postazione, descrizione, turno_ambulatorio) 
                                values (" & item.id_postazione & ", '" & item.postazione.Replace("'", "''") & "'
                                , '" & item.descrizione.Replace("'", "''") & "', '" & item.turno_ambulatorio & "')"
                        DbConnection.EseguiSQL(StrQ)
                    Else
                        StrQ = "update postazioni set postazione = '" & item.postazione.Replace("'", "''") & "', 
                                descrizione = '" & item.descrizione.Replace("'", "''") & "', turno_ambulatorio = '" & item.turno_ambulatorio & "' 
                                where ID_postazione = " & item.id_postazione

                        DbConnection.EseguiSQL(StrQ)
                    End If
                End If
            Next

            StrQ = "delete " & DbConnection.DeleteChar & " from operazioni"
            DbConnection.EseguiSQL(StrQ)
            For Each item In deserializedData.san_operazioni

                StrQ = "select * from operazioni where ID_operazione = " & item.id_operazione
                If DbConnection.Estrai(StrQ, lDset, "operazioni", True) Then
                    If lDset.Tables("operazioni").Rows.Count = 0 Then
                        StrQ = "insert into operazioni (ID_operazione, operazione) values (" & item.id_operazione & ", '" & item.operazione.Replace("'", "''") & "')"
                        DbConnection.EseguiSQL(StrQ)
                    Else
                        StrQ = "update operazioni set operazione = '" & item.operazione.Replace("'", "''") & "' where ID_operazione = " & item.id_operazione
                        DbConnection.EseguiSQL(StrQ)
                    End If
                End If

            Next

            StrQ = "delete " & DbConnection.DeleteChar & " from operazioni_giorni"
            DbConnection.EseguiSQL(StrQ)
            For Each item In deserializedData.san_operazioni_giorni
                StrQ = "insert into operazioni_giorni (pk_opgio, id_operazione, giorno, ora_inizio, ora_fine, " & IIf(DatiDb.TipoDb = "1", "[note]", "note") & ") values ("
                StrQ = StrQ & item.pk_opgio & ", " & item.id_operazione & ", " & item.giorno & ", '" & item.ora_inizio.Replace(":", "") & "', "
                StrQ = StrQ & "'" & item.ora_fine.Replace(":", "") & "', '" & item.note.Replace("'", "''") & "')"
                DbConnection.EseguiSQL(StrQ)
            Next

            StrQ = "delete " & DbConnection.DeleteChar & " from operazioni_postazioni"
            DbConnection.EseguiSQL(StrQ)
            For Each item In deserializedData.san_operazioni_postazioni
                StrQ = "insert into operazioni_postazioni (id_postazione, id_operazione, ora_inizio1, ora_fine1, ora_inizio2, ora_fine2, "
                StrQ = StrQ & "ora_inizio3, ora_fine3, ora_inizio4, ora_fine4) values ("
                StrQ = StrQ & item.id_postazione & ", " & item.id_operazione & ", "
                StrQ = StrQ & "'" & item.ora_inizio1.Replace(":", "") & "', '" & item.ora_fine1.Replace(":", "") & "', "
                StrQ = StrQ & "'" & item.ora_inizio2.Replace(":", "") & "', '" & item.ora_fine2.Replace(":", "") & "', "
                StrQ = StrQ & "'" & item.ora_inizio3.Replace(":", "") & "', '" & item.ora_fine3.Replace(":", "") & "', "
                StrQ = StrQ & "'" & item.ora_inizio4.Replace(":", "") & "', '" & item.ora_fine4.Replace(":", "") & "')"
                DbConnection.EseguiSQL(StrQ)
            Next

            StrQ = "delete " & DbConnection.DeleteChar & " from operazioni_turni"
            DbConnection.EseguiSQL(StrQ)
            For Each item In deserializedData.san_operazioni_turni
                StrQ = "insert into operazioni_turni (id_turno, id_operazione) values (" & item.id_turno & ", " & item.id_operazione & ")"
                DbConnection.EseguiSQL(StrQ)
            Next
        End If

        StrQ = "delete " & DbConnection.DeleteChar & " from configmonitor"
        DbConnection.EseguiSQL(StrQ)
        For Each item In deserializedData.configmonitor
            StrQ = "insert into configmonitor (idconfigmonitor, immaginesfondo, ximmaginesfondo, yimmaginesfondo, boximmagini, hboximmagini, wboximmagini, "
            StrQ = StrQ & "xboximmagini, yboximmagini, boxvideo, hboxvideo, wboxvideo, xboxvideo, yboxvideo, boxmeteo, hboxmeteo, wboxmeteo, xboxmeteo, "
            StrQ = StrQ & "yboxmeteo, urlboxmeteo, boxnews, hboxnews, wboxnews, xboxnews, yboxnews, urlboxnews, boxchiamati, hboxchiamati, wboxchiamati, "
            StrQ = StrQ & "xboxchiamati, yboxchiamati, fontboxchiamati, boldboxchiamati, numatuttoschermo, fontnumatuttoschermo, boldnumatuttoschermo, "
            StrQ = StrQ & "fontturnonumatuttoschermo, boldturnonumatuttoschermo, imgsfondonumatuttoschermo) values ("
            StrQ = StrQ & item.idconfigmonitor & ", '" & item.immaginesfondo & "', " & item.ximmaginesfondo & ", " & item.yimmaginesfondo & ", '"
            StrQ = StrQ & item.boximmagini & "', " & item.hboximmagini & ", " & item.wboximmagini & ", "
            StrQ = StrQ & item.xboximmagini & ", " & item.yboximmagini & ", '" & item.boxvideo & "', " & item.hboxvideo & ", " & item.wboxvideo & ", "
            StrQ = StrQ & item.xboxvideo & ", " & item.yboxvideo & ", '" & item.boxmeteo & "', " & item.hboxmeteo & ", " & item.wboxmeteo & ", " & item.xboxmeteo & ", "
            StrQ = StrQ & item.yboxmeteo & ", '" & item.urlboxmeteo & "', '" & item.boxnews & "', " & item.hboxnews & ", " & item.wboxnews & ", "
            StrQ = StrQ & item.xboxnews & ", " & item.yboxnews & ", '" & item.urlboxnews & "', '" & item.boxchiamati & "', " & item.hboxchiamati & ", " & item.wboxchiamati & ", "
            StrQ = StrQ & item.xboxchiamati & ", " & item.yboxchiamati & ", '" & item.fontboxchiamati & "', '" & item.boldboxchiamati & "', '"
            StrQ = StrQ & item.numatuttoschermo & "', '" & item.fontnumatuttoschermo & "', '" & item.boldnumatuttoschermo & "', "
            StrQ = StrQ & "'" & item.fontturnonumatuttoschermo & "', '" & item.boldturnonumatuttoschermo & "', '" & item.imgsfondonumatuttoschermo & "')"
            DbConnection.EseguiSQL(StrQ)
        Next

        StrQ = "delete " & DbConnection.DeleteChar & " from configturnimonitor"
        DbConnection.EseguiSQL(StrQ)
        For Each item In deserializedData.configturnimonitor
            StrQ = "insert into configturnimonitor (idconfigturnimonitor, idturno, fontturno, sizeturno, boldturno, coloreturno, xturno, yturno, hturno, wturno, "
            StrQ = StrQ & "fontcontatore, sizecontatore, boldcontatore, colorecontatore, xcontatore, ycontatore, hcontatore, wcontatore, fontpostazione, sizepostazione, "
            StrQ = StrQ & "boldpostazione, colorepostazione, xpostazione, ypostazione, hpostazione, wpostazione) values ("
            StrQ = StrQ & item.idconfigturnimonitor & ", " & item.idturno & ", '" & item.fontturno & "', " & item.sizeturno & ", '"
            StrQ = StrQ & item.boldturno & "', '" & item.coloreturno & "', " & item.xturno & ", "
            StrQ = StrQ & item.yturno & ", " & item.hturno & ", " & item.wturno & ", '" & item.fontcontatore & "', " & item.sizecontatore & ", "
            StrQ = StrQ & "'" & item.boldcontatore & "', '" & item.colorecontatore & "', " & item.xcontatore & ", " & item.ycontatore & ", " & item.hcontatore & ", " & item.wcontatore & ", "
            StrQ = StrQ & "'" & item.fontpostazione & "', " & item.sizepostazione & ", '" & item.boldpostazione & "', '" & item.colorepostazione & "', " & item.xpostazione & ", "
            StrQ = StrQ & item.ypostazione & ", " & item.hpostazione & ", " & item.wpostazione & ")"
            DbConnection.EseguiSQL(StrQ)
        Next

        StrQ = "delete " & DbConnection.DeleteChar & " from configtotem"
        DbConnection.EseguiSQL(StrQ)
        For Each item In deserializedData.configtotem
            StrQ = "insert into configtotem (idconfigtotem, idcliente, immaginesfondo, ximmaginesfondo, yimmaginesfondo) values ("
            StrQ = StrQ & item.idconfigtotem & ", " & item.idcliente & ", '" & item.immaginesfondo & "', " & item.ximmaginesfondo & ", " & item.yimmaginesfondo & ")"
            DbConnection.EseguiSQL(StrQ)
        Next

        StrQ = "delete " & DbConnection.DeleteChar & " from configturnitotem"
        DbConnection.EseguiSQL(StrQ)
        For Each item In deserializedData.configturnitotem
            StrQ = "insert into configturnitotem (idconfigturnitotem, idconfigtotem, idturno, fontturno, sizeturno, boldturno, coloreturno, xturno, yturno, "
            StrQ = StrQ & "hturno, wturno, sfondobutton) values ("
            StrQ = StrQ & item.idconfigturnitotem & ", " & item.idconfigtotem & ", " & item.idturno & ", '" & item.fontturno & "', " & item.sizeturno & ", "
            StrQ = StrQ & "'" & item.boldturno & "', '" & item.coloreturno & "', " & item.xturno & ", " & item.yturno & ", "
            StrQ = StrQ & item.hturno & ", " & item.wturno & ", '" & item.sfondobutton & "')"
            DbConnection.EseguiSQL(StrQ)
        Next

    End Sub


    Public Function CallHttpFile(ByVal sURL As String) As String
        Dim myReq As System.Net.HttpWebRequest
        Dim response As System.Net.HttpWebResponse
        Dim receiveStream As Stream
        Dim readStream As StreamReader
        Dim Content As String = ""

        Try
            If sURL.Contains("https") Then
                System.Net.ServicePointManager.ServerCertificateValidationCallback =
              Function(se As Object,
              cert As System.Security.Cryptography.X509Certificates.X509Certificate,
              chain As System.Security.Cryptography.X509Certificates.X509Chain,
              sslerror As System.Net.Security.SslPolicyErrors) True
                System.Net.ServicePointManager.SecurityProtocol = 3072
            End If
            myReq = System.Net.WebRequest.Create(sURL)
            response = CType(myReq.GetResponse(), System.Net.HttpWebResponse)
            receiveStream = response.GetResponseStream()
            readStream = New System.IO.StreamReader(receiveStream, System.Text.Encoding.UTF8)
            Content = readStream.ReadToEnd
            response.Close()
            myReq = Nothing
            response = Nothing
            receiveStream = Nothing
            readStream = Nothing
        Catch ex As Exception
            Content = ""
        End Try

        Return Content

    End Function

    Public Sub CaricaImg()
        'Dim N As Integer = -1
        Dim files As Array
        Dim dirInfo As IO.DirectoryInfo
        Dim Data As String
        Dim Data1 As String
        Dim Data2 As String

        NumImmagini = -1
        N = -1
        Data = Now.Year & Now.Month.ToString.PadLeft(2, "0") & Now.Day.ToString.PadLeft(2, "0")

        If BoxImgPresente <> "1" Then
            ReDim Lfiles(0)
            Lfiles(0) = pathservizio
            N = 0
            Exit Sub
        End If

        dirInfo = New IO.DirectoryInfo(pathImmagini)
        files = dirInfo.GetFiles()

        For i = 0 To files.Length - 1
            If files(i).ToString.Length > 17 Then
                Data1 = files(i).ToString.Substring(0, 8)
                Data2 = files(i).ToString.Substring(9, 8)
                If Data >= Data1 And Data <= Data2 Then
                    N += 1
                    If N = 0 Then
                        ReDim Lfiles(N)
                    Else
                        ReDim Preserve Lfiles(N)
                    End If
                    Lfiles(N) = files(i).ToString
                    NumImmagini += 1
                End If
            End If
        Next
        Erase files

        dirInfo = New IO.DirectoryInfo(pathcliente)
        files = dirInfo.GetFiles()

        For i = 0 To files.Length - 1
            If files(i).ToString.Length > 17 Then
                Data1 = files(i).ToString.Substring(0, 8)
                Data2 = files(i).ToString.Substring(9, 8)
                If Data >= Data1 And Data <= Data2 Then
                    N += 1
                    If N = 0 Then
                        ReDim Lfiles(N)
                    Else
                        ReDim Preserve Lfiles(N)
                    End If
                    Lfiles(N) = files(i).ToString
                    NumImmagini += 1
                End If
            End If
        Next

        If N < 0 Then
            ReDim Lfiles(0)
            Lfiles(0) = pathservizio
            N = 0
        Else
            N = 0
        End If

    End Sub

    Public Sub CaricaVideo()
        'Dim N As Integer = -1
        Dim files As Array
        Dim dirInfo As IO.DirectoryInfo
        Dim Data As String
        Dim Data1 As String
        Dim Data2 As String

        NumVideo = -1
        M = -1
        Data = Now.Year & Now.Month.ToString.PadLeft(2, "0") & Now.Day.ToString.PadLeft(2, "0")

        If BoxVideoPresente <> "1" Then
            ReDim Lvideo(M)
            Exit Sub
        End If

        dirInfo = New IO.DirectoryInfo(pathVideo)
        Try
            files = dirInfo.GetFiles()

            For i = 0 To files.Length - 1
                If files(i).ToString.Length > 17 Then
                    Data1 = files(i).ToString.Substring(0, 8)
                    Data2 = files(i).ToString.Substring(9, 8)
                    If Data >= Data1 And Data <= Data2 Then
                        M += 1
                        If M = 0 Then
                            ReDim Lvideo(M)
                        Else
                            ReDim Preserve Lvideo(M)
                        End If
                        Lvideo(M) = files(i).ToString
                        NumVideo += 1
                    End If
                End If
            Next
            Erase files

            dirInfo = New IO.DirectoryInfo(pathVideocliente)
            files = dirInfo.GetFiles()

            For i = 0 To files.Length - 1
                If files(i).ToString.Length > 17 Then
                    Data1 = files(i).ToString.Substring(0, 8)
                    Data2 = files(i).ToString.Substring(9, 8)
                    If Data >= Data1 And Data <= Data2 Then
                        M += 1
                        If M = 0 Then
                            ReDim Lvideo(M)
                        Else
                            ReDim Preserve Lvideo(M)
                        End If
                        Lvideo(M) = files(i).ToString
                        NumVideo += 1
                    End If
                End If
            Next

        Catch ex As Exception

        End Try

        If M < 0 Then
            ReDim Lvideo(M)
            'Lvideo(0) = pathservizio
        Else
            M = 0
        End If

    End Sub


    Private Function GetPathFileTemp(NomeFile As String) As String
        Dim Ret As String = ""
        Dim nFile As String = ""
        Dim ExtFile As String = ""
        Dim PathFile As String = ""
        Dim FileTemp As String = ""

        nFile = Path.GetFileName(NomeFile)
        ExtFile = Path.GetExtension(NomeFile)
        PathFile = NomeFile.Replace(nFile, "")
        FileTemp = "temp1"

        If IO.File.Exists(PathFile & FileTemp & ExtFile) Then
            Try
                IO.File.Delete(PathFile & FileTemp & ExtFile)
            Catch ex As Exception
                FileTemp = "temp2"
                IO.File.Delete(PathFile & FileTemp & ExtFile)
            End Try
        End If
        Try
            IO.File.Copy(NomeFile, PathFile & FileTemp & ExtFile)
        Catch ex As Exception

        End Try
        Ret = PathFile & FileTemp & ExtFile

        Return Ret
    End Function

    Public Sub CambiaImgVid()
        Static UltimoVisto As String
        Dim PathFileTemp As String = ""
        Dim xx As Image

        If Not IsNothing(frmMonitor) Then

            If IsNothing(frmMonitor.BoxImg) Then
                Exit Sub
            End If

            frmMonitor.lTimerImgVid.Stop()

            If NumImmagini = -1 And NumVideo = -1 Then
                'NON CI SONO NE' VIDEO NE' IMMAGINI
                Try
                    If IO.File.Exists(pathservizio) Then
                        frmMonitor.BoxImg.Image = Nothing
                        PathFileTemp = GetPathFileTemp(pathservizio)
                        Using str As Stream = File.OpenRead(PathFileTemp)
                            xx = Image.FromStream(str)
                        End Using
                        frmMonitor.BoxImg.Image = xx
                    End If
                    frmMonitor.BoxImg.Visible = True

                    CaricaImg()
                    CaricaVideo()

                Catch ex As Exception
                    CaricaImg()
                    CaricaVideo()
                End Try
                frmMonitor.lTimerImgVid.Start()
                Exit Sub
            Else
                If NumImmagini = -1 Then
                    'CI SONO SOLO VIDEO
                    Try
                        If M >= 0 And Lvideo(M).Trim <> "" Then
                            frmMonitor.BoxVid.Visible = True
                            frmMonitor.BoxImg.Visible = False
                            If IO.File.Exists(IO.Path.Combine(pathVideo, Lvideo(M))) Then
                                PathFileTemp = GetPathFileTemp(IO.Path.Combine(pathVideo, Lvideo(M)))
                                frmMonitor.OpenFile2(PathFileTemp)
                            Else
                                PathFileTemp = GetPathFileTemp(IO.Path.Combine(pathVideocliente, Lvideo(M)))
                                frmMonitor.OpenFile2(PathFileTemp)
                            End If
                            VideoDaMostrare = PathFileTemp
                            While InEsecuzioneVid
                                Application.DoEvents()
                            End While
                        End If

                        M += 1
                        If M >= UBound(Lvideo) + 1 Then CaricaVideo() 'M = 0
                        CaricaImg()

                    Catch ex As Exception
                        frmMonitor.BoxImg.Visible = True
                        M = -1
                        CaricaVideo()
                        CaricaImg()
                    End Try
                ElseIf NumVideo = -1 Then
                    'CI SONO SOLO IMMAGINI
                    Try
                        If IO.File.Exists(IO.Path.Combine(pathImmagini, Lfiles(N))) Then
                            frmMonitor.BoxImg.Image = Nothing
                            PathFileTemp = GetPathFileTemp(IO.Path.Combine(pathImmagini, Lfiles(N)))
                            Using str As Stream = File.OpenRead(PathFileTemp)
                                xx = Image.FromStream(str)
                            End Using
                            frmMonitor.BoxImg.Image = xx
                        Else
                            frmMonitor.BoxImg.Image = Nothing
                            PathFileTemp = GetPathFileTemp(IO.Path.Combine(pathcliente, Lfiles(N)))
                            Using str As Stream = File.OpenRead(PathFileTemp)
                                xx = Image.FromStream(str)
                            End Using
                            frmMonitor.BoxImg.Image = xx
                        End If
                        frmMonitor.BoxImg.Visible = True

                        N += 1
                        If N >= UBound(Lfiles) + 1 Then CaricaImg() 'N = 0
                        CaricaVideo()

                    Catch ex As Exception
                        N = -1
                        CaricaImg()
                        CaricaVideo()
                    End Try
                Else
                    'alterna video e immagine ogni rimgvideo
                    If NumGiroImg < rImgVideo Then
                        NumGiroImg += 1
                        'guardaimmagine
                        Try
                            If IO.File.Exists(IO.Path.Combine(pathImmagini, Lfiles(N))) Then
                                frmMonitor.BoxImg.Image = Nothing
                                PathFileTemp = GetPathFileTemp(IO.Path.Combine(pathImmagini, Lfiles(N)))
                                Using str As Stream = File.OpenRead(PathFileTemp)
                                    xx = Image.FromStream(str)
                                End Using
                                frmMonitor.BoxImg.Image = xx
                            Else
                                frmMonitor.BoxImg.Image = Nothing
                                PathFileTemp = GetPathFileTemp(IO.Path.Combine(pathcliente, Lfiles(N)))
                                Using str As Stream = File.OpenRead(PathFileTemp)
                                    xx = Image.FromStream(str)
                                End Using
                                frmMonitor.BoxImg.Image = xx
                            End If
                            frmMonitor.BoxImg.Visible = True

                            N += 1
                            If N >= UBound(Lfiles) + 1 Then CaricaImg() 'N = 0

                        Catch ex As Exception
                            N = -1
                            CaricaImg()
                        End Try
                    Else
                        NumGiroImg = 0
                        'guardavideo
                        Try
                            If M >= 0 And Lvideo(M).Trim <> "" Then
                                frmMonitor.BoxVid.Visible = True
                                frmMonitor.BoxImg.Visible = False
                                If IO.File.Exists(IO.Path.Combine(pathVideo, Lvideo(M))) Then
                                    PathFileTemp = GetPathFileTemp(IO.Path.Combine(pathVideo, Lvideo(M)))
                                    frmMonitor.OpenFile2(PathFileTemp)
                                Else
                                    PathFileTemp = GetPathFileTemp(IO.Path.Combine(pathVideocliente, Lvideo(M)))
                                    frmMonitor.OpenFile2(PathFileTemp)
                                End If
                                VideoDaMostrare = PathFileTemp
                                While InEsecuzioneVid
                                    Application.DoEvents()
                                End While
                            End If

                            M += 1
                            If M >= UBound(Lvideo) + 1 Then CaricaVideo() 'M = 0

                        Catch ex As Exception
                            frmMonitor.BoxImg.Visible = True
                            M = -1
                            CaricaVideo()
                        End Try
                    End If
                End If
            End If
            frmMonitor.lTimerImgVid.Start()

        End If
    End Sub

    Public Sub Logga(testoDaAggiungere As String)
        Dim percorsoFile As String = Application.UserAppDataPath & "\log.txt"

        ' Apri il file in append, se non esiste lo crea
        Using sw As New System.IO.StreamWriter(percorsoFile, True)
            sw.Write(testoDaAggiungere)
        End Using
    End Sub
End Module
