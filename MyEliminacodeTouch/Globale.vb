Imports System.Net, System.Text
Imports System.Net.Sockets
Imports System.IO
Imports System.Drawing.Printing
Imports System.Windows
Imports Microsoft.VisualBasic.Logging

Module Globale
    Public t As New TcpClient
    Public IpServer As String
    Public PortServer As String
    Public sPort As String
    Public sServer As String
    Dim testodastampare As String
    Public TForm As New TouchForm2
    Public CIDb As New CDb
    Public DbConnection As New DbParser
    Public DatiDb As CDb.Tdb
    Dim GiaConnesso As Boolean = False
    Public MesVisibile As Boolean = False
    Public MesVisibile2 As Boolean = False
    Public contatore2 As Integer = 10
    Public MesTxt As String = ""
    Private Const MaxColumns As Integer = 5
    Private Const MaxRows As Integer = 4
    Private Const ButtonSpacingPercentage As Double = 0.05 ' 5% di spazio tra i bottoni
    Private receiver As ImageReceiver
    Public eTurni() As String
    Public pathImg As String = Application.UserAppDataPath

    Public imgSfondoTotem As String = ""

    Public Sub Main()
        Dim Regi As New Registro
        sPort = Regi.Leggi("TOUCH", "SERVERPORT")
        sServer = Regi.Leggi("TOUCH", "SERVERIP")
        pathImg = Regi.Leggi("TOUCH", "PATHIMG")
        DatiDb = CIDb.DatiDb

        If DatiDb.TipoDb = "" Then
            Dim FImpostazioniDb As New ImpostazioniDb
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
                    Dim FImpostazioniDb As New ImpostazioniDb
                    FImpostazioniDb.ShowDialog()
                End If
                End
            End If
        End If

        If sPort <> "" And sServer <> "" Then
            GiaConnesso = True
        Else
            Client.ShowDialog()
        End If

        If Not pathImg.EndsWith("\") Then
            pathImg &= "\"
        End If
        pathImg &= "immaginitotem\"

        TForm.ShowDialog()


    End Sub

    Private Sub Printext(ByVal sender As Object, ByVal ev As PrintPageEventArgs)
        Dim Immagine As Image
        Dim msg() As String = testodastampare.Split("|")

        Immagine = Image.FromFile(Application.StartupPath & "\logo.png")

        Dim ImmagineRidimensionata As Image = RidimensionaImmagine(Immagine, 300, 300)

        'ev.Graphics.DrawString("TURNO", New Font("Courier", 34, FontStyle.Bold), Brushes.Black, 60, 30)
        ev.Graphics.DrawString("" & msg(1), New Font("Courier", 54, FontStyle.Bold), Brushes.Black, 70, 110)
        ev.Graphics.DrawString("" & msg(2).PadLeft(3, "0"), New Font("Courier", 62, FontStyle.Bold), Brushes.Black, 60, 200)
        ev.Graphics.DrawString("MYE " & Now, New Font("Courier", 10, FontStyle.Regular), Brushes.Black, 60, 380)
        ev.Graphics.DrawString(" ", New Font("Courier", 10, FontStyle.Regular), Brushes.Black, 60, 410)
        ev.Graphics.DrawImage(ImmagineRidimensionata, 1, 15)
        ev.HasMorePages = False

        Immagine.Dispose()
        ImmagineRidimensionata.Dispose()
    End Sub
    Public Function RidimensionaImmagine(ByVal immagineOriginale As Image, ByVal larghezzaMassima As Integer, ByVal altezzaMassima As Integer) As Image
        ' Calcola il rapporto di scala
        Dim rapportoLarghezza As Double = larghezzaMassima / immagineOriginale.Width
        Dim rapportoAltezza As Double = altezzaMassima / immagineOriginale.Height
        Dim rapportoScala As Double = Math.Min(rapportoLarghezza, rapportoAltezza)

        ' Calcola le nuove dimensioni
        Dim nuovaLarghezza As Integer = CInt(immagineOriginale.Width * rapportoScala)
        Dim nuovaAltezza As Integer = CInt(immagineOriginale.Height * rapportoScala)

        ' Crea un nuovo bitmap con le nuove dimensioni
        Dim bitmapRidimensionato As New Bitmap(nuovaLarghezza, nuovaAltezza)

        ' Disegna l'immagine ridimensionata
        Using grafica As Graphics = Graphics.FromImage(bitmapRidimensionato)
            grafica.InterpolationMode = Drawing2D.InterpolationMode.HighQualityBicubic
            grafica.CompositingQuality = Drawing2D.CompositingQuality.HighQuality
            grafica.SmoothingMode = Drawing2D.SmoothingMode.HighQuality
            grafica.DrawImage(immagineOriginale, 0, 0, nuovaLarghezza, nuovaAltezza)
        End Using

        Return bitmapRidimensionato
    End Function
    Private Sub stampascontrino(ByVal message As String)

        testodastampare = message
        Dim stampa As New PrintDocument
        AddHandler stampa.PrintPage, AddressOf Printext
        stampa.Print()

    End Sub

    Public Function connect(ByVal ip As String, ByVal port As Integer) As Boolean

        Try
            If IsNothing(t) Then
                t = New TcpClient
            End If
            t.Connect(ip, port) 'tries to connect
            If t.Connected Then 'if connected, start the reading procedure
                t.GetStream.BeginRead(New Byte() {0}, 0, 0, AddressOf doread, Nothing)
                login() 'send our details to the server
                Return True
            Else
                t.Close()
                t = Nothing
                t = New TcpClient
                Return False
            End If
        Catch ex As Exception
            'MsgBox(ex.Message)
            t.Close()
            t = Nothing
            t = New TcpClient
            Return False
            'System.Threading.Thread.Sleep(10000) 'if an error occurs sleep for 10 seconds
            'connect(ip, port) 'try to reconnect
        End Try
    End Function

    Sub login()

        senddata("LOGIN|") 'log in to the chatserver
    End Sub

    Sub senddata(ByVal message As String)
        If Not t.Connected Then
            Exit Sub
        End If
        Dim sw As New StreamWriter(t.GetStream) 'declare a new streamwriter
        sw.WriteLine(message) 'write the message
        sw.Flush()

    End Sub

    Sub messagerecieved(ByVal message As String)
        Dim msg() As String = message.Split("|") ' if a message is recieved, split it to process it

        Select Case msg(0) 'process it by the first element in the split array

            Case "CHAT"
                'TxtTesto.Text &= "Server: " & " " & msg(1) & vbNewLine
            Case "PRINT"
                stampascontrino(message)
            Case "ABD", "DBD"
                TForm.Disattiva(message)
            Case "QES"
                Threading.Thread.Sleep(5000)
                ProcessMessage(message)
                'Dim Testo() As String
                'Dim Messaggio As String
                'For i = 1 To msg.Length - 1
                '    Testo = msg(i).Split("§")
                '    If Testo(1) = "1" Then
                '        Messaggio = "ABD|" & Testo(0)
                '    Else
                '        Messaggio = "DBD|" & Testo(0) & "|" & Testo(2)
                '    End If
                '    TForm.Disattiva(Messaggio)
                'Next
            Case "CHIUS"
                MesTxt = msg(1).ToString.Replace("§", vbCrLf)
                contatore2 = 10
                MesVisibile2 = True
                'Threading.Thread.Sleep(10000)
                'MesTxt = ""
                'MesVisibile2 = False
            Case "TOTEM"
                If msg(1) = "" Then
                    Threading.Thread.Sleep(5000)
                    senddata("QES")
                    'TForm.Timer2.Enabled = True
                    'TForm.Timer2.Start()
                Else
                    ProcessMessage2(message)
                    'TForm.caricaImmagini()
                End If

        End Select

    End Sub

    Sub doread(ByVal ar As IAsyncResult)
        Try
            If IsNothing(t) Then
                If Not connect(sServer, sPort) Then
                    'Threading.Thread.Sleep(30000)
                    Exit Sub
                End If
            End If
                Dim sr As New StreamReader(t.GetStream) 'declare a new streamreader to read fromt eh network stream
            Dim msg As String = sr.ReadLine() 'the msg is what is bing read
            If IsNothing(msg) Then
                Exit Sub
            End If
            If msg & "" = "" Then
                Exit Sub
            End If
            messagerecieved(msg) 'start processing the message
            t.GetStream.BeginRead(New Byte() {0}, 0, 0, AddressOf doread, Nothing) 'continue to read

        Catch ex As Exception
            'MsgBox(ex.Message)
            'System.Threading.Thread.Sleep(10000) 'if an error occurs, wait for 10 seconds
            'connect(IpServer, PortServer) 'try to reconnect
            MesTxt = "Collegamento con il server interrotto." & vbCrLf & "Contattare l'assistenza."
            MesVisibile = True

            If IsNothing(t) Then
                t = New TcpClient
            End If
            If Not t.Connected Then
                If Not connect(sServer, sPort) Then
                    'Threading.Thread.Sleep(30000)
                    Exit Sub
                End If
            End If
            MesTxt = ""
            MesVisibile = False
        End Try
    End Sub

    Public Sub ProcessMessage(message As String)
        ' Pulisce i controlli esistenti
        'TForm.Controls.Clear()
        Dim gFont As System.Drawing.FontStyle = FontStyle.Bold
        Dim objFont As System.Drawing.Font
        Dim colore As Color = ColorTranslator.FromHtml("#000000")

        objFont = New System.Drawing.Font("Impact", 16, gFont)

        If TForm.InvokeRequired Then
            TForm.Invoke(New Action(Of String)(AddressOf ProcessMessage), New Object() {message})
        Else
            ' Verifica che il messaggio inizi con QES|
            If Not message.StartsWith("QES|") Then
                MessageBox.Show("Formato messaggio non valido")
                Return
            End If

            ' Divide il messaggio per ottenere i blocchi
            Dim parts() As String = message.Split("|"c)

            ' Salta la prima parte (QES)
            Dim blocks As New List(Of String)(parts.Skip(1))

            ' Calcola il numero di bottoni necessari
            Dim numberOfButtons As Integer = blocks.Count

            ' Calcola il numero effettivo di righe e colonne necessarie
            'Dim actualColumns As Integer = Math.Min(MaxColumns, numberOfButtons)
            'Dim actualRows As Integer = Math.Min(MaxRows, Math.Ceiling(numberOfButtons / MaxColumns))

            '' Calcola le dimensioni dei bottoni in base allo spazio disponibile
            'Dim availableWidth As Integer = TForm.ClientSize.Width
            'Dim availableHeight As Integer = TForm.ClientSize.Height

            'Dim horizontalSpacing As Integer = CInt(availableWidth * ButtonSpacingPercentage / (actualColumns + 1))
            'Dim verticalSpacing As Integer = CInt(availableHeight * ButtonSpacingPercentage / (actualRows + 1))

            'Dim maxBtnHeight = 350

            'Dim buttonWidth As Integer = CInt((availableWidth - (horizontalSpacing * (actualColumns + 1))) / actualColumns)
            'Dim buttonHeight As Integer = CInt((availableHeight - (verticalSpacing * (actualRows + 1))) / actualRows)
            'If buttonHeight > maxBtnHeight Then
            '    buttonHeight = maxBtnHeight
            'End If

            Dim fHeight As Integer = TForm.Height
            Dim bHeigth As Integer = CInt(fHeight / (numberOfButtons + 1))
            Dim hInizio As Integer = 30

            ' Colori per i bottoni
            Dim colors() As Color = {
                Color.Red, Color.Blue, Color.Green, Color.Orange,
                Color.Purple, Color.Aqua, Color.Brown, Color.Coral,
                Color.Crimson, Color.DarkBlue, Color.DarkGreen, Color.DarkOrange,
                Color.Fuchsia, Color.Gold, Color.IndianRed, Color.LightBlue,
                Color.LimeGreen, Color.Magenta, Color.Maroon, Color.MediumPurple
            }

            Dim currentRow As Integer = 0
            Dim currentCol As Integer = 0
            Dim colorIndex As Integer = 0

            Dim nButton As Integer = 0

            ' Elabora ciascun blocco
            For Each block As String In blocks
                ' Verifica se il blocco ha il formato corretto
                Dim blockParts() As String = block.Split("§"c)
                If blockParts.Length > 0 Then
                    Dim blockturno() As String = blockParts(0).Split("-"c)
                    Dim turno As String = blockturno(0)
                    Dim stato As String = blockturno(1)
                    Dim isActive As Integer = 0
                    Dim testoBtn As String = ""

                    ' Tenta di analizzare i valori
                    If stato <> "" Then

                        If stato = "A" Then
                            isActive = 1
                        End If

                        For i = 1 To blockParts.Count - 1
                            testoBtn = testoBtn & blockParts(i)
                            If i < blockParts.Count - 1 Then
                                testoBtn = testoBtn & " - "
                            End If
                        Next

                        ' Crea un bottone per ogni blocco
                        Dim btn As New Button()
                        btn.Text = ""
                        btn.TextAlign = ContentAlignment.MiddleRight
                        btn.Font = objFont
                        btn.Width = TForm.Width
                        btn.Height = bHeigth
                        btn.Left = 0
                        btn.Top = hInizio + (bHeigth * nButton)
                        btn.Tag = turno  ' Memorizza il valore del turno per l'evento click

                        Dim lbl As New Label
                        lbl.Text = testoBtn
                        lbl.TextAlign = ContentAlignment.MiddleLeft
                        lbl.BackColor = Color.Transparent
                        lbl.Left = CInt(TForm.Width / 2) - 50
                        lbl.Top = 20
                        lbl.Height = btn.Height - 40
                        lbl.Width = CInt(TForm.Width / 2)
                        lbl.Visible = True
                        lbl.Font = objFont
                        lbl.ForeColor = colore
                        lbl.Tag = turno
                        btn.Controls.Add(lbl)
                        ' Adatta la dimensione del font al bottone
                        AdjustFontSize(btn)

                        ' Assegna colore
                        btn.BackColor = Color.Transparent 'colors(colorIndex Mod colors.Length)
                        colorIndex += 1

                        ' Visualizzazione diversa per i turni attivi e non attivi
                        If isActive = 0 Then
                            btn.FlatStyle = FlatStyle.Flat
                            btn.ForeColor = Color.Gray
                        Else
                            btn.FlatStyle = FlatStyle.Standard
                            btn.ForeColor = Color.White
                            btn.Font = New Font(btn.Font, FontStyle.Bold)
                        End If

                        If System.IO.File.Exists(Application.StartupPath & "\turno" & turno & ".png") Then
                            btn.BackgroundImage = Image.FromFile(Application.StartupPath & "\turno" & turno & ".png")
                            btn.BackgroundImageLayout = ImageLayout.Stretch
                        Else
                            If System.IO.File.Exists(Application.StartupPath & "\turno" & turno & ".png") Or System.IO.File.Exists(Application.StartupPath & "\" & turno & ".jpg") Then
                                btn.BackgroundImage = Image.FromFile(Application.StartupPath & "\turno" & turno & ".jpg")
                                btn.BackgroundImageLayout = ImageLayout.Stretch
                            End If
                        End If

                        ' Aggiunge il gestore dell'evento click
                        AddHandler btn.Click, AddressOf Button_Click
                        AddHandler lbl.Click, AddressOf Label_Click

                        ' Aggiunge il bottone al form
                        TForm.Controls.Add(btn)

                        nButton += 1
                        '' Aggiorna la posizione per il prossimo bottone
                        'currentCol += 1
                        'If currentCol >= MaxColumns Then
                        '    currentCol = 0
                        '    currentRow += 1

                        '    ' Controlla se abbiamo superato il numero massimo di righe
                        '    If currentRow >= MaxRows Then
                        '        MessageBox.Show("Troppi turni da visualizzare (massimo " & MaxColumns * MaxRows & ")")
                        '        Exit For
                        '    End If
                        'End If
                    End If
                End If
            Next
        End If

    End Sub
    Public Sub ProcessMessage2(message As String)
        ' Pulisce i controlli esistenti
        'TForm.Controls.Clear()
        Dim gFont As System.Drawing.FontStyle = FontStyle.Bold
        Dim objFont As System.Drawing.Font
        Dim colore As Color = ColorTranslator.FromHtml("#000000")
        Dim vMessage() As String
        Dim vimmagine() As String
        Dim vparti() As String
        Dim ImgSfondo As Image
        Dim imageWidth As Integer = 0
        Dim imageHeight As Integer = 0
        Dim vTurni() As String
        Dim vTesto() As String
        Dim nButton As Integer = 0


        ' Verifica che il messaggio inizi con QES|
        If Not message.StartsWith("TOTEM|") Then
            Return
        End If

        vMessage = message.Split("|"c)
        vTesto = vMessage(1).Split("§"c)
        vimmagine = vTesto(0).Split(";"c)


        ' Colori per i bottoni
        Dim colors() As Color = {
                Color.Red, Color.Blue, Color.Green, Color.Orange,
                Color.Purple, Color.Aqua, Color.Brown, Color.Coral,
                Color.Crimson, Color.DarkBlue, Color.DarkGreen, Color.DarkOrange,
                Color.Fuchsia, Color.Gold, Color.IndianRed, Color.LightBlue,
                Color.LimeGreen, Color.Magenta, Color.Maroon, Color.MediumPurple
            }

        If TForm.InvokeRequired Then
            TForm.Invoke(New Action(Of String)(AddressOf ProcessMessage2), New Object() {message})
        Else
            Dim files As String() = Directory.GetFiles(pathImg, "sfondototem.*")
            If files.Length > 0 Then
                ImgSfondo = Image.FromFile(files(0))
                TForm.BackgroundImage = ImgSfondo
            End If
            If vimmagine(1) <> "" And IsNumeric(vimmagine(1)) Then
                imageWidth = Int(vimmagine(1))
            End If
            If vimmagine(2) <> "" And IsNumeric(vimmagine(2)) Then
                imageHeight = Int(vimmagine(2))
            End If

            Dim sFont As String = "Impact"
            Dim sSize As Integer = 16
            Dim sBold As String = "Bold"
            For n = 1 To vTesto.Count - 1
                vTurni = vTesto(n).Split(";"c)

                If vTurni(2) <> "" Then
                    sFont = vTurni(2)
                End If
                If vTurni(3) <> "" And IsNumeric(vTurni(3)) Then
                    sSize = CInt(vTurni(3))
                End If
                If vTurni(4) = "1" Then
                    gFont = FontStyle.Bold
                Else
                    gFont = FontStyle.Regular
                End If
                objFont = New System.Drawing.Font(sFont, sSize, gFont)

                Dim btn As New Button()
                btn.Text = ""
                btn.TextAlign = ContentAlignment.MiddleRight
                btn.Font = objFont
                btn.Tag = vTurni(0)  ' Memorizza il valore del turno per l'evento click
                TForm.PositionLabel(btn, vTurni(6), vTurni(7), vTurni(8), vTurni(9), imageWidth, imageHeight)
                btn.BackColor = colors(nButton)
                btn.Visible = True
                btn.FlatStyle = FlatStyle.Flat
                btn.FlatAppearance.BorderSize = 0

                Dim lbl As New Label
                lbl.Text = vTurni(1)
                lbl.TextAlign = ContentAlignment.MiddleRight
                lbl.BackColor = colors(nButton)
                'TForm.PositionLabel(lbl, vTurni(6), vTurni(7), vTurni(8), vTurni(9), imageWidth, imageHeight)

                lbl.Visible = True
                lbl.Font = objFont
                lbl.ForeColor = colore
                lbl.Tag = vTurni(0)
                btn.Controls.Add(lbl)
                lbl.Width = btn.Width - 100
                lbl.Height = btn.Height
                lbl.Top = 0
                lbl.Left = 0
                ' Adatta la dimensione del font al bottone
                AdjustFontSize(btn)

                ' Assegna colore
                btn.BackColor = Color.Transparent 'colors(colorIndex Mod colors.Length)
                'colorIndex += 1
                If vTurni(11) = "I" Then
                    btn.Enabled = False
                    lbl.Enabled = False
                End If

                ' Aggiunge il gestore dell'evento click
                AddHandler btn.Click, AddressOf Button_Click
                AddHandler lbl.Click, AddressOf Label_Click

                ' Aggiunge il bottone al form
                TForm.Controls.Add(btn)

                If IsNothing(eTurni) Then
                    ReDim eTurni(0)
                    eTurni(0) = vTurni(0)
                ElseIf eTurni.Count = 0 Then
                    ReDim eTurni(0)
                    eTurni(0) = vTurni(0)
                Else
                    ReDim Preserve eTurni(eTurni.Count)
                    eTurni(eTurni.Count - 1) = vTurni(0)
                End If

                nButton += 1
                If nButton > colors.Count - 1 Then
                    nButton = 0
                End If
            Next

        End If

    End Sub

    Private Sub AdjustFontSize(btn As Button)
        ' Calcola una dimensione del font proporzionale alla dimensione del bottone
        Dim fontSize As Single = Math.Min(btn.Width, btn.Height) / 5
        ' Limita la dimensione minima e massima
        fontSize = Math.Max(8, Math.Min(fontSize, 18))
        btn.Font = New Font(btn.Font.FontFamily, fontSize, btn.Font.Style)
    End Sub

    Private Sub Button_Click(sender As Object, e As EventArgs)
        Dim btn As Button = DirectCast(sender, Button)
        btn.Enabled = False

        'senddata("NEW|" & btn.Tag.ToString() & "")
        CreaCoda(btn.Tag.ToString() & "")
        'Threading.Thread.Sleep(2000)
        btn.Enabled = True
    End Sub

    Private Sub Label_Click(sender As Object, e As EventArgs)
        Dim lbl As Label = DirectCast(sender, Label)
        lbl.Enabled = False

        'senddata("NEW|" & lbl.Tag.ToString() & "")
        CreaCoda(lbl.Tag.ToString() & "")
        lbl.Enabled = True
    End Sub

    Private Sub startImgReceiver()
        Dim portToListen As Integer = 12345 ' Scegli la porta
        Dim directoryToSaveImages As String = "C:\ReceivedImages" ' Modifica il percorso

        If receiver IsNot Nothing AndAlso receiver.IsListening Then
            MessageBox.Show("Il server è già attivo.", "Info", MessageBoxButtons.OK, MessageBoxIcon.Information)
            Return
        End If

        Try
            receiver = New ImageReceiver(portToListen, directoryToSaveImages)

            ' Sottoscrivi gli eventi per ricevere notifiche (es. per aggiornare l'UI)
            AddHandler receiver.ImageReceived, AddressOf OnImageReceivedHandler
            AddHandler receiver.ClientConnected, AddressOf OnClientConnectedHandler
            AddHandler receiver.ServerError, AddressOf OnServerErrorHandler
            AddHandler receiver.ServerStatus, AddressOf OnServerStatusHandler

            receiver.StartListening()
            ' Qui potresti aggiornare l'UI per indicare che il server è attivo
            ' txtLog.AppendText("Server avviato..." & vbCrLf)
        Catch ex As Exception
            MessageBox.Show("Errore durante l'avvio del server: " & ex.Message, "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error)
        End Try
    End Sub
    Private Sub stopImgReceiver()
        receiver?.StopListening()
        ' Qui potresti aggiornare l'UI per indicare che il server è stato fermato
        ' txtLog.AppendText("Server arrestato." & vbCrLf)
    End Sub

    ' Gestori degli eventi (esempi)
    ' Ricorda: questi metodi saranno chiamati dal thread del ImageReceiver.
    ' Se aggiorni l'UI da qui, usa Me.Invoke o Me.BeginInvoke.

    Private Sub OnImageReceivedHandler(filePath As String)
        If TForm.InvokeRequired Then
            TForm.Invoke(New Action(Of String)(AddressOf OnImageReceivedHandler), filePath)
        Else
            ' Esempio: aggiungi a una ListBox o a un log
            ' lstReceivedFiles.Items.Add(filePath)
            ' txtLog.AppendText("Immagine salvata: " & filePath & vbCrLf)
            Logga("Immagine salvata: " & filePath)
        End If
    End Sub

    Private Sub OnClientConnectedHandler(clientInfo As String)
        If TForm.InvokeRequired Then
            TForm.Invoke(New Action(Of String)(AddressOf OnClientConnectedHandler), clientInfo)
        Else
            ' txtLog.AppendText("Client connesso: " & clientInfo & vbCrLf)
            Logga("Client connesso: " & clientInfo)
        End If
    End Sub

    Private Sub OnServerErrorHandler(errorMessage As String)
        If TForm.InvokeRequired Then
            TForm.Invoke(New Action(Of String)(AddressOf OnServerErrorHandler), errorMessage)
        Else
            ' MessageBox.Show(errorMessage, "Errore Server", MessageBoxButtons.OK, MessageBoxIcon.Error)
            ' txtLog.AppendText("ERRORE Server: " & errorMessage & vbCrLf)
            Logga("ERRORE Server: " & errorMessage)
        End If
    End Sub

    Private Sub OnServerStatusHandler(statusMessage As String)
        If TForm.InvokeRequired Then
            TForm.Invoke(New Action(Of String)(AddressOf OnServerStatusHandler), statusMessage)
        Else
            ' txtLog.AppendText("Stato Server: " & statusMessage & vbCrLf)
            Logga("Stato Server: " & statusMessage)
        End If
    End Sub

    Public Sub Logga(testoDaAggiungere As String)
        Dim percorsoFile As String = Application.UserAppDataPath & "\log.txt"

        ' Apri il file in append, se non esiste lo crea
        Using sw As New System.IO.StreamWriter(percorsoFile, True)
            sw.Write(testoDaAggiungere)
        End Using
    End Sub

    Private Function TurnoAttivo(idturno As Integer) As String
        Dim StrQ As String = ""
        Dim lDset As New DataSet
        Dim Ret As String = "OK"
        Dim vGiorni() As String = {"Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"}

        Dim GiornoSett As Integer
        Dim Ora As String

        GiornoSett = If(Now.DayOfWeek = DayOfWeek.Sunday, 7, Now.DayOfWeek)
        Ora = Now.Hour.ToString.PadLeft(2, "0") & Now.Minute.ToString.PadLeft(2, "0")

        'StrQ = "select og.* from operazioni_giorni og inner join operazioni_turni ot "
        'StrQ = StrQ & "on og.id_operazione=ot.id_operazione "
        'StrQ = StrQ & "where ot.id_turno=" & idturno & " and og.giorno=" & GiornoSett & " "
        'StrQ = StrQ & "and og.ora_inizio<='" & Ora & "' and og.ora_fine>='" & Ora & "' "
        'StrQ = "Select min(op.ora_inizio1) As orainizio1, max(op.ora_fine1) As orafine1, min(op.ora_inizio2) As orainizio2, max(op.ora_fine2) As ora_fine2 "
        'StrQ = StrQ & "from(operazioni_turni ot inner join operazioni_postazioni op On ot.id_operazione = op.id_operazione) "
        'StrQ = StrQ & "inner Join operazioni_giorni og on ot.id_operazione = og.id_operazione "
        'StrQ = StrQ & "where ot.id_turno = " & idturno & " And og.giorno = " & GiornoSett & " and og.ora_inizio<='" & Ora & "' and og.ora_fine>='" & Ora & "' "
        'StrQ = StrQ & "HAVING((min(op.ora_inizio1) <= '" & Ora & "' and max(op.ora_fine1) >='" & Ora & "') "
        'StrQ = StrQ & "OR (min(op.ora_inizio2)<='" & Ora & "' and  max(op.ora_fine2)>='" & Ora & "'))"

        StrQ = "select og.* from operazioni_giorni og inner join operazioni_turni ot on og.id_operazione = ot.id_operazione 
                where ot.id_turno = " & idturno & " and og.giorno = " & GiornoSett & " and og.ora_inizio<='" & Ora & "' and og.ora_fine>='" & Ora & "'"
        If DbConnection.Estrai(StrQ, lDset, "orari", True) Then
            If lDset.Tables("orari").Rows.Count > 0 Then
                Ret = "OK"
            Else
                Ret = "Il turno è chiuso.§Orari di apertura:§"
                'StrQ = "select og.* from operazioni_giorni og inner join operazioni_turni ot "
                'StrQ = StrQ & "on og.id_operazione=ot.id_operazione "
                'StrQ = StrQ & "where ot.id_turno=" & idturno & " "
                'StrQ = StrQ & "order by og.giorno, og.ora_inizio"
                StrQ = "Select og.ora_inizio As orainizio1, og.ora_fine As orafine1, og.giorno "
                StrQ = StrQ & "from operazioni_giorni og inner join operazioni_turni ot on og.id_operazione = ot.id_operazione 
                where ot.id_turno = " & idturno & " "
                'StrQ = StrQ & "inner Join operazioni_giorni og on ot.id_operazione = og.id_operazione "
                'StrQ = StrQ & "where ot.id_turno = " & idturno & " group by og.giorno "
                StrQ = StrQ & "order by og.giorno, og.ora_inizio "
                If DbConnection.Estrai(StrQ, lDset, "orari", True) Then
                    For i As Integer = 0 To lDset.Tables("orari").Rows.Count - 1
                        If lDset.Tables("orari").Rows(i).Item("giorno") > 0 Then
                            Ret = Ret & "§" & vGiorni(lDset.Tables("orari").Rows(i).Item("giorno") - 1) & " "
                        Else
                            Ret = Ret & "§" & vGiorni(6) & " "
                        End If
                        If lDset.Tables("orari").Rows(i).Item("orainizio1").ToString <> "0000" Then
                            Ret = Ret & " dalle " & lDset.Tables("orari").Rows(i).Item("orainizio1").ToString.Substring(0, 2)
                            Ret = Ret & ":" & lDset.Tables("orari").Rows(i).Item("orainizio1").ToString.Substring(2, 2)
                        End If
                        If lDset.Tables("orari").Rows(i).Item("orafine1").ToString <> "0000" Then
                            Ret = Ret & " alle " & lDset.Tables("orari").Rows(i).Item("orafine1").ToString.Substring(0, 2)
                            Ret = Ret & ":" & lDset.Tables("orari").Rows(i).Item("orafine1").ToString.Substring(2, 2)
                        End If
                        Ret = Ret & "§"
                        'If lDset.Tables("orari").Rows(i).Item("orainizio2").ToString <> "0000" Then
                        '    Ret = Ret & " dalle " & lDset.Tables("orari").Rows(i).Item("orainizio2").ToString.Substring(0, 2)
                        '    Ret = Ret & ";" & lDset.Tables("orari").Rows(i).Item("orainizio2").ToString.Substring(2, 2)
                        'End If
                        'If lDset.Tables("orari").Rows(i).Item("orafine2").ToString <> "0000" Then
                        '    Ret = Ret & " alle " & lDset.Tables("orari").Rows(i).Item("orafine2").ToString.Substring(0, 2)
                        '    Ret = Ret & ";" & lDset.Tables("orari").Rows(i).Item("orafine2").ToString.Substring(2, 2) & "§"
                        'End If
                    Next
                End If
            End If
        End If

        Return Ret

    End Function

    Public Sub CreaCoda(turnoChiamato As String)

        Dim ilturno As String
        Dim nextnumero As Integer = 0
        Dim idturno As Integer
        Dim Invia As Boolean = True
        Dim StrQ As String
        Dim lDset As New DataSet

        ilturno = turnoChiamato

        StrQ = "select id_turno from turni where turno='" & ilturno & "'"
        If DbConnection.Estrai(StrQ, lDset, "coda", True) Then
            idturno = lDset.Tables("coda").Rows(0).Item("id_turno")
        End If

        If idturno > 0 Then
            Dim tAttivo As String = ""
            tAttivo = TurnoAttivo(idturno)
            If tAttivo <> "OK" Then
                Dim msg() As String = tAttivo.Split("|")
                Invia = False
                MesTxt = tAttivo.ToString.Replace("§", vbCrLf)
                contatore2 = 10
                MesVisibile2 = True
                'Threading.Thread.Sleep(10000)
                'MesTxt = ""
                'MesVisibile2 = False
            End If
        End If

        If Invia Then
            StrQ = "select max(numero) as maxcoda from contatori c inner join turni t on c.id_turno=t.id_turno where "
            StrQ = StrQ & "t.turno='" & ilturno & "' and c.tipo='CODA'"
            If DbConnection.Estrai(StrQ, lDset, "coda", True) Then
                If lDset.Tables("coda").Rows.Count > 0 Then
                    If IsDBNull(lDset.Tables("coda").Rows(0).Item(0)) Then
                        Invia = False
                        nextnumero = 1
                        StrQ = "insert into contatori (tipo, id_turno, id_postazione, numero, data) values "
                        StrQ = StrQ & "('CODA', " & idturno & ", 0, " & nextnumero & ", '" & D2S(Now.Year, Now.Month, Now.Day) & "')"
                        StrQ = ""
                    Else
                        nextnumero = lDset.Tables("coda").Rows(0).Item(0) + 1
                        If nextnumero > 999 Then nextnumero = 0
                        Dim DsetControllo As New DataSet
                        StrQ = "select * from contatori where numero=" & nextnumero & " and data='" & D2S(Now.Year, Now.Month, Now.Day) & "' "
                        StrQ = StrQ & "and tipo='CODA' and id_turno=" & idturno
                        If DbConnection.Estrai(StrQ, DsetControllo, "controllo", True) Then
                            If DsetControllo.Tables("controllo").Rows.Count = 0 Then
                                StrQ = "update contatori set numero=" & nextnumero & ", data='" & D2S(Now.Year, Now.Month, Now.Day) & "' "
                                StrQ = StrQ & "where tipo='CODA' and id_turno=" & idturno
                                If Not DbConnection.EseguiSQL(StrQ) Then
                                    Invia = False
                                    'nextnumero += 1
                                    'StrQ = "update contatori set numero=" & nextnumero & ", data='" & D2S(Now.Year, Now.Month, Now.Day) & "' "
                                    'StrQ = StrQ & "where tipo='CODA' and id_turno=" & idturno
                                    'DbConnection.EseguiSQL(StrQ)
                                Else
                                    stampascontrino("PRINT|" & ilturno & "|" & nextnumero)
                                End If
                            End If
                        End If
                    End If
                Else
                    Invia = False
                    'nextnumero = 1
                    'StrQ = "insert into contatori (tipo, id_turno, id_postazione, numero, data) values "
                    'StrQ = StrQ & "('CODA', " & idturno & ", 0, " & nextnumero & ", '" & D2S(Now.Year, Now.Month, Now.Day) & "')"
                    'StrQ = ""
                End If
            End If
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

End Module
