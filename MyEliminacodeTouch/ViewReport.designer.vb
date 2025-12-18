<Global.Microsoft.VisualBasic.CompilerServices.DesignerGenerated()> _
Partial Class ViewReport
    Inherits System.Windows.Forms.Form

    'Form esegue l'override del metodo Dispose per pulire l'elenco dei componenti.
    <System.Diagnostics.DebuggerNonUserCode()> _
    Protected Overrides Sub Dispose(ByVal disposing As Boolean)
        If disposing AndAlso components IsNot Nothing Then
            components.Dispose()
        End If
        MyBase.Dispose(disposing)
    End Sub

    'Richiesto da Progettazione Windows Form
    Private components As System.ComponentModel.IContainer

    'NOTA: la procedura che segue è richiesta da Progettazione Windows Form
    'Può essere modificata in Progettazione Windows Form.  
    'Non modificarla nell'editor del codice.
    <System.Diagnostics.DebuggerStepThrough()> _
    Private Sub InitializeComponent()
        Me.CrystalReport = New CrystalDecisions.Windows.Forms.CrystalReportViewer
        Me.PdfDialog = New System.Windows.Forms.SaveFileDialog
        Me.SuspendLayout()
        '
        'CrystalReport
        '
        Me.CrystalReport.ActiveViewIndex = -1
        Me.CrystalReport.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle
        Me.CrystalReport.DisplayGroupTree = False
        Me.CrystalReport.Location = New System.Drawing.Point(0, 0)
        Me.CrystalReport.Name = "CrystalReport"
        Me.CrystalReport.SelectionFormula = ""
        Me.CrystalReport.Size = New System.Drawing.Size(592, 366)
        Me.CrystalReport.TabIndex = 16
        Me.CrystalReport.ViewTimeSelectionFormula = ""
        '
        'PdfDialog
        '
        Me.PdfDialog.Filter = "File Pdf|*.pdf"
        '
        'ViewReport
        '
        Me.AutoScaleDimensions = New System.Drawing.SizeF(6.0!, 13.0!)
        Me.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font
        Me.ClientSize = New System.Drawing.Size(763, 431)
        Me.Controls.Add(Me.CrystalReport)
        Me.Name = "ViewReport"
        Me.Text = "Report"
        Me.WindowState = System.Windows.Forms.FormWindowState.Maximized
        Me.ResumeLayout(False)

    End Sub
    Private WithEvents CrystalReport As CrystalDecisions.Windows.Forms.CrystalReportViewer
    Friend WithEvents PdfDialog As System.Windows.Forms.SaveFileDialog
End Class
