Imports Microsoft.Win32

Public Class Registro
    Public Function Leggi(ByVal NomeChiave As String, ByVal NomeValore As String) As String
        Dim Chiave As RegistryKey

        Chiave = Registry.CurrentUser.OpenSubKey("SOFTWARE\MYELIMINACODETOUCH\" + NomeChiave)
        If IsNothing(Chiave) Then
            Return ""
        End If
        Try
            Return Chiave.GetValue(NomeValore)
        Catch ex As Exception
            Return ""
        End Try
    End Function

    Public Sub Scrivi(ByVal NomeChiave As String, ByVal NomeValore As String, ByVal Valore As String)
        Dim Chiave As RegistryKey

        Chiave = Registry.CurrentUser.OpenSubKey("SOFTWARE\MYELIMINACODETOUCH\" + NomeChiave, True)
        If Chiave Is Nothing Then
            Chiave = Registry.CurrentUser.CreateSubKey("SOFTWARE\MYELIMINACODETOUCH\" + NomeChiave)
        End If
        Chiave.SetValue(NomeValore, Valore)

    End Sub
End Class