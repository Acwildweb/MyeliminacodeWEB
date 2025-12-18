Public Class ImpostazioniDb
    Dim SDb As CDb.Tdb

    Private Sub ImpostazioniDb_Load(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles MyBase.Load
        SDb = CIDb.DatiDb
        If SDb.TipoDb <> "" Then
            TipoDbCmb.SelectedIndex = CInt(SDb.TipoDb)
            Select Case TipoDbCmb.SelectedIndex
                Case 0
                    Impostazioni1.Text = SDb.NomeODBC
                Case 1
                    Impostazioni1.Text = SDb.PathAccess
                    Impostazioni2.Text = SDb.Pwd
                Case 2, 3, 4
                    Impostazioni1.Text = SDb.NomeServer
                    Impostazioni2.Text = SDb.NomeDb
                    Impostazioni3.Text = SDb.Uid
                    Impostazioni4.Text = SDb.Pwd
            End Select
        End If
    End Sub

    Private Sub TipoDbCmb_SelectedIndexChanged(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles TipoDbCmb.SelectedIndexChanged

        Impostazioni1.Text = ""
        Impostazioni2.Text = ""
        Impostazioni3.Text = ""
        Impostazioni4.Text = ""

        Select Case TipoDbCmb.SelectedIndex
            Case 0
                Label2.Visible = True
                Label3.Visible = False
                Label4.Visible = False
                Label5.Visible = False
                Impostazioni1.Visible = True
                Impostazioni2.Visible = False
                Impostazioni3.Visible = False
                Impostazioni4.Visible = False
                Label2.Text = "Nome ODBC"
            Case 1
                Label2.Visible = True
                Label3.Visible = True
                Label4.Visible = False
                Label5.Visible = False
                Impostazioni1.Visible = True
                Impostazioni2.Visible = True
                Impostazioni3.Visible = False
                Impostazioni4.Visible = False
                Label2.Text = "Percorso database"
                Label3.Text = "Password"
            Case 2, 3, 4
                Label2.Visible = True
                Label3.Visible = True
                Label4.Visible = True
                Label5.Visible = True
                Impostazioni1.Visible = True
                Impostazioni2.Visible = True
                Impostazioni3.Visible = True
                Impostazioni4.Visible = True
                Label2.Text = "Nome server"
                Label3.Text = "Database"
                Label4.Text = "Userid"
                Label5.Text = "Password"
        End Select

    End Sub

    Private Sub ConfermaCmd_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles ConfermaCmd.Click
        Select Case TipoDbCmb.SelectedIndex
            Case 0
                DbConnection.TipoDb = "0"
                DbConnection.NomeODBC = Impostazioni1.Text
                SDb.TipoDb = "0"
                SDb.PathAccess = ""
                SDb.NomeODBC = Impostazioni1.Text
                SDb.NomeServer = ""
                SDb.NomeDb = ""
                SDb.Uid = ""
                SDb.Pwd = ""
            Case 1
                DbConnection.TipoDb = "1"
                DbConnection.FilePath = Impostazioni1.Text
                DbConnection.PwdAccesso = Impostazioni2.Text
                SDb.TipoDb = "1"
                SDb.PathAccess = Impostazioni1.Text
                SDb.NomeODBC = ""
                SDb.NomeServer = ""
                SDb.NomeDb = ""
                SDb.Uid = ""
                SDb.Pwd = Impostazioni2.Text
            Case 2, 3, 4
                DbConnection.TipoDb = TipoDbCmb.SelectedIndex.ToString
                DbConnection.NomeServer = Impostazioni1.Text
                DbConnection.NomeDatabase = Impostazioni2.Text
                DbConnection.UseridAccesso = Impostazioni3.Text
                DbConnection.PwdAccesso = Impostazioni4.Text
                SDb.TipoDb = TipoDbCmb.SelectedIndex.ToString
                SDb.NomeServer = Impostazioni1.Text
                SDb.NomeDb = Impostazioni2.Text
                SDb.Uid = Impostazioni3.Text
                SDb.Pwd = Impostazioni4.Text
                SDb.PathAccess = ""
                SDb.NomeODBC = ""
        End Select

        If DbConnection.Connetti() Then
            CIDb.DatiDb = SDb
            CIDb.scrivi()
            MsgBox("L'applicazione verrà riavviata!", MsgBoxStyle.Exclamation)
            Application.ExitThread()
            Application.Exit()
            Application.Restart()
            End
        Else
            MsgBox(DbConnection.MsgErrore, MsgBoxStyle.Critical, "Errore di connessione")
        End If
    End Sub

    Private Sub AnnullaCmd_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles AnnullaCmd.Click
        Me.Dispose()
    End Sub
End Class