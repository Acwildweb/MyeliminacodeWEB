<Global.Microsoft.VisualBasic.CompilerServices.DesignerGenerated()> _
Partial Class Client
    Inherits System.Windows.Forms.Form

    'Form overrides dispose to clean up the component list.
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

    'Required by the Windows Form Designer
    Private components As System.ComponentModel.IContainer

    'NOTE: The following procedure is required by the Windows Form Designer
    'It can be modified using the Windows Form Designer.  
    'Do not modify it using the code editor.
    <System.Diagnostics.DebuggerStepThrough()> _
    Private Sub InitializeComponent()
        Me.Button2 = New System.Windows.Forms.Button
        Me.TxtMessaggio = New System.Windows.Forms.TextBox
        Me.TxtTesto = New System.Windows.Forms.TextBox
        Me.GrpDatiServer = New System.Windows.Forms.GroupBox
        Me.Label2 = New System.Windows.Forms.Label
        Me.Label1 = New System.Windows.Forms.Label
        Me.TxtPort = New System.Windows.Forms.TextBox
        Me.TxtServer = New System.Windows.Forms.TextBox
        Me.Button1 = New System.Windows.Forms.Button
        Me.GroupBox1 = New System.Windows.Forms.GroupBox
        Me.StatoLbl = New System.Windows.Forms.Label
        Me.RegistraCmd = New System.Windows.Forms.Button
        Me.GrpDatiServer.SuspendLayout()
        Me.GroupBox1.SuspendLayout()
        Me.SuspendLayout()
        '
        'Button2
        '
        Me.Button2.Location = New System.Drawing.Point(14, 230)
        Me.Button2.Name = "Button2"
        Me.Button2.Size = New System.Drawing.Size(87, 23)
        Me.Button2.TabIndex = 1
        Me.Button2.Text = "Send"
        Me.Button2.UseVisualStyleBackColor = True
        '
        'TxtMessaggio
        '
        Me.TxtMessaggio.Location = New System.Drawing.Point(108, 233)
        Me.TxtMessaggio.Name = "TxtMessaggio"
        Me.TxtMessaggio.Size = New System.Drawing.Size(233, 20)
        Me.TxtMessaggio.TabIndex = 4
        '
        'TxtTesto
        '
        Me.TxtTesto.Location = New System.Drawing.Point(363, 218)
        Me.TxtTesto.Multiline = True
        Me.TxtTesto.Name = "TxtTesto"
        Me.TxtTesto.Size = New System.Drawing.Size(327, 65)
        Me.TxtTesto.TabIndex = 5
        '
        'GrpDatiServer
        '
        Me.GrpDatiServer.Controls.Add(Me.Label2)
        Me.GrpDatiServer.Controls.Add(Me.Label1)
        Me.GrpDatiServer.Controls.Add(Me.TxtPort)
        Me.GrpDatiServer.Controls.Add(Me.TxtServer)
        Me.GrpDatiServer.Controls.Add(Me.Button1)
        Me.GrpDatiServer.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.GrpDatiServer.Location = New System.Drawing.Point(12, 11)
        Me.GrpDatiServer.Name = "GrpDatiServer"
        Me.GrpDatiServer.Size = New System.Drawing.Size(407, 80)
        Me.GrpDatiServer.TabIndex = 6
        Me.GrpDatiServer.TabStop = False
        Me.GrpDatiServer.Text = "Dati server"
        '
        'Label2
        '
        Me.Label2.AutoSize = True
        Me.Label2.Location = New System.Drawing.Point(161, 28)
        Me.Label2.Name = "Label2"
        Me.Label2.Size = New System.Drawing.Size(37, 13)
        Me.Label2.TabIndex = 8
        Me.Label2.Text = "Porta"
        '
        'Label1
        '
        Me.Label1.AutoSize = True
        Me.Label1.Location = New System.Drawing.Point(3, 28)
        Me.Label1.Name = "Label1"
        Me.Label1.Size = New System.Drawing.Size(60, 13)
        Me.Label1.TabIndex = 7
        Me.Label1.Text = "IP Server"
        '
        'TxtPort
        '
        Me.TxtPort.Location = New System.Drawing.Point(164, 47)
        Me.TxtPort.Name = "TxtPort"
        Me.TxtPort.Size = New System.Drawing.Size(59, 20)
        Me.TxtPort.TabIndex = 6
        Me.TxtPort.Text = "3460"
        '
        'TxtServer
        '
        Me.TxtServer.Location = New System.Drawing.Point(7, 47)
        Me.TxtServer.Name = "TxtServer"
        Me.TxtServer.Size = New System.Drawing.Size(139, 20)
        Me.TxtServer.TabIndex = 5
        Me.TxtServer.Text = "127.0.0.1"
        '
        'Button1
        '
        Me.Button1.Location = New System.Drawing.Point(252, 45)
        Me.Button1.Name = "Button1"
        Me.Button1.Size = New System.Drawing.Size(133, 23)
        Me.Button1.TabIndex = 4
        Me.Button1.Text = "Connetti"
        Me.Button1.UseVisualStyleBackColor = True
        '
        'GroupBox1
        '
        Me.GroupBox1.Controls.Add(Me.StatoLbl)
        Me.GroupBox1.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.GroupBox1.Location = New System.Drawing.Point(437, 16)
        Me.GroupBox1.Name = "GroupBox1"
        Me.GroupBox1.Size = New System.Drawing.Size(252, 74)
        Me.GroupBox1.TabIndex = 7
        Me.GroupBox1.TabStop = False
        Me.GroupBox1.Text = "Stato server"
        '
        'StatoLbl
        '
        Me.StatoLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 12.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.StatoLbl.Location = New System.Drawing.Point(29, 23)
        Me.StatoLbl.Name = "StatoLbl"
        Me.StatoLbl.Size = New System.Drawing.Size(196, 36)
        Me.StatoLbl.TabIndex = 0
        Me.StatoLbl.Text = "NON CONNESSO"
        Me.StatoLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'RegistraCmd
        '
        Me.RegistraCmd.Enabled = False
        Me.RegistraCmd.Location = New System.Drawing.Point(12, 113)
        Me.RegistraCmd.Name = "RegistraCmd"
        Me.RegistraCmd.Size = New System.Drawing.Size(351, 33)
        Me.RegistraCmd.TabIndex = 8
        Me.RegistraCmd.Text = "Registra le informazioni e inizia il servizio"
        Me.RegistraCmd.UseVisualStyleBackColor = True
        '
        'Client
        '
        Me.AutoScaleDimensions = New System.Drawing.SizeF(7.0!, 13.0!)
        Me.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font
        Me.ClientSize = New System.Drawing.Size(719, 158)
        Me.Controls.Add(Me.RegistraCmd)
        Me.Controls.Add(Me.GroupBox1)
        Me.Controls.Add(Me.GrpDatiServer)
        Me.Controls.Add(Me.TxtTesto)
        Me.Controls.Add(Me.TxtMessaggio)
        Me.Controls.Add(Me.Button2)
        Me.Font = New System.Drawing.Font("Microsoft Sans Serif", 8.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Name = "Client"
        Me.Text = "Client"
        Me.GrpDatiServer.ResumeLayout(False)
        Me.GrpDatiServer.PerformLayout()
        Me.GroupBox1.ResumeLayout(False)
        Me.ResumeLayout(False)
        Me.PerformLayout()

    End Sub
    Friend WithEvents Button2 As System.Windows.Forms.Button
    Friend WithEvents TxtMessaggio As System.Windows.Forms.TextBox
    Friend WithEvents TxtTesto As System.Windows.Forms.TextBox
    Friend WithEvents GrpDatiServer As System.Windows.Forms.GroupBox
    Friend WithEvents TxtPort As System.Windows.Forms.TextBox
    Friend WithEvents TxtServer As System.Windows.Forms.TextBox
    Friend WithEvents Button1 As System.Windows.Forms.Button
    Friend WithEvents Label2 As System.Windows.Forms.Label
    Friend WithEvents Label1 As System.Windows.Forms.Label
    Friend WithEvents GroupBox1 As System.Windows.Forms.GroupBox
    Friend WithEvents StatoLbl As System.Windows.Forms.Label
    Friend WithEvents RegistraCmd As System.Windows.Forms.Button

End Class
