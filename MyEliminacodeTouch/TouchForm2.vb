Imports System.IO

Public Class TouchForm2
    Dim receiver As ImageReceiver
    Dim localIp As String = ""
    Dim localPort As Integer = 11001
    Dim inCaricamento As Boolean = False
    Dim imgCaricate As Boolean = False
    Dim imgChiamata As Integer = -1

    Private Sub TouchForm2_Load(sender As Object, e As EventArgs) Handles MyBase.Load

        If Not System.IO.Directory.Exists(pathImg) Then
            System.IO.Directory.CreateDirectory(pathImg)
        End If

        Logga("pathImg: " & pathImg)

        LblErrore.Left = 0
        LblErrore.Top = 0
        LblErrore.Width = Me.Width
        LblErrore.Height = Me.Height
        LblGiorni.Left = 0
        LblGiorni.Top = 0
        LblGiorni.Width = Me.Width
        LblGiorni.Height = Me.Height
        If sServer <> "" And sPort <> "" Then
            If connect(sServer, sPort) Then
                Threading.Thread.Sleep(5000)
                senddata("TOTEM")
                LblErrore.Text = ""
            Else
                MesTxt = "Collegamento con il server interrotto." & vbCrLf & "Contattare l'assistenza."
                MesVisibile = True
            End If
        End If

        Logga("Inizio avvio")

        Threading.Thread.Sleep(2000)

        Logga("Fine avvio")

        Try
            localIp = GetLocalIPv4Address()

            Logga("Indirizzo ip: " & localIp)

            receiver = New ImageReceiver(localPort, pathImg)

            ' Sottoscrivi gli eventi per ricevere notifiche (es. per aggiornare l'UI)
            AddHandler receiver.ImageReceived, AddressOf OnImageReceivedHandler
            AddHandler receiver.ClientConnected, AddressOf OnClientConnectedHandler
            AddHandler receiver.ServerError, AddressOf OnServerErrorHandler
            AddHandler receiver.ServerStatus, AddressOf OnServerStatusHandler

            receiver.StartListening()

            System.Threading.Thread.Sleep(3000)
            ' Qui potresti aggiornare l'UI per indicare che il server è attivo
            ' txtLog.AppendText("Server avviato..." & vbCrLf)

            'caricaImmagini()

        Catch ex As Exception
            MessageBox.Show("Errore durante l'avvio del server: " & ex.Message, "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error)
        End Try

        Timer3.Enabled = True

    End Sub

    Delegate Sub DisattivaSafe(ByVal messaggio As String)
    Public Sub Disattiva(ByVal messaggio As String)
        If Me.InvokeRequired Then
            Dim d As New DisattivaSafe(AddressOf Disattiva)
            Me.Invoke(d, New Object() {messaggio})
        Else
            Dim msg() As String = messaggio.Split("|") ' if a message is recieved, split it to process it

            Select Case msg(0) 'process it by the first element in the split array
                Case "ABD"
                    'If msg(1) = "A" Then
                    '    Me.LblMsgA.Text = ""
                    '    Me.TurnoAImg.BackColor = Color.Transparent
                    '    Me.LblMsgA.BackColor = Color.Transparent
                    '    StampaA = True
                    'End If
                    'If msg(1) = "B" Then
                    '    Me.LblMsgB.Text = ""
                    '    Me.TurnoBImg.BackColor = Color.Transparent
                    '    Me.LblMsgB.BackColor = Color.Transparent
                    '    StampaB = True
                    'End If
                    'If msg(1) = "C" Then
                    '    Me.LblMsgC.Text = ""
                    '    Me.TurnoCImg.BackColor = Color.Transparent
                    '    Me.LblMsgC.BackColor = Color.Transparent
                    '    StampaC = True
                    'End If
                Case "DBD"
                    'If msg(1) = "A" Then
                    '    Me.TurnoAImg.BackColor = Color.White
                    '    Me.LblMsgA.Text = msg(2)
                    '    Me.LblMsgA.BackColor = Color.White
                    '    StampaA = False
                    'End If
                    'If msg(1) = "B" Then
                    '    Me.TurnoBImg.BackColor = Color.White
                    '    Me.LblMsgB.Text = msg(2)
                    '    Me.LblMsgB.BackColor = Color.White
                    '    StampaB = False
                    'End If
                    'If msg(1) = "C" Then
                    '    Me.TurnoCImg.BackColor = Color.White
                    '    Me.LblMsgC.Text = msg(2)
                    '    Me.LblMsgC.BackColor = Color.White
                    '    StampaC = False
                    'End If
            End Select
        End If
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
            If contatore2 > 0 Then
                LblConta.Text = contatore2
                LblGiorni.Text = vbCrLf & vbCrLf & MesTxt & vbCrLf & vbCrLf & LblConta.Text
                LblGiorni.Width = Me.Width
                LblGiorni.Height = Me.Height
                LblGiorni.Left = 0
                LblGiorni.Top = 0
                LblGiorni.BringToFront()
                LblGiorni.Visible = True
                contatore2 -= 1
            Else
                LblGiorni.Text = MesTxt
                LblGiorni.Visible = False
                LblGiorni.SendToBack()
                LblConta.Visible = False
            End If
        Else
            LblGiorni.Text = MesTxt
            LblGiorni.Visible = False
            LblGiorni.SendToBack()
            LblConta.Visible = False
            LblConta.Text = "11"
        End If
    End Sub

    Private Sub Timer2_Tick(sender As Object, e As EventArgs) Handles Timer2.Tick

        senddata("QES")
        Timer2.Stop()
        Timer2.Enabled = False

    End Sub

    Public Sub PositionLabel(label As Object, imageX As Integer, imageY As Integer, imageH As Integer, imageW As Integer, originalImageWidth As Integer, originalImageHeight As Integer)
        ' Dimensioni attuali del form
        Dim formWidth As Integer = Me.ClientSize.Width
        Dim formHeight As Integer = Me.ClientSize.Height

        '' Calcola il rapporto di ridimensionamento
        'Dim scaleX As Double = formWidth / originalImageWidth
        'Dim scaleY As Double = formHeight / originalImageHeight

        '' Usa il minore dei due rapporti per mantenere le proporzioni
        'Dim scale As Double = Math.Min(scaleX, scaleY)

        Dim scale() As Double = setScale(originalImageWidth, originalImageHeight)

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

    Public Function setScale(originalImageWidth As Integer, originalImageHeight As Integer) As Double()
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

    Public Sub caricaImmagini()

        If imgChiamata = -1 Then
            senddata("TOTEMIMG|" & localIp & ";" & localPort & ";SFONDOTOTEM")
            imgChiamata += 1
        Else
            If Not IsNothing(eTurni) Then
                If eTurni.Count > imgChiamata Then
                    senddata("TOTEMIMG|" & localIp & ";" & localPort & ";TURNOTOTEM;" & eTurni(imgChiamata))
                    imgChiamata += 1
                Else
                    imgChiamata = -1
                End If
            End If
        End If

    End Sub

    Private Sub OnImageReceivedHandler(filePath As String)
        If Me.InvokeRequired Then
            Me.Invoke(New Action(Of String)(AddressOf OnImageReceivedHandler), filePath)
        Else
            ' Esempio: aggiungi a una ListBox o a un log
            ' lstReceivedFiles.Items.Add(filePath)
            ' txtLog.AppendText("Immagine salvata: " & filePath & vbCrLf)
            Logga("Immagine salvata: " & filePath)
        End If
    End Sub

    Private Sub OnClientConnectedHandler(clientInfo As String)
        If Me.InvokeRequired Then
            Me.Invoke(New Action(Of String)(AddressOf OnClientConnectedHandler), clientInfo)
        Else
            ' txtLog.AppendText("Client connesso: " & clientInfo & vbCrLf)
            Logga("Client connesso: " & clientInfo)
        End If
    End Sub

    Private Sub OnServerErrorHandler(errorMessage As String)
        If Me.InvokeRequired Then
            Me.Invoke(New Action(Of String)(AddressOf OnServerErrorHandler), errorMessage)
        Else
            ' MessageBox.Show(errorMessage, "Errore Server", MessageBoxButtons.OK, MessageBoxIcon.Error)
            ' txtLog.AppendText("ERRORE Server: " & errorMessage & vbCrLf)
            Logga("ERRORE Server: " & errorMessage)
        End If
    End Sub

    Private Sub OnServerStatusHandler(statusMessage As String)
        If Me.InvokeRequired Then
            Me.Invoke(New Action(Of String)(AddressOf OnServerStatusHandler), statusMessage)
        Else
            ' txtLog.AppendText("Stato Server: " & statusMessage & vbCrLf)
            Logga("Stato Server: " & statusMessage)
        End If
    End Sub

    Private Sub Timer3_Tick(sender As Object, e As EventArgs) Handles Timer3.Tick
        Dim inCaricamento As Boolean = False

        Dim Ora = Now.Hour

        If Ora > 0 And imgCaricate Then
            imgCaricate = False
        End If

        If Not imgCaricate Then
            If Ora > 2 Then
                imgCaricate = True

                caricaImmagini()

                Timer3.Stop()
            End If
        End If

        If Not IsNothing(eTurni) Then
            For i As Integer = 0 To eTurni.Count - 1
                For Each oggetto In Me.Controls
                    Dim files As String() = Directory.GetFiles(pathImg & "\", "sfondoturno" & eTurni(i) & ".*")
                    If files.Length > 0 Then

                        If oggetto.tag = eTurni(i) And oggetto.GetType.ToString.ToUpper = "SYSTEM.WINDOWS.FORMS.BUTTON" Then
                            oggetto.BackgroundImage = Image.FromFile(files(0))
                            oggetto.BackgroundImageLayout = ImageLayout.Stretch
                            For Each oggetto2 In oggetto.Controls
                                If oggetto2.tag = eTurni(i) And oggetto2.GetType.ToString.ToUpper = "SYSTEM.WINDOWS.FORMS.LABEL" Then
                                    oggetto2.backcolor = Color.Transparent
                                End If
                            Next
                        End If
                    End If
                Next
            Next
        End If

    End Sub
End Class