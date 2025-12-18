Imports System.IO
Public Class Monitor
    Private WithEvents kbHook As New KeyboardHook

    Dim Intervallo As Integer = 0
    Dim Torna As Boolean = False
    Dim Posini1 As Integer = 30
    Dim posIni2 As Integer = 814
    Dim turnoSposta As Integer = 0

    Private Sub Monitor_KeyUp(ByVal sender As Object, ByVal e As System.Windows.Forms.KeyEventArgs) Handles Me.KeyUp
        If e.KeyValue = 27 Then Me.Close()
    End Sub

    Private Sub Monitor_Load(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles MyBase.Load
        Dim StrQ As String
        Dim lDset As New DataSet
        Dim lDset2 As New DataSet
        Dim i As Integer
        Dim lapostazione As String = ""


        StrQ = "select c.*, p.postazione from contatori c left join postazioni p on c.id_postazione=p.id_postazione where tipo='NUMERO'"
        If DbConnection.Estrai(StrQ, lDset, "numero", True) Then
            If lDset.Tables("numero").Rows.Count > 0 Then
                For i = 0 To lDset.Tables("numero").Rows.Count - 1
                    For Each controllo In Me.Controls
                        If controllo.name.ToString.Contains("Panel") Then
                            For Each controllo2 In controllo.Controls
                                If controllo2.name.ToString.Contains("Sportello") Then
                                    If controllo2.tag = lDset.Tables("numero").Rows(i).Item("id_turno").ToString Then
                                        controllo2.text = lDset.Tables("numero").Rows(i).Item("numero").ToString.PadLeft(3, "0")
                                    End If
                                End If
                                If controllo2.name.ToString.Contains("NSpo") Then
                                    If controllo2.tag = lDset.Tables("numero").Rows(i).Item("id_turno").ToString Then
                                        If Not IsDBNull(lDset.Tables("numero").Rows(i).Item("postazione")) Then
                                            controllo2.text = lDset.Tables("numero").Rows(i).Item("postazione").ToString
                                        End If
                                    End If
                                End If
                            Next
                        End If
                    Next

                    'StrQ = "select * from postazioni where id_postazione=" & lDset.Tables("numero").Rows(i).Item("id_postazione")
                    'If DbConnection.Estrai(StrQ, lDset2, "postazione", True) Then
                    '    If lDset2.Tables("postazione").Rows.Count > 0 Then
                    '        lapostazione = lDset2.Tables("postazione").Rows(0).Item("postazione")
                    '    Else
                    '        lapostazione = ""
                    '    End If
                    'End If
                    'Select Case lDset.Tables("numero").Rows(i).Item("id_turno")
                    '    Case 1
                    '        TurnoCLbl.Text = lDset.Tables("numero").Rows(i).Item("numero").ToString.PadLeft(3, "0")
                    '        SportelloCLbl.Text = lapostazione
                    '    Case 2
                    '        TurnoCLbl.Text = lDset.Tables("numero").Rows(i).Item("numero").ToString.PadLeft(3, "0")
                    '        SportelloCLbl.Text = lapostazione
                    '    Case 3
                    '        TurnoCLbl.Text = lDset.Tables("numero").Rows(i).Item("numero").ToString.PadLeft(3, "0")
                    '        SportelloCLbl.Text = lapostazione
                    'End Select
                Next
            End If
        End If
        'StrQ = "select * from turni"
        'If DbConnection.Estrai(StrQ, lDset, "turni", True) Then
        '    For i = 0 To lDset.Tables("turni").Rows.Count - 1
        '        If lDset.Tables("turni").Rows(i).Item("stato") = "1" Then
        '            If lDset.Tables("turni").Rows(i).Item("turno") = "A" Then
        '                SchermoA.Visible = False
        '            ElseIf lDset.Tables("turni").Rows(i).Item("turno") = "B" Then
        '                SchermoB.Visible = False
        '            ElseIf lDset.Tables("turni").Rows(i).Item("turno") = "C" Then
        '                SchermoC.Visible = False
        '            End If
        '        Else
        '            If lDset.Tables("turni").Rows(i).Item("turno") = "A" Then
        '                SchermoA.Visible = True
        '            ElseIf lDset.Tables("turni").Rows(i).Item("turno") = "B" Then
        '                SchermoB.Visible = True
        '            ElseIf lDset.Tables("turni").Rows(i).Item("turno") = "C" Then
        '                SchermoC.Visible = True
        '            End If
        '        End If
        '    Next
        'End If
        Timer1.Start()
        'WebBrowser2.Navigate(WebBrowser2.Url)
        Timer2.Start()

    End Sub

    Delegate Sub CambiaMonitorSafe(ByVal turno As Integer, ByVal prossimo As Integer, ByVal Postazione As String)
    Public Sub CambiaMonitor(ByVal turno As Integer, ByVal prossimo As Integer, ByVal Postazione As String)
        If Me.InvokeRequired Then
            Dim d As New CambiaMonitorSafe(AddressOf CambiaMonitor)
            Me.Invoke(d, New Object() {turno, prossimo, Postazione})
        Else
            For Each controllo In Me.Controls
                If controllo.name.ToString.Contains("Panel") Then
                    For Each controllo2 In controllo.controls
                        Try
                            If controllo2.tag.ToString = turno.ToString Then
                                If controllo2.name.ToString.Contains("NSpo") Then
                                    controllo2.text = Postazione
                                Else
                                    controllo2.text = prossimo.ToString.PadLeft(3, "0")
                                End If
                            End If
                        Catch ex As Exception

                        End Try
                    Next
                End If
            Next
            'Select Case turno
            '    Case 1
            '        TurnoCLbl.Text = prossimo.ToString.PadLeft(3, "0")
            '        SportelloCLbl.Text = Postazione
            '    Case 2
            '        TurnoCLbl.Text = prossimo.ToString.PadLeft(3, "0")
            '        SportelloCLbl.Text = Postazione
            '    Case 3
            '        TurnoCLbl.Text = prossimo.ToString.PadLeft(3, "0")
            '        SportelloCLbl.Text = Postazione
            'End Select
        End If
    End Sub

    Private Sub Timer1_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Timer1.Tick
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
                    Timer1.Stop()
                    numero = lDsetLoc.Tables("coda").Rows(0).Item("numero")
                    turno = lDsetLoc.Tables("coda").Rows(0).Item("turno")
                    sportello = lDsetLoc.Tables("coda").Rows(0).Item("sportello")
                    idturno = lDsetLoc.Tables("coda").Rows(0).Item("id_turno")
                    turnoSposta = idturno
                    idpostazione = lDsetLoc.Tables("coda").Rows(0).Item("id_postazione")
                    id = lDsetLoc.Tables("coda").Rows(0).Item("id")

                    TestoTurno = sportello & " -- " & turno & numero.PadLeft(3, "0")

                    ilmonitor.CambiaMonitor(idturno, Int(Val(numero)), idpostazione)

                    UC10.Text = UC9.Text
                    UC9.Text = UC8.Text
                    UC8.Text = UC7.Text
                    UC7.Text = UC6.Text
                    UC6.Text = UC5.Text
                    UC5.Text = UC4.Text
                    UC4.Text = UC3.Text
                    UC3.Text = UC2.Text
                    UC2.Text = UC1.Text
                    UC1.Text = TestoTurno

                    Scorrimento.Enabled = True
                    Scorrimento.Start()

                    Dim path As String = Application.UserAppDataPath & "\play.wpl"
                    Dim sw As StreamWriter
                    If File.Exists(path) Then My.Computer.FileSystem.DeleteFile(path)
                    sw = File.CreateText(path)

                    sw.WriteLine("<?wpl version='1.0'?>")
                    sw.WriteLine("<smil>")
                    sw.WriteLine("<head>")
                    sw.WriteLine("<meta name='Generator' content='Microsoft Windows Media Player -- 12.0.9600.17031'/>")
                    sw.WriteLine("<meta name='ItemCount' content='4'/>")
                    sw.WriteLine("<title>PROVA</title>")
                    sw.WriteLine("</head>")
                    sw.WriteLine("<body>")
                    sw.WriteLine("<seq>")
                    Percorso = Application.StartupPath & "\mp3\Turno " & turno & ".mp3"
                    sw.WriteLine("<media src='" & Percorso & "'/>")
                    Percorso = Application.StartupPath & "\mp3\" & numero & ".mp3"
                    sw.WriteLine("<media src='" & Percorso & "'/>")
                    'Percorso = Application.StartupPath & "\mp3\RECARSI ALLO SPORTELLO.mp3"
                    'sw.WriteLine("<media src='" & Percorso & "'/>")
                    Percorso = Application.StartupPath & "\mp3\Recarsi allo sportello " & sportello & ".mp3"
                    sw.WriteLine("<media src='" & Percorso & "'/>")
                    sw.WriteLine("</seq>")
                    sw.WriteLine("</body>")
                    sw.WriteLine("</smil>")
                    sw.Flush()
                    sw.Close()
                    Esegui()
                    StrQ = "delete from coda where id=" & id
                    DbConnection.EseguiSQL(StrQ)
                    'wait(4000)
                Else
                    Timer1.Start()
                End If
            Else
                Timer1.Start()
            End If

        Catch ex As Exception
            Timer1.Start()
        End Try
        'Timer1.Start()

    End Sub

    Private Sub Esegui()
        Dim path As String = Application.UserAppDataPath & "\play.wpl"

        wmp.settings.autoStart = True
        wmp.URL = path
        wmp.Ctlcontrols.play()

    End Sub

    Private Sub wmp_Enter(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles wmp.Enter

    End Sub

    Private Sub wmp_PlayStateChange(ByVal sender As Object, ByVal e As AxWMPLib._WMPOCXEvents_PlayStateChangeEvent) Handles wmp.PlayStateChange

        If e.newState = 10 Then
            Timer1.Start()
        End If

    End Sub

    Private Sub wmp_StatusChange(ByVal sender As Object, ByVal e As System.EventArgs) Handles wmp.StatusChange

    End Sub

    Private Sub Timer2_Tick(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Timer2.Tick
        Dim StrQ As String
        Dim lDset As New DataSet
        Dim GG As String
        Dim HH As String
        Dim GG2 As String
        Dim HH2 As String

        GG = Now.Year & Now.Month.ToString.PadLeft(2, "0") & Now.Day.ToString.PadLeft(2, "0")
        HH = Now.Hour.ToString.PadLeft(2, "0") & Now.Minute.ToString.PadLeft(2, "0")
        StrQ = "select * from azzeramenti"
        If DbConnection.Estrai(StrQ, lDset, "azzero", True) Then
            If lDset.Tables("azzero").Rows.Count = 0 Then
                StrQ = "insert into azzeramenti (ultimo, ora) values ('" & GG & "', '" & HH & "')"
                DbConnection.EseguiSQL(StrQ)
            Else
                GG2 = lDset.Tables("azzero").Rows(0).Item("ultimo")
                HH2 = lDset.Tables("azzero").Rows(0).Item("ora")
                If GG2 < GG And HH > "2000" Then
                    StrQ = "delete from coda"
                    DbConnection.EseguiSQL(StrQ)
                    StrQ = "update contatori set id_postazione=0, numero=0, data='', ora='', consecutivi=0"
                    DbConnection.EseguiSQL(StrQ)
                    StrQ = "update azzeramenti set ultimo='" & GG & "', ora='" & HH & "'"
                    DbConnection.EseguiSQL(StrQ)
                    NSpoALbl.Text = "0"
                    SportelloALbl.Text = "000"
                    NSpoBLbl.Text = "0"
                    SportelloBLbl.Text = "000"
                    NSpoCLbl.Text = "0"
                    SportelloCLbl.Text = "000"
                    NSpoDLbl.Text = "0"
                    SportelloDLbl.Text = "000"
                    NSpoELbl.Text = "0"
                    SportelloELbl.Text = "000"
                    NSpoFLbl.Text = "0"
                    SportelloFLbl.Text = "000"
                    NSpoGLbl.Text = "0"
                    SportelloGLbl.Text = "000"
                    NSpoHLbl.Text = "0"
                    SportelloHLbl.Text = "000"
                    NSpoILbl.Text = "0"
                    SportelloILbl.Text = "000"
                    NSpoLLbl.Text = "0"
                    SportelloLLbl.Text = "000"
                    UC1.Text = "--"
                    UC2.Text = "--"
                    UC3.Text = "--"
                    UC4.Text = "--"
                    UC5.Text = "--"
                    UC6.Text = "--"
                    UC7.Text = "--"
                    UC8.Text = "--"
                    UC9.Text = "--"
                    UC10.Text = "--"
                End If
            End If
        End If
        Try
            Intervallo += 1
            If Intervallo = 2 Then
                Intervallo = 0
                'WebBrowser1.Refresh()
                'WebBrowser2.Refresh()
                'meteo.Refresh()
            End If
        Catch ex As Exception

        End Try
    End Sub

    Private Sub Scorrimento_Tick(sender As Object, e As EventArgs) Handles Scorrimento.Tick

        For Each controllo In Me.Controls
            Dim a As String
            a = controllo.name
            If controllo.tag = turnoSposta.ToString Then
                Sposta(controllo)
            End If
        Next

    End Sub

    Private Sub Sposta(panel As Panel)

        If Not Torna Then
            If CInt(panel.Tag) <= 5 Then
                If panel.Left > -474 Then
                    panel.Left = panel.Left - 100
                Else
                    Torna = True
                End If
            Else
                If panel.Left < Me.Width Then
                    panel.Left = panel.Left + 100
                Else
                    Torna = True
                End If
            End If
        Else
            If CInt(panel.Tag) <= 5 Then
                If panel.Left < 9 Then
                    panel.Left = panel.Left + 100
                Else
                    Torna = False
                    Scorrimento.Stop()
                End If
            Else
                If panel.Left > 772 Then
                    panel.Left = panel.Left - 100
                Else
                    Torna = False
                    Scorrimento.Stop()
                End If
            End If

        End If

    End Sub
    Private Sub kbHook_KeyUp(ByVal Key As System.Windows.Forms.Keys) Handles kbHook.KeyUp
        If Key.ToString = "Escape" Then
            Application.Exit()
        End If
    End Sub

End Class