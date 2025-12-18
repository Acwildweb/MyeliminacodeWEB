<Global.Microsoft.VisualBasic.CompilerServices.DesignerGenerated()> _
Partial Class ImpostazioniDb
    Inherits System.Windows.Forms.Form

    'Form esegue l'override del metodo Dispose per pulire l'elenco dei componenti.
    <System.Diagnostics.DebuggerNonUserCode()> _
    Protected Overrides Sub Dispose(ByVal disposing As Boolean)
        Try
            If disposing AndAlso components IsNot Nothing Then
                components.Dispose()
            End If
        Finally
            MyBase.Dispose(disposing)
        End Try
    End Sub

    'Richiesto da Progettazione Windows Form
    Private components As System.ComponentModel.IContainer

    'NOTA: la procedura che segue è richiesta da Progettazione Windows Form
    'Può essere modificata in Progettazione Windows Form.  
    'Non modificarla mediante l'editor del codice.
    <System.Diagnostics.DebuggerStepThrough()> _
    Private Sub InitializeComponent()
        Me.AnnullaCmd = New System.Windows.Forms.Button()
        Me.ConfermaCmd = New System.Windows.Forms.Button()
        Me.Impostazioni4 = New System.Windows.Forms.TextBox()
        Me.Impostazioni3 = New System.Windows.Forms.TextBox()
        Me.Impostazioni2 = New System.Windows.Forms.TextBox()
        Me.Impostazioni1 = New System.Windows.Forms.TextBox()
        Me.Label5 = New System.Windows.Forms.Label()
        Me.Label4 = New System.Windows.Forms.Label()
        Me.Label3 = New System.Windows.Forms.Label()
        Me.Label2 = New System.Windows.Forms.Label()
        Me.TipoDbCmb = New System.Windows.Forms.ComboBox()
        Me.Label1 = New System.Windows.Forms.Label()
        Me.SuspendLayout()
        '
        'AnnullaCmd
        '
        Me.AnnullaCmd.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.AnnullaCmd.Location = New System.Drawing.Point(405, 290)
        Me.AnnullaCmd.Name = "AnnullaCmd"
        Me.AnnullaCmd.Size = New System.Drawing.Size(217, 34)
        Me.AnnullaCmd.TabIndex = 19
        Me.AnnullaCmd.Text = "Annulla"
        '
        'ConfermaCmd
        '
        Me.ConfermaCmd.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.ConfermaCmd.Location = New System.Drawing.Point(33, 290)
        Me.ConfermaCmd.Name = "ConfermaCmd"
        Me.ConfermaCmd.Size = New System.Drawing.Size(218, 34)
        Me.ConfermaCmd.TabIndex = 18
        Me.ConfermaCmd.Text = "Conferma"
        '
        'Impostazioni4
        '
        Me.Impostazioni4.Location = New System.Drawing.Point(238, 220)
        Me.Impostazioni4.Name = "Impostazioni4"
        Me.Impostazioni4.Size = New System.Drawing.Size(384, 20)
        Me.Impostazioni4.TabIndex = 16
        Me.Impostazioni4.Visible = False
        '
        'Impostazioni3
        '
        Me.Impostazioni3.Location = New System.Drawing.Point(238, 173)
        Me.Impostazioni3.Name = "Impostazioni3"
        Me.Impostazioni3.Size = New System.Drawing.Size(384, 20)
        Me.Impostazioni3.TabIndex = 14
        Me.Impostazioni3.Visible = False
        '
        'Impostazioni2
        '
        Me.Impostazioni2.Location = New System.Drawing.Point(238, 126)
        Me.Impostazioni2.Name = "Impostazioni2"
        Me.Impostazioni2.Size = New System.Drawing.Size(384, 20)
        Me.Impostazioni2.TabIndex = 12
        Me.Impostazioni2.Visible = False
        '
        'Impostazioni1
        '
        Me.Impostazioni1.Location = New System.Drawing.Point(238, 80)
        Me.Impostazioni1.Name = "Impostazioni1"
        Me.Impostazioni1.Size = New System.Drawing.Size(384, 20)
        Me.Impostazioni1.TabIndex = 10
        Me.Impostazioni1.Visible = False
        '
        'Label5
        '
        Me.Label5.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label5.Location = New System.Drawing.Point(33, 221)
        Me.Label5.Name = "Label5"
        Me.Label5.Size = New System.Drawing.Size(192, 24)
        Me.Label5.TabIndex = 17
        Me.Label5.Text = "Label5"
        Me.Label5.Visible = False
        '
        'Label4
        '
        Me.Label4.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label4.Location = New System.Drawing.Point(33, 175)
        Me.Label4.Name = "Label4"
        Me.Label4.Size = New System.Drawing.Size(192, 23)
        Me.Label4.TabIndex = 15
        Me.Label4.Text = "Label4"
        Me.Label4.Visible = False
        '
        'Label3
        '
        Me.Label3.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label3.Location = New System.Drawing.Point(33, 132)
        Me.Label3.Name = "Label3"
        Me.Label3.Size = New System.Drawing.Size(192, 24)
        Me.Label3.TabIndex = 13
        Me.Label3.Text = "Label3"
        Me.Label3.Visible = False
        '
        'Label2
        '
        Me.Label2.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label2.Location = New System.Drawing.Point(33, 85)
        Me.Label2.Name = "Label2"
        Me.Label2.Size = New System.Drawing.Size(192, 24)
        Me.Label2.TabIndex = 11
        Me.Label2.Text = "Label2"
        Me.Label2.Visible = False
        '
        'TipoDbCmb
        '
        Me.TipoDbCmb.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList
        Me.TipoDbCmb.Items.AddRange(New Object() {"0 - Database ODBC", "1 - Database Microsoft Access", "2 - Database Microsoft SQL Server", "3 - Database MySQL", "4 - Database PostgreSQL"})
        Me.TipoDbCmb.Location = New System.Drawing.Point(238, 9)
        Me.TipoDbCmb.Name = "TipoDbCmb"
        Me.TipoDbCmb.Size = New System.Drawing.Size(384, 21)
        Me.TipoDbCmb.TabIndex = 9
        '
        'Label1
        '
        Me.Label1.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label1.Location = New System.Drawing.Point(21, 9)
        Me.Label1.Name = "Label1"
        Me.Label1.Size = New System.Drawing.Size(217, 24)
        Me.Label1.TabIndex = 8
        Me.Label1.Text = "Tipo di database"
        '
        'ImpostazioniDb
        '
        Me.AutoScaleDimensions = New System.Drawing.SizeF(6.0!, 13.0!)
        Me.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font
        Me.ClientSize = New System.Drawing.Size(649, 342)
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
        Me.MaximizeBox = False
        Me.MinimizeBox = False
        Me.Name = "ImpostazioniDb"
        Me.Text = "ImpostazioniDb"
        Me.ResumeLayout(False)
        Me.PerformLayout()

    End Sub

    Friend WithEvents AnnullaCmd As Button
    Friend WithEvents ConfermaCmd As Button
    Friend WithEvents Impostazioni4 As TextBox
    Friend WithEvents Impostazioni3 As TextBox
    Friend WithEvents Impostazioni2 As TextBox
    Friend WithEvents Impostazioni1 As TextBox
    Friend WithEvents Label5 As Label
    Friend WithEvents Label4 As Label
    Friend WithEvents Label3 As Label
    Friend WithEvents Label2 As Label
    Friend WithEvents TipoDbCmb As ComboBox
    Friend WithEvents Label1 As Label
End Class
