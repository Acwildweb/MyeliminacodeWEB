
Imports System.IO
Public Class Client

    Private Sub Button1_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button1.Click
        Dim Regi As New Registro

        If t.Connected Then t.Close()

        If connect(TxtServer.Text, TxtPort.Text) Then            'connect textbox1 as ip and textbox2 as port 
            StatoLbl.Text = "CONNESSO"
            StatoLbl.ForeColor = Color.Green
            RegistraCmd.Enabled = True
        Else
            MsgBox("Impossibile connettersi al server!", MsgBoxStyle.Critical)
        End If
    End Sub

    Private Sub Button2_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button2.Click
        senddata("CHAT|" & TxtMessaggio.Text) 'send the data with CHAT| as header
        TxtTesto.Text &= "You: " & " " & TxtMessaggio.Text.Split("|")(0) & vbNewLine
    End Sub

    Private Sub Form1_Load(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles MyBase.Load
        Dim sPort As String
        Dim sServer As String
        Dim Regi As New Registro

        sPort = Regi.Leggi("TOUCH", "SERVERPORT")
        sServer = Regi.Leggi("TOUCH", "SERVERIP")
        TxtServer.Text = sServer
        TxtPort.Text = sPort


        If t.Connected Then
            StatoLbl.ForeColor = Color.Green
            RegistraCmd.Enabled = True
        Else
            StatoLbl.ForeColor = Color.Red
            RegistraCmd.Enabled = False
        End If

    End Sub

    Private Sub RegistraCmd_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles RegistraCmd.Click
        Dim Regi As New Registro

        Regi.Scrivi("TOUCH", "SERVERPORT", TxtPort.Text)
        sPort = TxtPort.Text
        Regi.Scrivi("TOUCH", "SERVERIP", TxtServer.Text)
        sServer = TxtServer.Text
        Me.Hide()
        TForm.ShowDialog()

    End Sub
End Class
