Imports System.Net.Sockets
Imports System.Net
Imports System.IO

Public Class Server
    Dim clients As New Hashtable 'new database (hashtable) to hold the clients
    Private Sub Button1_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button1.Click
        Dim Regi As New Registro

        If TxtIdMonitor.Text.Trim = "" Then
            MsgBox("Inserire l'id del monitor!", MsgBoxStyle.Critical)
            Exit Sub
        End If

        If WebChk.Checked And TxtPercorsoWeb.Text.Trim = "" Then
            MsgBox("Inserire il percorso web!", MsgBoxStyle.Critical)
            Exit Sub
        End If

        listener = Nothing
        listener = New System.Threading.Thread(AddressOf listen) 'initialize a new thread for the listener so our GUI doesn't lag
        listener.IsBackground = True
        listener.Start(TxtPort.Text) 'start the listener, with the port specified as a parameter (textbox1 is our port textbox)
        'Button1.Enabled = False 'disable our button so the user cannot try to make any further listeners which will result in errors
        Regi.Scrivi("SERVERMEC", "SERVERPORT", TxtPort.Text)
        Regi.Scrivi("ContactSVR", "IdCliente", TxtIdMonitor.Text)
        Regi.Scrivi("ContactSVR", "WebMonitor", IIf(WebChk.Checked, "1", "0"))
        Regi.Scrivi("ContactSVR", "PercorsoWeb", TxtPercorsoWeb.Text)
        Application.Restart()
    End Sub

    Private Sub Button2_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button2.Click
        senddata("CHAT|" & TextBox2.Text) 'send teh data with CHAT as the header so the clietn knows to process the message as a chat message
        TextBox3.Text &= "You Say: " & " " & TextBox2.Text & vbNewLine 'add a message to the chat textbox showing we have sent a public message
    End Sub

    Private Sub SendToolStripMenuItem_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles SendToolStripMenuItem.Click
        Try
            For Each cli As String In ListBox1.SelectedItems 'for each selected client in the selected listbox of our clients
                sendsingle("CHAT|" & ToolStripTextBox1.Text, cli) 'send a message to the only the selected client by providing it's name as a second parameter
                TextBox3.Text &= "To " & cli & " :" & " " & ToolStripTextBox1.Text.Split("|")(1) & vbNewLine 'add a message on textbox3 which suggests a private message 
            Next 'go to the next selected client if any
        Catch
        End Try

    End Sub

    Private Sub Server_Load(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles MyBase.Load
        Dim sPort As String
        Dim Regi As New Registro
        Dim Testo As String

        sPort = Regi.Leggi("SERVERMEC", "SERVERPORT")
        TxtPort.Text = sPort

        Testo = Regi.Leggi("ContactSVR", "IdCliente")
        TxtIdMonitor.Text = Testo

        Testo = Regi.Leggi("ContactSVR", "WebMonitor")
        If Testo = "1" Then
            WebChk.Checked = True
        Else
            WebChk.Checked = False
        End If

    End Sub

    Private Sub SfogliaBtn_Click(sender As Object, e As EventArgs) Handles SfogliaBtn.Click
        Using fbd As New FolderBrowserDialog()
            fbd.Description = "Seleziona una cartella"
            fbd.ShowNewFolderButton = True
            If fbd.ShowDialog() = DialogResult.OK Then
                ' Ad esempio, imposta il percorso selezionato su una TextBox
                TxtPercorsoWeb.Text = fbd.SelectedPath
            End If
        End Using
    End Sub
End Class
