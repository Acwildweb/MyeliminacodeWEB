Public Class TouchFrm
    Private WithEvents kbHook As New KeyboardHook

    Dim StampaA As Boolean = True
    Dim StampaB As Boolean = True
    Dim StampaC As Boolean = True
    Dim StatoBtn As Integer = 0
    Dim SenderName As String = ""
    Dim SenderTag As String = ""
    'Private Sub TurnoAImg_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles TurnoAImg.Click, LblMsgA.Click

    '    If StampaA Then senddata("NEW|C")

    'End Sub

    'Private Sub TurnoBImg_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles LblMsgB.Click
    '    If StampaB Then senddata("NEW|C")
    'End Sub

    'Private Sub TurnoCImg_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles LblMsgC.Click
    '    If StampaC Then senddata("NEW|C")
    'End Sub

    Delegate Sub DisattivaSafe(ByVal messaggio As String)
    Public Sub Disattiva(ByVal messaggio As String)
        If Me.InvokeRequired Then
            Dim d As New DisattivaSafe(AddressOf Disattiva)
            Me.Invoke(d, New Object() {messaggio})
        Else
            Dim msg() As String = messaggio.Split("|") ' if a message is recieved, split it to process it

            Select Case msg(0) 'process it by the first element in the split array
                Case "ABD"
                    If msg(1) = "A" Then
                        Me.LblMsgA.Text = ""
                        Me.TurnoAImg.BackColor = Color.Transparent
                        Me.LblMsgA.BackColor = Color.Transparent
                        StampaA = True
                    End If
                    If msg(1) = "B" Then
                        Me.LblMsgB.Text = ""
                        Me.TurnoBImg.BackColor = Color.Transparent
                        Me.LblMsgB.BackColor = Color.Transparent
                        StampaB = True
                    End If
                    If msg(1) = "C" Then
                        Me.LblMsgC.Text = ""
                        Me.TurnoCImg.BackColor = Color.Transparent
                        Me.LblMsgC.BackColor = Color.Transparent
                        StampaC = True
                    End If
                Case "DBD"
                    If msg(1) = "A" Then
                        Me.TurnoAImg.BackColor = Color.White
                        Me.LblMsgA.Text = msg(2)
                        Me.LblMsgA.BackColor = Color.White
                        StampaA = False
                    End If
                    If msg(1) = "B" Then
                        Me.TurnoBImg.BackColor = Color.White
                        Me.LblMsgB.Text = msg(2)
                        Me.LblMsgB.BackColor = Color.White
                        StampaB = False
                    End If
                    If msg(1) = "C" Then
                        Me.TurnoCImg.BackColor = Color.White
                        Me.LblMsgC.Text = msg(2)
                        Me.LblMsgC.BackColor = Color.White
                        StampaC = False
                    End If
            End Select
        End If
    End Sub

    Private Sub TouchFrm_Load(ByVal sender As Object, ByVal e As System.EventArgs) Handles Me.Load
        If sServer <> "" And sPort <> "" Then
            If connect(sServer, sPort) Then
                senddata("QES")
                LblErrore.Text = ""
            Else
                MesTxt = "Collegamento con il server interrotto." & vbCrLf & "Contattare l'assistenza."
                MesVisibile = True
            End If
        End If
    End Sub

    Private Sub TurnoAImg_MouseDown(sender As Object, e As MouseEventArgs) Handles TurnoAImg.MouseDown, TurnoBImg.MouseDown, TurnoCImg.MouseDown, TurnoDImg.MouseDown, TurnoEImg.MouseDown, TurnoFImg.MouseDown, TurnoGImg.MouseDown, TurnoHImg.MouseDown, TurnoIImg.MouseDown, TurnoLImg.MouseDown,
            Label1A.MouseDown, Label1B.MouseDown, Label1C.MouseDown, Label1D.MouseDown, Label1E.MouseDown, Label1F.MouseDown, Label1G.MouseDown, Label1H.MouseDown, Label1I.MouseDown, Label1L.MouseDown,
            Label2A.MouseDown, Label2B.MouseDown, Label2C.MouseDown, Label2D.MouseDown, Label2E.MouseDown, Label2F.MouseDown, Label2G.MouseDown, Label2H.MouseDown, Label2I.MouseDown, Label2L.MouseDown

        'For Each control In Me.Controls
        '    If control.name = sender.name & "2" Or control.name = sender.tag & "2" Then
        '        For Each control2 In Me.Controls
        '            If control2.name = sender.tag Then
        '                control2.BackgroundImage = control.InitialImage
        '            End If
        '        Next
        '    End If
        'Next

    End Sub

    Private Sub TurnoAImg_MouseUp(sender As Object, e As MouseEventArgs) Handles TurnoAImg.MouseUp, TurnoBImg.MouseUp, TurnoCImg.MouseUp, TurnoDImg.MouseUp, TurnoEImg.MouseUp, TurnoFImg.MouseUp, TurnoGImg.MouseUp, TurnoHImg.MouseUp, TurnoIImg.MouseUp, TurnoLImg.MouseUp,
            Label1A.MouseUp, Label1B.MouseUp, Label1C.MouseUp, Label1D.MouseUp, Label1E.MouseUp, Label1F.MouseUp, Label1G.MouseUp, Label1H.MouseUp, Label1I.MouseUp, Label1L.MouseUp,
            Label2A.MouseUp, Label2B.MouseUp, Label2C.MouseUp, Label2D.MouseUp, Label2E.MouseUp, Label2F.MouseUp, Label2G.MouseUp, Label2H.MouseUp, Label2I.MouseUp, Label2L.MouseUp

        'For Each control In Me.Controls
        '    If control.name = sender.tag & "2" Or control.name = sender.tag & "2" Then
        '        For Each control2 In Me.Controls
        '            If control2.name = sender.tag Then
        '                control2.BackgroundImage = control.Image
        '            End If
        '        Next
        '    End If
        'Next
        'Dim Lettera As String = ""
        'Lettera = sender.tag.ToString.Replace("Turno", "").Replace("Img", "")

        'If MesVisibile Then Exit Sub

        'senddata("NEW|" & Lettera & "")

    End Sub

    Private Sub Timer1_Tick(sender As Object, e As EventArgs) Handles Timer1.Tick
        If MesVisibile Then
            Try
                If connect(sServer, sPort) Then
                    MesVisibile = False
                End If
            Catch ex As Exception

            End Try
            LblErrore.Text = MesTxt
            LblErrore.Visible = True
            LblErrore.Width = Me.Width
            LblErrore.Height = Me.Height
            LblErrore.Top = 0
            LblErrore.Left = 0
            LblErrore.BringToFront()
        Else
            LblErrore.Text = MesTxt
            LblErrore.Visible = False
        End If

        If MesVisibile2 Then
            LblConta.Text = CInt(LblConta.Text) - 1
            LblGiorni.Text = vbCrLf & vbCrLf & vbCrLf & vbCrLf & vbCrLf & vbCrLf & MesTxt & vbCrLf & vbCrLf & LblConta.Text
            LblGiorni.Width = Me.Width
            LblGiorni.Height = Me.Height
            LblGiorni.Left = 0
            LblGiorni.Top = 0
            LblGiorni.BringToFront()
            LblGiorni.Visible = True
        Else
            LblGiorni.Text = MesTxt
            LblGiorni.Visible = False
            LblGiorni.SendToBack()
            LblConta.Visible = False
            LblConta.Text = "11"
        End If
    End Sub

    Private Sub Timer2_Tick(sender As Object, e As EventArgs) Handles Timer2.Tick

        If StatoBtn = 0 Then Exit Sub

        If StatoBtn = 1 Then
            For Each control In Me.Controls
                If control.name = SenderName & "2" Or control.name = SenderTag & "2" Then
                    For Each control2 In Me.Controls
                        If control2.name = SenderTag Then
                            control2.BackgroundImage = control.InitialImage
                            control2.refresh
                        End If
                    Next
                End If
            Next
            Threading.Thread.Sleep(500)

            Dim Lettera As String = ""
            Lettera = SenderTag.ToString.Replace("Turno", "").Replace("Img", "")

            If MesVisibile Then Exit Sub

            senddata("NEW|" & Lettera & "")
            StatoBtn = 2
        End If
        If StatoBtn = 2 Then
            For Each control In Me.Controls
                If control.name = SenderName & "2" Or control.name = SenderTag & "2" Then
                    For Each control2 In Me.Controls
                        If control2.name = SenderTag Then
                            control2.BackgroundImage = control.Image
                        End If
                    Next
                End If
            Next
            StatoBtn = 0
        End If

    End Sub

    Private Sub TurnoLImg_Click(sender As Object, e As EventArgs) Handles TurnoAImg.Click, TurnoBImg.Click, TurnoCImg.Click, TurnoDImg.Click, TurnoEImg.Click, TurnoFImg.Click, TurnoGImg.Click, TurnoHImg.Click, TurnoIImg.Click, TurnoLImg.Click,
            Label1A.Click, Label1B.Click, Label1C.Click, Label1D.Click, Label1E.Click, Label1F.Click, Label1G.Click, Label1H.Click, Label1I.Click, Label1L.Click,
            Label2A.Click, Label2B.Click, Label2C.Click, Label2D.Click, Label2E.Click, Label2F.Click, Label2G.Click, Label2H.Click, Label2I.Click, Label2L.Click

        SenderName = sender.name
        SenderTag = sender.tag
        StatoBtn = 1

        'Dim Lettera As String = ""
        'Lettera = SenderTag.ToString.Replace("Turno", "").Replace("Img", "")

        'If MesVisibile Then Exit Sub

        'senddata("NEW|" & Lettera & "")
        'Threading.Thread.Sleep(600)
        'StatoBtn = 2
        'Threading.Thread.Sleep(300)
        'StatoBtn = 0

    End Sub

    Private Sub kbHook_KeyUp(ByVal Key As System.Windows.Forms.Keys) Handles kbHook.KeyUp
        If Key.ToString = "Escape" Then
            Application.Exit()
        End If
    End Sub


End Class

