Public Class AppContext
    Inherits ApplicationContext
    Private CaratterePrec As String = ""

#Region " Storage "

    Private WithEvents Tray As NotifyIcon
    Private WithEvents MainMenu As ContextMenuStrip
    Private WithEvents mnuApriMonitor As ToolStripMenuItem
    Private WithEvents mnuMostraImpostazioni As ToolStripMenuItem
    Private WithEvents mnuAttivaStampe As ToolStripMenuItem
    Private WithEvents mnuCaricaImpostazioniLight As ToolStripMenuItem
    Private WithEvents mnuCaricaImpostazioniTot As ToolStripMenuItem
    Private WithEvents mnuSep1 As ToolStripSeparator
    Private WithEvents mnuExit As ToolStripMenuItem
    Public WithEvents lTimer As Timer
    Public WithEvents lTimer2 As Timer
    Public WithEvents lTimerScaricoFiles As Timer
    Public WithEvents lTimerChiusuraMonitor As Timer

#End Region

#Region " Constructor "

    Public Sub New()
        Dim minuti As Integer = 15
        'Initialize the menus
        mnuApriMonitor = New ToolStripMenuItem("Apri monitor")
        mnuMostraImpostazioni = New ToolStripMenuItem("Impostazioni")
        mnuAttivaStampe = New ToolStripMenuItem("Servizio attivo")
        mnuAttivaStampe.CheckOnClick = True
        mnuAttivaStampe.Checked = True
        mnuCaricaImpostazioniLight = New ToolStripMenuItem("Carica solo le configurazioni")
        mnuCaricaImpostazioniTot = New ToolStripMenuItem("Carica tutti i dati")
        mnuSep1 = New ToolStripSeparator()
        mnuExit = New ToolStripMenuItem("Chiudi servizio")
        MainMenu = New ContextMenuStrip
        MainMenu.Items.AddRange(New ToolStripItem() {mnuApriMonitor, mnuMostraImpostazioni, mnuAttivaStampe, mnuCaricaImpostazioniLight, mnuCaricaImpostazioniTot, mnuSep1, mnuExit})


        Dim i As Integer

        lTimer = New Timer
        lTimer.Interval = minuti * 60000
        lTimer.Enabled = True

        lTimer2 = New Timer
        'lTimer2.Interval = 1000
        'lTimer2.Enabled = True

        lTimerScaricoFiles = New Timer
        lTimerScaricoFiles.Interval = 300000
        lTimerScaricoFiles.Enabled = True

        lTimerChiusuraMonitor = New Timer
        lTimerChiusuraMonitor.Interval = 1800000
        lTimerChiusuraMonitor.Enabled = True

        'Initialize the tray
        Tray = New NotifyIcon
        Tray.Icon = Icone.TryIcon
        Tray.ContextMenuStrip = MainMenu
        Tray.Text = "AmgListener"

        'Display
        Tray.Visible = True

        ShowMonitor()

    End Sub

#End Region

