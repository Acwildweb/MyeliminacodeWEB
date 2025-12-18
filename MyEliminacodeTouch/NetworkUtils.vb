Imports System.Net
Imports System.Net.Sockets

Public Module NetworkUtils

    Public Function GetLocalIPv4Address() As String
        Dim hostName As String = Dns.GetHostName() ' Ottiene il nome host del PC locale
        Dim hostEntry As IPHostEntry = Dns.GetHostEntry(hostName) ' Ottiene le informazioni sull'host

        For Each address As IPAddress In hostEntry.AddressList
            ' Filtra per indirizzi IPv4
            If address.AddressFamily = AddressFamily.InterNetwork Then
                ' Restituisce il primo indirizzo IPv4 trovato
                ' Potresti voler aggiungere logica per scegliere tra più interfacce
                ' o per escludere indirizzi specifici se necessario (es. loopback 127.0.0.1)
                Return address.ToString()
            End If
        Next

        Return String.Empty ' Restituisce una stringa vuota se nessun indirizzo IPv4 viene trovato
    End Function

    Public Function GetActiveLocalIPv4Address() As String
        ' Questo metodo tenta di trovare un indirizzo IPv4 non di loopback
        ' che sia operativo su una delle interfacce di rete.
        For Each ni As Net.NetworkInformation.NetworkInterface In Net.NetworkInformation.NetworkInterface.GetAllNetworkInterfaces()
            ' Considera solo le interfacce operative e non di loopback
            If ni.OperationalStatus = Net.NetworkInformation.OperationalStatus.Up AndAlso
               ni.NetworkInterfaceType <> Net.NetworkInformation.NetworkInterfaceType.Loopback AndAlso
               ni.NetworkInterfaceType <> Net.NetworkInformation.NetworkInterfaceType.Tunnel Then

                For Each ip As Net.NetworkInformation.UnicastIPAddressInformation In ni.GetIPProperties().UnicastAddresses
                    If ip.Address.AddressFamily = Sockets.AddressFamily.InterNetwork Then
                        ' Restituisce il primo indirizzo IPv4 trovato su un'interfaccia attiva e non di loopback
                        Return ip.Address.ToString()
                    End If
                Next
            End If
        Next
        Return String.Empty ' O gestisci il caso in cui non ne viene trovato nessuno
    End Function

End Module