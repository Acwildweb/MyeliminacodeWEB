Imports System
Imports System.Collections
Imports System.ComponentModel
Imports System.Drawing
Imports System.Runtime.InteropServices
Imports System.Windows.Forms
Imports DirectShowLib
Imports System.Drawing.Drawing2D
Imports System.Text
Imports PVS.MediaPlayer
Imports Microsoft.SqlServer
Imports System.Net.Mime.MediaTypeNames
Imports System.IO
Imports System.Security.Cryptography

Public Class Monitor2
    Public Enum PlayState
        Stopped
        Paused
        Running
        Init
    End Enum

    Private Enum MediaType
        Audio
        Video
    End Enum

#Region "PlayerVar"
    Private myPlayer As PVS.MediaPlayer.Player
    Private shapeStatus As Integer              ' shapes - 0:none, 1:oval, 2:none, 3:rounded, 4:none, 5:star
    Private levelUnit As Double = 140           ' / 32767.0 - changed in PVS.MediaPlayer version 0.91
    Private leftLevel As Integer
    Private rightLevel As Integer
    Private levelBrush As Brush = New HatchBrush(HatchStyle.LightVertical, Color.FromArgb(179, 173, 146))

    Private myMetadata As Metadata              ' media metadata properties
    Private isInitializing As Boolean
    Private wasDisposed As Boolean

    Private myPlayerAudio As Player
    Private myMetadataAudio As Metadata              ' media metadata properties
    Private isInitializingAudio As Boolean
    Private wasDisposedAudio As Boolean
