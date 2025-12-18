Imports System.Net
Imports System.IO
Imports System.Text

Module ImageDownloader
    Public Function DownloadImage(ByVal imagePath As String, ByVal savePath As String) As Boolean
        ' Codifica il percorso del file in Base64
        Dim encodedImagePath As String = Convert.ToBase64String(Encoding.UTF8.GetBytes(imagePath))

        ' URL del file PHP con il parametro codificato
        'Dim phpUrl As String = "http://localhost/myeliminacode/api/image_sender.php?image_path=" & encodedImagePath
        Dim phpUrl As String = "https://myeliminacode.acwild.eu/api/image_sender.php?image_path=" & encodedImagePath

        ' Crea una richiesta HTTP
        Dim request As HttpWebRequest = CType(WebRequest.Create(phpUrl), HttpWebRequest)

        Try
            ' Ottiene la risposta dal server
            Using response As HttpWebResponse = CType(request.GetResponse(), HttpWebResponse)
                ' Controlla se la risposta è OK
                If response.StatusCode = HttpStatusCode.OK Then
                    ' Legge il contenuto della risposta
                    Using responseStream As Stream = response.GetResponseStream()
                        ' Crea un file per salvare l'immagine
                        Try
                            Using fileStream As FileStream = New FileStream(savePath, FileMode.Create, FileAccess.Write)
                                responseStream.CopyTo(fileStream) ' Copia i dati dal flusso della risposta al file
                            End Using
                        Catch ex As IOException
                            Debug.WriteLine("Il file potrebbe essere già in uso: " & ex.Message)
                            Return False
                        End Try
                    End Using
                    Return True
                Else
                    Return False
                End If
            End Using
        Catch ex As Exception
            Return False
        End Try
    End Function
End Module