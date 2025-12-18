Imports CrystalDecisions.CrystalReports.Engine
Imports CrystalDecisions.Shared
Public Class ViewReport

    Dim LTurno As String
    Dim LNumero As String
    Dim LReportName As String
    Dim LQueryFilter As String
    Dim ActualZoom As Integer
    Dim MaxZoom As Integer = 200
    Dim MinZoom As Integer = 50

    Private Sub ViewReport_Load(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles MyBase.Load

        Dim crConnectionInfo As New CrystalDecisions.Shared.ConnectionInfo()
        Dim CrTables As CrystalDecisions.CrystalReports.Engine.Tables
        Dim CrTable As CrystalDecisions.CrystalReports.Engine.Table
        Dim crTableLogoninfo As New CrystalDecisions.Shared.TableLogOnInfo()

        ActualZoom = 100
        'With crConnectionInfo
        '    .ServerName = "ecm"
        '    .DatabaseName = DbConnection.NomeDatabase
        '    .UserID = DbConnection.UseridAccesso
        '    .Password = DbConnection.PwdAccesso
        'End With

        'EcmRpt.Load("C:\Users\giovanni\Documents\Visual Studio 2008\Projects\EcmPresenze\EcmPresenze\bin\x86\Debug\reports\elencocorsi.rpt")
        Dim percorso As String
        percorso = Application.StartupPath & "\" & LReportName & ".rpt"
        AmgRpt.Load(percorso)
        AmgRpt.DataDefinition.FormulaFields(0).Text = "'" & LTurno & "'"
        AmgRpt.DataDefinition.FormulaFields(1).Text = "'" & LNumero & "'"
        'If IsXp Then
        '    EcmRpt.Load(Application.StartupPath & "\reports\" & LReportName & ".rpt")
        'Else
        '    EcmRpt.Load(Application.UserAppDataPath & "\reports\" & LReportName & ".rpt")
        'End If

        'CrTables = AmgRpt.Database.Tables

        'For Each CrTable In CrTables
        '    crTableLogoninfo = CrTable.LogOnInfo
        '    crTableLogoninfo.ConnectionInfo = crConnectionInfo
        '    CrTable.ApplyLogOnInfo(crTableLogoninfo)
        'Next

        System.Threading.Thread.Sleep(500)

        CrystalReport.ReportSource = AmgRpt
        CrystalReport.PrintReport()
        AmgRpt.Close()
        Me.Close()
        'CrystalReport.SelectionFormula = LQueryFilter
        'CrystalReport.DisplayToolbar = False

        'CrystalReportViewer1.ExportReport()
        'CrystalReportViewer1.Refresh()

        'Dim rpt As Object
        'e.Handled = True
        'AerreRpt.Refresh()
        'rpt = CrystalReportViewer1.ReportSource
        'CrystalReportViewer1.ReportSource = Nothing
        'CrystalReportViewer1.ParameterFieldInfo = rpt.ParameterFields
        'If rpt.ParameterFields.Count > 0 Then rpt.ParameterFields.RemoveAt(0)
        'rpt.ParameterFields.Add("aa", CrystalDecisions.Shared.ParameterValueKind.StringParameter, CrystalDecisions.Shared.DiscreteOrRangeKind.DiscreteValue, "")
        'rpt.Refresh()
        'CrystalReportViewer1.ReportSource = rpt
        'CrystalReportViewer1.Refresh()

    End Sub

    Public WriteOnly Property Numero() As String
        Set(ByVal Value As String)
            LNumero = Value
        End Set
    End Property

    Public WriteOnly Property Turno() As String
        Set(ByVal Value As String)
            LTurno = Value
        End Set
    End Property

    Public WriteOnly Property ReportName() As String
        Set(ByVal Value As String)
            LReportName = Value
        End Set
    End Property

    Public WriteOnly Property QueryFilter() As String
        Set(ByVal Value As String)
            LQueryFilter = Value
        End Set
    End Property

    Private Sub ViewReport_Resize(ByVal sender As Object, ByVal e As System.EventArgs) Handles Me.Resize
        CrystalReport.Width = Me.Width - 8
        CrystalReport.Height = Me.Height - 84
    End Sub

    Private Sub ViewReport_FormClosed(ByVal sender As Object, ByVal e As System.Windows.Forms.FormClosedEventArgs) Handles Me.FormClosed
        AmgRpt.Close()
    End Sub

    Private Sub CrystalReportViewer1_ReportRefresh(ByVal source As Object, ByVal e As CrystalDecisions.Windows.Forms.ViewerEventArgs) Handles CrystalReport.ReportRefresh
        'Dim rpt As Object
        'e.Handled = True
        'AerreRpt.Refresh()
        'AerreRpt.ParameterFields.RemoveAt(0)
        'AerreRpt.ParameterFields.Add("aa", CrystalDecisions.Shared.ParameterValueKind.StringParameter, CrystalDecisions.Shared.DiscreteOrRangeKind.DiscreteValue, "")
        'rpt = CrystalReportViewer1.ReportSource
        'CrystalReportViewer1.ReportSource = Nothing
        'CrystalReportViewer1.ParameterFieldInfo = rpt.ParameterFields
        'rpt.Refresh()
        'CrystalReportViewer1.ReportSource = rpt
    End Sub


    Private Sub PrintButton_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)

        CrystalReport.PrintReport()

    End Sub

    Private Sub ExportButton_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)
        Dim PathENome As String

        PdfDialog.ShowDialog()

        If PdfDialog.FileName <> "" Then
            PathENome = PdfDialog.FileName
            AmgRpt.ExportToDisk(ExportFormatType.PortableDocFormat, PathENome)
        End If

    End Sub

    Private Sub PrimaButton_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)

        CrystalReport.ShowPreviousPage()

    End Sub

    Private Sub UltimaButton_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)

        CrystalReport.ShowNextPage()

    End Sub

    Private Sub ZoomOut_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)

        If ActualZoom > MinZoom Then ActualZoom = ActualZoom - 10
        CrystalReport.Zoom(ActualZoom)

    End Sub

    Private Sub ZoomIn_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)

        If ActualZoom < MaxZoom Then ActualZoom = ActualZoom + 10
        CrystalReport.Zoom(ActualZoom)

    End Sub
End Class