#Region " Event handlers "

    Private Sub AppContext_ThreadExit(ByVal sender As Object, ByVal e As System.EventArgs) Handles Me.ThreadExit
        'Guarantees that the icon will not linger.
        Tray.Visible = False
    End Sub

    Private Sub mnuDisplayMonitor_Click(ByVal sender As Object, ByVal e As System.EventArgs) Handles mnuApriMonitor.Click
        chiediAzzeramento = True
        ShowMonitor()
        chiediAzzeramento = False
    End Sub

    Private Sub mnuDisplayForm_Click(ByVal sender As Object, ByVal e As System.EventArgs) Handles mnuMostraImpostazioni.Click
        chiediAzzeramento = True
        ShowMonitor()
        chiediAzzeramento = False
    End Sub

    Private Sub mnuCaricaImpostazioniLight_click(ByVal sender As Object, ByVal e As System.EventArgs) Handles mnuCaricaImpostazioniLight.Click
        LoadImpostazioniFromUrl()
        CaricaImpostazioni()
        CaricaTurniLbl()
    End Sub

    Private Sub mnuCaricaImpostazioniTot_click(ByVal sender As Object, ByVal e As System.EventArgs) Handles mnuCaricaImpostazioniTot.Click
        LoadImpostazioniFromUrl(True)
        CaricaImpostazioni()
        CaricaTurniLbl()
    End Sub

    Private Sub mnuExit_Click(ByVal sender As Object, ByVal e As System.EventArgs) Handles mnuExit.Click
        ExitApplication()
    End Sub

    Private Sub Tray_DoubleClick(ByVal sender As Object, ByVal e As System.EventArgs) Handles Tray.DoubleClick
        ShowDialog()
    End Sub

    Private Sub mnuAttDisatt_Click(ByVal sender As Object, ByVal e As System.EventArgs) Handles mnuAttivaStampe.Click

        If mnuAttivaStampe.Checked Then
            'lTimer.Start()
            Tray.Icon = Icone.TryIcon
        Else
            'lTimer.Stop()
            Tray.Icon = Icone.TryIconRed
        End If

    End Sub

    Private Sub lTimer_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimer.Tick
        Dim oraAttuale As TimeSpan = DateTime.Now.TimeOfDay
        Dim oraInizio As New TimeSpan(19, 0, 0) ' 01:00
        Dim oraFine As New TimeSpan(19, 30, 0)    ' 01:30

        If oraAttuale < oraInizio Then
            Azzerati = False
        End If

        If oraAttuale > oraInizio And oraAttuale < oraFine Then
            If Not Azzerati Then
                LoadImpostazioniFromUrl(True)
                CaricaImpostazioni()
                CaricaTurniLbl()

                Azzerati = True
            End If
        End If

        Dim Sql As String
        Dim lDsetFake As New DataSet

        Sql = "select 1"
        DbConnection.Estrai(Sql, lDsetFake, "fake", True)


    End Sub

    Private Sub lTimerChiusuraMonitor_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerChiusuraMonitor.Tick

        Dim Ora = Now.Hour

        If Ora > 1 And Ora < 3 Then
            closeMonitor()
        End If

        If Ora > 3 And Ora < 5 Then
            ShowMonitor()
        End If

    End Sub

    Private Sub lTimer2_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimer2.Tick

    End Sub

    Private Sub lTimerScaricoFiles_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles lTimerScaricoFiles.Tick

        lTimerScaricoFiles.Stop()

        If SincroAttiva Then
            InScaricamento = True
            ScaricaFilesIV("getfilesgruppo.php", "i", pathImmagini, "gruppi")
            ScaricaFilesIV("getfilesgruppo.php", "v", pathVideo, "gruppi")
            ScaricaFilesIV("getfilescliente.php", "i", pathcliente, "clienti")
            ScaricaFilesIV("getfilescliente.php", "v", pathVideocliente, "clienti")
            InScaricamento = False
        End If

        lTimerScaricoFiles.Start()
    End Sub

    Private Sub ScaricaFilesIV(FilePHP As String, tipo As String, PathSave As String, GruppoCliente As String)
        Dim sGet As String = ""
        Dim Ret As String = ""
        Dim files As Array
        Dim dirInfo As IO.DirectoryInfo
        Dim UrlFiles As String = ""
        Dim Efiles() As String
        Dim FC() As String
        Dim J As Integer
        Dim i As Integer
        Dim CartellaGC As String

        UrlFiles = UrlContact & FilePHP

        sGet = "?idcliente=" & IdCliente & "&tipo=" & tipo
        Ret = CallHttpFile(UrlFiles & sGet)

        If Ret <> "" Then
            If Ret.Contains("NOFILE") Then
                dirInfo = New IO.DirectoryInfo(PathSave)
                files = dirInfo.GetFiles()
                If files.Length > 0 Then
                    For i = 0 To files.Length - 1
                        Try
                            If Not files(i).ToString.ToUpper.Contains("SFONDOMONITOR") & Not files(i).ToString.ToUpper.Contains("SFONDOTOTEM") & Not files(i).ToString.ToUpper.Contains("SFONDOTURNO") Then
                                System.IO.File.Delete(PathSave & "\" & files(i).ToString)
                            End If
                        Catch ex As Exception

                        End Try
                    Next
                End If
            Else
                dirInfo = New IO.DirectoryInfo(PathSave)
                files = dirInfo.GetFiles()
                If files.Length > 0 Then
                    For i = 0 To files.Length - 1
                        If Not Ret.Contains(files(i).ToString) Then
                            Try
                                If Not files(i).ToString.ToUpper.Contains("SFONDOMONITOR") & Not files(i).ToString.ToUpper.Contains("SFONDOTOTEM") & Not files(i).ToString.ToUpper.Contains("SFONDOTURNO") Then
                                    System.IO.File.Delete(PathSave & "\" & files(i).ToString)
                                End If
                            Catch ex As Exception

                            End Try
                        End If
                    Next
                End If

                FC = Ret.Split("§")
                If tipo = "i" Then
                    CartellaGC = FC(1)
                Else
                    CartellaGC = FC(1) & "/video"
                End If
                Efiles = FC(0).Split("|")
                For J = 0 To Efiles.Length - 1
                    For i = 0 To files.Length - 1
                        If files(i).ToString = Efiles(J) Then Exit For
                    Next
                    If i >= files.Length Then
                        If RepartoImmagini <> "" Then
                            If Efiles(J).ToUpper.Contains(RepartoImmagini.ToUpper) Then
                                Try
                                    My.Computer.Network.DownloadFile(UrlAggiornamenti & "/" & GruppoCliente & "/" & CartellaGC & "/" & Efiles(J), PathSave & "\" & Efiles(J), "", "", True, 10000, True)
                                    If WebMonitor Then
                                        If PercorsoWeb <> "" Then
                                            If tipo = "i" Then
                                                System.IO.File.Copy(PathSave & "\" & Efiles(J), PercorsoWeb & "\immaginicliente\" & Efiles(J))
                                            Else
                                                System.IO.File.Copy(PathSave & "\" & Efiles(J), PercorsoWeb & "\videocliente\" & Efiles(J))
                                            End If
                                        End If
                                    End If
                                Catch ex As Exception

                                End Try
                            End If
                        Else
                            Try
                                My.Computer.Network.DownloadFile(UrlAggiornamenti & "/" & GruppoCliente & "/" & CartellaGC & "/" & Efiles(J), PathSave & "\" & Efiles(J), "", "", True, 10000, True)
                                If WebMonitor Then
                                    If PercorsoWeb <> "" Then
                                        If tipo = "i" Then
                                            System.IO.File.Copy(PathSave & "\" & Efiles(J), PercorsoWeb & "\immaginicliente\" & Efiles(J))
                                        Else
                                            System.IO.File.Copy(PathSave & "\" & Efiles(J), PercorsoWeb & "\videocliente\" & Efiles(J))
                                        End If
                                    End If
                                End If
                            Catch ex As Exception

                            End Try
                        End If
                    End If
                Next
            End If
        End If

    End Sub
#End Region
End Class
