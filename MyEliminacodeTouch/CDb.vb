Public Class CDb

    Public Structure Tdb
        Dim TipoDb As String
        Dim NomeODBC As String
        Dim PathAccess As String
        Dim NomeServer As String
        Dim Uid As String
        Dim Pwd As String
        Dim NomeDb As String
    End Structure

    Dim StructDb As Tdb

    Public Property DatiDb() As Tdb
        Get
            Return StructDb
        End Get
        Set(ByVal Value As Tdb)
            StructDb = Value
        End Set
    End Property

    Public Sub New()
        Dim LReg As New Registro

        StructDb.TipoDb = LReg.Leggi("database", "tipo")
        StructDb.NomeODBC = LReg.Leggi("database", "nomeodbc")
        StructDb.PathAccess = LReg.Leggi("database", "pathaccess")
        StructDb.NomeServer = LReg.Leggi("database", "nomeserver")
        StructDb.NomeDb = LReg.Leggi("database", "nomedb")
        StructDb.Uid = LReg.Leggi("database", "uid")
        StructDb.Pwd = LReg.Leggi("database", "pwd")

    End Sub

    Public Sub scrivi()
        Dim LReg As New Registro

        LReg.Scrivi("database", "tipo", StructDb.TipoDb)
        LReg.Scrivi("database", "nomeodbc", StructDb.NomeODBC)
        LReg.Scrivi("database", "pathaccess", StructDb.PathAccess)
        LReg.Scrivi("database", "nomeserver", StructDb.NomeServer)
        LReg.Scrivi("database", "nomedb", StructDb.NomeDb)
        LReg.Scrivi("database", "uid", StructDb.Uid)
        LReg.Scrivi("database", "pwd", StructDb.Pwd)

    End Sub
End Class