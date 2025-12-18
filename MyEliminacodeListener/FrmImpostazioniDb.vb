Public Class FrmImpostazioniDb
    Inherits System.Windows.Forms.Form
#Region " Codice generato da Progettazione Windows Form "

    Public Sub New()
        MyBase.New()

        'Chiamata richiesta da Progettazione Windows Form.
        InitializeComponent()

        'Aggiungere le eventuali istruzioni di inizializzazione dopo la chiamata a InitializeComponent()

    End Sub

    'Form esegue l'override del metodo Dispose per pulire l'elenco dei componenti.
    Protected Overloads Overrides Sub Dispose(ByVal disposing As Boolean)
        If disposing Then
            If Not (components Is Nothing) Then
                components.Dispose()
            End If
        End If
        MyBase.Dispose(disposing)
    End Sub

    'Richiesto da Progettazione Windows Form
    Private components As System.ComponentModel.IContainer

    'NOTA: la procedura che segue è richiesta da Progettazione Windows Form.
    'Può essere modificata in Progettazione Windows Form.  
    'Non modificarla nell'editor del codice.
    Friend WithEvents Label1 As System.Windows.Forms.Label
    Friend WithEvents TipoDbCmb As System.Windows.Forms.ComboBox
    Friend WithEvents Label2 As System.Windows.Forms.Label
    Friend WithEvents Label3 As System.Windows.Forms.Label
    Friend WithEvents Label4 As System.Windows.Forms.Label
    Friend WithEvents Label5 As System.Windows.Forms.Label
    Friend WithEvents Impostazioni1 As System.Windows.Forms.TextBox
    Friend WithEvents Impostazioni2 As System.Windows.Forms.TextBox
    Friend WithEvents Impostazioni3 As System.Windows.Forms.TextBox
    Friend WithEvents Impostazioni4 As System.Windows.Forms.TextBox
    Friend WithEvents ConfermaCmd As System.Windows.Forms.Button
    Friend WithEvents AnnullaCmd As System.Windows.Forms.Button
    <System.Diagnostics.DebuggerStepThrough()> Private Sub InitializeComponent()
        Me.Label1 = New System.Windows.Forms.Label()
        Me.TipoDbCmb = New System.Windows.Forms.ComboBox()
        Me.Label2 = New System.Windows.Forms.Label()
        Me.Label3 = New System.Windows.Forms.Label()
        Me.Label4 = New System.Windows.Forms.Label()
        Me.Label5 = New System.Windows.Forms.Label()
        Me.Impostazioni1 = New System.Windows.Forms.TextBox()
        Me.Impostazioni2 = New System.Windows.Forms.TextBox()
        Me.Impostazioni3 = New System.Windows.Forms.TextBox()
        Me.Impostazioni4 = New System.Windows.Forms.TextBox()
        Me.ConfermaCmd = New System.Windows.Forms.Button()
        Me.AnnullaCmd = New System.Windows.Forms.Button()
        Me.SuspendLayout()
        '
        'Label1
        '
        Me.Label1.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label1.Location = New System.Drawing.Point(16, 16)
        Me.Label1.Name = "Label1"
        Me.Label1.Size = New System.Drawing.Size(136, 16)
        Me.Label1.TabIndex = 0
        Me.Label1.Text = "Tipo di database"
        '
        'TipoDbCmb
        '
        Me.TipoDbCmb.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList
        Me.TipoDbCmb.Items.AddRange(New Object() {"0 - Database ODBC", "1 - Database Microsoft Access", "2 - Database Microsoft SQL Server", "3 - Database MySQL", "4 - Database PostgreSQL"})
        Me.TipoDbCmb.Location = New System.Drawing.Point(152, 16)
        Me.TipoDbCmb.Name = "TipoDbCmb"
        Me.TipoDbCmb.Size = New System.Drawing.Size(240, 21)
        Me.TipoDbCmb.TabIndex = 1
        '
        'Label2
        '
        Me.Label2.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label2.Location = New System.Drawing.Point(24, 68)
        Me.Label2.Name = "Label2"
        Me.Label2.Size = New System.Drawing.Size(120, 16)
        Me.Label2.TabIndex = 2
        Me.Label2.Text = "Label2"
        Me.Label2.Visible = False
        '
        'Label3
        '
        Me.Label3.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label3.Location = New System.Drawing.Point(24, 100)
        Me.Label3.Name = "Label3"
        Me.Label3.Size = New System.Drawing.Size(120, 16)
        Me.Label3.TabIndex = 3
        Me.Label3.Text = "Label3"
        Me.Label3.Visible = False
        '
        'Label4
        '
        Me.Label4.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label4.Location = New System.Drawing.Point(24, 129)
        Me.Label4.Name = "Label4"
        Me.Label4.Size = New System.Drawing.Size(120, 16)
        Me.Label4.TabIndex = 4
        Me.Label4.Text = "Label4"
        Me.Label4.Visible = False
        '
        'Label5
        '
        Me.Label5.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label5.Location = New System.Drawing.Point(24, 161)
        Me.Label5.Name = "Label5"
        Me.Label5.Size = New System.Drawing.Size(120, 16)
        Me.Label5.TabIndex = 5
        Me.Label5.Text = "Label5"
        Me.Label5.Visible = False
        '
        'Impostazioni1
        '
        Me.Impostazioni1.Location = New System.Drawing.Point(152, 64)
        Me.Impostazioni1.Name = "Impostazioni1"
        Me.Impostazioni1.Size = New System.Drawing.Size(240, 20)
        Me.Impostazioni1.TabIndex = 2
        Me.Impostazioni1.Visible = False
        '
        'Impostazioni2
        '
        Me.Impostazioni2.Location = New System.Drawing.Point(152, 96)
        Me.Impostazioni2.Name = "Impostazioni2"
        Me.Impostazioni2.Size = New System.Drawing.Size(240, 20)
        Me.Impostazioni2.TabIndex = 3
        Me.Impostazioni2.Visible = False
        '
        'Impostazioni3
        '
        Me.Impostazioni3.Location = New System.Drawing.Point(152, 128)
        Me.Impostazioni3.Name = "Impostazioni3"
        Me.Impostazioni3.Size = New System.Drawing.Size(240, 20)
        Me.Impostazioni3.TabIndex = 4
        Me.Impostazioni3.Visible = False
        '
        'Impostazioni4
        '
        Me.Impostazioni4.Location = New System.Drawing.Point(152, 160)
        Me.Impostazioni4.Name = "Impostazioni4"
        Me.Impostazioni4.Size = New System.Drawing.Size(240, 20)
        Me.Impostazioni4.TabIndex = 5
        Me.Impostazioni4.Visible = False
        '
        'ConfermaCmd
        '
        Me.ConfermaCmd.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.ConfermaCmd.Location = New System.Drawing.Point(24, 208)
        Me.ConfermaCmd.Name = "ConfermaCmd"
        Me.ConfermaCmd.Size = New System.Drawing.Size(136, 23)
        Me.ConfermaCmd.TabIndex = 6
        Me.ConfermaCmd.Text = "Conferma"
        '
        'AnnullaCmd
        '
        Me.AnnullaCmd.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.AnnullaCmd.Location = New System.Drawing.Point(256, 208)
        Me.AnnullaCmd.Name = "AnnullaCmd"
        Me.AnnullaCmd.Size = New System.Drawing.Size(136, 23)
        Me.AnnullaCmd.TabIndex = 7
        Me.AnnullaCmd.Text = "Annulla"
        '
        'FrmImpostazioniDb
        '
        Me.AutoScaleBaseSize = New System.Drawing.Size(5, 13)
        Me.ClientSize = New System.Drawing.Size(519, 320)
        Me.Controls.Add(Me.AnnullaCmd)
        Me.Controls.Add(Me.ConfermaCmd)
        Me.Controls.Add(Me.Impostazioni4)
        Me.Controls.Add(Me.Impostazioni3)
        Me.Controls.Add(Me.Impostazioni2)
        Me.Controls.Add(Me.Impostazioni1)
        Me.Controls.Add(Me.Label5)
        Me.Controls.Add(Me.Label4)
        Me.Controls.Add(Me.Label3)
        Me.Controls.Add(Me.Label2)
        Me.Controls.Add(Me.TipoDbCmb)
        Me.Controls.Add(Me.Label1)
        Me.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedToolWindow
        Me.Name = "FrmImpostazioniDb"
        Me.Text = "Impostazioni Db"
        Me.ResumeLayout(False)
        Me.PerformLayout()

    End Sub

#End Region

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