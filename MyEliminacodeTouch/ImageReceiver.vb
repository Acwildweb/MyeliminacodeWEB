Imports System.Net
Imports System.Net.Sockets
Imports System.IO
Imports System.Threading

Public Class ImageReceiver
    Private _listenerThread As Thread
    Private _tcpListener As TcpListener
    Private _port As Integer
    Private _saveDirectory As String
    Private _shouldStop As Boolean = False ' Volatile per la sicurezza tra thread

    ' Eventi per fornire feedback all'applicazione principale
    Public Event ImageReceived As Action(Of String) ' Argomento: percorso del file salvato
    Public Event ClientConnected As Action(Of String) ' Argomento: informazioni sul client
    Public Event ServerError As Action(Of String) ' Argomento: messaggio di errore
    Public Event ServerStatus As Action(Of String) ' Argomento: messaggio di stato

    Public ReadOnly Property IsListening As Boolean
        Get
            Return _listenerThread IsNot Nothing AndAlso _listenerThread.IsAlive
        End Get
    End Property

    Public Sub New(port As Integer, saveDirectory As String)
        _port = port
        _saveDirectory = saveDirectory

        ' Crea la directory di salvataggio se non esiste
        If Not Directory.Exists(_saveDirectory) Then
            Try
                Directory.CreateDirectory(_saveDirectory)
            Catch ex As Exception
                ' Gestisci l'eccezione se non è possibile creare la directory
                ' Potresti sollevare un evento di errore o un'eccezione personalizzata
                RaiseEvent ServerError("Impossibile creare la directory di salvataggio: " & ex.Message)
                Throw ' Rilancia l'eccezione o gestiscila diversamente
            End Try
        End If
    End Sub

    Public Sub StartListening()
        If IsListening Then
            RaiseEvent ServerError("Il server è già in ascolto.")
            Return
        End If

        _shouldStop = False
        _listenerThread = New Thread(AddressOf ListenLoop)
        _listenerThread.IsBackground = True ' Permette all'applicazione di chiudersi anche se il thread è in esecuzione
        _listenerThread.Start()
        RaiseEvent ServerStatus("Server avviato. In ascolto sulla porta " & _port)
    End Sub

    Public Sub StopListening()
        If Not IsListening Then
            Return
        End If

        System.Threading.Volatile.Write(Me._shouldStop, True) ' Modifica qui
        _tcpListener?.Stop() ' Questo causerà un'eccezione in AcceptTcpClient, sbloccandolo

        _shouldStop = True
        _tcpListener?.Stop() ' Questo causerà un'eccezione in AcceptTcpClient, sbloccandolo

        ' Attendi che il thread termini, con un timeout
        If _listenerThread IsNot Nothing AndAlso _listenerThread.IsAlive Then
            If Not _listenerThread.Join(TimeSpan.FromSeconds(5)) Then
                ' Opzionale: tenta di interrompere il thread se non si ferma normalmente (usare con cautela)
                ' _listenerThread.Abort()
                RaiseEvent ServerError("Timeout durante l'arresto del thread del server.")
            End If
        End If
        _listenerThread = Nothing
        RaiseEvent ServerStatus("Server arrestato.")
    End Sub

    Private Sub ListenLoop()
        Try
            _tcpListener = New TcpListener(IPAddress.Any, _port)
            _tcpListener.Start()

            While Not System.Threading.Volatile.Read(Me._shouldStop)
                Try
                    ' AcceptTcpClient è bloccante. Solleverà un'eccezione quando _tcpListener.Stop() viene chiamato.
                    Dim client As TcpClient = _tcpListener.AcceptTcpClient()
                    RaiseEvent ClientConnected("Client connesso da: " & client.Client.RemoteEndPoint.ToString())

                    ' Gestisci la connessione del client.
                    ' Questo bloccherà il ListenLoop per la durata della gestione di questo client.
                    ' Per gestire più client contemporaneamente, potresti avviare un nuovo Thread/Task qui per ogni client.
                    HandleClient(client)

                Catch ex As SocketException
                    If _shouldStop Then
                        ' Eccezione attesa quando StopListening viene chiamato.
                        RaiseEvent ServerStatus("Il listener TCP è stato interrotto correttamente.")
                        Exit While ' Esce dal ciclo
                    Else
                        ' Eccezione socket inattesa.
                        RaiseEvent ServerError("SocketException nel ListenLoop: " & ex.Message)
                        Thread.Sleep(1000) ' Breve pausa prima di ritentare
                    End If
                Catch ex As Exception
                    ' Altre eccezioni inattese.
                    RaiseEvent ServerError("Errore nel ListenLoop: " & ex.Message)
                    Thread.Sleep(1000) ' Evita cicli rapidi su errori persistenti
                End Try
            End While

        Catch ex As Exception
            ' Eccezione durante l'avvio del listener stesso.
            RaiseEvent ServerError("Errore fatale nell'avvio del listener: " & ex.Message)
        Finally
            _tcpListener?.Stop() ' Assicura che il listener sia fermato se il ciclo termina per qualsiasi motivo.
            RaiseEvent ServerStatus("Thread di ascolto terminato.")
        End Try
    End Sub

    Private Sub HandleClient(client As TcpClient)
        Try
            Using client ' Assicura che TcpClient venga eliminato correttamente
                Using stream As NetworkStream = client.GetStream()
                    ' --- Ricevi lunghezza nome file ---
                    Dim fileNameLengthBuffer(3) As Byte
                    stream.Read(fileNameLengthBuffer, 0, 4)
                    Dim fileNameLength As Integer = BitConverter.ToInt32(fileNameLengthBuffer, 0)

                    ' --- Ricevi nome file ---
                    Dim fileNameBuffer(fileNameLength - 1) As Byte
                    stream.Read(fileNameBuffer, 0, fileNameLength)
                    Dim fileName As String = System.Text.Encoding.UTF8.GetString(fileNameBuffer)

                    ' Imposta timeout per le operazioni di lettura per evitare blocchi indefiniti
                    stream.ReadTimeout = 30000 ' 30 secondi, modifica se necessario
                    stream.WriteTimeout = 30000

                    ' 1. Ricevi la dimensione del file (4 byte per un Int32)
                    Dim sizeBuffer(3) As Byte
                    Dim bytesReadForSize As Integer = stream.Read(sizeBuffer, 0, 4)

                    If bytesReadForSize < 4 Then
                        RaiseEvent ServerError("Errore: Non sono stati ricevuti abbastanza byte per la dimensione del file da " & client.Client.RemoteEndPoint.ToString())
                        Return
                    End If
                    Dim fileSize As Integer = BitConverter.ToInt32(sizeBuffer, 0)

                    ' Valida la dimensione del file (es. max 50MB, modifica secondo necessità)
                    If fileSize <= 0 OrElse fileSize > (50 * 1024 * 1024) Then
                        RaiseEvent ServerError("Dimensione del file non valida o troppo grande: " & fileSize & " bytes da " & client.Client.RemoteEndPoint.ToString())
                        Return
                    End If

                    RaiseEvent ServerStatus("Inizio ricezione immagine (" & fileSize & " bytes) da " & client.Client.RemoteEndPoint.ToString())

                    ' 2. Ricevi i dati dell'immagine
                    Dim imageBytes(fileSize - 1) As Byte
                    Dim totalBytesRead As Integer = 0
                    Dim receiveBufferSize As Integer = 8192 ' Leggi in blocchi (chunk)

                    While totalBytesRead < fileSize
                        Dim bytesToRead As Integer = Math.Min(receiveBufferSize, fileSize - totalBytesRead)
                        Dim bytesReadThisChunk As Integer = stream.Read(imageBytes, totalBytesRead, bytesToRead)

                        If bytesReadThisChunk = 0 Then
                            ' Connessione chiusa prematuramente dal client
                            RaiseEvent ServerError("Connessione interrotta dal client durante la ricezione dei dati dell'immagine (" & client.Client.RemoteEndPoint.ToString() & ").")
                            Return
                        End If
                        totalBytesRead += bytesReadThisChunk
                    End While

                    If totalBytesRead = fileSize Then
                        ' 3. Salva l'immagine
                        ' Genera un nome file univoco per evitare sovrascritture
                        'Dim fileExtension As String = ".jpg" ' Assumi jpg, o invia il tipo di file dal client
                        'Dim fileName As String = "img_" & DateTime.Now.ToString("yyyyMMdd_HHmmss_fff") & "_" & Guid.NewGuid().ToString("N").Substring(0, 8) & fileExtension
                        Dim fullSavePath As String = Path.Combine(_saveDirectory, fileName)

                        File.WriteAllBytes(fullSavePath, imageBytes)
                        RaiseEvent ImageReceived(fullSavePath)
                        RaiseEvent ServerStatus("Immagine ricevuta e salvata: " & fullSavePath)
                    Else
                        RaiseEvent ServerError("Errore: Ricevuti " & totalBytesRead & " bytes, ma attesi " & fileSize & " bytes da " & client.Client.RemoteEndPoint.ToString())
                    End If
                End Using
            End Using
        Catch ex As IOException
            ' Gestisce eccezioni IO specifiche, come i timeout
            RaiseEvent ServerError("IOException in HandleClient (" & client.Client.RemoteEndPoint.ToString() & "): " & ex.Message)
        Catch ex As Exception
            RaiseEvent ServerError("Errore in HandleClient (" & client.Client.RemoteEndPoint.ToString() & "): " & ex.Message)
        Finally
            client?.Close() ' Assicura che il client sia chiuso
        End Try
    End Sub

End Class