Imports System.Data.SqlClient

Public Class DbParser

    Private TDB As String
    Private PathDb As String
    Private ServerDb As String
    Private NomeDb As String
    Private UidDb As String
    Private PwdDb As String
    Private ODBCName As String
    Private DbConn As Object
    Private ConnString As String
    Private MessaggioErrore As String
    Private RecInteressati As Int64
    Private LocalDA As Object
    Private LocalDS As New DataSet
    Private LocalCmd As Object
    Private Trans As Object
    Private InTransaction As Boolean
    Private LDateSep1 As String
    Private LDateSep2 As String
    Private LiLike As String
    Private LTop As String
    Private LLimit As String
    Private LStringConcat As String
    Private LToUpperCase As String
    Private DbConnOdbc As Data.Odbc.OdbcConnection
    Private LocalDAOdbc As New Data.Odbc.OdbcDataAdapter
    Private LocalCmdOdbc As New Data.Odbc.OdbcCommand
    Private DbConnOleDb As Data.OleDb.OleDbConnection
    Private LocalDAOleDb As New Data.OleDb.OleDbDataAdapter
    Private LocalCmdOleDb As New Data.OleDb.OleDbCommand
    Private DbConnSql As Data.SqlClient.SqlConnection
    Private LocalDASql As New Data.SqlClient.SqlDataAdapter
    Private LocalCmdSql As New Data.SqlClient.SqlCommand

    Public Property TipoDb() As String
        Get
            Return TDB
        End Get
        Set(ByVal NTipo As String)
            TDB = NTipo
        End Set
    End Property

    Public Property NomeODBC() As String
        Get
            Return ODBCName
        End Get
        Set(ByVal NODBC As String)
            ODBCName = NODBC
        End Set
    End Property

    Public Property FilePath() As String
        Get
            Return PathDb
        End Get
        Set(ByVal NPath As String)
            PathDb = NPath
        End Set
    End Property

    Public Property NomeServer() As String
        Get
            Return ServerDb
        End Get
        Set(ByVal NServer As String)
            ServerDb = NServer
        End Set
    End Property

    Public Property NomeDatabase() As String
        Get
            Return NomeDb
        End Get
        Set(ByVal NDb As String)
            NomeDb = NDb
        End Set
    End Property

    Public Property UseridAccesso() As String
        Get
            Return UidDb
        End Get
        Set(ByVal NUid As String)
            UidDb = NUid
        End Set
    End Property

    Public Property PwdAccesso() As String
        Get
            Return PwdDb
        End Get
        Set(ByVal NPwd As String)
            PwdDb = NPwd
        End Set
    End Property

    Public ReadOnly Property MsgErrore() As String
        Get
            Return MessaggioErrore
        End Get
    End Property

    Public ReadOnly Property RecordsInteressati() As Int64
        Get
            Return RecInteressati
        End Get
    End Property

    Public ReadOnly Property StringaConnessione() As String
        Get
            Return ConnString
        End Get
    End Property

    Public ReadOnly Property DateSep1() As String
        Get
            Return LDateSep1
        End Get
    End Property

    Public ReadOnly Property DateSep2() As String
        Get
            Return LDateSep2
        End Get
    End Property

    Public ReadOnly Property iLike() As String
        Get
            Return LiLike
        End Get
    End Property

    Public ReadOnly Property DbTop() As String
        Get
            Return LTop
        End Get
    End Property

    Public ReadOnly Property DbTop(ByVal NRec As Integer) As String
        Get
            If LTop <> "" Then
                Return LTop & " " & NRec
            Else
                Return ""
            End If
        End Get
    End Property

    Public ReadOnly Property DbLimit() As String
        Get
            Return LLimit
        End Get
    End Property

    Public ReadOnly Property DbLimit(ByVal NRec As Integer) As String
        Get
            Return LLimit & " " & NRec
        End Get
    End Property

    Public ReadOnly Property StringConcat() As String
        Get
            Return LStringConcat
        End Get
    End Property

    Public ReadOnly Property ToUpperCase() As String
        Get
            Return LToUpperCase
        End Get
    End Property

    Public Function ConcatStrings(ByRef vStrings() As String) As String
        Dim i As Integer
        Dim sSeparator As String = ""
        Dim sReturn As String = ""

        i = vStrings.Count
        If i > 0 Then
            For j = 0 To i - 1
                Select Case TDB
                    Case 1
                        sReturn = sReturn & sSeparator & vStrings(j)
                        sSeparator = " & "
                    Case 3
                        If sReturn = "" Then sReturn = "CONCAT("
                        sReturn = sReturn & sSeparator & vStrings(j)
                        sSeparator = ", "
                        If j = i - 1 Then sReturn = sReturn & ")"
                    Case 0, 2, 4
                        sReturn = sReturn & sSeparator & " " & vStrings(j)
                        sSeparator = "||"
                End Select
            Next
        End If
        Return sReturn
    End Function

    Public Function Connetti() As Boolean

        Try
            Select Case TDB
                Case "0"    'ODBC
                    ConnString = ODBCName
                    DbConnOdbc = New Data.Odbc.OdbcConnection(ConnString)
                    DbConn = DbConnOdbc
                    AddHandler LocalDAOdbc.RowUpdated, New System.Data.Odbc.OdbcRowUpdatedEventHandler(AddressOf OnRowUpdatedOdbc)
                    LocalDA = LocalDAOdbc
                    LocalCmd = LocalCmdOdbc
                    LDateSep1 = "'"
                    LDateSep2 = "'"
                    LiLike = "like"
                    LTop = ""
                    LLimit = "limit"
                    LStringConcat = " || "
                    LToUpperCase = ""
                Case "1"    'access
                    'ConnString = "Driver={Microsoft Access Driver (*.mdb)};Dbq=" + PathDb + ";" 'User Id=adminju
                    ConnString = "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=" + PathDb + ";Jet OLEDB:Database Password=" + PwdDb + ";" 'User Id=admin;"
                    'ConnString = "Provider=Microsoft.ACE.OLEDB.12.0;Data Source=" + PathDb + ";"
                    DbConnOleDb = New Data.OleDb.OleDbConnection(ConnString)
                    DbConn = DbConnOleDb
                    AddHandler LocalDAOleDb.RowUpdated, New System.Data.OleDb.OleDbRowUpdatedEventHandler(AddressOf OnRowUpdatedOledb)
                    LocalDA = LocalDAOleDb
                    LocalCmd = LocalCmdOleDb
                    LDateSep1 = "'"
                    LDateSep2 = "'"
                    LiLike = "like"
                    LTop = "top"
                    LLimit = ""
                    LStringConcat = " & "
                    LToUpperCase = "ucase"
                Case "2"    'SqlServer
                    ConnString = "Provider=sqloledb;Data Source=" + ServerDb + ",1433;Network Library=DBMSSOCN;Initial Catalog=" + NomeDb + ";User ID=" + UidDb + ";Password=" + PwdDb + ";"
                    DbConnSql = New Data.SqlClient.SqlConnection(ConnString)
                    DbConn = DbConnSql
                    AddHandler LocalDASql.RowUpdated, New System.Data.SqlClient.SqlRowUpdatedEventHandler(AddressOf OnRowUpdatedSql)
                    LocalDA = LocalDASql
                    LocalCmd = LocalCmdSql
                    LDateSep1 = "'"
                    LDateSep2 = "'"
                    LiLike = "like"
                    LTop = "top"
                    LLimit = ""
                    LStringConcat = " || "
                    LToUpperCase = "upper"
                Case "3"    'MySQL ODBC 3.51
                    'ConnString = "DRIVER={MySQL ODBC 3.51 Driver};SERVER=" + ServerDb + ";PORT=3306;DATABASE=" + NomeDb + "; USER=" + UidDb + ";PASSWORD=" + PwdDb + ";OPTION=3;"
                    'ConnString = "Provider=MSDASQL; DRIVER={MySQL ODBC 5.1 Driver};SERVER=" + ServerDb + ";PORT=3306;DATABASE=" + NomeDb + "; USER=" + UidDb + ";PASSWORD=" + PwdDb + ";OPTION=3;"
                    'ConnString = "Provider=MSDASQL; DRIVER={MySQL ODBC 8.2 Unicode Driver};SERVER=" + ServerDb + ";PORT=3306;DATABASE=" + NomeDb + "; USER=" + UidDb + ";PASSWORD=" + PwdDb + ";OPTION=3;"
                    ConnString = "Provider=MSDASQL; DRIVER={MySQL ODBC 9.3 Unicode Driver};SERVER=" + ServerDb + ";PORT=3306;DATABASE=" + NomeDb + "; USER=" + UidDb + ";PASSWORD=" + PwdDb + ";OPTION=3;"
                    DbConnOdbc = New Data.Odbc.OdbcConnection(ConnString)
                    DbConn = DbConnOdbc
                    AddHandler LocalDAOdbc.RowUpdated, New System.Data.Odbc.OdbcRowUpdatedEventHandler(AddressOf OnRowUpdatedOdbc)
                    LocalDA = LocalDAOdbc
                    LocalCmd = LocalCmdOdbc
                    LDateSep1 = "'"
                    LDateSep2 = "'"
                    LiLike = "like"
                    LTop = ""
                    LLimit = "limit"
                    LStringConcat = " "
                    LToUpperCase = "upper"
                Case "4"    'PostgreSQL
                    ConnString = "DRIVER={PostgreSQL Unicode};SERVER=" + ServerDb + ";port=5432;DATABASE=" + NomeDb + ";UID=" + UidDb + ";PWD=" + PwdDb + ";" 'SslMode=require;"
                    'ConnString = "DRIVER={PostgreSQL ODBC Driver(UNICODE)};SERVER=" + ServerDb + ";port=5432;DATABASE=" + NomeDb + ";UID=" + UidDb + ";PWD=" + PwdDb + ";" 'SslMode=require;"
                    'ConnString = "Provider=PostgreSQL OLE DB Provider;Data Source=" + ServerDb + ";location=" + NomeDb + ";User ID=" + UidDb + ";password=" + PwdDb + ";timeout=1000;"
                    DbConnOdbc = New Data.Odbc.OdbcConnection(ConnString)
                    DbConn = DbConnOdbc
                    AddHandler LocalDAOdbc.RowUpdated, New System.Data.Odbc.OdbcRowUpdatedEventHandler(AddressOf OnRowUpdatedOdbc)
                    LocalDA = LocalDAOdbc
                    LocalCmd = LocalCmdOdbc
                    LDateSep1 = "'"
                    LDateSep2 = "'"
                    LiLike = "ilike"
                    LTop = ""
                    LLimit = "limit"
                    LStringConcat = " || "
                    LToUpperCase = "upper"
            End Select

            DbConn.Open()
            LocalCmd.Connection = DbConn

            Return True
        Catch ex As Exception
            MessaggioErrore = "Impossibile connettersi al DB." & ControlChars.CrLf & "Errore restituito: " & ex.Message
            'MsgBox(ConnString)
            MsgBox(MessaggioErrore)
            Return False
        End Try
    End Function

    Private Shared Sub OnRowUpdated(ByVal sender As Object, ByVal args As SqlRowUpdatedEventArgs)
        If args.RecordsAffected = 0 Then
            args.Row.RowError = "Optimistic Concurrency Violation!"
            args.Status = UpdateStatus.SkipCurrentRow
        End If
    End Sub

    Private Shared Sub OnRowUpdatedOdbc(ByVal sender As Object, ByVal args As System.Data.Odbc.OdbcRowUpdatedEventArgs)
        If args.RecordsAffected = 0 Then
            args.Row.RowError = "Optimistic Concurrency Violation!"
            args.Status = UpdateStatus.SkipCurrentRow
        End If
    End Sub

    Private Shared Sub OnRowUpdatedOledb(ByVal sender As Object, ByVal args As System.Data.OleDb.OleDbRowUpdatedEventArgs)
        If args.RecordsAffected = 0 Then
            args.Row.RowError = "Optimistic Concurrency Violation!"
            args.Status = UpdateStatus.SkipCurrentRow
        End If
    End Sub

    Private Shared Sub OnRowUpdatedSql(ByVal sender As Object, ByVal args As System.Data.SqlClient.SqlRowUpdatedEventArgs)
        If args.RecordsAffected = 0 Then
            args.Row.RowError = "Optimistic Concurrency Violation!"
            args.Status = UpdateStatus.SkipCurrentRow
        End If
    End Sub

    Public Function EseguiSQL(ByVal StrQ As String, Optional ByRef Righe As Integer = 0) As Boolean
        If StrQ = "" Then
            MessaggioErrore = "Stringa query non valida!"
            Return False
            Exit Function
        End If
        Try
            LocalCmd.CommandText = StrQ
            RecInteressati = LocalCmd.ExecutenonQuery()
            Righe = RecInteressati
            Return True
        Catch ex As Exception
            If ex.Message = "ExecuteNonQuery richiede una oggetto Connection aperto e disponibile. Lo stato attuale della connessione è chiuso." Then
                If MsgBox("La connessione è stata interrotta." & vbCrLf & "Provare a riconnettersi?", MsgBoxStyle.Question + MsgBoxStyle.YesNo) = MsgBoxResult.Yes Then
                    Call Connetti()
                    Return EseguiSQL(StrQ)
                End If
            End If
            MessaggioErrore = "Errore nell'esecuzione del comando." & ControlChars.CrLf & "Errore restituito: " & ex.Message
            Dim paths As String
            paths = Application.UserAppDataPath & "\logamg.txt"
            Dim fw As New System.IO.StreamWriter(paths, True)
            fw.WriteLine(MessaggioErrore)
            fw.WriteLine(StrQ)
            fw.Close()
            Return False
        End Try
    End Function

    Public Function Estrai(ByVal StrQ As String, ByRef ExtDS As DataSet, ByVal NomeTab As String, ByVal Reimposta As Boolean) As Boolean
        Dim ECmd As New Object

        Try
            Select Case TDB
                Case "0", "3", "4"
                    ECmd = New Data.Odbc.OdbcCommand(StrQ, DbConn)
                Case "1"
                    ECmd = New Data.OleDb.OleDbCommand(StrQ, DbConn)
                Case "2"
                    ECmd = New Data.SqlClient.SqlCommand(StrQ, DbConn)
            End Select
            If InTransaction Then ECmd.Transaction = Trans
            LocalDA.SELECTcommand = ECmd
            If Reimposta Then   'azzera il dataset
                ExtDS = New DataSet("Elenco")
                ExtDS.Clear()
            End If
            LocalDA.fill(ExtDS, NomeTab)
            'ECmd.Dispose()
            Return True
        Catch ex As Exception
            If ex.Message = "ExecuteNonQuery richiede una oggetto Connection aperto e disponibile. Lo stato attuale della connessione è chiuso." Or ex.Message = "La connessione è stata disattivata" Then
                If MsgBox("La connessione è stata interrotta." & vbCrLf & "Provare a riconnettersi?", MsgBoxStyle.Question + MsgBoxStyle.YesNo) = MsgBoxResult.Yes Then
                    Call Connetti()
                    Return Estrai(StrQ, ExtDS, NomeTab, Reimposta)
                End If
            End If
            MessaggioErrore = "Impossibile estrarre i dati." & ControlChars.CrLf & "Errore restituito: " & ex.Message
            Dim paths As String
            paths = Application.UserAppDataPath & "\logamg.txt"
            Dim fw As New System.IO.StreamWriter(paths, True)
            fw.WriteLine(MessaggioErrore)
            fw.WriteLine(StrQ)
            fw.Close()
            Return False
        End Try

    End Function

    Public Function MaxPk(ByVal NomeTabella As String, ByVal NomeCampo As String) As Long
        Dim StrQ As String
        Dim LDSet As New DataSet
        Dim ValNull As DBNull

        ValNull = DBNull.Value

        StrQ = "SELECT max(" & NomeCampo & ") as maxpk FROM " & NomeTabella
        If Estrai(StrQ, LDSet, "estrazione", True) Then
            If LDSet.Tables("estrazione").Rows.Count > 0 Then
                If LDSet.Tables("estrazione").Rows(0).Item("maxpk").Equals(ValNull) Then
                    Return 1
                Else
                    Return LDSet.Tables("estrazione").Rows(0).Item("maxpk") + 1
                End If
            End If
        Else
            MsgBox(MessaggioErrore, MsgBoxStyle.Critical, "Errore in estrazione nuova Primary Key")
        End If

    End Function

    Public Sub BeginTrans()
        Try
            Trans = DbConn.BeginTransaction(System.Data.IsolationLevel.Unspecified)
        Catch ex As Exception
            If ex.Message = "ExecuteNonQuery richiede una oggetto Connection aperto e disponibile. Lo stato attuale della connessione è chiuso." Then
                If MsgBox("La connessione è stata interrotta." & vbCrLf & "Provare a riconnettersi?", MsgBoxStyle.Question + MsgBoxStyle.YesNo) = MsgBoxResult.Yes Then
                    Call Connetti()
                    DbConn.Dispose()
                    Trans = DbConn.BeginTransaction(System.Data.IsolationLevel.Serializable)
                End If
            End If
        End Try
        LocalCmd.Transaction = Trans
        InTransaction = True
    End Sub

    Public Sub RollBackTrans()
        Trans.RollBack()
        InTransaction = False
    End Sub

    Public Sub CommitTrans()
        Try
            Trans.Commit()
        Catch ex As Exception
        End Try
        InTransaction = False
    End Sub

    Public Sub New()
        InTransaction = False
    End Sub

    Public Sub ReturnTable(ByRef DtTable As DataTable, ByVal StrQ As String, ByVal TableName As String)
        Dim DSetRT As New DataSet
        Dim Indice As Integer

        If Estrai(StrQ, DSetRT, TableName, True) Then
            For Indice = 0 To DSetRT.Tables(TableName).Columns.Count - 1
                DtTable.Columns.Add(DSetRT.Tables(TableName).Columns(Indice).ColumnName)
            Next
        End If
    End Sub

    Public Function SubStringSql(ByVal NomeCampo As String, ByVal Partenza As Integer, ByVal Lunghezza As Integer)
        Dim Ritorno As String = ""

        Select Case TDB
            Case 0, 2, 3
                Ritorno = "substring(" & NomeCampo & ", " & Partenza & ", " & Lunghezza & ")"
            Case 1
                Ritorno = "mid(" & NomeCampo & ", " & Partenza & ", " & Lunghezza & ")"
            Case 4
                Ritorno = "substr(" & NomeCampo & ", " & Partenza & ", " & Lunghezza & ")"
        End Select
        Return Ritorno

    End Function

    Public Function CloseDb() As Boolean

        Select Case TDB
            Case "0"
            Case "1"
                Try
                    DbConn.Close()
                    DbConn = Nothing
                    Return True
                Catch ex As Exception
                    MessaggioErrore = ex.Message
                    Return False
                End Try
        End Select

    End Function

    Public Function BkpDbAccess() As Boolean
        Dim NuovoNome As String
        Dim FilesTarget As New System.Windows.Forms.SaveFileDialog

        Try
            NuovoNome = "BKP_" & Now.Year & Now.Month.ToString.PadLeft(2).Replace(" ", "0") & Now.Day.ToString.PadLeft(2).Replace(" ", "0")
            NuovoNome = NuovoNome & "_" & Now.Hour.ToString.PadLeft(2).Replace(" ", "0") & Now.Minute.ToString.PadLeft(2).Replace(" ", "0")
            NuovoNome = NuovoNome & Now.Second.ToString.PadLeft(2).Replace(" ", "0") & ".mdb"
            FilesTarget.FileName = NuovoNome
            FilesTarget.CheckFileExists = False
            If FilesTarget.ShowDialog = DialogResult.OK Then
                NuovoNome = System.IO.Path.GetDirectoryName(FilesTarget.FileName) & "\" & NuovoNome
                Try
                    System.IO.File.Copy(PathDb, NuovoNome)
                    Return True
                Catch ex As Exception
                    MessaggioErrore = ex.Message
                    Return False
                End Try
            End If
            Return True
        Catch ex As Exception
            MessaggioErrore = ex.Message
            Return False
        End Try

    End Function

End Class