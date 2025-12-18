<Global.Microsoft.VisualBasic.CompilerServices.DesignerGenerated()>
Partial Class TouchForm2
    Inherits System.Windows.Forms.Form

    'Form esegue l'override del metodo Dispose per pulire l'elenco dei componenti.
    <System.Diagnostics.DebuggerNonUserCode()>
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
    <System.Diagnostics.DebuggerStepThrough()>
    Private Sub InitializeComponent()
        Me.components = New System.ComponentModel.Container()
        Me.LblErrore = New System.Windows.Forms.Label()
        Me.Timer1 = New System.Windows.Forms.Timer(Me.components)
        Me.LblConta = New System.Windows.Forms.Label()
        Me.LblGiorni = New System.Windows.Forms.Label()
        Me.Timer2 = New System.Windows.Forms.Timer(Me.components)
        Me.Timer3 = New System.Windows.Forms.Timer(Me.components)
        Me.SuspendLayout()
        '
        'LblErrore
        '
        Me.LblErrore.Font = New System.Drawing.Font("Microsoft Sans Serif", 72.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.LblErrore.ForeColor = System.Drawing.Color.Red
        Me.LblErrore.Location = New System.Drawing.Point(740, 9)
        Me.LblErrore.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.LblErrore.Name = "LblErrore"
        Me.LblErrore.Size = New System.Drawing.Size(168, 86)
        Me.LblErrore.TabIndex = 0
        Me.LblErrore.Text = "Label1"
        Me.LblErrore.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        Me.LblErrore.Visible = False
        '
        'Timer1
        '
        Me.Timer1.Enabled = True
        Me.Timer1.Interval = 1000
        '
        'LblConta
        '
        Me.LblConta.Font = New System.Drawing.Font("Microsoft Sans Serif", 72.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.LblConta.ForeColor = System.Drawing.Color.Red
        Me.LblConta.Location = New System.Drawing.Point(531, 9)
        Me.LblConta.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.LblConta.Name = "LblConta"
        Me.LblConta.Size = New System.Drawing.Size(183, 77)
        Me.LblConta.TabIndex = 1
        Me.LblConta.Text = "Label1"
        Me.LblConta.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        Me.LblConta.Visible = False
        '
        'LblGiorni
        '
        Me.LblGiorni.Font = New System.Drawing.Font("Microsoft Sans Serif", 26.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.LblGiorni.ForeColor = System.Drawing.Color.Red
        Me.LblGiorni.Location = New System.Drawing.Point(929, 24)
        Me.LblGiorni.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.LblGiorni.Name = "LblGiorni"
        Me.LblGiorni.Size = New System.Drawing.Size(125, 64)
        Me.LblGiorni.TabIndex = 2
        Me.LblGiorni.Text = "Label1"
        Me.LblGiorni.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        Me.LblGiorni.Visible = False
        '
        'Timer2
        '
        Me.Timer2.Interval = 5000
        '
        'Timer3
        '
        Me.Timer3.Enabled = True
        Me.Timer3.Interval = 60000
        '
        'TouchForm2
        '
        Me.AutoScaleDimensions = New System.Drawing.SizeF(8.0!, 16.0!)
        Me.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font
        Me.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.ClientSize = New System.Drawing.Size(1067, 554)
        Me.ControlBox = False
        Me.Controls.Add(Me.LblGiorni)
        Me.Controls.Add(Me.LblConta)
        Me.Controls.Add(Me.LblErrore)
        Me.FormBorderStyle = System.Windows.Forms.FormBorderStyle.None
        Me.Margin = New System.Windows.Forms.Padding(4)
        Me.Name = "TouchForm2"
        Me.Text = "TouchForm2"
        Me.WindowState = System.Windows.Forms.FormWindowState.Maximized
        Me.ResumeLayout(False)

    End Sub

    Friend WithEvents LblErrore As Label
    Friend WithEvents Timer1 As Timer
    Friend WithEvents LblConta As Label
    Friend WithEvents LblGiorni As Label
    Friend WithEvents Timer2 As Timer
    Friend WithEvents Timer3 As Timer
End Class
