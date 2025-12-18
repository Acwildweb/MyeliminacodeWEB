Imports System.Net
Imports Newtonsoft.Json
Module getJson
    ' Definizione di una struttura per deserializzare i dati JSONPublic Class Root
    Public Class Root
        Public Property configmonitor As List(Of ConfigMonitor)
        Public Property configturnimonitor As List(Of ConfigTurniMonitor)
        Public Property configtotem As List(Of ConfigTotem)
        Public Property configturnitotem As List(Of ConfigTurniTotem)
        Public Property san_operazioni As List(Of SanOperazioni)
        Public Property san_operazioni_giorni As List(Of SanOperazioniGiorni)
        Public Property san_operazioni_postazioni As List(Of SanOperazioniPostazioni)
        Public Property san_operazioni_turni As List(Of SanOperazioniTurni)
        Public Property san_postazioni As List(Of SanPostazioni)
        Public Property san_turni As List(Of SanTurni)
    End Class

    Public Class ConfigMonitor
        Public Property idconfigmonitor As Integer
        Public Property idcliente As Integer
        Public Property immaginesfondo As String
        Public Property ximmaginesfondo As Integer
        Public Property yimmaginesfondo As Integer
        Public Property boximmagini As String
        Public Property hboximmagini As Integer
        Public Property wboximmagini As Integer
        Public Property xboximmagini As Integer
        Public Property yboximmagini As Integer
        Public Property boxvideo As String
        Public Property hboxvideo As Integer
        Public Property wboxvideo As Integer
        Public Property xboxvideo As Integer
        Public Property yboxvideo As Integer
        Public Property boxmeteo As String
        Public Property hboxmeteo As Integer
        Public Property wboxmeteo As Integer
        Public Property xboxmeteo As Integer
        Public Property yboxmeteo As Integer
        Public Property urlboxmeteo As String
        Public Property boxnews As String
        Public Property hboxnews As Integer
        Public Property wboxnews As Integer
        Public Property xboxnews As Integer
        Public Property yboxnews As Integer
        Public Property urlboxnews As String
        Public Property boxchiamati As String
        Public Property hboxchiamati As Integer
        Public Property wboxchiamati As Integer
        Public Property xboxchiamati As Integer
        Public Property yboxchiamati As Integer
        Public Property fontboxchiamati As String
        Public Property boldboxchiamati As String
        Public Property numatuttoschermo As String
        Public Property fontnumatuttoschermo As String
        Public Property boldnumatuttoschermo As String
        Public Property fontturnonumatuttoschermo As String
        Public Property boldturnonumatuttoschermo As String
        Public Property imgsfondonumatuttoschermo As String
    End Class

    Public Class ConfigTurniMonitor
        Public Property idconfigturnimonitor As Integer
        Public Property idconfigmonitor As Integer
        Public Property idturno As Integer
        Public Property fontturno As String
        Public Property sizeturno As String
        Public Property boldturno As String
        Public Property coloreturno As String
        Public Property xturno As Integer
        Public Property yturno As Integer
        Public Property hturno As Integer
        Public Property wturno As Integer
        Public Property fontcontatore As String
        Public Property sizecontatore As String
        Public Property boldcontatore As String
        Public Property colorecontatore As String
        Public Property xcontatore As Integer
        Public Property ycontatore As Integer
        Public Property hcontatore As Integer
        Public Property wcontatore As Integer
        Public Property fontpostazione As String
        Public Property sizepostazione As String
        Public Property boldpostazione As String
        Public Property colorepostazione As String
        Public Property xpostazione As Integer
        Public Property ypostazione As Integer
        Public Property hpostazione As Integer
        Public Property wpostazione As Integer
    End Class

    Public Class ConfigTotem
        Public Property idconfigtotem As Integer
        Public Property idcliente As Integer
        Public Property immaginesfondo As String
        Public Property ximmaginesfondo As Integer
        Public Property yimmaginesfondo As Integer
    End Class

    Public Class ConfigTurniTotem
        Public Property idconfigturnitotem As Integer
        Public Property idconfigtotem As Integer
        Public Property idturno As Integer
        Public Property fontturno As String
        Public Property sizeturno As Integer
        Public Property boldturno As String
        Public Property coloreturno As String
        Public Property xturno As Integer
        Public Property yturno As Integer
        Public Property hturno As Integer
        Public Property wturno As Integer
        Public Property sfondobutton As String

    End Class

    Public Class SanOperazioni
        Public Property id_operazione As Integer
        Public Property operazione As String
        Public Property idcliente As Integer
    End Class

    Public Class SanOperazioniGiorni
        Public Property pk_opgio As Integer
        Public Property id_operazione As Integer
        Public Property giorno As Integer
        Public Property ora_inizio As String
        Public Property ora_fine As String
        Public Property note As String
    End Class

    Public Class SanOperazioniPostazioni
        Public Property id_postazione As Integer
        Public Property id_operazione As Integer
        Public Property ora_inizio1 As String
        Public Property ora_fine1 As String
        Public Property ora_inizio2 As String
        Public Property ora_fine2 As String
        Public Property ora_inizio3 As String
        Public Property ora_fine3 As String
        Public Property ora_inizio4 As String
        Public Property ora_fine4 As String
    End Class

    Public Class SanOperazioniTurni
        Public Property id_turno As Integer
        Public Property id_operazione As Integer
    End Class

    Public Class SanPostazioni
        Public Property id_postazione As Integer
        Public Property postazione As String
        Public Property idcliente As Integer
        Public Property descrizione As String
        Public Property turno_ambulatorio As String

    End Class

    Public Class SanTurni
        Public Property id_turno As Integer
        Public Property turno As String
        Public Property stato As String
        Public Property desstato As String
        Public Property priorita As Integer
        Public Property idcliente As Integer
    End Class

    ' Funzione per chiamare un URL e deserializzare la risposta JSON
    Public Function GetDataFromUrl(Of T)(url As String) As T
        Try
            ' Creazione di un WebClient per effettuare la richiesta HTTP
            Using client As New WebClient()
                ' Imposta l'intestazione (opzionale, ma utile se necessario)
                client.Headers(HttpRequestHeader.ContentType) = "application/json"

                ' Effettua la richiesta e ottiene la risposta come stringa
                Dim jsonResponse As String = client.DownloadString(url)

                ' Deserializza la risposta JSON in un oggetto VB.NET di tipo T
                Dim data As T = JsonConvert.DeserializeObject(Of T)(jsonResponse)

                ' Restituisce i dati deserializzati
                Return data
            End Using
        Catch ex As Exception
            ' Gestione degli errori
            Logga($"Errore: {ex.Message}")
            Return Nothing
        End Try
    End Function

End Module