#End Region

    Private WithEvents kbHook As New KeyboardHook
    Public Property InConfigurazione As Boolean = False

    Public vLblReparti() As Label
    Public BoxImg As PictureBox
    Public BoxNumeroImg As Label
    Public BoxVid As PictureBox
    Public BoxMeteo As PictureBox
    Public BoxAudio As PictureBox
    Public WithEvents UrlMeteo As WebBrowser
    Public BoxNews As WebBrowser
    Public WithEvents UrlNews As WebBrowser
    Public NumTuttoSchermoPct As PictureBox
    Public NumTuttoSchermoLbl As Label
    Public NumTuttoSchermoRepLbl As Label
    Public BoxChiamati As Label

    Public AlertTuttoSchermoPct As PictureBox
    Public AlertTuttoSchermoLbl As Label

    Dim BoxFuoriServizio As PictureBox

    Dim locLabel As Label
    Dim LocBox As PictureBox
    Dim ObjMove As String = ""

    Dim BoxMetUrl As String
    Dim BoxNewUrl As String

    'TIMER FUNZIONAMENTO
    Public WithEvents lTimerImgVid As Timer
    Private WithEvents lTimerPlayer As Timer
    Private WithEvents lTimerChiamate As Timer
    Private WithEvents lTimerNews As Timer
    Private WithEvents lTimerMeteo As Timer
    Private WithEvents lTimerMonitorAttivo As Timer
    Private WithEvents lTimerNumTuttoSchermo As Timer
    Private WithEvents lTimerNoServer As Timer
    Private WithEvents lTimerMp3Orario As Timer
    Private WithEvents lTimerMp3Intervallo As Timer
    Dim nMp3Orario As Integer = -1
    Dim nMp3Intervallo As Integer = -1
    Dim vMp3Orario(2, 0) As String
    Dim vMp3Intervallo(1, 0) As String
    Dim ActualNMp3Orario As Integer = -1
    Dim ActualNMp3Intervallo As Integer = -1

    Dim ColoreGiallo As Color = Color.Yellow
    'VARIABILI PER VIDEOPLAYER
    'VARIABILI PER VIDEOPLAYER
    Private Const WMGraphNotify As Integer = 13
    Private Const VolumeFull As Integer = 0
    Private Const VolumeSilence As Integer = -10000

    Private graphBuilder As IGraphBuilder = Nothing
    Private mediaControl As IMediaControl = Nothing
    Private mediaEventEx As IMediaEventEx = Nothing
    Private videoWindow As IVideoWindow = Nothing
    Private basicAudio As IBasicAudio = Nothing
    Private basicVideo As IBasicVideo = Nothing
    Private mediaSeeking As IMediaSeeking = Nothing
    Private mediaPosition As IMediaPosition = Nothing
    Private frameStep As IVideoFrameStep = Nothing

    Private filename As String = String.Empty
    Private isAudioOnly As Boolean = False
    Private isFullScreen As Boolean = False
    Private currentVolume As Integer = VolumeFull
    Public currentState As PlayState = PlayState.Stopped
    Private currentPlaybackRate As Double = 1.0
    Private UseHand As IntPtr
    Private UseCtrl As System.Windows.Forms.Control
    Private FsDrain As IntPtr = IntPtr.Zero
    Private Event MedClose()

    Dim originalImageWidth As Integer = 0
    Dim originalImageHeight As Integer = 0
    Private Sub Monitor2_Load(sender As Object, e As EventArgs) Handles MyBase.Load
        Dim lReg As New Registro
        Dim TestoReg As String = ""
        Dim sReparto As String = ""
        Dim vReparto() As String
        Dim objFont As System.Drawing.Font
        Dim colore As Color
        Dim sFont As String = ""
        Dim dFont As Integer = 15
        Dim gFont As System.Drawing.FontStyle
        Dim sTag As String = ""
        Dim vsTag() As String
        Dim vdimsTag() As String
        Dim originalImage As System.Drawing.Image
        Dim scaledImageWidth As Integer = Me.ClientSize.Width
        Dim scaledImageHeight As Integer = Me.ClientSize.Height
        Dim scaleX As Double = 0
        Dim scaleY As Double = 0
        Dim labelx As Integer = 0
        Dim labely As Integer = 0
        Dim labelh As Integer = 0
        Dim labelw As Integer = 0

        Threading.Thread.Sleep(500)

        Dim oraAttuale As TimeSpan = DateTime.Now.TimeOfDay
        Dim oraInizio As New TimeSpan(3, 0, 0) ' 03:00
        Dim oraFine As New TimeSpan(5, 0, 0)    ' 05:00
        Dim oraInizio2 As New TimeSpan(7, 0, 0) ' 07:00
        Dim oraFine2 As New TimeSpan(21, 0, 0)    ' 21:00

        If (oraAttuale >= oraInizio) And (oraAttuale < oraFine) Then
            LoadImpostazioniFromUrl(True)
        Else
            If chiediAzzeramento Then
                If MsgBox("Azzerare i dati e ricaricare tutto dal web?", MsgBoxStyle.Question + MsgBoxStyle.YesNo, "Azzeramento dati") = MsgBoxResult.Yes Then
                    chiediAzzeramento = False
                    LoadImpostazioniFromUrl(True)
                End If
            Else
                LoadImpostazioniFromUrl(False)
            End If
        End If

        'LeggiImpostazioni()
        CaricaImpostazioni()
        CaricaTurniLbl()

        frmMonitor = Me

        Threading.Thread.Sleep(500)

        If SfondoMonitor <> "" Then

            Using fileStream As New FileStream(SfondoMonitor, FileMode.Open, FileAccess.Read)
                Using memoryStream As New MemoryStream()
                    fileStream.CopyTo(memoryStream)
                    memoryStream.Position = 0
                    originalImage = System.Drawing.Image.FromStream(memoryStream)
                End Using
            End Using

            Me.BackgroundImage = originalImage
            Me.BackgroundImageLayout = ImageLayout.Stretch
            'originalImage = System.Drawing.Image.FromFile(SfondoMonitor)
            originalImageWidth = originalImage.Width
            originalImageHeight = originalImage.Height
            scaleX = scaledImageWidth / originalImageWidth
            scaleY = scaledImageHeight / originalImageHeight
        End If

        If nTurni >= 0 Then
            For i = 0 To nTurni
                Me.Controls.Add(vLblTurni(i))
                vLblTurni(i).AutoSize = False
                sTag = vLblTurni(i).Tag
                vsTag = sTag.Split("|")
                For k = 0 To vsTag.Count - 1
                    vdimsTag = vsTag(k).Split(":")
                    Select Case vdimsTag(0)
                        Case "H"
                            labelh = CInt(vdimsTag(1))
                        Case "W"
                            labelw = CInt(vdimsTag(1))
                        Case "X"
                            labelx = CInt(vdimsTag(1))
                        Case "Y"
                            labely = CInt(vdimsTag(1))
                    End Select
                Next
                PositionLabel(vLblTurni(i), labelx, labely, labelh, labelw)
                vLblTurni(i).Visible = True
                vLblTurni(i).TextAlign = ContentAlignment.MiddleCenter

                Me.Controls.Add(vLblContatoriTurni(i))
                vLblContatoriTurni(i).AutoSize = False
                sTag = vLblContatoriTurni(i).Tag
                vsTag = sTag.Split("|")
                For k = 0 To vsTag.Count - 1
                    vdimsTag = vsTag(k).Split(":")
                    Select Case vdimsTag(0)
                        Case "H"
                            labelh = CInt(vdimsTag(1))
                        Case "W"
                            labelw = CInt(vdimsTag(1))
                        Case "X"
                            labelx = CInt(vdimsTag(1))
                        Case "Y"
                            labely = CInt(vdimsTag(1))
                    End Select
                Next
                PositionLabel(vLblContatoriTurni(i), labelx, labely, labelh, labelw)
                vLblContatoriTurni(i).Visible = True
                vLblContatoriTurni(i).TextAlign = ContentAlignment.MiddleCenter

                Me.Controls.Add(vLblPostazioniTurni(i))
                vLblPostazioniTurni(i).AutoSize = False
                sTag = vLblPostazioniTurni(i).Tag
                vsTag = sTag.Split("|")
                For k = 0 To vsTag.Count - 1
                    vdimsTag = vsTag(k).Split(":")
                    Select Case vdimsTag(0)
                        Case "H"
                            labelh = CInt(vdimsTag(1))
                        Case "W"
                            labelw = CInt(vdimsTag(1))
                        Case "X"
                            labelx = CInt(vdimsTag(1))
                        Case "Y"
                            labely = CInt(vdimsTag(1))
                    End Select
                Next
                PositionLabel(vLblPostazioniTurni(i), labelx, labely, labelh, labelw)
                vLblPostazioniTurni(i).Visible = True
                vLblPostazioniTurni(i).TextAlign = ContentAlignment.MiddleCenter
                'INSERIRE LETTURA CONTATORE E VALORIZZAZIONE

            Next
        End If

        If BoxChiamatiPresente = "1" Then
            BoxChiamati = New Label
            BoxChiamati.Visible = True
            BoxChiamati.BorderStyle = BorderStyle.None
            If FontBoxChiamati <> "" Then
                Dim testoChiamati As String = ""
                If GrassettoBoxChiamati = "1" Then
                    gFont = FontStyle.Bold
                Else
                    gFont = FontStyle.Regular
                End If
                objFont = New System.Drawing.Font(sFont, 25, gFont)
                BoxChiamati.Font = objFont
                BoxChiamati.BackColor = Color.Transparent
                BoxChiamati.TextAlign = ContentAlignment.TopCenter
                BoxChiamati.ForeColor = Color.Black
                testoChiamati = "Elenco numeri chiamati"
                BoxChiamati.Text = testoChiamati
                labelh = CInt(HBoxChiamati)
                labelw = CInt(WBoxChiamati)
                labelx = CInt(XBoxChiamati)
                labely = CInt(YBoxChiamati)
                Me.Controls.Add(BoxChiamati)
                PositionLabel(BoxChiamati, labelx, labely, labelh, labelw)
            End If
        End If

        'VERIFICA PRESENZA BOX IMMAGINI E SUA CONFIGURAZIONE
        If BoxImgPresente = "1" Then
            'BoxImg = New PictureBox
            'BoxImg.BorderStyle = BorderStyle.None
            'BoxImg.Name = "BoxImg"
            'Me.Controls.Add(BoxImg)
            'CreaBoxImg("1", WBoxImg, HBoxImg, XBoxImg, YBoxImg, Color.Transparent)
            BoxNumeroImg = New Label
            BoxNumeroImg.BorderStyle = BorderStyle.None
            BoxNumeroImg.Name = "BoxNumeroImg"
            gFont = FontStyle.Bold
            objFont = New System.Drawing.Font(sFont, 145, gFont)
            BoxNumeroImg.Font = objFont
            Me.Controls.Add(BoxNumeroImg)
            BoxNumeroImg.Visible = True
            BoxNumeroImg.Text = "0-000"
            BoxNumeroImg.TextAlign = ContentAlignment.MiddleCenter
            PositionLabel(BoxNumeroImg, XBoxImg, YBoxImg, HBoxImg, WBoxImg)
        End If

        'VERIFICA PRESENZA BOX VIDEO E SUA CONFIGURAZIONE
        If BoxVideoPresente = "1" Then
            BoxVid = New PictureBox
            BoxVid.BorderStyle = BorderStyle.None
            BoxVid.Name = "BoxVid"
            Me.Controls.Add(BoxVid)
            CreaBoxVid("1", WBoxVid, HBoxVid, XBoxVid, YBoxVid)
            BoxVid.BackColor = Color.Transparent
        End If

        'VERIFICA PRESENZA WEB METEO E SUA CONFIGURAZIONE
        If BoxMeteoPresente = "1" Then
            UrlMeteo = New WebBrowser
            UrlMeteo.Name = "UrlMeteo"
            UrlMeteo.Anchor = AnchorStyles.None
            Me.Controls.Add(UrlMeteo)
            CreaBoxNew("1", WBoxNews, HBoxNews, XBoxNews, YBoxNews)
            If LinkUrlMeteo <> "" Then
                UrlMeteo.Navigate(LinkUrlMeteo)
            End If
            UrlMeteo.ScrollBarsEnabled = False
        End If

        'VERIFICA PRESENZA WEB NEWS E SUA CONFIGURAZIONE
        If BoxNewsPresente = "1" Then
            UrlNews = New WebBrowser
            UrlNews.Name = "UrlNews"
            UrlNews.Anchor = AnchorStyles.None
            Me.Controls.Add(UrlNews)
            CreaBoxNew("1", WBoxNews, HBoxNews, XBoxNews, YBoxNews)
            If LinkUrlNews <> "" Then
                UrlNews.Navigate(LinkUrlNews)
            End If
            UrlNews.ScrollBarsEnabled = False
        End If

        CaricaImg()
        CaricaVideo()

        lTimerImgVid = New Timer
        'lTimerPlayer = New Timer
        lTimerChiamate = New Timer
        'lTimerNews = New Timer
        'lTimerMeteo = New Timer
        'lTimerMonitorAttivo = New Timer
        'lTimerNumTuttoSchermo = New Timer
        'lTimerNoServer = New Timer

        'If EMp3 <> "" Then
        '    lTimerMp3Orario = New Timer
        '    lTimerMp3Intervallo = New Timer
        '    For k As Integer = 0 To vMP3.Length - 1
        '        If vMP3(k).Split("|")(0) = "1" Then
        '            nMp3Orario += 1
        '            If nMp3Orario = 0 Then
        '                ReDim vMp3Orario(2, 0)
        '            Else
        '                ReDim Preserve vMp3Orario(2, nMp3Orario)
        '            End If
        '            vMp3Orario(0, nMp3Orario) = vMP3(k).Split("|")(2)   'ORARIO IN CUI DEVE PARTIRE
        '            vMp3Orario(1, nMp3Orario) = vMP3(k).Split("|")(1)   'FILE DA ESEGUIRE
        '            vMp3Orario(2, nMp3Orario) = "00:00"                 'ULTIMO ORARIO IN CUI è STATO ESEGUITO
        '        Else
        '            nMp3Intervallo += 1
        '            If nMp3Intervallo = 0 Then
        '                ReDim vMp3Intervallo(1, 0)
        '            Else
        '                ReDim Preserve vMp3Intervallo(1, nMp3Intervallo)
        '            End If
        '            vMp3Intervallo(0, nMp3Intervallo) = vMP3(k).Split("|")(1)
        '            vMp3Intervallo(1, nMp3Intervallo) = vMP3(k).Split("|")(2)
        '        End If
        '    Next
        'End If

        'Cursor.Show()

        lTimerImgVid.Stop()

        'If Not InConfigurazione Then

        '    BoxAudio = New PictureBox
        '    BoxAudio.Name = "BoxAudio"
        '    Me.Controls.Add(BoxAudio)
        '    BoxAudio.Left = 10000
        '    BoxAudio.Top = 10000
        '    BoxAudio.Width = 10
        '    BoxAudio.Height = 10
        '    BoxAudio.Visible = True

        myPlayer = New Player()             ' create a player
        myPlayer.Display.Window = BoxVid    ' and set its display to Panel1
        myPlayer.Repeat = False              ' repeat media playback when finished

        myPlayer.SleepDisabled = True       ' prevent the computer from entering sleep mode
        myPlayer.CursorHide.Add(Me)
        myPlayer.CursorHide.Delay = 3 ' 3 seconds is also the default waiting time
        AddHandler myPlayer.Events.MediaEnded, AddressOf MyPlayer_MediaEnded  ' see eventhandler below
        AddHandler myPlayer.Events.MediaEndedNotice, AddressOf MyPlayer_MediaEndedNotice  ' see below
        myPlayer.Audio.Volume = 0

        '    'myPlayerAudio = New Player()             ' create a player
        '    'myPlayerAudio.Display.Window = BoxAudio    ' and set its display to Panel1
        '    'myPlayerAudio.Repeat = False              ' repeat media playback when finished

        '    'myPlayerAudio.SleepDisabled = True       ' prevent the computer from entering sleep mode
        '    ''myPlayerAudio.CursorHide.Add(Me)
        '    ''myPlayerAudio.CursorHide.Delay = 3 ' 3 seconds is also the default waiting time
        '    'AddHandler myPlayerAudio.Events.MediaEnded, AddressOf MyPlayerAudio_MediaEnded  ' see eventhandler below
        '    'AddHandler myPlayerAudio.Events.MediaEndedNotice, AddressOf MyPlayerAudio_MediaEndedNotice  ' see below
        ImgVidTimer = "4"
        If CInt("0" & ImgVidTimer) > 0 Then
            lTimerImgVid.Interval = ImgVidTimer * 1000
        End If
        lTimerImgVid.Start()

        lTimerChiamate.Interval = 5000
        lTimerChiamate.Start()
        '    lTimerNoServer.Interval = 500
        '    lTimerNoServer.Start()

        '    If EMp3 <> "" Then
        '        If nMp3Orario >= 0 Then
        '            lTimerMp3Orario.Interval = 600000
        '            lTimerMp3Orario.Start()
        '        End If
        '        If nMp3Intervallo >= 0 Then
        '            lTimerMp3Intervallo.Interval = CInt(vMp3Intervallo(1, 0)) * 60000
        '            lTimerMp3Intervallo.Start()
        '        End If
        '    End If

        '    If CInt("0" & NewsTimer) > 0 Then
        '        lTimerNews.Interval = CInt("0" & NewsTimer) * 60000
        '        lTimerNews.Start()
        '    End If
        '    If CInt("0" & MeteoTimer) > 0 Then
        '        lTimerMeteo.Interval = CInt("0" & MeteoTimer) * 60000
        '        lTimerMeteo.Start()
        '    End If

        '    If CInt("0" & ClienteAttivoTimer) > 0 Then
        '        lTimerMonitorAttivo.Interval = CInt("0" & ClienteAttivoTimer) * 60000
        '        lTimerMonitorAttivo.Start()
        '    Else
        '        lTimerMonitorAttivo.Interval = 60000
        '        lTimerMonitorAttivo.Start()
        '    End If

        '    lTimerNumTuttoSchermo.Interval = 3000
        '    lTimerNumTuttoSchermo.Stop()

        '    CaricaImg()
        '    CaricaVideo()

        '    Cursor.Position = New Point(10, 10)
        '    Cursor.Hide()

        '    TestoReg = lReg.Leggi("Monitor", "WebRadio")
        '    If TestoReg = "1" Then
        '        TestoReg = lReg.Leggi("Monitor", "WebRadioUrl")
        '        startInfo = Process.Start(TestoReg)
        '        startInfo.StartInfo.WindowStyle = ProcessWindowStyle.Hidden
        '        startInfo.StartInfo.WindowStyle = ProcessWindowStyle.Minimized
        '    End If

        'End If
        Threading.Thread.Sleep(500)

    End Sub

    Private Sub kbHook_KeyUp(ByVal Key As System.Windows.Forms.Keys) Handles kbHook.KeyUp

        If Key = Keys.Tab Then
            Me.Dispose()
            frmMonitor = Nothing
        End If

    End Sub
    Private Sub kbHook_KeyDown(ByVal Key As System.Windows.Forms.Keys) Handles kbHook.KeyDown
        If Key = Keys.Tab Then
            Me.Dispose()
            frmMonitor = Nothing
        End If
    End Sub

    Private Sub LabelMousedown(sender As Object, e As MouseEventArgs)
        Dim LBL As Label = DirectCast(sender, Label)

        If InConfigurazione Then
            If e.Button = MouseButtons.Left Then
                If LBL.Tag = RepartoSel.ToString Then
                    locLabel = LBL
                    LBL.DoDragDrop(LBL, DragDropEffects.Move)
                    frmMonitor.ObjMove = LBL.Name
                End If
            End If
        End If

    End Sub

    Private Sub BoxImgMousedown(sender As Object, e As MouseEventArgs)
        Dim Box As PictureBox = DirectCast(sender, PictureBox)

        If frmMonitor.InConfigurazione Or InConfigurazione Then
            frmMonitor.LocBox = Box
            Box.DoDragDrop(Box, DragDropEffects.Move)
            frmMonitor.ObjMove = frmMonitor.LocBox.Name
        End If

    End Sub

    Private Sub BoxVidMousedown(sender As Object, e As MouseEventArgs)
        Dim Box As PictureBox = DirectCast(sender, PictureBox)

        If frmMonitor.InConfigurazione Or InConfigurazione Then
            frmMonitor.LocBox = Box
            Box.DoDragDrop(Box, DragDropEffects.Move)
            frmMonitor.ObjMove = frmMonitor.LocBox.Name
        End If

    End Sub

    Private Sub BoxMeteoMousedown(sender As Object, e As MouseEventArgs)
        Dim Box As PictureBox = DirectCast(sender, PictureBox)

        If frmMonitor.InConfigurazione Or InConfigurazione Then
            frmMonitor.LocBox = Box
            Box.DoDragDrop(Box, DragDropEffects.Move)
            frmMonitor.ObjMove = frmMonitor.LocBox.Name
        End If

    End Sub

    Private Sub Monitor_DragEnter(sender As Object, e As DragEventArgs) Handles Me.DragEnter

        If InConfigurazione Or frmMonitor.InConfigurazione Then
            e.Effect = DragDropEffects.Move
        End If

    End Sub

    Private Sub Monitor_DragDrop(sender As Object, e As DragEventArgs) Handles Me.DragDrop

        'If frmMonitor.InConfigurazione Or InConfigurazione Then
        '    If frmMonitor.ObjMove.Contains("CONTATORE") Then
        '        locLabel.Location = New Point(Cursor.Position.X, Cursor.Position.Y)
        '        frmConfigurazioneMonitor.XLocationUd.Value = Cursor.Position.X
        '        frmConfigurazioneMonitor.YLocationUd.Value = Cursor.Position.Y
        '    ElseIf frmMonitor.ObjMove.Contains("BoxImg") Then
        '        frmMonitor.LocBox.Location = New Point(Cursor.Position.X, Cursor.Position.Y)
        '        frmConfigurazioneMonitor.XLoctionImgUd.Value = Cursor.Position.X
        '        frmConfigurazioneMonitor.YLoctionImgUd.Value = Cursor.Position.Y
        '    ElseIf frmMonitor.ObjMove.Contains("BoxVid") Then
        '        frmMonitor.LocBox.Location = New Point(Cursor.Position.X, Cursor.Position.Y)
        '        frmConfigurazioneMonitor.XLocationVidUd.Value = Cursor.Position.X
        '        frmConfigurazioneMonitor.YLocationVidUd.Value = Cursor.Position.Y
        '    ElseIf frmMonitor.ObjMove.Contains("UrlMeteo") Then
        '        frmMonitor.LocBox.Location = New Point(Cursor.Position.X, Cursor.Position.Y)
        '        frmConfigurazioneMonitor.XLocationMetUD.Value = Cursor.Position.X
        '        frmConfigurazioneMonitor.YLocationMetUD.Value = Cursor.Position.Y
        '    ElseIf frmMonitor.ObjMove.Contains("UrlNews") Then
        '        frmMonitor.LocBox.Location = New Point(Cursor.Position.X, Cursor.Position.Y)
        '        frmConfigurazioneMonitor.XLocationNewUd.Value = Cursor.Position.X
        '        frmConfigurazioneMonitor.YLocationNewUd.Value = Cursor.Position.Y
        '    End If
        'End If

    End Sub

    Public Sub SelezionaContatore()

        For i As Integer = 0 To nReparti - 1
            frmMonitor.vLblReparti(i).BorderStyle = BorderStyle.None
            frmMonitor.vLblReparti(i).BackColor = Color.Transparent
            frmMonitor.vLblReparti(i).Visible = False
        Next
        If RepartoSel >= 0 Then
            frmMonitor.vLblReparti(RepartoSel).BorderStyle = BorderStyle.None
            frmMonitor.vLblReparti(RepartoSel).BackColor = Color.Yellow
            frmMonitor.vLblReparti(RepartoSel).Visible = True
        End If

    End Sub

    Public Sub SetFontContatore(NomeFont As String, Dimensione As Integer, Grassetto As Boolean)
        Dim objFont As System.Drawing.Font

        If NomeFont <> "" Then
            If Grassetto Then
                objFont = New System.Drawing.Font(NomeFont, Dimensione, FontStyle.Bold)
            Else
                objFont = New System.Drawing.Font(NomeFont, Dimensione, FontStyle.Regular)
            End If
            frmMonitor.vLblReparti(RepartoSel).Font = objFont
        End If

    End Sub

    Public Sub SetForeColor(Colore As Integer)

        frmMonitor.vLblReparti(RepartoSel).ForeColor = Color.FromArgb(Colore)

    End Sub

    Public Sub SetPosizione(X As Integer, Y As Integer)

        frmMonitor.vLblReparti(RepartoSel).Location = New Point(X, Y)

    End Sub

    Public Sub CreaBoxImg(Presente As String, W As Integer, H As Integer, X As Integer, Y As Integer, Colore As Color)
        ' Dimensioni attuali del form
        Dim formWidth As Integer = Me.ClientSize.Width
        Dim formHeight As Integer = Me.ClientSize.Height

        Dim scale() As Double = setScale()

        ' Calcola larghezza e altezza dell'immagine ridimensionata
        Dim scaledImageWidth As Integer = CInt(originalImageWidth * scale(0))
        Dim scaledImageHeight As Integer = CInt(originalImageHeight * scale(1))

        ' Calcola i margini (spazi vuoti) attorno all'immagine
        Dim marginX As Integer = (formWidth - scaledImageWidth) \ 2
        Dim marginY As Integer = (formHeight - scaledImageHeight) \ 2

        ' Calcola la posizione della label in base alle coordinate originali dell'immagine
        Dim labelX As Integer = marginX + CInt(X * scale(0))
        Dim labelY As Integer = marginY + CInt(Y * scale(1))
        Dim labelH As Integer = CInt(H * scale(1))
        Dim labelW As Integer = CInt(W * scale(0))

        If Presente = "0" Then
            frmMonitor.BoxImg = Nothing
        Else
            If frmMonitor.BoxImg Is Nothing Then
                frmMonitor.BoxImg = New PictureBox
            End If
            frmMonitor.BoxImg.BorderStyle = BorderStyle.None
            frmMonitor.BoxImg.Name = "BoxImg"
            frmMonitor.Controls.Add(frmMonitor.BoxImg)
            frmMonitor.BoxImg.Left = labelX
            frmMonitor.BoxImg.Top = labelY
            frmMonitor.BoxImg.Width = labelW
            frmMonitor.BoxImg.Height = labelH
            frmMonitor.BoxImg.BackColor = Colore
            frmMonitor.BoxImg.SizeMode = PictureBoxSizeMode.Zoom
            frmMonitor.BoxImg.Visible = True
            If frmMonitor.InConfigurazione Or InConfigurazione Then
                AddHandler frmMonitor.BoxImg.MouseDown, AddressOf BoxImgMousedown
                AddHandler frmMonitor.BoxImg.MouseClick, AddressOf BoxImgMousedown
                frmMonitor.BoxImg.Visible = True
            Else
                AddHandler frmMonitor.BoxImg.Click, AddressOf AllElements_Click
                AddHandler frmMonitor.BoxImg.MouseMove, AddressOf AllElements_MouseMove
                frmMonitor.BoxImg.Visible = True
            End If
        End If

    End Sub

    Public Sub CreaBoxVid(Presente As String, W As Integer, H As Integer, X As Integer, Y As Integer)
        ' Dimensioni attuali del form
        Dim formWidth As Integer = Me.ClientSize.Width
        Dim formHeight As Integer = Me.ClientSize.Height

        Dim scale() As Double = setScale()

        ' Calcola larghezza e altezza dell'immagine ridimensionata
        Dim scaledImageWidth As Integer = CInt(originalImageWidth * scale(0))
        Dim scaledImageHeight As Integer = CInt(originalImageHeight * scale(1))

        ' Calcola i margini (spazi vuoti) attorno all'immagine
        Dim marginX As Integer = (formWidth - scaledImageWidth) \ 2
        Dim marginY As Integer = (formHeight - scaledImageHeight) \ 2

        ' Calcola la posizione della label in base alle coordinate originali dell'immagine
        Dim labelX As Integer = marginX + CInt(X * scale(0))
        Dim labelY As Integer = marginY + CInt(Y * scale(1))
        Dim labelH As Integer = CInt(H * scale(1))
        Dim labelW As Integer = CInt(W * scale(0))

        If Presente = "0" Then
            frmMonitor.BoxVid = Nothing
        Else
            If frmMonitor.BoxVid Is Nothing Then
                frmMonitor.BoxVid = New PictureBox
            End If
            frmMonitor.BoxVid.BorderStyle = BorderStyle.None
            frmMonitor.BoxVid.Name = "BoxVid"
            frmMonitor.Controls.Add(frmMonitor.BoxVid)
            frmMonitor.BoxVid.Left = labelX
            frmMonitor.BoxVid.Top = labelY
            frmMonitor.BoxVid.Width = labelW
            frmMonitor.BoxVid.Height = labelH
            frmMonitor.BoxVid.BackColor = Color.Yellow
            frmMonitor.BoxVid.Visible = True
            AddHandler frmMonitor.BoxVid.MouseDown, AddressOf BoxVidMousedown
            AddHandler frmMonitor.BoxVid.MouseClick, AddressOf BoxVidMousedown
        End If

    End Sub

    Public Sub CreaBoxMet(Presente As String, W As Integer, H As Integer, X As Integer, Y As Integer)

        If Presente = "0" Then
            frmMonitor.BoxMeteo = Nothing
        Else
            If frmMonitor.BoxMeteo Is Nothing Then
                frmMonitor.BoxMeteo = New PictureBox
            End If
            frmMonitor.BoxMeteo.BorderStyle = BorderStyle.None
            frmMonitor.BoxMeteo.Name = "UrlMeteo"
            frmMonitor.Controls.Add(frmMonitor.BoxMeteo)
            frmMonitor.BoxMeteo.Left = X
            frmMonitor.BoxMeteo.Top = Y
            frmMonitor.BoxMeteo.Width = W
            frmMonitor.BoxMeteo.Height = H
            frmMonitor.BoxMeteo.BackColor = Color.Yellow
            frmMonitor.BoxMeteo.Visible = True
            AddHandler frmMonitor.BoxMeteo.MouseDown, AddressOf BoxMeteoMousedown
            AddHandler frmMonitor.BoxMeteo.MouseClick, AddressOf BoxMeteoMousedown
        End If

    End Sub

    Public Sub CreaBoxNew(Presente As String, W As Integer, H As Integer, X As Integer, Y As Integer)
        ' Dimensioni attuali del form
        Dim formWidth As Integer = Me.ClientSize.Width
        Dim formHeight As Integer = Me.ClientSize.Height

        Dim scale() As Double = setScale()

        ' Calcola larghezza e altezza dell'immagine ridimensionata
        Dim scaledImageWidth As Integer = CInt(originalImageWidth * scale(0))
        Dim scaledImageHeight As Integer = CInt(originalImageHeight * scale(1))

        ' Calcola i margini (spazi vuoti) attorno all'immagine
        Dim marginX As Integer = (formWidth - scaledImageWidth) \ 2
        Dim marginY As Integer = (formHeight - scaledImageHeight) \ 2

        ' Calcola la posizione della label in base alle coordinate originali dell'immagine
        Dim labelX As Integer = marginX + CInt(X * scale(0))
        Dim labelY As Integer = marginY + CInt(Y * scale(1))
        Dim labelH As Integer = CInt(H * scale(1))
        Dim labelW As Integer = CInt(W * scale(0))

        If Presente = "0" Then
            frmMonitor.UrlNews = Nothing
        Else
            Try
                If frmMonitor.UrlNews Is Nothing Then
                    frmMonitor.UrlNews = New WebBrowser
                End If

            Catch ex As Exception
                frmMonitor.UrlNews = New WebBrowser

            End Try
            'frmMonitor.UrlNews.BorderStyle = BorderStyle.None
            frmMonitor.UrlNews.Name = "UrlNews"
            frmMonitor.Controls.Add(frmMonitor.UrlNews)
            frmMonitor.UrlNews.Left = labelX
            frmMonitor.UrlNews.Top = labelY
            frmMonitor.UrlNews.Width = labelW
            frmMonitor.UrlNews.Height = labelH
            frmMonitor.UrlNews.BackColor = Color.Yellow
            frmMonitor.UrlNews.Visible = True
        End If

    End Sub

    Public Sub CreaBoxNumTuttoSchermo(Numero As String, Rep As String)
        Dim objFont As System.Drawing.Font
        Dim objFontRep As System.Drawing.Font

        If Numero = "" Then
            If Not IsNothing(frmMonitor.NumTuttoSchermoLbl) Then
                If Not frmMonitor.NumTuttoSchermoLbl.IsDisposed Then frmMonitor.NumTuttoSchermoLbl.Dispose()
                frmMonitor.NumTuttoSchermoLbl = Nothing
            End If
            If Not IsNothing(frmMonitor.NumTuttoSchermoPct) Then
                If Not frmMonitor.NumTuttoSchermoPct.IsDisposed Then frmMonitor.NumTuttoSchermoPct.Dispose()
                frmMonitor.NumTuttoSchermoPct = Nothing
            End If
            If Not IsNothing(frmMonitor.NumTuttoSchermoRepLbl) Then
                If Not frmMonitor.NumTuttoSchermoRepLbl.IsDisposed Then frmMonitor.NumTuttoSchermoRepLbl.Dispose()
                frmMonitor.NumTuttoSchermoLbl = Nothing
            End If
            Exit Sub
        End If

        If FontNumeroTuttoSchermo <> "" Then
            If GrassettoFontNumeroTuttoSchermo = "1" Then
                objFont = New System.Drawing.Font(FontNumeroTuttoSchermo, CInt(DimensioneFontNumeroTuttoSchermo), FontStyle.Bold)
            Else
                objFont = New System.Drawing.Font(FontNumeroTuttoSchermo, CInt(DimensioneFontNumeroTuttoSchermo), FontStyle.Regular)
            End If
        End If

        If FontNumeroTuttoSchermoRep <> "" Then
            If GrassettoFontNumeroTuttoSchermoRep = "1" Then
                objFontRep = New System.Drawing.Font(FontNumeroTuttoSchermoRep, CInt(DimensioneFontNumeroTuttoSchermoRep), FontStyle.Bold)
            Else
                objFontRep = New System.Drawing.Font(FontNumeroTuttoSchermoRep, CInt(DimensioneFontNumeroTuttoSchermoRep), FontStyle.Regular)
            End If
        End If

        frmMonitor.NumTuttoSchermoPct = Nothing
        frmMonitor.NumTuttoSchermoPct = New PictureBox
        frmMonitor.NumTuttoSchermoPct.BorderStyle = BorderStyle.None
        frmMonitor.NumTuttoSchermoPct.Name = "NumTuttoSchermo"
        frmMonitor.Controls.Add(frmMonitor.NumTuttoSchermoPct)
        frmMonitor.NumTuttoSchermoPct.Left = 0
        frmMonitor.NumTuttoSchermoPct.Top = 0
        frmMonitor.NumTuttoSchermoPct.Width = frmMonitor.Width
        frmMonitor.NumTuttoSchermoPct.Height = frmMonitor.Height
        frmMonitor.NumTuttoSchermoPct.Visible = True
        frmMonitor.NumTuttoSchermoPct.BringToFront()

        Dim hNumTuttoSchermoLbl As Integer
        Dim hNumTuttoSchermoRepLbl As Integer

        hNumTuttoSchermoLbl = Math.Round(frmMonitor.Height / 4, 0) * 3
        hNumTuttoSchermoRepLbl = frmMonitor.Height - hNumTuttoSchermoLbl

        frmMonitor.NumTuttoSchermoLbl = Nothing
        frmMonitor.NumTuttoSchermoLbl = New Label
        If FontNumeroTuttoSchermo <> "" Then
            frmMonitor.NumTuttoSchermoLbl.Font = objFont
        End If
        frmMonitor.NumTuttoSchermoLbl.Name = "NumTuttoSchermoL"
        frmMonitor.NumTuttoSchermoPct.Controls.Add(frmMonitor.NumTuttoSchermoLbl)
        frmMonitor.NumTuttoSchermoLbl.Visible = True
        frmMonitor.NumTuttoSchermoLbl.Left = 0
        frmMonitor.NumTuttoSchermoLbl.Top = 0
        frmMonitor.NumTuttoSchermoLbl.Width = frmMonitor.Width
        frmMonitor.NumTuttoSchermoLbl.Height = hNumTuttoSchermoLbl
        frmMonitor.NumTuttoSchermoLbl.Text = Numero
        frmMonitor.NumTuttoSchermoLbl.TextAlign = ContentAlignment.MiddleCenter
        frmMonitor.NumTuttoSchermoLbl.BackColor = Color.Transparent
        'frmMonitor.NumTuttoSchermoLbl.BringToFront()

        frmMonitor.NumTuttoSchermoRepLbl = Nothing
        frmMonitor.NumTuttoSchermoRepLbl = New Label
        If FontNumeroTuttoSchermoRep <> "" Then
            frmMonitor.NumTuttoSchermoRepLbl.Font = objFontRep
        End If
        frmMonitor.NumTuttoSchermoRepLbl.Name = "NumTuttoSchermoL"
        frmMonitor.NumTuttoSchermoPct.Controls.Add(frmMonitor.NumTuttoSchermoRepLbl)
        frmMonitor.NumTuttoSchermoRepLbl.Visible = True
        frmMonitor.NumTuttoSchermoRepLbl.Left = 0
        frmMonitor.NumTuttoSchermoRepLbl.Top = hNumTuttoSchermoLbl
        frmMonitor.NumTuttoSchermoRepLbl.Width = frmMonitor.Width
        frmMonitor.NumTuttoSchermoRepLbl.Height = hNumTuttoSchermoRepLbl
        frmMonitor.NumTuttoSchermoRepLbl.Text = Rep
        frmMonitor.NumTuttoSchermoRepLbl.TextAlign = ContentAlignment.MiddleCenter
        frmMonitor.NumTuttoSchermoRepLbl.BackColor = Color.Transparent

        If SfondoNumeroTuttoSchermo <> "" Then
            frmMonitor.NumTuttoSchermoPct.Image = System.Drawing.Image.FromFile(SfondoNumeroTuttoSchermo)
            frmMonitor.NumTuttoSchermoPct.SizeMode = PictureBoxSizeMode.Zoom
        End If

        If frmMonitor.InConfigurazione Or InConfigurazione Then
            frmMonitor.NumTuttoSchermoPct.BackColor = Color.Yellow
        Else
            frmMonitor.NumTuttoSchermoPct.BackColor = Color.White
            frmMonitor.lTimerNumTuttoSchermo.Start()
        End If

    End Sub

    Public Sub SelezionaBoxImg(Selezione As String)

        If frmMonitor.BoxImg Is Nothing Then
            Exit Sub
        End If

        If Selezione = "1" Then
            frmMonitor.BoxImg.BackColor = Color.Yellow
            frmMonitor.BoxImg.Visible = True
        Else
            frmMonitor.BoxImg.BackColor = Color.Transparent
            frmMonitor.BoxImg.Visible = False
        End If

    End Sub

    Public Sub SelezionaBoxVid(Selezione As String)

        If frmMonitor.BoxVid Is Nothing Then
            Exit Sub
        End If

        If Selezione = "1" Then
            frmMonitor.BoxVid.BackColor = Color.Yellow
            frmMonitor.BoxVid.Visible = True
        Else
            frmMonitor.BoxVid.BackColor = Color.Transparent
            frmMonitor.BoxVid.Visible = False
        End If

    End Sub

    Public Sub SelezionaBoxMet(Selezione As String)

        If frmMonitor.BoxMeteo Is Nothing Then
            Exit Sub
        End If

        If Selezione = "1" Then
            frmMonitor.BoxMeteo.BackColor = Color.Yellow
            frmMonitor.BoxMeteo.Visible = True
        Else
            frmMonitor.BoxMeteo.BackColor = Color.Transparent
            frmMonitor.BoxMeteo.Visible = False
        End If

    End Sub

    Public Sub SelezionaBoxNew(Selezione As String)

        If frmMonitor.BoxNews Is Nothing Then
            Exit Sub
        End If

        If Selezione = "1" Then
            frmMonitor.BoxNews.BackColor = Color.Yellow
            frmMonitor.BoxNews.Visible = True
        Else
            frmMonitor.BoxNews.BackColor = Color.Transparent
            frmMonitor.BoxNews.Visible = False
        End If

    End Sub

    Private Sub lTimerImgVid_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerImgVid.Tick

        If Not InScaricamento Then
            CambiaImgVid()
        End If

    End Sub

    Public Sub AvanzaReparto(ByVal NomeR As String, ByVal nReparto As Integer, ByVal Numero As Integer)
        Dim NomeFile As String
        Dim LivVolume As Single = 0

        'LivVolume = myPlayer.Audio.Volume
        'myPlayer.Audio.Volume = 0
        NomeFile = PathAudio & IIf(PathAudio.EndsWith("\"), "", "\") & NomeR & "\" & NomeR & "_" & Numero.ToString & ".mp3"
        If Not My.Computer.FileSystem.FileExists(NomeFile) Then
            NomeFile = PathAudio & IIf(PathAudio.EndsWith("\"), "", "\") & NomeR & "\" & NomeR & "_" & Numero.ToString & ".wav"
        End If
        If Not My.Computer.FileSystem.FileExists(NomeFile) Then
            Exit Sub
        End If
        frmMonitor.vLblReparti(nReparto).Text = Numero.ToString.PadLeft(2, "0")
        'frmMonitor.OpenFile2Audio(NomeFile)
        If NonChiamareNumeri Then Exit Sub
        frmMonitor.OpenFile(NomeFile, frmMonitor.BoxVid.Handle, frmMonitor)
        'While currentState = PlayState.Running
        '    TextBox1.Text = "Dentro"
        '    Try
        '        Application.DoEvents()

        '    Catch ex As Exception
        '        MsgBox(ex.Message)
        '    End Try
        '    TextBox1.Text = "Fuori"
        'End While
        'TextBox1.Text = "Riparte timer da reparto"
        'lTimerChiamate.Start()
        'While InEsecuzioneAud
        '    Application.DoEvents()
        'End While
        'myPlayer.Audio.Volume = LivVolume

    End Sub

    Public Sub EseguiMP3(ByVal NomeMp3 As String)
        Dim LivVolume As Single = 0

        LivVolume = myPlayer.Audio.Volume
        myPlayer.Audio.Volume = 0

        'frmMonitor.OpenFile2Audio(NomeMp3)
        frmMonitor.OpenFile(NomeMp3, frmMonitor.BoxVid.Handle, frmMonitor)
        'While currentState = PlayState.Running
        '    Application.DoEvents()
        'End While
        'myPlayer.Audio.Volume = LivVolume

    End Sub


    'FUNZIONI PER IL VIDEO PLAYER
    Public Sub OpenFile(ByVal fName As String, ByVal VidHand As IntPtr, ByVal VidCtrl As System.Windows.Forms.Control)

        'OpenFile(OpenFileDialog1.FileName, VidScreenBox.Handle, Me)

        ' Make sure everything is closed
        filename = ""
        CloseClip()

        UseHand = VidHand 'Handle to Display Video if any
        UseCtrl = VidCtrl 'Control to Display Video if any

        filename = fName
        currentState = PlayState.Stopped 'Reset State to Stopped
        currentVolume = VolumeFull 'Reset Volume

        PlayMedia(fName) 'Call Main Sub
        lTimerPlayer.Interval = 500
        lTimerPlayer.Start()
    End Sub

    Private Sub PlayMedia(ByVal fName As String)
        Dim hr As Integer = 0
        If fName = Nothing Then Exit Sub
        Try
            graphBuilder = DirectCast(New FilterGraph, IFilterGraph2) 'Load Graph Builder Device

            hr = graphBuilder.RenderFile(fName, Nothing) ' Initialize Graph Builder
            DsError.ThrowExceptionForHR(hr)

            'Load all Interfaces we will use
            mediaControl = DirectCast(graphBuilder, IMediaControl)
            mediaEventEx = DirectCast(graphBuilder, IMediaEventEx)
            mediaSeeking = DirectCast(graphBuilder, IMediaSeeking)
            mediaPosition = DirectCast(graphBuilder, IMediaPosition)
            videoWindow = DirectCast(graphBuilder, IVideoWindow)
            basicAudio = DirectCast(graphBuilder, IBasicVideo)
            basicVideo = DirectCast(graphBuilder, IBasicAudio)

            Call CheckType() 'Check to See if Audio or Video Call

            If isAudioOnly = False Then
                'Notfy Window of Video
                hr = mediaEventEx.SetNotifyWindow(UseHand, WMGraphNotify, IntPtr.Zero)
                DsError.ThrowExceptionForHR(hr)

                'Set Owner to Display Video
                hr = videoWindow.put_Owner(UseHand)
                DsError.ThrowExceptionForHR(hr)

                'Set Owner Video Style
                hr = videoWindow.put_WindowStyle(WindowStyle.Child And WindowStyle.ClipSiblings And WindowStyle.ClipChildren)
                DsError.ThrowExceptionForHR(hr)
            End If

#If DEBUG Then
            'rot = New DsROTEntry(graphBuilder)
#End If

            Me.Focus()

            'Start Media
            hr = mediaControl.Run
            DsError.ThrowExceptionForHR(hr)

            currentState = PlayState.Running

            If isAudioOnly = False Then
                'Set Video Size
                hr = VideoWindowSize(1, 1)
                DsError.ThrowExceptionForHR(hr)
            End If
        Catch ex As Exception
            MsgBox("Error " & ex.Message, MsgBoxStyle.Critical, "Error")
            RaiseEvent MedClose()
        End Try
    End Sub

    Private Sub CheckType()
        Try
            Dim hr As Integer = 0
            Dim lVisible As OABool

            'If Interface is Nothing then Media is Audio
            If basicVideo Is Nothing Or videoWindow Is Nothing Then
                isAudioOnly = True
            Else
                isAudioOnly = False
            End If

            'Another way to test if Audio or Video
            hr = videoWindow.get_Visible(lVisible)
            If hr < 0 Then
                isAudioOnly = True
            End If
        Catch ex As Exception
            MsgBox("Errpr " & ex.Message, MsgBoxStyle.Critical, "Error")
            RaiseEvent MedClose()
        End Try
    End Sub

    Private Function VideoWindowSize(ByVal nMultiplier As Integer, ByVal nDivider As Integer) As Integer
        Try
            Dim hr As Integer = 0
            Dim lHeight As Integer, lWidth As Integer
            'Get Video Size
            hr = basicVideo.GetVideoSize(lWidth, lHeight)
            If hr = DsResults.E_NoInterface Then Return 0 : Exit Function

            'Change if Different Size is selected in menu(50%, 200%..)
            lWidth = lWidth * nMultiplier / nDivider
            lHeight = lHeight * nMultiplier / nDivider

            lWidth = WBoxVid
            lHeight = HBoxVid

            'Set Window Size video will play on
            UseCtrl.ClientSize = New Size(lWidth, lHeight)
            Windows.Forms.Application.DoEvents()

            'Set Video Position on Window
            hr = videoWindow.SetWindowPosition(0, 0, lWidth, lHeight)
            Return hr
        Catch ex As Exception
            MsgBox("Error " & ex.Message, MsgBoxStyle.Critical, "Error")
            RaiseEvent MedClose()
        End Try
    End Function

    Private Sub CloseClip()
        Try
            Dim hr As Integer = 0

            'Reset all Properties to Default
            currentState = PlayState.Stopped
            isAudioOnly = True
            isFullScreen = False
            filename = ""

            'Call sub to Close and Release from memory
            Call CloseInterfaces()

            'Reset more properties
            currentState = PlayState.Init

        Catch ex As Exception
            MsgBox("Errpr " & ex.Message, MsgBoxStyle.Critical, "Error")
            RaiseEvent MedClose()
        End Try
    End Sub

    Private Sub CloseInterfaces()
        Try
            Dim hr As Integer = 0
            'Release Window Handle, Reset back to Normal
            If isAudioOnly = False Then
                hr = videoWindow.put_Visible(OABool.False)
                DsError.ThrowExceptionForHR(hr)

                hr = videoWindow.put_Owner(IntPtr.Zero)
                DsError.ThrowExceptionForHR(hr)
            End If

            If mediaEventEx Is Nothing = False Then
                hr = mediaEventEx.SetNotifyWindow(IntPtr.Zero, 0, IntPtr.Zero)
                DsError.ThrowExceptionForHR(hr)
            End If


#If DEBUG Then
            'If rot Is Nothing = False Then
            '    rot.Dispose()
            '    rot = Nothing
            'End If
#End If

            'Release everything from memory
            mediaEventEx = Nothing
            mediaSeeking = Nothing
            mediaPosition = Nothing
            mediaControl = Nothing
            basicAudio = Nothing
            basicVideo = Nothing
            videoWindow = Nothing
            frameStep = Nothing
            If Not IsNothing(graphBuilder) Then
                Marshal.ReleaseComObject(graphBuilder)
            End If
            graphBuilder = Nothing
            GC.Collect()
        Catch ex As Exception

        End Try
    End Sub
    'FINE FUNZIONI VIDEOPLAYER

    'NUOVE FUNZIONI NUOVO VIDEOPLAYER
    Public Sub OpenFile2(ByVal fName As String)

        InEsecuzioneVid = True
        frmMonitor.myPlayer.Play(fName)
        If (frmMonitor.myPlayer.LastError) Then
            'MessageBox.Show(myPlayer.LastErrorString)
            InEsecuzioneVid = False
        Else
            ' show media metadata properties (here for audio media only)
            If Not frmMonitor.myPlayer.Has.Video Then
                frmMonitor.myMetadata = frmMonitor.myPlayer.Media.GetMetadata
                frmMonitor.BoxVid.BackgroundImageLayout = ImageLayout.Zoom

                frmMonitor.BoxVid.BackgroundImage = frmMonitor.myMetadata.Image
            End If
        End If

    End Sub

    Public Sub OpenFile2Audio(ByVal fName As String)

        InEsecuzioneAud = True
        frmMonitor.myPlayerAudio.Play(fName)
        If (frmMonitor.myPlayerAudio.LastError) Then
            'MessageBox.Show(myPlayer.LastErrorString)
            InEsecuzioneAud = False
        Else
            ' show media metadata properties (here for audio media only)
            If Not frmMonitor.myPlayerAudio.Has.Video Then
                frmMonitor.myMetadataAudio = frmMonitor.myPlayerAudio.Media.GetMetadata
                frmMonitor.BoxAudio.BackgroundImageLayout = ImageLayout.Zoom

                frmMonitor.BoxAudio.BackgroundImage = frmMonitor.myMetadataAudio.Image
            End If
        End If

    End Sub

    Private Sub DisposeMetadata()
        If myMetadata IsNot Nothing Then
            BoxVid.BackgroundImage = Nothing
            myMetadata.Dispose()
            myMetadata = Nothing
        End If
    End Sub
    Private Sub DisposeMetadataAudio()
        If myMetadataAudio IsNot Nothing Then
            BoxAudio.BackgroundImage = Nothing
            myMetadataAudio.Dispose()
            myMetadataAudio = Nothing
        End If
    End Sub

    Private Sub MyPlayer_MediaEndedNotice(sender As Object, e As EndedEventArgs)

        If e.StopReason = StopReason.Finished Then
            InEsecuzioneVid = False
        End If
        ' you can just stop any processes (and not starting new media) from the
        ' MediaEndedNotice eventhandler that is fired just before the MediaEnded event.
    End Sub

    ' Media has finished playing (2)
    Private Sub MyPlayer_MediaEnded(sender As Object, e As EndedEventArgs)

        DisposeMetadata()

    End Sub

    Private Sub MyPlayerAudio_MediaEndedNotice(sender As Object, e As EndedEventArgs)

        If e.StopReason = StopReason.Finished Then
        End If
        InEsecuzioneAud = False
        ' you can just stop any processes (and not starting new media) from the
        ' MediaEndedNotice eventhandler that is fired just before the MediaEnded event.
    End Sub

    ' Media has finished playing (2)
    Private Sub MyPlayerAudio_MediaEnded(sender As Object, e As EndedEventArgs)

        DisposeMetadataAudio()

    End Sub
    'FINE NUOVE FUNZIONI NUOVO VIDEOPLAYER


    Private Sub lTimerPlayer_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerPlayer.Tick
        Try
            Dim hr As Integer = 0
            If filename = Nothing Then

            Else
                If currentState = PlayState.Stopped Or currentState = PlayState.Paused Or currentState = PlayState.Init Then Exit Sub
                Dim MedDur As Double
                Dim MedPos As Double

                hr = mediaPosition.get_Duration(MedDur)
                DsError.ThrowExceptionForHR(hr)

                hr = mediaPosition.get_CurrentPosition(MedPos)
                DsError.ThrowExceptionForHR(hr)

                'If MedPos >= MedDur Then
                If Math.Abs(MedPos - MedDur) < 0.5 Then
                    CloseClip()
                End If

                MedPos = Nothing
                MedDur = Nothing
            End If
        Catch ex As Exception
            MsgBox("Error " & Err.Description, MsgBoxStyle.Critical, "Error")
            RaiseEvent MedClose()
        End Try

    End Sub

    Private Sub lTimerChiamate_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerChiamate.Tick
        Dim StrQ As String
        Dim lDsetLoc As New DataSet
        Dim i As Integer = 0
        Dim numero As String = ""
        Dim turno As String = ""
        Dim sportello As String = ""
        Dim Percorso As String
        Dim idturno As String
        Dim idpostazione As String
        Dim id As Integer
        Dim TestoTurno As String = ""

        Try
            'StrQ = "select * from turni"
            'If DbConnection.Estrai(StrQ, lDsetLoc, "turni", True) Then
            '    For i = 0 To lDsetLoc.Tables("turni").Rows.Count - 1
            '        If lDsetLoc.Tables("turni").Rows(i).Item("stato") = "1" Then
            '            If lDsetLoc.Tables("turni").Rows(i).Item("turno") = "A" Then
            '                SchermoA.Visible = False
            '            ElseIf lDsetLoc.Tables("turni").Rows(i).Item("turno") = "B" Then
            '                SchermoB.Visible = False
            '            ElseIf lDsetLoc.Tables("turni").Rows(i).Item("turno") = "C" Then
            '                SchermoC.Visible = False
            '            End If
            '        Else
            '            If lDsetLoc.Tables("turni").Rows(i).Item("turno") = "A" Then
            '                SchermoA.Visible = True
            '            ElseIf lDsetLoc.Tables("turni").Rows(i).Item("turno") = "B" Then
            '                SchermoB.Visible = True
            '            ElseIf lDsetLoc.Tables("turni").Rows(i).Item("turno") = "C" Then
            '                SchermoC.Visible = True
            '            End If
            '        End If
            '    Next
            'End If
            StrQ = "select * from coda order by id"
            If DbConnection.Estrai(StrQ, lDsetLoc, "coda", True) Then
                If lDsetLoc.Tables("coda").Rows.Count > 0 Then
                    lTimerChiamate.Stop()
                    numero = lDsetLoc.Tables("coda").Rows(0).Item("numero")
                    turno = lDsetLoc.Tables("coda").Rows(0).Item("turno")
                    sportello = lDsetLoc.Tables("coda").Rows(0).Item("sportello")
                    idturno = lDsetLoc.Tables("coda").Rows(0).Item("id_turno")
                    'turnoSposta = idturno
                    idpostazione = lDsetLoc.Tables("coda").Rows(0).Item("id_postazione")
                    id = lDsetLoc.Tables("coda").Rows(0).Item("id")

                    TestoTurno = sportello & " -- " & turno & numero.PadLeft(3, "0")

                    ilmonitor.CambiaMonitor(idturno, Int(Val(numero)), idpostazione)

                    'CODICE PER BOX CHIAMATI
                    'UC10.Text = UC9.Text
                    'UC9.Text = UC8.Text
                    'UC8.Text = UC7.Text
                    'UC7.Text = UC6.Text
                    'UC6.Text = UC5.Text
                    'UC5.Text = UC4.Text
                    'UC4.Text = UC3.Text
                    'UC3.Text = UC2.Text
                    'UC2.Text = UC1.Text
                    'UC1.Text = TestoTurno

                    'Scorrimento.Enabled = True
                    'Scorrimento.Start()

                    Dim path As String = Windows.Forms.Application.UserAppDataPath ' & "\play.wpl"

                    ' Cancella tutti i file .wpl nella cartella
                    For Each wplFile As String In Directory.GetFiles(path, "*.wpl")
                        Try
                            File.Delete(wplFile)
                        Catch ex As Exception
                            ' Gestisci eventuali errori di eliminazione, se necessario
                        End Try
                    Next

                    path = System.IO.Path.Combine(path, "play_" & Now.Ticks.ToString() & ".wpl")

                    Dim tracce As New List(Of String)
                    tracce.Add(Windows.Forms.Application.StartupPath & "\mp3\Turno " & turno & ".mp3")
                    tracce.Add(Windows.Forms.Application.StartupPath & "\mp3\" & numero & ".mp3")
                    tracce.Add(Windows.Forms.Application.StartupPath & "\mp3\Recarsi allo sportello " & idpostazione & ".mp3")

                    Using sw2 As New StreamWriter(path, False, System.Text.Encoding.UTF8)
                        sw2.WriteLine("<?wpl version=""1.0""?>")
                        sw2.WriteLine("<smil>")
                        sw2.WriteLine("  <head>")
                        sw2.WriteLine("    <meta name=""Generator"" content=""Microsoft Windows Media Player -- 12.0.9600.17031""/>")
                        sw2.WriteLine("    <meta name=""ItemCount"" content=""" & tracce.Count.ToString() & """/>")
                        sw2.WriteLine("    <title>PROVA</title>")
                        sw2.WriteLine("  </head>")
                        sw2.WriteLine("  <body>")
                        sw2.WriteLine("    <seq>")
                        For Each Percorso2 As String In tracce
                            sw2.WriteLine("      <media src=""" & Percorso2.Replace("""", "&quot;") & """/>")
                        Next
                        sw2.WriteLine("    </seq>")
                        sw2.WriteLine("  </body>")
                        sw2.WriteLine("</smil>")
                    End Using

                    'Dim sw As StreamWriter
                    'If File.Exists(path) Then My.Computer.FileSystem.DeleteFile(path)
                    'sw = File.CreateText(path)

                    'sw.WriteLine("<?wpl version='1.0'?>")
                    'sw.WriteLine("<smil>")
                    'sw.WriteLine("<head>")
                    'sw.WriteLine("<meta name='Generator' content='Microsoft Windows Media Player -- 12.0.9600.17031'/>")
                    'sw.WriteLine("<meta name='ItemCount' content='4'/>")
                    'sw.WriteLine("<title>PROVA</title>")
                    'sw.WriteLine("</head>")
                    'sw.WriteLine("<body>")
                    'sw.WriteLine("<seq>")
                    'Percorso = Windows.Forms.Application.StartupPath & "\mp3\Turno " & turno & ".mp3"
                    'sw.WriteLine("<media src='" & Percorso & "'/>")
                    'Percorso = Windows.Forms.Application.StartupPath & "\mp3\" & numero & ".mp3"
                    'sw.WriteLine("<media src='" & Percorso & "'/>")
                    ''Percorso = Application.StartupPath & "\mp3\RECARSI ALLO SPORTELLO.mp3"
                    ''sw.WriteLine("<media src='" & Percorso & "'/>")
                    'Percorso = Windows.Forms.Application.StartupPath & "\mp3\Recarsi allo sportello " & idpostazione & ".mp3"
                    'sw.WriteLine("<media src='" & Percorso & "'/>")
                    'sw.WriteLine("</seq>")
                    'sw.WriteLine("</body>")
                    'sw.WriteLine("</smil>")
                    'sw.Flush()
                    'sw.Close()
                    Esegui(path)
                    StrQ = "delete from coda where id=" & id
                    DbConnection.EseguiSQL(StrQ)
                    wait(1000)
                    'sw = Nothing
                    'wmp.close()
                    'wmp.currentPlaylist.clear()
                    lTimerChiamate.Start()
                Else
                    lTimerChiamate.Start()
                End If
            Else
                lTimerChiamate.Start()
            End If

        Catch ex As Exception
            lTimerChiamate.Start()
        End Try

    End Sub

    Private Sub lTimerNews_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerNews.Tick

        If IsNothing(UrlNews) Then Exit Sub
        If Not IsNothing(BoxNewUrl) And Not UrlNews.IsDisposed Then
            If BoxNewUrl <> "" Then
                If Not IsNothing(UrlNews) Then
                    UrlNews.Navigate(BoxNewUrl)
                End If
            End If
        End If

    End Sub

    Private Sub lTimerMeteo_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerMeteo.Tick

        If IsNothing(UrlMeteo) Then Exit Sub
        If Not IsNothing(BoxMetUrl) And Not UrlMeteo.IsDisposed Then
            If BoxMetUrl <> "" Then
                If Not IsNothing(UrlMeteo) Then
                    UrlMeteo.Navigate(BoxMetUrl)
                End If
            End If
        End If

    End Sub

    Private Sub lTimerMonitorAttivo_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerMonitorAttivo.Tick

        'If Not IsNothing(UrlMonitorAttivo) Then
        '    Dim Att As String = CallHttpFile(UrlMonitorAttivo & "?idcliente=" & IdCliente)
        '    If Att <> "SI" Then
        '        If NoControllo <> "" Then
        '            Dim DataOggi As String = Now.Year.ToString & Now.Month.ToString.PadLeft(2, "0") & Now.Day.ToString.PadLeft(2, "0")
        '            If DataOggi < NoControllo Then
        '                Exit Sub
        '            End If
        '        End If
        '        ClienteAttivo = "0"
        '        MostraFuoriServizio()
        '    Else
        '        ClienteAttivo = "1"
        '        If Not IsNothing(BoxFuoriServizio) Then
        '            BoxFuoriServizio.Visible = False
        '            BoxFuoriServizio = Nothing
        '        End If
        '    End If
        'Else
        '    ClienteAttivo = "0"
        '    MostraFuoriServizio()
        'End If

    End Sub

    Private Sub lTimerNumTuttoSchermo_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerNumTuttoSchermo.Tick

        If Not IsNothing(frmMonitor.NumTuttoSchermoLbl) Then
            If Not frmMonitor.NumTuttoSchermoLbl.IsDisposed Then frmMonitor.NumTuttoSchermoLbl.Dispose()
            frmMonitor.NumTuttoSchermoLbl = Nothing
        End If
        If Not IsNothing(frmMonitor.NumTuttoSchermoPct) Then
            If Not frmMonitor.NumTuttoSchermoPct.IsDisposed Then frmMonitor.NumTuttoSchermoPct.Dispose()
            frmMonitor.NumTuttoSchermoPct = Nothing
        End If
        lTimerNumTuttoSchermo.Stop()

    End Sub

    Private Sub lTimerNoServer_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerNoServer.Tick

        If Not NoServer Then

            Call NascondiServerLocaleInattivo()

        End If

    End Sub

    Private Sub lTimerMp3Orario_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerMp3Orario.Tick
        Dim OrarioOra As String = ""

        lTimerMp3Orario.Stop()

        If nMp3Orario >= 0 Then
            OrarioOra = Now.Hour.ToString.PadLeft(2, "0") & ":" & Now.Minute.ToString.PadLeft(2, "0")
            For i = 0 To nMp3Orario
                If OrarioOra.Split(":")(0) = "00" Then
                    vMp3Orario(2, i) = "00:00"
                Else
                    If OrarioOra.Split(":")(0) = vMp3Orario(0, i).Split(":")(0) Then
                        If OrarioOra.Split(":")(1) > vMp3Orario(0, i).Split(":")(1) Then
                            If vMp3Orario(2, i) = "00:00" Then
                                If nChiamate = 0 Then
                                    ReDim vChiamate(2, 0)
                                Else
                                    ReDim Preserve vChiamate(2, nChiamate)
                                End If

                                vChiamate(0, nChiamate) = "MP3"
                                vChiamate(1, nChiamate) = vMp3Orario(1, i)
                                vChiamate(2, nChiamate) = ""
                                nChiamate += 1

                                vMp3Orario(2, i) = OrarioOra
                            End If
                        End If
                    End If
                End If
            Next
        End If

        lTimerMp3Orario.Start()

    End Sub

    Private Sub lTimerMp3Intervallo_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerMp3Intervallo.Tick

        lTimerMp3Intervallo.Stop()

        If nMp3Intervallo >= 0 Then
            ActualNMp3Intervallo += 1
            If ActualNMp3Intervallo > nMp3Intervallo Then
                ActualNMp3Intervallo = 0
            End If

            If nChiamate = 0 Then
                ReDim vChiamate(2, 0)
            Else
                ReDim Preserve vChiamate(2, nChiamate)
            End If

            vChiamate(0, nChiamate) = "MP3"
            vChiamate(1, nChiamate) = vMp3Intervallo(0, ActualNMp3Intervallo)
            vChiamate(2, nChiamate) = ""
            nChiamate += 1

            If nMp3Intervallo > ActualNMp3Intervallo Then
                lTimerMp3Intervallo.Interval = CInt(vMp3Intervallo(1, ActualNMp3Intervallo + 1)) * 60000
            Else
                lTimerMp3Intervallo.Interval = CInt(vMp3Intervallo(1, 0)) * 60000
            End If

        End If

        lTimerMp3Intervallo.Start()

    End Sub

    Private Sub Monitor_Click(sender As Object, e As EventArgs) Handles Me.Click

        'If Not InConfigurazione And Avanzamento = "1" Then
        '    ChiamataClick()
        '    'Threading.Thread.Sleep(800)
        'End If


    End Sub

    Private Sub AllElements_Click(sender As Object, e As EventArgs)

        'If Not InConfigurazione And Avanzamento = "1" Then
        '    ChiamataClick()
        '    'Threading.Thread.Sleep(800)
        'End If

    End Sub

    Private Sub Monitor_MouseMove(sender As Object, e As MouseEventArgs) Handles Me.MouseMove

        'If (Not InConfigurazione And Not frmMonitor.InConfigurazione) And Avanzamento = "1" Then
        '    Cursor.Position = New Point(1, 1)
        'End If

    End Sub

    Private Sub AllElements_MouseMove(sender As Object, e As MouseEventArgs)

        If Not InConfigurazione And Avanzamento = "1" Then
            Cursor.Position = New Point(1, 1)
        End If

    End Sub

    Private Sub MostraFuoriServizio()

        If IsNothing(BoxFuoriServizio) Then
            BoxFuoriServizio = New PictureBox
            BoxFuoriServizio.BorderStyle = BorderStyle.None
            BoxFuoriServizio.Name = "BoxVid"
            Me.Controls.Add(BoxFuoriServizio)

            BoxFuoriServizio.Left = 0
            BoxFuoriServizio.Top = 0
            BoxFuoriServizio.Width = Me.Width
            BoxFuoriServizio.Height = Me.Height
            BoxFuoriServizio.BackColor = Color.White
            BoxFuoriServizio.Visible = True
            Try
                BoxFuoriServizio.Image = System.Drawing.Image.FromFile(Windows.Forms.Application.StartupPath & "\licenza-scaduta.png")
                BoxFuoriServizio.SizeMode = PictureBoxSizeMode.CenterImage
                BoxFuoriServizio.BringToFront()

            Catch ex As Exception
                MsgBox("Attenzione: file immagine fuori servizio mancante.", MsgBoxStyle.Critical)
            End Try

        End If

    End Sub

    Public Sub MostraServerLocaleInattivo()
        Dim objFont As System.Drawing.Font


        'objFont = New System.Drawing.Font("NOME DEL FONT", DIMENSIONEDELFORM, FontStyle.Bold) QUESTO PER GRASSETTO
        'objFont = New System.Drawing.Font("NOME DEL FONT", DIMENSIONEDELFORM, FontStyle.Regular) QUESTO PER NON GRASSETTO

        objFont = New System.Drawing.Font("Microsoft Sans Serif", 25, FontStyle.Bold) 'QUESTO PER GRASSETTO

        frmMonitor.AlertTuttoSchermoPct = Nothing
        frmMonitor.AlertTuttoSchermoPct = New PictureBox
        frmMonitor.AlertTuttoSchermoPct.BorderStyle = BorderStyle.None
        frmMonitor.AlertTuttoSchermoPct.Name = "AlertTuttoSchermoPct"
        frmMonitor.Controls.Add(frmMonitor.AlertTuttoSchermoPct)
        frmMonitor.AlertTuttoSchermoPct.Left = 0
        frmMonitor.AlertTuttoSchermoPct.Top = Int(frmMonitor.Height / 3)
        frmMonitor.AlertTuttoSchermoPct.Width = frmMonitor.Width
        frmMonitor.AlertTuttoSchermoPct.Height = Int(frmMonitor.Height / 3)
        frmMonitor.AlertTuttoSchermoPct.Visible = True
        frmMonitor.AlertTuttoSchermoPct.BringToFront()
        'PER CARICARE UNO SFONDO
        'frmMonitor.NumTuttoSchermoPct.Image = Image.FromFile("PERCORSO DELLO SFONDO")
        'frmMonitor.NumTuttoSchermoPct.SizeMode = PictureBoxSizeMode.Zoom

        frmMonitor.AlertTuttoSchermoLbl = Nothing
        frmMonitor.AlertTuttoSchermoLbl = New Label
        frmMonitor.AlertTuttoSchermoLbl.Font = objFont
        frmMonitor.AlertTuttoSchermoLbl.Name = "NumTuttoSchermoL"
        frmMonitor.AlertTuttoSchermoPct.Controls.Add(frmMonitor.AlertTuttoSchermoLbl)
        frmMonitor.AlertTuttoSchermoLbl.Visible = True
        frmMonitor.AlertTuttoSchermoLbl.Left = 0
        frmMonitor.AlertTuttoSchermoLbl.Top = 0
        frmMonitor.AlertTuttoSchermoLbl.Width = frmMonitor.AlertTuttoSchermoPct.Width
        frmMonitor.AlertTuttoSchermoLbl.Height = frmMonitor.AlertTuttoSchermoPct.Height
        frmMonitor.AlertTuttoSchermoLbl.Text = "Attenzione: il server locale non è contattabile." & vbCrLf & "Verificare il funzionamento e provare nuovamente a richiamare il numero"
        frmMonitor.AlertTuttoSchermoLbl.TextAlign = ContentAlignment.MiddleCenter
        frmMonitor.AlertTuttoSchermoLbl.BackColor = Color.Transparent
        'frmMonitor.NumTuttoSchermoLbl.BringToFront()

        NoServer = True

    End Sub

    Public Sub NascondiServerLocaleInattivo()

        If Not IsNothing(frmMonitor.AlertTuttoSchermoLbl) Then
            frmMonitor.AlertTuttoSchermoLbl.Top = 50000
            frmMonitor.AlertTuttoSchermoLbl.Visible = False
            'If Not frmMonitor.AlertTuttoSchermoLbl.IsDisposed Then
            '    frmMonitor.AlertTuttoSchermoLbl.Dispose()
            'End If
            'frmMonitor.AlertTuttoSchermoLbl = Nothing
        End If
        If Not IsNothing(frmMonitor.AlertTuttoSchermoPct) Then
            frmMonitor.AlertTuttoSchermoPct.Visible = False
            'If Not frmMonitor.AlertTuttoSchermoPct.IsDisposed Then
            '    frmMonitor.AlertTuttoSchermoPct.Dispose()
            'End If
            'frmMonitor.AlertTuttoSchermoPct = Nothing
        End If

    End Sub

    Private Sub Monitor_Disposed(sender As Object, e As EventArgs) Handles Me.Disposed

        If Not IsNothing(startInfo) Then

            Try
                startInfo.Kill()
            Catch ex As Exception

            End Try

        End If

        Try

            If Not IsNothing(lTimerImgVid) Then lTimerImgVid.Stop()
            If Not IsNothing(lTimerChiamate) Then lTimerChiamate.Stop()
            If Not IsNothing(lTimerMeteo) Then lTimerMeteo.Stop()
            If Not IsNothing(lTimerMonitorAttivo) Then lTimerMonitorAttivo.Stop()
            If Not IsNothing(lTimerNews) Then lTimerNews.Stop()
            If Not IsNothing(lTimerPlayer) Then lTimerPlayer.Stop()
            If Not IsNothing(lTimerNoServer) Then lTimerNoServer.Stop()
        Catch ex As Exception

        End Try


    End Sub

    Private Sub UrlMeteo_Navigating(sender As Object, e As WebBrowserNavigatingEventArgs) Handles UrlMeteo.Navigating, UrlNews.Navigating

        sender.ScriptErrorsSuppressed = True

    End Sub

    Delegate Sub CambiaMonitorSafe(ByVal turno As Integer, ByVal prossimo As Integer, ByVal Postazione As String)
    Public Sub CambiaMonitor(ByVal turno As Integer, ByVal prossimo As Integer, ByVal Postazione As String)
        If Me.InvokeRequired Then
            Dim d As New CambiaMonitorSafe(AddressOf CambiaMonitor)
            Me.Invoke(d, New Object() {turno, prossimo, Postazione})
        Else
            For Each controllo In Me.Controls
                If controllo.name.ToString = "CONTATORETURNO_" & turno Then
                    controllo.text = prossimo.ToString.PadLeft(3, "0")
                End If
                If controllo.name.ToString = "POSTAZIONETURNO_" & turno Then
                    controllo.text = Postazione
                End If
            Next

            Dim StrQ As String
            Dim lDset As New DataSet
            Dim NomeTurno As String = ""

            StrQ = "select * from turni where ID_turno = " & turno
            If DbConnection.Estrai(StrQ, lDset, "turni", True) Then
                If lDset.Tables("turni").Rows.Count > 0 Then
                    NomeTurno = lDset.Tables("turni").Rows(0).Item("turno")
                End If
            End If


            If IsNothing(vChiamati) Then
                ReDim vChiamati(0)
                vChiamati(0) = "TURNO " & NomeTurno & " - N. " & prossimo.ToString.PadLeft(3, "0") & " - Sp. " & Postazione
            Else
                If vChiamati.Count < maxSizeChiamati Then
                    Dim dimensione = vChiamati.Count
                    ReDim Preserve vChiamati(dimensione)
                    vChiamati(dimensione) = "TURNO " & NomeTurno & " - N. " & prossimo.ToString.PadLeft(3, "0") & " - Sp. " & Postazione
                Else
                    For i As Integer = 0 To maxSizeChiamati - 2
                        vChiamati(i) = vChiamati(i + 1)
                    Next
                    vChiamati(maxSizeChiamati - 1) = "TURNO " & NomeTurno & " - N. " & prossimo.ToString.PadLeft(3, "0") & " - Sp. " & Postazione
                End If
            End If

            Dim numerone As String = NomeTurno & "-" & prossimo.ToString.PadLeft(3, "0")
            BoxNumeroImg.Text = numerone

            Dim testochiamati As String = ""

            testochiamati = "Elenco numeri chiamati" & vbCrLf
            For i As Integer = vChiamati.Count - 1 To 0 Step -1
                If Not testochiamati.Contains(vChiamati(i)) Then
                    testochiamati &= vChiamati(i) & vbCrLf
                End If
            Next
            BoxChiamati.Text = testochiamati

        End If
    End Sub

    Private Sub PositionLabel(label As Label, imageX As Integer, imageY As Integer, imageH As Integer, imageW As Integer)
        ' Dimensioni attuali del form
        Dim formWidth As Integer = Me.ClientSize.Width
        Dim formHeight As Integer = Me.ClientSize.Height

        '' Calcola il rapporto di ridimensionamento
        'Dim scaleX As Double = formWidth / originalImageWidth
        'Dim scaleY As Double = formHeight / originalImageHeight

        '' Usa il minore dei due rapporti per mantenere le proporzioni
        'Dim scale As Double = Math.Min(scaleX, scaleY)

        Dim scale() As Double = setScale()

        ' Calcola larghezza e altezza dell'immagine ridimensionata
        Dim scaledImageWidth As Double = CDbl(originalImageWidth * scale(0))
        Dim scaledImageHeight As Double = CDbl(originalImageHeight * scale(1))

        ' Calcola i margini (spazi vuoti) attorno all'immagine
        Dim marginX As Integer = (formWidth - scaledImageWidth) \ 2
        Dim marginY As Integer = (formHeight - scaledImageHeight) \ 2

        ' Calcola la posizione della label in base alle coordinate originali dell'immagine
        Dim labelX As Integer = marginX + CInt(imageX * scale(0))
        Dim labelY As Integer = marginY + CInt(imageY * scale(1))
        Dim labelH As Integer = CInt(imageH * scale(1))
        Dim labelW As Integer = CInt(imageW * scale(0))

        ' Imposta la posizione della label
        label.Left = labelX
        label.Top = labelY
        label.Height = labelH
        label.Width = labelW

    End Sub

    Private Function setScale() As Double()
        ' Dimensioni attuali del form
        Dim formWidth As Integer = Me.ClientSize.Width
        Dim formHeight As Integer = Me.ClientSize.Height

        ' Calcola il rapporto di ridimensionamento
        Dim scaleX As Double = formWidth / originalImageWidth
        Dim scaleY As Double = formHeight / originalImageHeight

        ' Usa il minore dei due rapporti per mantenere le proporzioni
        Dim scale() As Double '= Math.Min(scaleX, scaleY)
        ReDim scale(1)
        scale(0) = scaleX
        scale(1) = scaleY

        Return scale

    End Function

    Private Sub Esegui(NomeFile As String)
        'Dim path As String = Windows.Forms.Application.UserAppDataPath & "\play.wpl"

        wmp.Ctlcontrols.stop()
        wmp.settings.autoStart = True
        wmp.URL = NomeFile
        wmp.Ctlcontrols.play()

    End Sub

    Private Sub Monitor2_KeyPress(sender As Object, e As KeyPressEventArgs) Handles Me.KeyPress



    End Sub

    Private Sub Monitor2_KeyDown(sender As Object, e As KeyEventArgs) Handles Me.KeyDown
        If e.KeyCode = Keys.P AndAlso e.Shift Then
            Me.Close()
            Me.Dispose()
        End If
    End Sub
End Class