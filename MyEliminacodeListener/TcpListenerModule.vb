Imports System.Net.Sockets
Imports System.Net
Imports System.IO
Imports MyEliminacodeListener.getJson

Module TcpListenerModule
    Public listener As New System.Threading.Thread(AddressOf listen)
    Public clients As New Hashtable 'new database (hashtable) to hold the clients
    Public Sub recieved(ByVal msg As String, ByVal client As ConnectedClient)

        If IsNothing(msg) Then
            Exit Sub
        End If

        Dim message() As String = msg.Split("|") 'make an array with elements of the message recieved
        Dim StrQ As String
        Dim lDset As New DataSet
        Dim file As System.IO.StreamWriter

        Select Case message(0) 'process by the first element in the array
            Case "CHAT" 'if it's CHAT
                'TextBox3.Text &= client.name & " says: " & " " & message(1) & vbNewLine 'add the message to the chatbox
                sendallbutone(message(1), client.name) 'this will update all clients with the new message
                '                                       and it will not send the message to the client it recieved it from :)
            Case "LOGIN" 'A client has connected
                clients.Add(client, client.name) 'add the client to our database (a hashtable)
                'ListBox1.Items.Add(client.name) 'add the client to the listbox to display the new user
            Case "NEW"
                Dim ilturno As String
                Dim nextnumero As Integer = 0
                Dim idturno As Integer
                Dim Invia As Boolean = True

                ilturno = message(1)

                StrQ = "select id_turno from turni where turno='" & ilturno & "'"
                If DbConnection.Estrai(StrQ, lDset, "coda", True) Then
                    idturno = lDset.Tables("coda").Rows(0).Item("id_turno")
                End If

                If idturno > 0 Then
                    Dim tAttivo As String = ""
                    tAttivo = TurnoAttivo(idturno)
                    If tAttivo <> "OK" Then
                        Invia = False
                        sendsingle("CHIUS|" & tAttivo & "|", client.name)
                    End If
                End If

                If Invia Then
                    StrQ = "select max(numero) as maxcoda from contatori c inner join turni t on c.id_turno=t.id_turno where "
                    StrQ = StrQ & "t.turno='" & ilturno & "' and c.tipo='CODA'"
                    If DbConnection.Estrai(StrQ, lDset, "coda", True) Then
                        If lDset.Tables("coda").Rows.Count > 0 Then
                            If IsDBNull(lDset.Tables("coda").Rows(0).Item(0)) Then
                                Invia = False
                                nextnumero = 1
                                StrQ = "insert into contatori (tipo, id_turno, id_postazione, numero, data) values "
                                StrQ = StrQ & "('CODA', " & idturno & ", 0, " & nextnumero & ", '" & D2S(Now.Year, Now.Month, Now.Day) & "')"
                                StrQ = ""
                            Else
                                nextnumero = lDset.Tables("coda").Rows(0).Item(0) + 1
                                If nextnumero > 999 Then nextnumero = 0
                                Dim DsetControllo As New DataSet
                                StrQ = "select * from contatori where numero=" & nextnumero & " and data='" & D2S(Now.Year, Now.Month, Now.Day) & "' "
                                StrQ = StrQ & "and tipo='CODA' and id_turno=" & idturno
                                If DbConnection.Estrai(StrQ, DsetControllo, "controllo", True) Then
                                    If DsetControllo.Tables("controllo").Rows.Count = 0 Then
                                        StrQ = "update contatori set numero=" & nextnumero & ", data='" & D2S(Now.Year, Now.Month, Now.Day) & "' "
                                        StrQ = StrQ & "where tipo='CODA' and id_turno=" & idturno
                                        If Not DbConnection.EseguiSQL(StrQ) Then
                                            Invia = False
                                            'nextnumero += 1
                                            'StrQ = "update contatori set numero=" & nextnumero & ", data='" & D2S(Now.Year, Now.Month, Now.Day) & "' "
                                            'StrQ = StrQ & "where tipo='CODA' and id_turno=" & idturno
                                            'DbConnection.EseguiSQL(StrQ)
                                        Else
                                            sendsingle("PRINT|" & ilturno & "|" & nextnumero, client.name)
                                        End If
                                    End If
                                End If
                            End If
                        Else
                            Invia = False
                            'nextnumero = 1
                            'StrQ = "insert into contatori (tipo, id_turno, id_postazione, numero, data) values "
                            'StrQ = StrQ & "('CODA', " & idturno & ", 0, " & nextnumero & ", '" & D2S(Now.Year, Now.Month, Now.Day) & "')"
                            'StrQ = ""
                        End If
                    End If
                End If


            Case "NEXT", "CISONO"
                Dim Sportello As String
                Dim LDsetT As New DataSet
                Dim LDsetT2 As New DataSet
                Dim UNumero As Integer = 0
                Dim UChiamato As Integer = 0
                Dim Chiamato As Integer
                Dim StrQ2 As String
                Dim LettTurno As String = ""
                Dim ETurni As String = ""
                Dim SepTurni As String = ""
                Dim SepValori As String = "£"
                Dim TurnoChiamato As Integer
                Dim InCoda As Boolean = False
                Dim sTurno As String = ""

                'StrQ = "delete from coda where id_turno in (select id_turno from turni where stato='0')"
                'DbConnection.EseguiSQL(StrQ)
                'StrQ = "update contatori set numero=0 where id_turno in (select id_turno from turni where stato='0')"
                'DbConnection.EseguiSQL(StrQ)
                Sportello = message(1)
                If message.Count > 2 Then
                    sTurno = message(2)
                End If
                StrQ = "select distinct ot.id_turno from (((postazioni p inner join operazioni_postazioni op on p.id_postazione = op.id_postazione) "
                StrQ = StrQ & "inner join operazioni_turni ot on op.id_operazione = ot.id_operazione) inner join turni t "
                StrQ = StrQ & "on ot.id_turno=t.id_turno) "
                StrQ = StrQ & "where p.ID_postazione = " & Sportello & " and t.stato='A' "
                If sTurno <> "" Then
                    StrQ = StrQ & "and t.turno = '" & sTurno & "'"
                End If
                If DbConnection.Estrai(StrQ, lDset, "numero", True) Then
                    If lDset.Tables("numero").Rows.Count > 0 Then
                        If lDset.Tables("numero").Rows.Count = 1 Then
                            StrQ = "select * from contatori where tipo='CODA' and id_turno=" & lDset.Tables("numero").Rows(0).Item("id_turno")
                            If DbConnection.Estrai(StrQ, LDsetT, "turno", True) Then
                                If LDsetT.Tables("turno").Rows.Count > 0 Then
                                    UNumero = LDsetT.Tables("turno").Rows(0).Item("numero")
                                End If
                            End If
                            StrQ = "select * from contatori where tipo='NUMERO' and id_turno=" & lDset.Tables("numero").Rows(0).Item("id_turno")
                            If DbConnection.Estrai(StrQ, LDsetT, "turno", True) Then
                                If LDsetT.Tables("turno").Rows.Count > 0 Then
                                    UChiamato = LDsetT.Tables("turno").Rows(0).Item("numero")
                                End If
                            End If
                            If UNumero > UChiamato Or (UNumero < UChiamato And UNumero < 100 And UChiamato > 900) Then
                                StrQ = "select * from turni where id_turno=" & LDsetT.Tables("turno").Rows(0).Item("id_turno")
                                If DbConnection.Estrai(StrQ, LDsetT2, "turno", True) Then
                                    If LDsetT2.Tables("turno").Rows.Count > 0 Then
                                        LettTurno = LDsetT2.Tables("turno").Rows(0).Item("turno")
                                    End If
                                End If
                                If message(0) = "NEXT" Then
                                    Chiamato = ChiamaProssimo(lDset.Tables("numero").Rows(0).Item("id_turno"), Sportello)
                                End If
                                StrQ = "select distinct t.turno, ot.id_turno from turni t, operazioni_turni ot, operazioni_postazioni op, postazioni p "
                                StrQ = StrQ & "where t.id_turno=ot.id_turno and ot.id_operazione=op.id_operazione and op.id_postazione=p.id_postazione "
                                StrQ = StrQ & "and p.ID_postazione=" & Sportello & ""
                                If DbConnection.Estrai(StrQ, LDsetT2, "turno", True) Then
                                    For i = 0 To LDsetT2.Tables("turno").Rows.Count - 1
                                        ETurni = ETurni & SepTurni & LDsetT2.Tables("turno").Rows(i).Item("turno") & SepValori
                                        StrQ = "select numero from contatori where id_turno=" & LDsetT2.Tables("turno").Rows(i).Item("id_turno") & " order by tipo"
                                        If DbConnection.Estrai(StrQ, LDsetT, "turno", True) Then
                                            If LDsetT.Tables("turno").Rows.Count > 0 Then
                                                ETurni = ETurni & (LDsetT.Tables("turno").Rows(0).Item("numero") - LDsetT.Tables("turno").Rows(1).Item("numero"))
                                            End If
                                        End If
                                        SepTurni = "§"
                                    Next
                                End If
                                If message(0) = "NEXT" Then
                                    sendsingle("CALL|" & Chiamato & "|" & LettTurno & "|" & ETurni, client.name)
                                ElseIf message(0) = "CISONO" Then
                                    sendsingle("CISONO|" & Chiamato & "|" & LettTurno & "|" & ETurni, client.name)
                                End If
                            Else
                            End If
                        Else
                            '*************************************MODIFICA
                            Chiamato = SmaltisciCoda(Sportello, message(0), TurnoChiamato, InCoda)
                            StrQ = "select distinct t.turno, ot.id_turno from turni t, operazioni_turni ot, operazioni_postazioni op, postazioni p "
                            StrQ = StrQ & "where t.id_turno=ot.id_turno and ot.id_operazione=op.id_operazione and op.id_postazione=p.id_postazione "
                            StrQ = StrQ & "and p.ID_postazione=" & Sportello & ""
                            If DbConnection.Estrai(StrQ, LDsetT2, "turno", True) Then
                                For j = 0 To LDsetT2.Tables("turno").Rows.Count - 1
                                    ETurni = ETurni & SepTurni & LDsetT2.Tables("turno").Rows(j).Item("turno") & SepValori
                                    StrQ = "select numero from contatori where id_turno=" & LDsetT2.Tables("turno").Rows(j).Item("id_turno") & " order by tipo"
                                    If DbConnection.Estrai(StrQ, LDsetT, "turno", True) Then
                                        If LDsetT.Tables("turno").Rows.Count > 0 Then
                                            ETurni = ETurni & (LDsetT.Tables("turno").Rows(0).Item("numero") - LDsetT.Tables("turno").Rows(1).Item("numero"))
                                        End If
                                    End If
                                    SepTurni = "§"
                                Next
                            End If
                            StrQ = "select * from turni where id_turno=" & TurnoChiamato
                            If DbConnection.Estrai(StrQ, LDsetT2, "turno", True) Then
                                If LDsetT2.Tables("turno").Rows.Count > 0 Then
                                    LettTurno = LDsetT2.Tables("turno").Rows(0).Item("turno")
                                End If
                            End If
                            If message(0) = "NEXT" And Chiamato > 0 Then
                                sendsingle("CALL|" & Chiamato & "|" & LettTurno & "|" & ETurni, client.name)
                            ElseIf message(0) = "CISONO" And InCoda Then
                                sendsingle("CISONO|" & Chiamato & "|" & LettTurno & "|" & ETurni, client.name)
                            End If

                            '*************************************FINE MODIFICA

                            'StrQ2 = "select distinct ot.id_turno from (((postazioni p inner join operazioni_postazioni op on p.id_postazione = op.id_postazione) "
                            'StrQ2 = StrQ2 & "inner join operazioni_turni ot on op.id_operazione = ot.id_operazione) inner join turni t "
                            'StrQ2 = StrQ2 & "on ot.id_turno=t.id_turno) "
                            'StrQ2 = StrQ2 & "where p.postazione = '" & Sportello & "' and t.stato='1'"

                            'StrQ = "select * from contatori where tipo='NUMERO' and id_turno in (" & StrQ2 & ") order by ora asc"
                            'If DbConnection.Estrai(StrQ, LDsetT, "turno", True) Then
                            '    If LDsetT.Tables("turno").Rows.Count > 0 Then
                            '        For i = 0 To LDsetT.Tables("turno").Rows.Count - 1
                            '            UChiamato = LDsetT.Tables("turno").Rows(i).Item("numero")
                            '            StrQ = "select * from contatori where tipo='CODA' and id_turno=" & LDsetT.Tables("turno").Rows(i).Item("id_turno")
                            '            If DbConnection.Estrai(StrQ, lDset, "turno", True) Then
                            '                If lDset.Tables("turno").Rows.Count > 0 Then
                            '                    UNumero = lDset.Tables("turno").Rows(0).Item("numero")
                            '                End If
                            '            End If
                            '            If UNumero > UChiamato Then
                            '                StrQ = "select * from turni where id_turno=" & LDsetT.Tables("turno").Rows(i).Item("id_turno")
                            '                If DbConnection.Estrai(StrQ, LDsetT2, "turno", True) Then
                            '                    If LDsetT2.Tables("turno").Rows.Count > 0 Then
                            '                        LettTurno = LDsetT2.Tables("turno").Rows(0).Item("turno")
                            '                    End If
                            '                End If
                            '                If message(0) = "NEXT" Then
                            '                    Chiamato = ChiamaProssimo(LDsetT.Tables("turno").Rows(i).Item("id_turno"), Sportello)
                            '                End If
                            '                StrQ = "select distinct t.turno, ot.id_turno from turni t, operazioni_turni ot, operazioni_postazioni op, postazioni p "
                            '                StrQ = StrQ & "where t.id_turno=ot.id_turno and ot.id_operazione=op.id_operazione and op.id_postazione=p.id_postazione "
                            '                StrQ = StrQ & "and p.postazione='" & Sportello & "'"
                            '                If DbConnection.Estrai(StrQ, LDsetT2, "turno", True) Then
                            '                    For j = 0 To LDsetT2.Tables("turno").Rows.Count - 1
                            '                        ETurni = ETurni & SepTurni & LDsetT2.Tables("turno").Rows(j).Item("turno") & SepValori
                            '                        StrQ = "select numero from contatori where id_turno=" & LDsetT2.Tables("turno").Rows(j).Item("id_turno") & " order by tipo"
                            '                        If DbConnection.Estrai(StrQ, LDsetT, "turno", True) Then
                            '                            If LDsetT.Tables("turno").Rows.Count > 0 Then
                            '                                ETurni = ETurni & (LDsetT.Tables("turno").Rows(0).Item("numero") - LDsetT.Tables("turno").Rows(1).Item("numero"))
                            '                            End If
                            '                        End If
                            '                        SepTurni = "§"
                            '                    Next
                            '                End If
                            '                If message(0) = "NEXT" Then
                            '                    sendsingle("CALL|" & Chiamato & "|" & LettTurno & "|" & ETurni, client.name)
                            '                ElseIf message(0) = "CISONO" Then
                            '                    sendsingle("CISONO|" & Chiamato & "|" & LettTurno & "|" & ETurni, client.name)
                            '                End If
                            '                Exit For
                            '            End If
                            '        Next
                            '    End If
                            'End If
                        End If
                    End If
                End If
            Case "ABD"
                StrQ = "update turni set stato='1' where turno='" & message(1) & "'"
                DbConnection.EseguiSQL(StrQ)
                sendallbutone(msg, "")
            Case "DBD"
                StrQ = "update turni set stato='0', desstato='" & message(2).Replace("'", "''") & "' where turno='" & message(1) & "'"
                DbConnection.EseguiSQL(StrQ)
                sendallbutone(msg, "")
            Case "QES"
                StrQ = "select t.turno, t.stato, o.operazione from (turni t inner join operazioni_turni ot on t.ID_turno = ot.id_turno) 
                        inner join operazioni o on ot.id_operazione = o.ID_operazione order by t.turno"
                Dim Risposta As String = "QES"
                If DbConnection.Estrai(StrQ, lDset, "turni", True) Then
                    Dim sTurno As String = ""
                    For i = 0 To lDset.Tables("turni").Rows.Count - 1
                        If sTurno <> lDset.Tables("turni").Rows(i).Item("turno") Then
                            Risposta = Risposta & "|" & lDset.Tables("turni").Rows(i).Item("turno") & "-" & lDset.Tables("turni").Rows(i).Item("stato")
                            sTurno = lDset.Tables("turni").Rows(i).Item("turno")
                        End If
                        Risposta = Risposta & "§" & lDset.Tables("turni").Rows(i).Item("operazione")
                    Next
                    sendsingle(Risposta, client.name)
                End If
            Case "GET"
                Dim GetS As String = ""
                Dim lDsetGet As New DataSet
                Dim Res As String = ""
                GetS = message(1)

                Select Case GetS
                    Case "TURNI"
                        Res = "TURNI|"
                        StrQ = "select * from turni order by turno"
                        If DbConnection.Estrai(StrQ, lDsetGet, "turni", True) Then
                            For i As Integer = 0 To lDsetGet.Tables("turni").Rows.Count - 1
                                Res = Res & lDsetGet.Tables("turni").Rows(i).Item("id_turno") & "§" & lDsetGet.Tables("turni").Rows(i).Item("turno") & "§" & lDsetGet.Tables("turni").Rows(i).Item("priorita") & ";"
                            Next
                        End If
                        sendsingle(Res, client.name)
                    Case "POSTAZIONI"
                        Res = "POSTAZIONI|"
                        StrQ = "select * from postazioni order by id_postazione"
                        If DbConnection.Estrai(StrQ, lDsetGet, "postazioni", True) Then
                            For i As Integer = 0 To lDsetGet.Tables("postazioni").Rows.Count - 1
                                Res = Res & lDsetGet.Tables("postazioni").Rows(i).Item("id_postazione") & "§" & lDsetGet.Tables("postazioni").Rows(i).Item("postazione") & ";"
                            Next
                        End If
                        sendsingle(Res, client.name)
                    Case "OPERAZIONI"
                        Res = "OPERAZIONI|"
                        StrQ = "select * from operazioni order by operazione"
                        If DbConnection.Estrai(StrQ, lDsetGet, "operazioni", True) Then
                            For i As Integer = 0 To lDsetGet.Tables("operazioni").Rows.Count - 1
                                Res = Res & lDsetGet.Tables("operazioni").Rows(i).Item("id_operazione") & "§" & lDsetGet.Tables("operazioni").Rows(i).Item("operazione") & ";"
                            Next
                        End If
                        sendsingle(Res, client.name)
                    Case "APRI"
                        sendsingle("APRI|", client.name)
                    Case "TO"

                        Res = "TO|"
                        StrQ = "select * from operazioni_turni where id_turno=" & message(2)
                        If DbConnection.Estrai(StrQ, lDsetGet, "operazioni", True) Then
                            For i As Integer = 0 To lDsetGet.Tables("operazioni").Rows.Count - 1
                                Res = Res & lDsetGet.Tables("operazioni").Rows(i).Item("id_operazione") & ";"
                            Next
                        End If
                        sendsingle(Res, client.name)
                End Select
            Case "APRI"
            Case "CODA"
                Dim lSportello As String = ""
                Dim LDsetT As New DataSet
                Dim LDsetT2 As New DataSet
                Dim ETurni As String = ""
                Dim SepTurni As String = ""
                Dim SepValori As String = "£"
                Dim SepOperazioni As String = "#"
                Dim lDsetOp As New DataSet

                lSportello = message(1)

                StrQ = "select distinct t.turno, ot.id_turno from turni t, operazioni_turni ot, operazioni_postazioni op, postazioni p "
                StrQ = StrQ & "where t.id_turno=ot.id_turno and ot.id_operazione=op.id_operazione and op.id_postazione=p.id_postazione "
                StrQ = StrQ & "and p.ID_postazione=" & lSportello & ""
                If DbConnection.Estrai(StrQ, LDsetT2, "turno", True) Then
                    For j = 0 To LDsetT2.Tables("turno").Rows.Count - 1
                        ETurni = ETurni & SepTurni & LDsetT2.Tables("turno").Rows(j).Item("turno") & SepValori
                        StrQ = "select numero from contatori where id_turno=" & LDsetT2.Tables("turno").Rows(j).Item("id_turno") & " order by tipo"
                        If DbConnection.Estrai(StrQ, LDsetT, "turno", True) Then
                            If LDsetT.Tables("turno").Rows.Count > 0 Then
                                ETurni = ETurni & (LDsetT.Tables("turno").Rows(0).Item("numero") - LDsetT.Tables("turno").Rows(1).Item("numero"))
                            End If
                        End If
                        SepTurni = "§"
                        StrQ = "select o.operazione from (turni t inner join operazioni_turni ot on t.ID_turno = ot.id_turno) 
                        inner join operazioni o on ot.id_operazione = o.ID_operazione where t.ID_turno = " & LDsetT2.Tables("turno").Rows(j).Item("id_turno") & " 
                        order by o.operazione"
                        ETurni = ETurni & SepOperazioni
                        If DbConnection.Estrai(StrQ, lDsetOp, "operazione", True) Then
                            For kk = 0 To lDsetOp.Tables("operazione").Rows.Count - 1
                                ETurni = ETurni & lDsetOp.Tables("operazione").Rows(kk).Item("operazione") & "-"
                            Next
                        End If
                    Next
                End If
                sendsingle("CODA|" & ETurni, client.name)
            Case "ELENCOS"
                Dim LDsetT2 As New DataSet
                Dim Testo As String = ""

                StrQ = "select * from postazioni order by postazione"
                If DbConnection.Estrai(StrQ, LDsetT2, "postazione", True) Then
                    For j = 0 To LDsetT2.Tables("postazione").Rows.Count - 1
                        If Testo <> "" Then
                            Testo = Testo & "§"
                        End If
                        Testo = Testo & LDsetT2.Tables("postazione").Rows(j).Item("id_postazione") & "_" & LDsetT2.Tables("postazione").Rows(j).Item("postazione")
                    Next
                End If
                sendsingle("ELENCOS|" & Testo, client.name)
            Case "TOTEM"

                Dim ImpDSet As New DataSet
                Dim tempImmagine As String
                Dim pathsave As String = ""
                Dim fileExtension As String = ""

                pathsave = pathImmagini & "sfondototem"
                StrQ = "select * from configtotem"
                If DbConnection.Estrai(StrQ, ImpDSet, "configtotem", True) Then
                    If ImpDSet.Tables("configtotem").Rows.Count > 0 Then
                        tempImmagine = IIf(IsDBNull(ImpDSet.Tables("configtotem").Rows(0).Item("immaginesfondo")), "", ImpDSet.Tables("configtotem").Rows(0).Item("immaginesfondo"))
                        If tempImmagine <> "" Then
                            fileExtension = Path.GetExtension(tempImmagine)
                            pathsave = pathsave & fileExtension
                            If DownloadImage(tempImmagine, pathsave) Then
                                SfondoTotem = pathsave
                            Else
                                SfondoTotem = ""
                            End If
                        Else
                            SfondoTotem = ""
                        End If
                    End If
                End If

                Dim lDsetTotem As New DataSet
                Dim lDsetTurniTotem As New DataSet
                Dim Testo As String = ""
                Threading.Thread.Sleep(5000)
                StrQ = "select * from configtotem"
                If DbConnection.Estrai(StrQ, lDsetTotem, "totem", True) Then
                    If lDsetTotem.Tables("totem").Rows.Count > 0 Then
                        Testo = SfondoTotem & ";" & lDsetTotem.Tables("totem").Rows(0).Item("ximmaginesfondo") & ";" & lDsetTotem.Tables("totem").Rows(0).Item("yimmaginesfondo")
                        StrQ = "select t.turno, t.desstato, t.stato, ct.fontturno, ct.sizeturno, ct.boldturno, ct.coloreturno, ct.xturno, ct.yturno, ct.hturno, ct.wturno, ct.sfondobutton "
                        StrQ = StrQ & "from turni t inner join configturnitotem ct on t.id_turno = ct.idturno order by t.turno"
                        If DbConnection.Estrai(StrQ, lDsetTurniTotem, "turnitotem", True) Then
                            If lDsetTurniTotem.Tables("turnitotem").Rows.Count > 0 Then
                                For j = 0 To lDsetTurniTotem.Tables("turnitotem").Rows.Count - 1
                                    Testo = Testo & "§"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("turno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("desstato") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("fontturno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("sizeturno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("boldturno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("coloreturno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("xturno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("yturno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("hturno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("wturno") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("sfondobutton") & ";"
                                    Testo &= lDsetTurniTotem.Tables("turnitotem").Rows(j).Item("stato")
                                Next
                            Else
                                Testo = ""
                            End If
                        Else
                            Testo = ""
                        End If
                    End If
                End If
                sendsingle("TOTEM|" & Testo, client.name)

            Case "TOTEMIMG"
                'STRUTTURA DEL MESSAGGIO IN ARRIVO
                'TOTEMIMG|INDIRIZZO IP DEL RECEIVER;PORTA DEL RECEIVER;IMMAGINE DA RICEVERE
                Dim imgToSend As String = message(1)
                Dim vImgToSend() As String
                Dim ipReceiver As String
                Dim portReceiver As String

                vImgToSend = imgToSend.Split(";")
                If vImgToSend.Count > 0 Then
                    ipReceiver = vImgToSend(0)
                End If
                If vImgToSend.Count > 1 Then
                    portReceiver = vImgToSend(1)
                End If
                If vImgToSend(2) = "SFONDOTOTEM" Then
                    If SfondoTotem <> "" Then
                        Try

                            If Not System.IO.File.Exists(SfondoTotem) Then
                                Logga("File immagine non trovato: " & SfondoTotem)
                                Return
                            End If

                            Using client2 As New TcpClient(ipReceiver, portReceiver)
                                Logga("Connesso al server.")

                                Using stream As NetworkStream = client2.GetStream()
                                    ' --- Invio nome file ---
                                    Dim fileName As String = Path.GetFileName(SfondoTotem)
                                    Dim fileNameBytes As Byte() = System.Text.Encoding.UTF8.GetBytes(fileName)
                                    Dim fileNameLength As Integer = fileNameBytes.Length
                                    stream.Write(BitConverter.GetBytes(fileNameLength), 0, 4) ' 4 byte per la lunghezza
                                    stream.Write(fileNameBytes, 0, fileNameLength)             ' Nome file
                                    ' Leggi i byte dell'immagine

                                    Dim imageBytes As Byte() = System.IO.File.ReadAllBytes(SfondoTotem)

                                    ' 1. Invia la dimensione del file
                                    Dim fileSize As Integer = imageBytes.Length
                                    Dim sizeBuffer As Byte() = BitConverter.GetBytes(fileSize)
                                    stream.Write(sizeBuffer, 0, 4)
                                    Logga("Dimensione immagine inviata: " & fileSize & " bytes")

                                    ' 2. Invia i dati dell'immagine
                                    stream.Write(imageBytes, 0, imageBytes.Length)
                                    Logga("Immagine inviata.")
                                End Using
                            End Using
                        Catch ex As SocketException
                            Logga("SocketException: " & ex.Message)
                            If ex.SocketErrorCode = SocketError.ConnectionRefused Then
                                Logga("Assicurati che il server sia in esecuzione e l'IP/porta siano corretti.")
                            End If
                        Catch ex As IOException
                            Logga("IOException: " & ex.Message)
                        Catch ex As Exception
                            Logga("Errore: " & ex.Message)
                        End Try
                    End If
                ElseIf vImgToSend(2) = "TURNOTOTEM" Then
                    Dim nomeTurno As String = vImgToSend(3)

                    Dim files As String() = Directory.GetFiles(pathImmagini, "sfondoturno" & nomeTurno & ".*")
                    If files.Length > 0 Then
                        Try

                            If Not System.IO.File.Exists(files(0)) Then
                                Logga("File immagine non trovato: " & files(0))
                                Return
                            End If

                            Using client2 As New TcpClient(ipReceiver, portReceiver)
                                Logga("Connesso al server.")

                                Using stream As NetworkStream = client2.GetStream()
                                    ' --- Invio nome file ---
                                    Dim fileName As String = Path.GetFileName(files(0))
                                    Dim fileNameBytes As Byte() = System.Text.Encoding.UTF8.GetBytes(fileName)
                                    Dim fileNameLength As Integer = fileNameBytes.Length
                                    stream.Write(BitConverter.GetBytes(fileNameLength), 0, 4) ' 4 byte per la lunghezza
                                    stream.Write(fileNameBytes, 0, fileNameLength)             ' Nome file
                                    ' Leggi i byte dell'immagine

                                    Dim imageBytes As Byte() = System.IO.File.ReadAllBytes(files(0))

                                    ' 1. Invia la dimensione del file
                                    Dim fileSize As Integer = imageBytes.Length
                                    Dim sizeBuffer As Byte() = BitConverter.GetBytes(fileSize)
                                    stream.Write(sizeBuffer, 0, 4)
                                    Logga("Dimensione immagine inviata: " & fileSize & " bytes")

                                    ' 2. Invia i dati dell'immagine
                                    stream.Write(imageBytes, 0, imageBytes.Length)
                                    Logga("Immagine inviata.")
                                End Using
                            End Using
                        Catch ex As SocketException
                            Logga("SocketException: " & ex.Message)
                            If ex.SocketErrorCode = SocketError.ConnectionRefused Then
                                Logga("Assicurati che il server sia in esecuzione e l'IP/porta siano corretti.")
                            End If
                        Catch ex As IOException
                            Logga("IOException: " & ex.Message)
                        Catch ex As Exception
                            Logga("Errore: " & ex.Message)
                        End Try

                    End If
                End If
            Case "RECALL"
                Dim recall() As String = message(1).Split(";")
                Dim turnoRecall As String = recall(1)
                Dim numeroRecall As String = recall(0)
                Dim sportelloRecall As String = recall(2)

                recallNumero(numeroRecall, turnoRecall, sportelloRecall)

            Case "TRASF"
                Dim trasferisci() As String = message(1).Split(";")
                Dim turnotrasferisci As String = trasferisci(1)
                Dim numerotrasferisci As String = trasferisci(0)
                Dim sportellotrasferisci As String = trasferisci(2)

                recallNumero(numerotrasferisci, turnotrasferisci, sportellotrasferisci, True)
        End Select

    End Sub

    Public Sub sendallbutone(ByVal message As String, ByVal exemptclientname As String) 'this sends to all clients except the one specified
        Dim entry As DictionaryEntry 'declare a variable of type dictionary entry
        Try
            For Each entry In clients 'for each dictionary entry in the hashtable with all clients (clients)
                If entry.Value <> exemptclientname Then 'if the entry IS NOT the exempt client name
                    Dim cli As ConnectedClient = CType(entry.Key, ConnectedClient) ' cast the hashtable entry to a connection class
                    cli.senddata(message) 'send the message to it
                End If
            Next
        Catch
        End Try
    End Sub

    Public Sub sendsingle(ByVal message As String, ByVal clientname As String)
        Dim entry As DictionaryEntry 'declare a variable of type dictionary entry
        Try
            For Each entry In clients 'for each dictionary entry in the hashtable with all clients (clients)
                If entry.Value = clientname Then 'if the entry is belongs to the client specified
                    Dim cli As ConnectedClient = CType(entry.Key, ConnectedClient) ' cast the hashtable entry to a connection class
                    cli.senddata(message) 'send the message to it
                End If
            Next
        Catch
        End Try

    End Sub
    Public Sub senddata(ByVal message As String) 'this sends a message to all connected clients
        Dim entry As DictionaryEntry 'declare a variable of type dictionary entry
        Try
            For Each entry In clients 'for each dictionary entry in the hashtable with all clients (clients)
                Dim cli As ConnectedClient = CType(entry.Key, ConnectedClient) ' cast the hashtable entry to a connection class
                cli.senddata(message) 'send the message to it
            Next  'go to the next client
        Catch
        End Try

    End Sub
    Public Sub disconnected(ByVal client As ConnectedClient) 'if a client is disconnected, this is raised
        clients.Remove(client) 'remove the client from the hashtable
        'ListBox1.Items.Remove(client.name) 'remove it from our listbox
    End Sub

    Public Sub listen(ByVal port As Integer)
        Try
            Dim t As New TcpListener(IPAddress.Any, port) 'declare a new tcplistener
            t.Start() 'start the listener
            Do

                Dim client As New ConnectedClient(t.AcceptTcpClient) 'initialize a new connected client
                AddHandler client.gotmessage, AddressOf recieved 'add the handler which will raise an event when a message is recieved
                AddHandler client.disconnected, AddressOf disconnected 'add the handler which will raise an event when the client disconnects

            Loop Until False
        Catch
        End Try

    End Sub

    Private Function TurnoAttivo(idturno As Integer) As String
        Dim StrQ As String = ""
        Dim lDset As New DataSet
        Dim Ret As String = "OK"
        Dim vGiorni() As String = {"Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"}

        Dim GiornoSett As Integer
        Dim Ora As String

        GiornoSett = If(Now.DayOfWeek = DayOfWeek.Sunday, 7, Now.DayOfWeek)
        Ora = Now.Hour.ToString.PadLeft(2, "0") & Now.Minute.ToString.PadLeft(2, "0")

        'StrQ = "select og.* from operazioni_giorni og inner join operazioni_turni ot "
        'StrQ = StrQ & "on og.id_operazione=ot.id_operazione "
        'StrQ = StrQ & "where ot.id_turno=" & idturno & " and og.giorno=" & GiornoSett & " "
        'StrQ = StrQ & "and og.ora_inizio<='" & Ora & "' and og.ora_fine>='" & Ora & "' "
        StrQ = "Select min(op.ora_inizio1) As orainizio1, max(op.ora_fine1) As orafine1, min(op.ora_inizio2) As orainizio2, max(op.ora_fine2) As ora_fine2 "
        StrQ = StrQ & "from(operazioni_turni ot inner join operazioni_postazioni op On ot.id_operazione = op.id_operazione) "
        StrQ = StrQ & "inner Join operazioni_giorni og on ot.id_operazione = og.id_operazione "
        StrQ = StrQ & "where ot.id_turno = " & idturno & " And og.giorno = " & GiornoSett & " "
        StrQ = StrQ & "HAVING((min(op.ora_inizio1) <= '" & Ora & "' and max(op.ora_fine1) >='" & Ora & "') "
        StrQ = StrQ & "OR (min(op.ora_inizio2)<='" & Ora & "' and  max(op.ora_fine2)>='" & Ora & "'))"
        If DbConnection.Estrai(StrQ, lDset, "orari", True) Then
            If lDset.Tables("orari").Rows.Count > 0 Then
                Ret = "OK"
            Else
                Ret = "Il turno è chiuso.§Orari di apertura:§"
                'StrQ = "select og.* from operazioni_giorni og inner join operazioni_turni ot "
                'StrQ = StrQ & "on og.id_operazione=ot.id_operazione "
                'StrQ = StrQ & "where ot.id_turno=" & idturno & " "
                'StrQ = StrQ & "order by og.giorno, og.ora_inizio"
                StrQ = "Select min(op.ora_inizio1) As orainizio1, max(op.ora_fine1) As orafine1, min(op.ora_inizio2) As orainizio2, "
                StrQ = StrQ & "max(op.ora_fine2) As orafine2, og.giorno "
                StrQ = StrQ & "from (operazioni_turni ot inner join operazioni_postazioni op On ot.id_operazione = op.id_operazione) "
                StrQ = StrQ & "inner Join operazioni_giorni og on ot.id_operazione = og.id_operazione "
                StrQ = StrQ & "where ot.id_turno = " & idturno & " group by og.giorno "
                StrQ = StrQ & "order by og.giorno, min(op.ora_inizio1) "
                If DbConnection.Estrai(StrQ, lDset, "orari", True) Then
                    For i As Integer = 0 To lDset.Tables("orari").Rows.Count - 1
                        Ret = Ret & "§" & vGiorni(lDset.Tables("orari").Rows(i).Item("giorno") - 1) & " "
                        If lDset.Tables("orari").Rows(i).Item("orainizio1").ToString <> "0000" Then
                            Ret = Ret & " dalle " & lDset.Tables("orari").Rows(i).Item("orainizio1").ToString.Substring(0, 2)
                            Ret = Ret & ";" & lDset.Tables("orari").Rows(i).Item("orainizio1").ToString.Substring(2, 2)
                        End If
                        If lDset.Tables("orari").Rows(i).Item("orafine1").ToString <> "0000" Then
                            Ret = Ret & " alle " & lDset.Tables("orari").Rows(i).Item("orafine1").ToString.Substring(0, 2)
                            Ret = Ret & ";" & lDset.Tables("orari").Rows(i).Item("orafine1").ToString.Substring(2, 2)
                        End If
                        If lDset.Tables("orari").Rows(i).Item("orainizio2").ToString <> "0000" Then
                            Ret = Ret & " dalle " & lDset.Tables("orari").Rows(i).Item("orainizio2").ToString.Substring(0, 2)
                            Ret = Ret & ";" & lDset.Tables("orari").Rows(i).Item("orainizio2").ToString.Substring(2, 2)
                        End If
                        If lDset.Tables("orari").Rows(i).Item("orafine2").ToString <> "0000" Then
                            Ret = Ret & " alle " & lDset.Tables("orari").Rows(i).Item("orafine2").ToString.Substring(0, 2)
                            Ret = Ret & ";" & lDset.Tables("orari").Rows(i).Item("orafine2").ToString.Substring(2, 2) & "§"
                        End If
                    Next
                End If
            End If
        End If

        Return Ret

    End Function

    Private Function SmaltisciCoda(sportello As String, chiamata As String, ByRef nTurno As Integer, ByRef InCoda As Boolean) As Integer
        Dim StrQ As String
        Dim StrQ2 As String
        Dim lDset As New DataSet
        Dim CodaA As Integer = 0
        Dim CodaB As Integer = 0
        Dim CodaC As Integer = 0
        Dim CodaD As Integer = 0
        Dim CodaE As Integer = 0
        Dim CodaF As Integer = 0
        Dim CodaG As Integer = 0
        Dim ConsA As Integer = 0
        Dim ConsB As Integer = 0
        Dim ConsC As Integer = 0
        Dim ConsD As Integer = 0
        Dim ConsE As Integer = 0
        Dim ConsF As Integer = 0
        Dim ConsG As Integer = 0
        Dim PrioA As Integer = 0
        Dim PrioB As Integer = 0
        Dim PrioC As Integer = 0
        Dim PrioD As Integer = 0
        Dim PrioE As Integer = 0
        Dim PrioF As Integer = 0
        Dim PrioG As Integer = 0

        Dim Chiamato As Integer = 0
        Dim TurnoChiama As Integer = 0

        StrQ2 = "select distinct ot.id_turno from (((postazioni p inner join operazioni_postazioni op on p.id_postazione = op.id_postazione) "
        StrQ2 = StrQ2 & "inner join operazioni_turni ot on op.id_operazione = ot.id_operazione) inner join turni t "
        StrQ2 = StrQ2 & "on ot.id_turno=t.id_turno) "
        StrQ2 = StrQ2 & "where p.ID_postazione = " & sportello & " and t.stato='A'"

        StrQ = "select c1.id_turno, c1.numero as coda, c1.consecutivi, c2.numero as numeri, t.priorita from (contatori c1 "
        StrQ = StrQ & "inner join contatori c2 on c1.id_turno=c2.id_turno) "
        StrQ = StrQ & "inner join turni t on c1.id_turno=t.id_turno "
        StrQ = StrQ & "where c1.tipo='CODA' and c2.tipo='NUMERO' and c1.numero>c2.numero "
        StrQ = StrQ & "and c1.id_turno in (" & StrQ2 & ") order by  t.priorita, c1.numero-c2.numero desc"
        If DbConnection.Estrai(StrQ, lDset, "numeri", True) Then
            If lDset.Tables("numeri").Rows.Count > 0 Then
                InCoda = True
                If lDset.Tables("numeri").Rows.Count > 1 Then
                    CodaA = lDset.Tables("numeri").Rows(0).Item("coda") - lDset.Tables("numeri").Rows(0).Item("numeri")
                    ConsA = lDset.Tables("numeri").Rows(0).Item("consecutivi")
                    PrioA = lDset.Tables("numeri").Rows(0).Item("priorita")
                    CodaB = lDset.Tables("numeri").Rows(1).Item("coda") - lDset.Tables("numeri").Rows(1).Item("numeri")
                    ConsB = lDset.Tables("numeri").Rows(1).Item("consecutivi")
                    PrioB = lDset.Tables("numeri").Rows(1).Item("priorita")
                    If lDset.Tables("numeri").Rows.Count > 2 Then
                        ConsC = lDset.Tables("numeri").Rows(2).Item("consecutivi")
                        If lDset.Tables("numeri").Rows.Count > 3 Then
                            ConsD = lDset.Tables("numeri").Rows(3).Item("consecutivi")
                        End If
                    End If

                    If ConsA < 2 Then
                        TurnoChiama = lDset.Tables("numeri").Rows(0).Item("id_turno")
                        'consa+=1
                        StrQ = "update contatori set consecutivi=consecutivi+1 where "
                        StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                        DbConnection.EseguiSQL(StrQ)
                    Else
                        If CodaB >= CodaA And ConsB <= 1 Then
                            TurnoChiama = lDset.Tables("numeri").Rows(1).Item("id_turno")
                            'consa+=1
                            StrQ = "update contatori set consecutivi=consecutivi+1 where "
                            StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                            DbConnection.EseguiSQL(StrQ)
                        Else
                            If CodaB < CodaA And CodaB > 0 Then
                                If ConsA < 4 Then
                                    TurnoChiama = lDset.Tables("numeri").Rows(0).Item("id_turno")
                                    'consa+=1
                                    StrQ = "update contatori set consecutivi=consecutivi+1 where "
                                    StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                                    DbConnection.EseguiSQL(StrQ)
                                Else
                                    If ConsB < 2 Then
                                        TurnoChiama = lDset.Tables("numeri").Rows(1).Item("id_turno")
                                        'consa+=1
                                        StrQ = "update contatori set consecutivi=consecutivi+1 where "
                                        StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                                        DbConnection.EseguiSQL(StrQ)
                                    Else
                                        If lDset.Tables("numeri").Rows.Count > 2 Then
                                            If ConsC = 0 Then
                                                TurnoChiama = lDset.Tables("numeri").Rows(2).Item("id_turno")
                                                'consa+=1
                                                StrQ = "update contatori set consecutivi=consecutivi+1 where "
                                                StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                                                DbConnection.EseguiSQL(StrQ)
                                            Else
                                                If lDset.Tables("numeri").Rows.Count > 3 Then
                                                    If ConsD = 0 Then
                                                        TurnoChiama = lDset.Tables("numeri").Rows(3).Item("id_turno")
                                                        'consa+=1
                                                        StrQ = "update contatori set consecutivi=consecutivi+1 where "
                                                        StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                                                        DbConnection.EseguiSQL(StrQ)
                                                    Else
                                                        StrQ = "update contatori set consecutivi=0 where "
                                                        StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                                                        DbConnection.EseguiSQL(StrQ)
                                                    End If
                                                Else
                                                    StrQ = "update contatori set consecutivi=0 where "
                                                    StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                                                    DbConnection.EseguiSQL(StrQ)
                                                End If
                                            End If
                                        Else
                                            StrQ = "update contatori set consecutivi=0 where "
                                            StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                                            DbConnection.EseguiSQL(StrQ)
                                        End If
                                    End If
                                End If
                            Else
                                If ConsB >= 2 Then
                                    If lDset.Tables("numeri").Rows.Count > 2 Then
                                        If ConsC = 0 Then
                                            TurnoChiama = lDset.Tables("numeri").Rows(2).Item("id_turno")
                                            'consa+=1
                                            StrQ = "update contatori set consecutivi=consecutivi+1 where "
                                            StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                                            DbConnection.EseguiSQL(StrQ)
                                        Else
                                            If lDset.Tables("numeri").Rows.Count > 3 Then
                                                If ConsD = 0 Then
                                                    TurnoChiama = lDset.Tables("numeri").Rows(3).Item("id_turno")
                                                    'consa+=1
                                                    StrQ = "update contatori set consecutivi=consecutivi+1 where "
                                                    StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                                                    DbConnection.EseguiSQL(StrQ)
                                                Else
                                                    StrQ = "update contatori set consecutivi=0 where "
                                                    StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                                                    DbConnection.EseguiSQL(StrQ)
                                                End If
                                            Else
                                                StrQ = "update contatori set consecutivi=0 where "
                                                StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                                                DbConnection.EseguiSQL(StrQ)
                                            End If
                                        End If
                                    Else
                                        StrQ = "update contatori set consecutivi=0 where "
                                        StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                                        DbConnection.EseguiSQL(StrQ)
                                    End If
                                End If
                            End If
                        End If
                    End If

                    'If CodaA - CodaB <= 5 Then
                    '    If ConsA < 5 Then
                    '        If ConsA < 2 Then
                    '            chiamaA
                    '            TurnoChiama = lDset.Tables("numeri").Rows(0).Item("id_turno")
                    '            consa += 1
                    '            StrQ = "update contatori set consecutivi=consecutivi+1 where "
                    '            StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                    '            DbConnection.EseguiSQL(StrQ)
                    '            Consb = 0
                    '            StrQ = "update contatori set consecutivi=0 where "
                    '            StrQ = StrQ & "id_turno=" & lDset.Tables("numeri").Rows(1).Item("id_turno") & " and tipo='CODA'"
                    '            DbConnection.EseguiSQL(StrQ)
                    '        Else
                    '            If ConsB < 2 Then
                    '                chiamab
                    '                TurnoChiama = lDset.Tables("numeri").Rows(1).Item("id_turno")
                    '                ConsB = 3
                    '                StrQ = "update contatori set consecutivi=3 where "
                    '                StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                    '                DbConnection.EseguiSQL(StrQ)
                    '                If lDset.Tables("numeri").Rows.Count = 2 Then
                    '                    azzera tutto
                    '                    StrQ = "update contatori set consecutivi=0 where "
                    '                    StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                    DbConnection.EseguiSQL(StrQ)
                    '                End If
                    '            Else
                    '                If lDset.Tables("numeri").Rows.Count > 2 Then
                    '                    ConsC = lDset.Tables("numeri").Rows(2).Item("consecutivi")
                    '                    If ConsC = 0 Then
                    '                        chiamac
                    '                        TurnoChiama = lDset.Tables("numeri").Rows(2).Item("id_turno")
                    '                        Consc = 1
                    '                        StrQ = "update contatori set consecutivi=1 where "
                    '                        StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                    '                        DbConnection.EseguiSQL(StrQ)
                    '                        If lDset.Tables("numeri").Rows.Count = 3 Then
                    '                            azzera tutto
                    '                            StrQ = "update contatori set consecutivi=0 where "
                    '                            StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                            DbConnection.EseguiSQL(StrQ)
                    '                        End If
                    '                    Else
                    '                        If lDset.Tables("numeri").Rows.Count > 3 Then
                    '                            chiamad
                    '                            TurnoChiama = lDset.Tables("numeri").Rows(3).Item("id_turno")
                    '                            azzera tutto
                    '                            StrQ = "update contatori set consecutivi=0 where "
                    '                            StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                            DbConnection.EseguiSQL(StrQ)
                    '                        End If
                    '                    End If
                    '                End If
                    '            End If
                    '        End If
                    '    Else 'consA>=5
                    '        If ConsB = 0 Then
                    '            chiamab
                    '            TurnoChiama = lDset.Tables("numeri").Rows(1).Item("id_turno")
                    '            ConsB = 1
                    '            StrQ = "update contatori set consecutivi=1 where "
                    '            StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                    '            DbConnection.EseguiSQL(StrQ)
                    '            If lDset.Tables("numeri").Rows.Count = 2 Then
                    '                azzera tutto
                    '                StrQ = "update contatori set consecutivi=0 where "
                    '                StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                DbConnection.EseguiSQL(StrQ)
                    '            End If
                    '        Else
                    '            If lDset.Tables("numeri").Rows.Count > 2 Then
                    '                ConsC = lDset.Tables("numeri").Rows(2).Item("consecutivi")
                    '                If ConsC = 0 Then
                    '                    chiamac
                    '                    TurnoChiama = lDset.Tables("numeri").Rows(2).Item("id_turno")
                    '                    Consc = 1
                    '                    StrQ = "update contatori set consecutivi=1 where "
                    '                    StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                    '                    DbConnection.EseguiSQL(StrQ)
                    '                    If lDset.Tables("numeri").Rows.Count = 3 Then
                    '                        azzera tutto
                    '                        StrQ = "update contatori set consecutivi=0 where "
                    '                        StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                        DbConnection.EseguiSQL(StrQ)
                    '                    End If
                    '                Else
                    '                    If lDset.Tables("numeri").Rows.Count > 3 Then
                    '                        chiamad
                    '                        TurnoChiama = lDset.Tables("numeri").Rows(3).Item("id_turno")
                    '                        azzera tutto
                    '                        StrQ = "update contatori set consecutivi=0 where "
                    '                        StrQ = StrQ & "id_turno in in (" & StrQ2 & ") and tipo='CODA'"
                    '                        DbConnection.EseguiSQL(StrQ)
                    '                    Else
                    '                        azzera tutto
                    '                        StrQ = "update contatori set consecutivi=0 where "
                    '                        StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                        DbConnection.EseguiSQL(StrQ)
                    '                    End If
                    '                End If
                    '            End If
                    '        End If
                    '    End If
                    'Else
                    '    If ConsA < 5 Then
                    '        chiamaA
                    '        TurnoChiama = lDset.Tables("numeri").Rows(0).Item("id_turno")
                    '        consa += 1
                    '        StrQ = "update contatori set consecutivi=consecutivi+1 where "
                    '        StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                    '        DbConnection.EseguiSQL(StrQ)
                    '    Else
                    '        If ConsB < 2 Then
                    '            chiamab
                    '            TurnoChiama = lDset.Tables("numeri").Rows(1).Item("id_turno")
                    '            ConsB += 1
                    '            StrQ = "update contatori set consecutivi=1 where "
                    '            StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                    '            DbConnection.EseguiSQL(StrQ)
                    '            If lDset.Tables("numeri").Rows.Count = 2 Then
                    '                azzera tutto
                    '                StrQ = "update contatori set consecutivi=0 where "
                    '                StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                DbConnection.EseguiSQL(StrQ)
                    '            Else
                    '                StrQ = "update contatori set consecutivi=consecutivi+1 where "
                    '                StrQ = StrQ & "id_turno in (" & TurnoChiama & ") and tipo='CODA'"
                    '                DbConnection.EseguiSQL(StrQ)
                    '            End If
                    '        Else
                    '            If lDset.Tables("numeri").Rows.Count > 2 Then
                    '                ConsC = lDset.Tables("numeri").Rows(2).Item("consecutivi")
                    '                If ConsC = 0 Then
                    '                    chiamac
                    '                    TurnoChiama = lDset.Tables("numeri").Rows(2).Item("id_turno")
                    '                    Consc = 1
                    '                    StrQ = "update contatori set consecutivi=1 where "
                    '                    StrQ = StrQ & "id_turno=" & TurnoChiama & " and tipo='CODA'"
                    '                    DbConnection.EseguiSQL(StrQ)
                    '                    If lDset.Tables("numeri").Rows.Count = 3 Then
                    '                        azzera tutto
                    '                        StrQ = "update contatori set consecutivi=0 where "
                    '                        StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                        DbConnection.EseguiSQL(StrQ)
                    '                    Else
                    '                        StrQ = "update contatori set consecutivi=consecutivi+1 where "
                    '                        StrQ = StrQ & "id_turno in (" & TurnoChiama & ") and tipo='CODA'"
                    '                        DbConnection.EseguiSQL(StrQ)
                    '                    End If
                    '                Else
                    '                    If lDset.Tables("numeri").Rows.Count > 3 Then
                    '                        chiamad
                    '                        TurnoChiama = lDset.Tables("numeri").Rows(3).Item("id_turno")
                    '                        azzera tutto
                    '                        StrQ = "update contatori set consecutivi=0 where "
                    '                        StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                        DbConnection.EseguiSQL(StrQ)
                    '                    Else
                    '                        azzera tutto
                    '                        StrQ = "update contatori set consecutivi=0 where "
                    '                        StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                        DbConnection.EseguiSQL(StrQ)
                    '                    End If
                    '                End If
                    '            Else
                    '                azzera tutto
                    '                StrQ = "update contatori set consecutivi=0 where "
                    '                StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    '                DbConnection.EseguiSQL(StrQ)
                    '            End If
                    '        End If
                    '    End If
                    'End If
                Else
                    'chiamaa
                    TurnoChiama = lDset.Tables("numeri").Rows(0).Item("id_turno")
                    'azzera tutto
                    StrQ = "update contatori set consecutivi=0 where "
                    StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                    DbConnection.EseguiSQL(StrQ)
                End If
            Else
                StrQ = "update contatori set consecutivi=0 where "
                StrQ = StrQ & "id_turno in (" & StrQ2 & ") and tipo='CODA'"
                DbConnection.EseguiSQL(StrQ)
            End If
        End If

        If chiamata = "NEXT" And TurnoChiama > 0 Then
            Chiamato = ChiamaProssimo(TurnoChiama, sportello)
        End If
        nTurno = TurnoChiama

        Return Chiamato

    End Function

    Private Function sendImage(serverIp As String, port As Integer, filePath As String) As Boolean
        Try

            If Not File.Exists(filePath) Then
                Return False
            End If

            Using client As New TcpClient(serverIp, port)
                Logga("Connesso al server.")

                Using stream As NetworkStream = client.GetStream()
                    ' Leggi i byte dell'immagine
                    Dim imageBytes As Byte() = File.ReadAllBytes(filePath)

                    ' 1. Invia la dimensione del file
                    Dim fileSize As Integer = imageBytes.Length
                    Dim sizeBuffer As Byte() = BitConverter.GetBytes(fileSize)
                    stream.Write(sizeBuffer, 0, 4)

                    ' 2. Invia i dati dell'immagine
                    stream.Write(imageBytes, 0, imageBytes.Length)
                End Using
            End Using
            Return True
        Catch ex As SocketException
            If ex.SocketErrorCode = SocketError.ConnectionRefused Then
                Logga("Assicurati che il server sia in esecuzione e l'IP/porta siano corretti.")
            End If
            Return False
        Catch ex As IOException
            Logga("IOException: " & ex.Message)
            Return False
        Catch ex As Exception
            Logga("Errore: " & ex.Message)
            Return False
        End Try
    End Function

End Module
