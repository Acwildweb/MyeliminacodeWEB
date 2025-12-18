<Global.Microsoft.VisualBasic.CompilerServices.DesignerGenerated()> _
Partial Class Monitor
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
    'Non modificarla nell'editor del codice.
    <System.Diagnostics.DebuggerStepThrough()> _
    Private Sub InitializeComponent()
        Me.components = New System.ComponentModel.Container()
        Dim resources As System.ComponentModel.ComponentResourceManager = New System.ComponentModel.ComponentResourceManager(GetType(Monitor))
        Me.TurnoALbl = New System.Windows.Forms.Label()
        Me.TurnoBLbl = New System.Windows.Forms.Label()
        Me.TurnoCLbl = New System.Windows.Forms.Label()
        Me.SportelloALbl = New System.Windows.Forms.Label()
        Me.Timer1 = New System.Windows.Forms.Timer(Me.components)
        Me.meteo = New System.Windows.Forms.WebBrowser()
        Me.WebBrowser1 = New System.Windows.Forms.WebBrowser()
        Me.WebBrowser2 = New System.Windows.Forms.WebBrowser()
        Me.wmp = New AxWMPLib.AxWindowsMediaPlayer()
        Me.Timer2 = New System.Windows.Forms.Timer(Me.components)
        Me.SchermoA = New System.Windows.Forms.PictureBox()
        Me.SchermoB = New System.Windows.Forms.PictureBox()
        Me.SchermoC = New System.Windows.Forms.PictureBox()
        Me.Panel1 = New System.Windows.Forms.Panel()
        Me.Label11 = New System.Windows.Forms.Label()
        Me.Label1 = New System.Windows.Forms.Label()
        Me.NSpoALbl = New System.Windows.Forms.Label()
        Me.Panel2 = New System.Windows.Forms.Panel()
        Me.Label13 = New System.Windows.Forms.Label()
        Me.SportelloBLbl = New System.Windows.Forms.Label()
        Me.Label2 = New System.Windows.Forms.Label()
        Me.NSpoBLbl = New System.Windows.Forms.Label()
        Me.Panel3 = New System.Windows.Forms.Panel()
        Me.Label15 = New System.Windows.Forms.Label()
        Me.SportelloCLbl = New System.Windows.Forms.Label()
        Me.Label3 = New System.Windows.Forms.Label()
        Me.NSpoCLbl = New System.Windows.Forms.Label()
        Me.Panel4 = New System.Windows.Forms.Panel()
        Me.Label27 = New System.Windows.Forms.Label()
        Me.SportelloDLbl = New System.Windows.Forms.Label()
        Me.Label4 = New System.Windows.Forms.Label()
        Me.NSpoDLbl = New System.Windows.Forms.Label()
        Me.Panel5 = New System.Windows.Forms.Panel()
        Me.Label29 = New System.Windows.Forms.Label()
        Me.SportelloELbl = New System.Windows.Forms.Label()
        Me.Label5 = New System.Windows.Forms.Label()
        Me.NSpoELbl = New System.Windows.Forms.Label()
        Me.Panel6 = New System.Windows.Forms.Panel()
        Me.Label17 = New System.Windows.Forms.Label()
        Me.SportelloFLbl = New System.Windows.Forms.Label()
        Me.Label6 = New System.Windows.Forms.Label()
        Me.NSpoFLbl = New System.Windows.Forms.Label()
        Me.Panel7 = New System.Windows.Forms.Panel()
        Me.Label19 = New System.Windows.Forms.Label()
        Me.SportelloGLbl = New System.Windows.Forms.Label()
        Me.Label7 = New System.Windows.Forms.Label()
        Me.NSpoGLbl = New System.Windows.Forms.Label()
        Me.Panel9 = New System.Windows.Forms.Panel()
        Me.Label21 = New System.Windows.Forms.Label()
        Me.SportelloHLbl = New System.Windows.Forms.Label()
        Me.Label8 = New System.Windows.Forms.Label()
        Me.NSpoHLbl = New System.Windows.Forms.Label()
        Me.Panel10 = New System.Windows.Forms.Panel()
        Me.Label23 = New System.Windows.Forms.Label()
        Me.SportelloILbl = New System.Windows.Forms.Label()
        Me.Label9 = New System.Windows.Forms.Label()
        Me.NSpoILbl = New System.Windows.Forms.Label()
        Me.Panel11 = New System.Windows.Forms.Panel()
        Me.Label25 = New System.Windows.Forms.Label()
        Me.SportelloLLbl = New System.Windows.Forms.Label()
        Me.Label10 = New System.Windows.Forms.Label()
        Me.NSpoLLbl = New System.Windows.Forms.Label()
        Me.Scorrimento = New System.Windows.Forms.Timer(Me.components)
        Me.Label12 = New System.Windows.Forms.Label()
        Me.UC1 = New System.Windows.Forms.Label()
        Me.UC2 = New System.Windows.Forms.Label()
        Me.UC3 = New System.Windows.Forms.Label()
        Me.UC6 = New System.Windows.Forms.Label()
        Me.UC5 = New System.Windows.Forms.Label()
        Me.UC4 = New System.Windows.Forms.Label()
        Me.UC9 = New System.Windows.Forms.Label()
        Me.UC8 = New System.Windows.Forms.Label()
        Me.UC7 = New System.Windows.Forms.Label()
        Me.UC10 = New System.Windows.Forms.Label()
        Me.Label14 = New System.Windows.Forms.Label()
        CType(Me.wmp, System.ComponentModel.ISupportInitialize).BeginInit()
        CType(Me.SchermoA, System.ComponentModel.ISupportInitialize).BeginInit()
        CType(Me.SchermoB, System.ComponentModel.ISupportInitialize).BeginInit()
        CType(Me.SchermoC, System.ComponentModel.ISupportInitialize).BeginInit()
        Me.Panel1.SuspendLayout()
        Me.Panel2.SuspendLayout()
        Me.Panel3.SuspendLayout()
        Me.Panel4.SuspendLayout()
        Me.Panel5.SuspendLayout()
        Me.Panel6.SuspendLayout()
        Me.Panel7.SuspendLayout()
        Me.Panel9.SuspendLayout()
        Me.Panel10.SuspendLayout()
        Me.Panel11.SuspendLayout()
        Me.SuspendLayout()
        '
        'TurnoALbl
        '
        Me.TurnoALbl.BackColor = System.Drawing.Color.Transparent
        Me.TurnoALbl.Font = New System.Drawing.Font("Arial Rounded MT Bold", 72.0!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.TurnoALbl.ForeColor = System.Drawing.Color.White
        Me.TurnoALbl.Location = New System.Drawing.Point(14257, 260)
        Me.TurnoALbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.TurnoALbl.Name = "TurnoALbl"
        Me.TurnoALbl.Size = New System.Drawing.Size(383, 134)
        Me.TurnoALbl.TabIndex = 0
        Me.TurnoALbl.Text = "000"
        Me.TurnoALbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'TurnoBLbl
        '
        Me.TurnoBLbl.BackColor = System.Drawing.Color.Transparent
        Me.TurnoBLbl.Font = New System.Drawing.Font("Arial Rounded MT Bold", 72.0!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.TurnoBLbl.ForeColor = System.Drawing.Color.White
        Me.TurnoBLbl.Location = New System.Drawing.Point(14257, 474)
        Me.TurnoBLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.TurnoBLbl.Name = "TurnoBLbl"
        Me.TurnoBLbl.Size = New System.Drawing.Size(383, 134)
        Me.TurnoBLbl.TabIndex = 1
        Me.TurnoBLbl.Text = "000"
        Me.TurnoBLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'TurnoCLbl
        '
        Me.TurnoCLbl.BackColor = System.Drawing.Color.Transparent
        Me.TurnoCLbl.Font = New System.Drawing.Font("Arial Rounded MT Bold", 72.0!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.TurnoCLbl.ForeColor = System.Drawing.Color.White
        Me.TurnoCLbl.Location = New System.Drawing.Point(32767, 481)
        Me.TurnoCLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.TurnoCLbl.Name = "TurnoCLbl"
        Me.TurnoCLbl.Size = New System.Drawing.Size(383, 134)
        Me.TurnoCLbl.TabIndex = 2
        Me.TurnoCLbl.Text = "000"
        Me.TurnoCLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'SportelloALbl
        '
        Me.SportelloALbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloALbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloALbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloALbl.Location = New System.Drawing.Point(384, 30)
        Me.SportelloALbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloALbl.Name = "SportelloALbl"
        Me.SportelloALbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloALbl.TabIndex = 3
        Me.SportelloALbl.Tag = "1"
        Me.SportelloALbl.Text = "0"
        Me.SportelloALbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Timer1
        '
        Me.Timer1.Interval = 1000
        '
        'meteo
        '
        Me.meteo.Location = New System.Drawing.Point(13333, -20)
        Me.meteo.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.meteo.MinimumSize = New System.Drawing.Size(27, 25)
        Me.meteo.Name = "meteo"
        Me.meteo.ScrollBarsEnabled = False
        Me.meteo.Size = New System.Drawing.Size(805, 170)
        Me.meteo.TabIndex = 6
        Me.meteo.Url = New System.Uri("", System.UriKind.Relative)
        Me.meteo.Visible = False
        '
        'WebBrowser1
        '
        Me.WebBrowser1.Location = New System.Drawing.Point(13333, 272)
        Me.WebBrowser1.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.WebBrowser1.MinimumSize = New System.Drawing.Size(27, 25)
        Me.WebBrowser1.Name = "WebBrowser1"
        Me.WebBrowser1.ScrollBarsEnabled = False
        Me.WebBrowser1.Size = New System.Drawing.Size(233, 118)
        Me.WebBrowser1.TabIndex = 7
        Me.WebBrowser1.Url = New System.Uri("", System.UriKind.Relative)
        Me.WebBrowser1.Visible = False
        '
        'WebBrowser2
        '
        Me.WebBrowser2.Location = New System.Drawing.Point(40, 1115)
        Me.WebBrowser2.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.WebBrowser2.MinimumSize = New System.Drawing.Size(27, 25)
        Me.WebBrowser2.Name = "WebBrowser2"
        Me.WebBrowser2.ScrollBarsEnabled = False
        Me.WebBrowser2.Size = New System.Drawing.Size(2449, 102)
        Me.WebBrowser2.TabIndex = 8
        Me.WebBrowser2.Url = New System.Uri("", System.UriKind.Relative)
        '
        'wmp
        '
        Me.wmp.Enabled = True
        Me.wmp.Location = New System.Drawing.Point(584, 633)
        Me.wmp.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.wmp.Name = "wmp"
        Me.wmp.OcxState = CType(resources.GetObject("wmp.OcxState"), System.Windows.Forms.AxHost.State)
        Me.wmp.Size = New System.Drawing.Size(268, 265)
        Me.wmp.TabIndex = 9
        Me.wmp.Visible = False
        '
        'Timer2
        '
        Me.Timer2.Enabled = True
        Me.Timer2.Interval = 60000
        '
        'SchermoA
        '
        Me.SchermoA.BackColor = System.Drawing.Color.FromArgb(CType(CType(255, Byte), Integer), CType(CType(128, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.SchermoA.Location = New System.Drawing.Point(15128, 348)
        Me.SchermoA.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.SchermoA.Name = "SchermoA"
        Me.SchermoA.Size = New System.Drawing.Size(20, 23)
        Me.SchermoA.TabIndex = 10
        Me.SchermoA.TabStop = False
        Me.SchermoA.Visible = False
        '
        'SchermoB
        '
        Me.SchermoB.BackColor = System.Drawing.Color.FromArgb(CType(CType(0, Byte), Integer), CType(CType(192, Byte), Integer), CType(CType(192, Byte), Integer))
        Me.SchermoB.Location = New System.Drawing.Point(15128, 318)
        Me.SchermoB.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.SchermoB.Name = "SchermoB"
        Me.SchermoB.Size = New System.Drawing.Size(27, 23)
        Me.SchermoB.TabIndex = 11
        Me.SchermoB.TabStop = False
        Me.SchermoB.Visible = False
        '
        'SchermoC
        '
        Me.SchermoC.BackColor = System.Drawing.Color.MediumAquamarine
        Me.SchermoC.Location = New System.Drawing.Point(15129, 290)
        Me.SchermoC.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.SchermoC.Name = "SchermoC"
        Me.SchermoC.Size = New System.Drawing.Size(25, 20)
        Me.SchermoC.TabIndex = 12
        Me.SchermoC.TabStop = False
        Me.SchermoC.Visible = False
        '
        'Panel1
        '
        Me.Panel1.BackColor = System.Drawing.Color.Transparent
        Me.Panel1.BackgroundImage = CType(resources.GetObject("Panel1.BackgroundImage"), System.Drawing.Image)
        Me.Panel1.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel1.Controls.Add(Me.Label11)
        Me.Panel1.Controls.Add(Me.Label1)
        Me.Panel1.Controls.Add(Me.SportelloALbl)
        Me.Panel1.Controls.Add(Me.NSpoALbl)
        Me.Panel1.Location = New System.Drawing.Point(12, 53)
        Me.Panel1.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel1.Name = "Panel1"
        Me.Panel1.Size = New System.Drawing.Size(689, 153)
        Me.Panel1.TabIndex = 13
        Me.Panel1.Tag = "1"
        '
        'Label11
        '
        Me.Label11.AutoSize = True
        Me.Label11.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label11.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label11.Location = New System.Drawing.Point(304, 30)
        Me.Label11.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label11.Name = "Label11"
        Me.Label11.Size = New System.Drawing.Size(84, 20)
        Me.Label11.TabIndex = 23
        Me.Label11.Text = "Sportello"
        '
        'Label1
        '
        Me.Label1.AutoSize = True
        Me.Label1.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label1.ForeColor = System.Drawing.Color.White
        Me.Label1.Location = New System.Drawing.Point(89, 30)
        Me.Label1.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label1.Name = "Label1"
        Me.Label1.Size = New System.Drawing.Size(93, 91)
        Me.Label1.TabIndex = 4
        Me.Label1.Text = "A"
        '
        'NSpoALbl
        '
        Me.NSpoALbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoALbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoALbl.Location = New System.Drawing.Point(307, 54)
        Me.NSpoALbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoALbl.Name = "NSpoALbl"
        Me.NSpoALbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoALbl.TabIndex = 23
        Me.NSpoALbl.Tag = "1"
        Me.NSpoALbl.Text = "0"
        Me.NSpoALbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel2
        '
        Me.Panel2.BackColor = System.Drawing.Color.Transparent
        Me.Panel2.BackgroundImage = CType(resources.GetObject("Panel2.BackgroundImage"), System.Drawing.Image)
        Me.Panel2.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel2.Controls.Add(Me.Label13)
        Me.Panel2.Controls.Add(Me.SportelloBLbl)
        Me.Panel2.Controls.Add(Me.Label2)
        Me.Panel2.Controls.Add(Me.NSpoBLbl)
        Me.Panel2.Location = New System.Drawing.Point(12, 219)
        Me.Panel2.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel2.Name = "Panel2"
        Me.Panel2.Size = New System.Drawing.Size(689, 153)
        Me.Panel2.TabIndex = 14
        Me.Panel2.Tag = "2"
        '
        'Label13
        '
        Me.Label13.AutoSize = True
        Me.Label13.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label13.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label13.Location = New System.Drawing.Point(301, 27)
        Me.Label13.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label13.Name = "Label13"
        Me.Label13.Size = New System.Drawing.Size(84, 20)
        Me.Label13.TabIndex = 24
        Me.Label13.Text = "Sportello"
        '
        'SportelloBLbl
        '
        Me.SportelloBLbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloBLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloBLbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloBLbl.Location = New System.Drawing.Point(384, 27)
        Me.SportelloBLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloBLbl.Name = "SportelloBLbl"
        Me.SportelloBLbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloBLbl.TabIndex = 6
        Me.SportelloBLbl.Tag = "2"
        Me.SportelloBLbl.Text = "0"
        Me.SportelloBLbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label2
        '
        Me.Label2.AutoSize = True
        Me.Label2.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label2.ForeColor = System.Drawing.Color.White
        Me.Label2.Location = New System.Drawing.Point(88, 27)
        Me.Label2.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label2.Name = "Label2"
        Me.Label2.Size = New System.Drawing.Size(93, 91)
        Me.Label2.TabIndex = 5
        Me.Label2.Text = "B"
        '
        'NSpoBLbl
        '
        Me.NSpoBLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoBLbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoBLbl.Location = New System.Drawing.Point(311, 55)
        Me.NSpoBLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoBLbl.Name = "NSpoBLbl"
        Me.NSpoBLbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoBLbl.TabIndex = 25
        Me.NSpoBLbl.Tag = "2"
        Me.NSpoBLbl.Text = "0"
        Me.NSpoBLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel3
        '
        Me.Panel3.BackColor = System.Drawing.Color.Transparent
        Me.Panel3.BackgroundImage = CType(resources.GetObject("Panel3.BackgroundImage"), System.Drawing.Image)
        Me.Panel3.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel3.Controls.Add(Me.Label15)
        Me.Panel3.Controls.Add(Me.SportelloCLbl)
        Me.Panel3.Controls.Add(Me.Label3)
        Me.Panel3.Controls.Add(Me.NSpoCLbl)
        Me.Panel3.Location = New System.Drawing.Point(12, 379)
        Me.Panel3.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel3.Name = "Panel3"
        Me.Panel3.Size = New System.Drawing.Size(689, 153)
        Me.Panel3.TabIndex = 15
        Me.Panel3.Tag = "3"
        '
        'Label15
        '
        Me.Label15.AutoSize = True
        Me.Label15.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label15.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label15.Location = New System.Drawing.Point(301, 18)
        Me.Label15.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label15.Name = "Label15"
        Me.Label15.Size = New System.Drawing.Size(84, 20)
        Me.Label15.TabIndex = 24
        Me.Label15.Text = "Sportello"
        '
        'SportelloCLbl
        '
        Me.SportelloCLbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloCLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloCLbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloCLbl.Location = New System.Drawing.Point(384, 27)
        Me.SportelloCLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloCLbl.Name = "SportelloCLbl"
        Me.SportelloCLbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloCLbl.TabIndex = 7
        Me.SportelloCLbl.Tag = "3"
        Me.SportelloCLbl.Text = "0"
        Me.SportelloCLbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label3
        '
        Me.Label3.AutoSize = True
        Me.Label3.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label3.ForeColor = System.Drawing.Color.White
        Me.Label3.Location = New System.Drawing.Point(89, 27)
        Me.Label3.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label3.Name = "Label3"
        Me.Label3.Size = New System.Drawing.Size(98, 91)
        Me.Label3.TabIndex = 6
        Me.Label3.Text = "C"
        '
        'NSpoCLbl
        '
        Me.NSpoCLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoCLbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoCLbl.Location = New System.Drawing.Point(304, 41)
        Me.NSpoCLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoCLbl.Name = "NSpoCLbl"
        Me.NSpoCLbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoCLbl.TabIndex = 25
        Me.NSpoCLbl.Tag = "3"
        Me.NSpoCLbl.Text = "0"
        Me.NSpoCLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel4
        '
        Me.Panel4.BackColor = System.Drawing.Color.Transparent
        Me.Panel4.BackgroundImage = CType(resources.GetObject("Panel4.BackgroundImage"), System.Drawing.Image)
        Me.Panel4.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel4.Controls.Add(Me.Label27)
        Me.Panel4.Controls.Add(Me.SportelloDLbl)
        Me.Panel4.Controls.Add(Me.Label4)
        Me.Panel4.Controls.Add(Me.NSpoDLbl)
        Me.Panel4.Location = New System.Drawing.Point(12, 539)
        Me.Panel4.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel4.Name = "Panel4"
        Me.Panel4.Size = New System.Drawing.Size(689, 153)
        Me.Panel4.TabIndex = 16
        Me.Panel4.Tag = "4"
        '
        'Label27
        '
        Me.Label27.AutoSize = True
        Me.Label27.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label27.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label27.Location = New System.Drawing.Point(295, 37)
        Me.Label27.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label27.Name = "Label27"
        Me.Label27.Size = New System.Drawing.Size(84, 20)
        Me.Label27.TabIndex = 24
        Me.Label27.Text = "Sportello"
        '
        'SportelloDLbl
        '
        Me.SportelloDLbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloDLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloDLbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloDLbl.Location = New System.Drawing.Point(384, 27)
        Me.SportelloDLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloDLbl.Name = "SportelloDLbl"
        Me.SportelloDLbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloDLbl.TabIndex = 8
        Me.SportelloDLbl.Tag = "4"
        Me.SportelloDLbl.Text = "0"
        Me.SportelloDLbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label4
        '
        Me.Label4.AutoSize = True
        Me.Label4.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label4.ForeColor = System.Drawing.Color.White
        Me.Label4.Location = New System.Drawing.Point(89, 27)
        Me.Label4.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label4.Name = "Label4"
        Me.Label4.Size = New System.Drawing.Size(98, 91)
        Me.Label4.TabIndex = 7
        Me.Label4.Text = "D"
        '
        'NSpoDLbl
        '
        Me.NSpoDLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoDLbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoDLbl.Location = New System.Drawing.Point(297, 59)
        Me.NSpoDLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoDLbl.Name = "NSpoDLbl"
        Me.NSpoDLbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoDLbl.TabIndex = 25
        Me.NSpoDLbl.Tag = "4"
        Me.NSpoDLbl.Text = "0"
        Me.NSpoDLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel5
        '
        Me.Panel5.BackColor = System.Drawing.Color.Transparent
        Me.Panel5.BackgroundImage = CType(resources.GetObject("Panel5.BackgroundImage"), System.Drawing.Image)
        Me.Panel5.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel5.Controls.Add(Me.Label29)
        Me.Panel5.Controls.Add(Me.SportelloELbl)
        Me.Panel5.Controls.Add(Me.Label5)
        Me.Panel5.Controls.Add(Me.NSpoELbl)
        Me.Panel5.Location = New System.Drawing.Point(12, 699)
        Me.Panel5.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel5.Name = "Panel5"
        Me.Panel5.Size = New System.Drawing.Size(689, 153)
        Me.Panel5.TabIndex = 17
        Me.Panel5.Tag = "5"
        '
        'Label29
        '
        Me.Label29.AutoSize = True
        Me.Label29.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label29.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label29.Location = New System.Drawing.Point(288, 31)
        Me.Label29.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label29.Name = "Label29"
        Me.Label29.Size = New System.Drawing.Size(84, 20)
        Me.Label29.TabIndex = 24
        Me.Label29.Text = "Sportello"
        '
        'SportelloELbl
        '
        Me.SportelloELbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloELbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloELbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloELbl.Location = New System.Drawing.Point(384, 31)
        Me.SportelloELbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloELbl.Name = "SportelloELbl"
        Me.SportelloELbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloELbl.TabIndex = 8
        Me.SportelloELbl.Tag = "5"
        Me.SportelloELbl.Text = "0"
        Me.SportelloELbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label5
        '
        Me.Label5.AutoSize = True
        Me.Label5.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label5.ForeColor = System.Drawing.Color.White
        Me.Label5.Location = New System.Drawing.Point(89, 26)
        Me.Label5.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label5.Name = "Label5"
        Me.Label5.Size = New System.Drawing.Size(93, 91)
        Me.Label5.TabIndex = 7
        Me.Label5.Text = "E"
        '
        'NSpoELbl
        '
        Me.NSpoELbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoELbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoELbl.Location = New System.Drawing.Point(291, 53)
        Me.NSpoELbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoELbl.Name = "NSpoELbl"
        Me.NSpoELbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoELbl.TabIndex = 25
        Me.NSpoELbl.Tag = "5"
        Me.NSpoELbl.Text = "0"
        Me.NSpoELbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel6
        '
        Me.Panel6.BackColor = System.Drawing.Color.Transparent
        Me.Panel6.BackgroundImage = CType(resources.GetObject("Panel6.BackgroundImage"), System.Drawing.Image)
        Me.Panel6.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel6.Controls.Add(Me.Label17)
        Me.Panel6.Controls.Add(Me.SportelloFLbl)
        Me.Panel6.Controls.Add(Me.Label6)
        Me.Panel6.Controls.Add(Me.NSpoFLbl)
        Me.Panel6.Location = New System.Drawing.Point(1029, 53)
        Me.Panel6.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel6.Name = "Panel6"
        Me.Panel6.Size = New System.Drawing.Size(689, 153)
        Me.Panel6.TabIndex = 18
        Me.Panel6.Tag = "6"
        '
        'Label17
        '
        Me.Label17.AutoSize = True
        Me.Label17.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label17.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label17.Location = New System.Drawing.Point(300, 37)
        Me.Label17.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label17.Name = "Label17"
        Me.Label17.Size = New System.Drawing.Size(84, 20)
        Me.Label17.TabIndex = 24
        Me.Label17.Text = "Sportello"
        '
        'SportelloFLbl
        '
        Me.SportelloFLbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloFLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloFLbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloFLbl.Location = New System.Drawing.Point(376, 30)
        Me.SportelloFLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloFLbl.Name = "SportelloFLbl"
        Me.SportelloFLbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloFLbl.TabIndex = 8
        Me.SportelloFLbl.Tag = "6"
        Me.SportelloFLbl.Text = "0"
        Me.SportelloFLbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label6
        '
        Me.Label6.AutoSize = True
        Me.Label6.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label6.ForeColor = System.Drawing.Color.White
        Me.Label6.Location = New System.Drawing.Point(99, 27)
        Me.Label6.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label6.Name = "Label6"
        Me.Label6.Size = New System.Drawing.Size(89, 91)
        Me.Label6.TabIndex = 7
        Me.Label6.Text = "F"
        '
        'NSpoFLbl
        '
        Me.NSpoFLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoFLbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoFLbl.Location = New System.Drawing.Point(303, 59)
        Me.NSpoFLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoFLbl.Name = "NSpoFLbl"
        Me.NSpoFLbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoFLbl.TabIndex = 25
        Me.NSpoFLbl.Tag = "6"
        Me.NSpoFLbl.Text = "0"
        Me.NSpoFLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel7
        '
        Me.Panel7.BackColor = System.Drawing.Color.Transparent
        Me.Panel7.BackgroundImage = CType(resources.GetObject("Panel7.BackgroundImage"), System.Drawing.Image)
        Me.Panel7.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel7.Controls.Add(Me.Label19)
        Me.Panel7.Controls.Add(Me.SportelloGLbl)
        Me.Panel7.Controls.Add(Me.Label7)
        Me.Panel7.Controls.Add(Me.NSpoGLbl)
        Me.Panel7.Location = New System.Drawing.Point(1029, 219)
        Me.Panel7.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel7.Name = "Panel7"
        Me.Panel7.Size = New System.Drawing.Size(689, 153)
        Me.Panel7.TabIndex = 19
        Me.Panel7.Tag = "7"
        '
        'Label19
        '
        Me.Label19.AutoSize = True
        Me.Label19.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label19.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label19.Location = New System.Drawing.Point(300, 33)
        Me.Label19.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label19.Name = "Label19"
        Me.Label19.Size = New System.Drawing.Size(84, 20)
        Me.Label19.TabIndex = 24
        Me.Label19.Text = "Sportello"
        '
        'SportelloGLbl
        '
        Me.SportelloGLbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloGLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloGLbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloGLbl.Location = New System.Drawing.Point(376, 23)
        Me.SportelloGLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloGLbl.Name = "SportelloGLbl"
        Me.SportelloGLbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloGLbl.TabIndex = 8
        Me.SportelloGLbl.Tag = "7"
        Me.SportelloGLbl.Text = "0"
        Me.SportelloGLbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label7
        '
        Me.Label7.AutoSize = True
        Me.Label7.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label7.ForeColor = System.Drawing.Color.White
        Me.Label7.Location = New System.Drawing.Point(91, 27)
        Me.Label7.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label7.Name = "Label7"
        Me.Label7.Size = New System.Drawing.Size(102, 91)
        Me.Label7.TabIndex = 7
        Me.Label7.Text = "G"
        '
        'NSpoGLbl
        '
        Me.NSpoGLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoGLbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoGLbl.Location = New System.Drawing.Point(303, 55)
        Me.NSpoGLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoGLbl.Name = "NSpoGLbl"
        Me.NSpoGLbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoGLbl.TabIndex = 25
        Me.NSpoGLbl.Tag = "7"
        Me.NSpoGLbl.Text = "0"
        Me.NSpoGLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel9
        '
        Me.Panel9.BackColor = System.Drawing.Color.Transparent
        Me.Panel9.BackgroundImage = CType(resources.GetObject("Panel9.BackgroundImage"), System.Drawing.Image)
        Me.Panel9.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel9.Controls.Add(Me.Label21)
        Me.Panel9.Controls.Add(Me.SportelloHLbl)
        Me.Panel9.Controls.Add(Me.Label8)
        Me.Panel9.Controls.Add(Me.NSpoHLbl)
        Me.Panel9.Location = New System.Drawing.Point(1029, 379)
        Me.Panel9.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel9.Name = "Panel9"
        Me.Panel9.Size = New System.Drawing.Size(689, 153)
        Me.Panel9.TabIndex = 20
        Me.Panel9.Tag = "8"
        '
        'Label21
        '
        Me.Label21.AutoSize = True
        Me.Label21.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label21.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label21.Location = New System.Drawing.Point(300, 37)
        Me.Label21.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label21.Name = "Label21"
        Me.Label21.Size = New System.Drawing.Size(84, 20)
        Me.Label21.TabIndex = 24
        Me.Label21.Text = "Sportello"
        '
        'SportelloHLbl
        '
        Me.SportelloHLbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloHLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloHLbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloHLbl.Location = New System.Drawing.Point(376, 27)
        Me.SportelloHLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloHLbl.Name = "SportelloHLbl"
        Me.SportelloHLbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloHLbl.TabIndex = 8
        Me.SportelloHLbl.Tag = "8"
        Me.SportelloHLbl.Text = "0"
        Me.SportelloHLbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label8
        '
        Me.Label8.AutoSize = True
        Me.Label8.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label8.ForeColor = System.Drawing.Color.White
        Me.Label8.Location = New System.Drawing.Point(92, 27)
        Me.Label8.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label8.Name = "Label8"
        Me.Label8.Size = New System.Drawing.Size(98, 91)
        Me.Label8.TabIndex = 7
        Me.Label8.Text = "H"
        '
        'NSpoHLbl
        '
        Me.NSpoHLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoHLbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoHLbl.Location = New System.Drawing.Point(303, 59)
        Me.NSpoHLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoHLbl.Name = "NSpoHLbl"
        Me.NSpoHLbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoHLbl.TabIndex = 25
        Me.NSpoHLbl.Tag = "8"
        Me.NSpoHLbl.Text = "0"
        Me.NSpoHLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel10
        '
        Me.Panel10.BackColor = System.Drawing.Color.Transparent
        Me.Panel10.BackgroundImage = CType(resources.GetObject("Panel10.BackgroundImage"), System.Drawing.Image)
        Me.Panel10.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel10.Controls.Add(Me.Label23)
        Me.Panel10.Controls.Add(Me.SportelloILbl)
        Me.Panel10.Controls.Add(Me.Label9)
        Me.Panel10.Controls.Add(Me.NSpoILbl)
        Me.Panel10.Location = New System.Drawing.Point(1029, 539)
        Me.Panel10.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel10.Name = "Panel10"
        Me.Panel10.Size = New System.Drawing.Size(689, 153)
        Me.Panel10.TabIndex = 21
        Me.Panel10.Tag = "9"
        '
        'Label23
        '
        Me.Label23.AutoSize = True
        Me.Label23.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label23.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label23.Location = New System.Drawing.Point(300, 37)
        Me.Label23.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label23.Name = "Label23"
        Me.Label23.Size = New System.Drawing.Size(84, 20)
        Me.Label23.TabIndex = 24
        Me.Label23.Text = "Sportello"
        '
        'SportelloILbl
        '
        Me.SportelloILbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloILbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloILbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloILbl.Location = New System.Drawing.Point(376, 27)
        Me.SportelloILbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloILbl.Name = "SportelloILbl"
        Me.SportelloILbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloILbl.TabIndex = 8
        Me.SportelloILbl.Tag = "9"
        Me.SportelloILbl.Text = "0"
        Me.SportelloILbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label9
        '
        Me.Label9.AutoSize = True
        Me.Label9.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label9.ForeColor = System.Drawing.Color.White
        Me.Label9.Location = New System.Drawing.Point(111, 27)
        Me.Label9.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label9.Name = "Label9"
        Me.Label9.Size = New System.Drawing.Size(62, 91)
        Me.Label9.TabIndex = 7
        Me.Label9.Text = "I"
        Me.Label9.TextAlign = System.Drawing.ContentAlignment.TopCenter
        '
        'NSpoILbl
        '
        Me.NSpoILbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoILbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoILbl.Location = New System.Drawing.Point(303, 59)
        Me.NSpoILbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoILbl.Name = "NSpoILbl"
        Me.NSpoILbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoILbl.TabIndex = 25
        Me.NSpoILbl.Tag = "9"
        Me.NSpoILbl.Text = "0"
        Me.NSpoILbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Panel11
        '
        Me.Panel11.BackColor = System.Drawing.Color.Transparent
        Me.Panel11.BackgroundImage = CType(resources.GetObject("Panel11.BackgroundImage"), System.Drawing.Image)
        Me.Panel11.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch
        Me.Panel11.Controls.Add(Me.Label25)
        Me.Panel11.Controls.Add(Me.SportelloLLbl)
        Me.Panel11.Controls.Add(Me.Label10)
        Me.Panel11.Controls.Add(Me.NSpoLLbl)
        Me.Panel11.Location = New System.Drawing.Point(1029, 699)
        Me.Panel11.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Panel11.Name = "Panel11"
        Me.Panel11.Size = New System.Drawing.Size(689, 153)
        Me.Panel11.TabIndex = 22
        Me.Panel11.Tag = "10"
        '
        'Label25
        '
        Me.Label25.AutoSize = True
        Me.Label25.Font = New System.Drawing.Font("Microsoft Sans Serif", 9.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label25.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label25.Location = New System.Drawing.Point(300, 37)
        Me.Label25.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label25.Name = "Label25"
        Me.Label25.Size = New System.Drawing.Size(84, 20)
        Me.Label25.TabIndex = 24
        Me.Label25.Text = "Sportello"
        '
        'SportelloLLbl
        '
        Me.SportelloLLbl.BackColor = System.Drawing.Color.Transparent
        Me.SportelloLLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.SportelloLLbl.ForeColor = System.Drawing.Color.Black
        Me.SportelloLLbl.Location = New System.Drawing.Point(376, 26)
        Me.SportelloLLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.SportelloLLbl.Name = "SportelloLLbl"
        Me.SportelloLLbl.Size = New System.Drawing.Size(247, 95)
        Me.SportelloLLbl.TabIndex = 8
        Me.SportelloLLbl.Tag = "10"
        Me.SportelloLLbl.Text = "0"
        Me.SportelloLLbl.TextAlign = System.Drawing.ContentAlignment.MiddleRight
        '
        'Label10
        '
        Me.Label10.AutoSize = True
        Me.Label10.Font = New System.Drawing.Font("Microsoft Sans Serif", 48.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label10.ForeColor = System.Drawing.Color.White
        Me.Label10.Location = New System.Drawing.Point(97, 26)
        Me.Label10.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label10.Name = "Label10"
        Me.Label10.Size = New System.Drawing.Size(84, 91)
        Me.Label10.TabIndex = 7
        Me.Label10.Text = "L"
        '
        'NSpoLLbl
        '
        Me.NSpoLLbl.Font = New System.Drawing.Font("Microsoft Sans Serif", 27.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.NSpoLLbl.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.NSpoLLbl.Location = New System.Drawing.Point(303, 59)
        Me.NSpoLLbl.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.NSpoLLbl.Name = "NSpoLLbl"
        Me.NSpoLLbl.Size = New System.Drawing.Size(85, 55)
        Me.NSpoLLbl.TabIndex = 25
        Me.NSpoLLbl.Tag = "10"
        Me.NSpoLLbl.Text = "0"
        Me.NSpoLLbl.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Scorrimento
        '
        Me.Scorrimento.Interval = 1
        '
        'Label12
        '
        Me.Label12.BackColor = System.Drawing.Color.Transparent
        Me.Label12.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label12.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label12.Location = New System.Drawing.Point(709, 474)
        Me.Label12.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label12.Name = "Label12"
        Me.Label12.Size = New System.Drawing.Size(312, 28)
        Me.Label12.TabIndex = 24
        Me.Label12.Text = "Ultimi chiamati"
        Me.Label12.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC1
        '
        Me.UC1.BackColor = System.Drawing.Color.Transparent
        Me.UC1.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC1.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC1.Location = New System.Drawing.Point(709, 543)
        Me.UC1.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC1.Name = "UC1"
        Me.UC1.Size = New System.Drawing.Size(312, 28)
        Me.UC1.TabIndex = 25
        Me.UC1.Text = "--"
        Me.UC1.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC2
        '
        Me.UC2.BackColor = System.Drawing.Color.Transparent
        Me.UC2.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC2.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC2.Location = New System.Drawing.Point(709, 575)
        Me.UC2.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC2.Name = "UC2"
        Me.UC2.Size = New System.Drawing.Size(312, 28)
        Me.UC2.TabIndex = 26
        Me.UC2.Text = "--"
        Me.UC2.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC3
        '
        Me.UC3.BackColor = System.Drawing.Color.Transparent
        Me.UC3.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC3.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC3.Location = New System.Drawing.Point(709, 607)
        Me.UC3.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC3.Name = "UC3"
        Me.UC3.Size = New System.Drawing.Size(312, 28)
        Me.UC3.TabIndex = 27
        Me.UC3.Text = "--"
        Me.UC3.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC6
        '
        Me.UC6.BackColor = System.Drawing.Color.Transparent
        Me.UC6.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC6.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC6.Location = New System.Drawing.Point(709, 699)
        Me.UC6.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC6.Name = "UC6"
        Me.UC6.Size = New System.Drawing.Size(312, 28)
        Me.UC6.TabIndex = 30
        Me.UC6.Text = "--"
        Me.UC6.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC5
        '
        Me.UC5.BackColor = System.Drawing.Color.Transparent
        Me.UC5.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC5.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC5.Location = New System.Drawing.Point(709, 667)
        Me.UC5.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC5.Name = "UC5"
        Me.UC5.Size = New System.Drawing.Size(312, 28)
        Me.UC5.TabIndex = 29
        Me.UC5.Text = "--"
        Me.UC5.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC4
        '
        Me.UC4.BackColor = System.Drawing.Color.Transparent
        Me.UC4.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC4.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC4.Location = New System.Drawing.Point(709, 635)
        Me.UC4.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC4.Name = "UC4"
        Me.UC4.Size = New System.Drawing.Size(312, 28)
        Me.UC4.TabIndex = 28
        Me.UC4.Text = "--"
        Me.UC4.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC9
        '
        Me.UC9.BackColor = System.Drawing.Color.Transparent
        Me.UC9.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC9.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC9.Location = New System.Drawing.Point(709, 791)
        Me.UC9.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC9.Name = "UC9"
        Me.UC9.Size = New System.Drawing.Size(312, 28)
        Me.UC9.TabIndex = 33
        Me.UC9.Text = "--"
        Me.UC9.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC8
        '
        Me.UC8.BackColor = System.Drawing.Color.Transparent
        Me.UC8.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC8.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC8.Location = New System.Drawing.Point(709, 759)
        Me.UC8.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC8.Name = "UC8"
        Me.UC8.Size = New System.Drawing.Size(312, 28)
        Me.UC8.TabIndex = 32
        Me.UC8.Text = "--"
        Me.UC8.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC7
        '
        Me.UC7.BackColor = System.Drawing.Color.Transparent
        Me.UC7.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC7.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC7.Location = New System.Drawing.Point(709, 727)
        Me.UC7.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC7.Name = "UC7"
        Me.UC7.Size = New System.Drawing.Size(312, 28)
        Me.UC7.TabIndex = 31
        Me.UC7.Text = "--"
        Me.UC7.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'UC10
        '
        Me.UC10.BackColor = System.Drawing.Color.Transparent
        Me.UC10.Font = New System.Drawing.Font("Microsoft Sans Serif", 15.75!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.UC10.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.UC10.Location = New System.Drawing.Point(709, 823)
        Me.UC10.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.UC10.Name = "UC10"
        Me.UC10.Size = New System.Drawing.Size(312, 28)
        Me.UC10.TabIndex = 34
        Me.UC10.Text = "--"
        Me.UC10.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Label14
        '
        Me.Label14.BackColor = System.Drawing.Color.Transparent
        Me.Label14.Font = New System.Drawing.Font("Microsoft Sans Serif", 14.25!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, CType(0, Byte))
        Me.Label14.ForeColor = System.Drawing.Color.FromArgb(CType(CType(192, Byte), Integer), CType(CType(64, Byte), Integer), CType(CType(0, Byte), Integer))
        Me.Label14.Location = New System.Drawing.Point(709, 502)
        Me.Label14.Margin = New System.Windows.Forms.Padding(4, 0, 4, 0)
        Me.Label14.Name = "Label14"
        Me.Label14.Size = New System.Drawing.Size(312, 28)
        Me.Label14.TabIndex = 35
        Me.Label14.Text = "Sportello --  Numero"
        Me.Label14.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        'Monitor
        '
        Me.AutoScaleDimensions = New System.Drawing.SizeF(8.0!, 16.0!)
        Me.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font
        Me.AutoSize = True
        Me.BackgroundImage = CType(resources.GetObject("$this.BackgroundImage"), System.Drawing.Image)
        Me.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Zoom
        Me.ClientSize = New System.Drawing.Size(1733, 911)
        Me.ControlBox = False
        Me.Controls.Add(Me.Label14)
        Me.Controls.Add(Me.UC10)
        Me.Controls.Add(Me.UC9)
        Me.Controls.Add(Me.UC8)
        Me.Controls.Add(Me.UC7)
        Me.Controls.Add(Me.UC6)
        Me.Controls.Add(Me.UC5)
        Me.Controls.Add(Me.UC4)
        Me.Controls.Add(Me.UC3)
        Me.Controls.Add(Me.UC2)
        Me.Controls.Add(Me.UC1)
        Me.Controls.Add(Me.Label12)
        Me.Controls.Add(Me.Panel11)
        Me.Controls.Add(Me.Panel10)
        Me.Controls.Add(Me.Panel9)
        Me.Controls.Add(Me.Panel7)
        Me.Controls.Add(Me.Panel6)
        Me.Controls.Add(Me.Panel5)
        Me.Controls.Add(Me.Panel2)
        Me.Controls.Add(Me.Panel4)
        Me.Controls.Add(Me.Panel3)
        Me.Controls.Add(Me.Panel1)
        Me.Controls.Add(Me.SchermoC)
        Me.Controls.Add(Me.SchermoB)
        Me.Controls.Add(Me.SchermoA)
        Me.Controls.Add(Me.wmp)
        Me.Controls.Add(Me.WebBrowser2)
        Me.Controls.Add(Me.WebBrowser1)
        Me.Controls.Add(Me.meteo)
        Me.Controls.Add(Me.TurnoCLbl)
        Me.Controls.Add(Me.TurnoBLbl)
        Me.Controls.Add(Me.TurnoALbl)
        Me.FormBorderStyle = System.Windows.Forms.FormBorderStyle.None
        Me.Margin = New System.Windows.Forms.Padding(4, 4, 4, 4)
        Me.Name = "Monitor"
        Me.Text = "Monitor"
        Me.WindowState = System.Windows.Forms.FormWindowState.Maximized
        CType(Me.wmp, System.ComponentModel.ISupportInitialize).EndInit()
        CType(Me.SchermoA, System.ComponentModel.ISupportInitialize).EndInit()
        CType(Me.SchermoB, System.ComponentModel.ISupportInitialize).EndInit()
        CType(Me.SchermoC, System.ComponentModel.ISupportInitialize).EndInit()
        Me.Panel1.ResumeLayout(False)
        Me.Panel1.PerformLayout()
        Me.Panel2.ResumeLayout(False)
        Me.Panel2.PerformLayout()
        Me.Panel3.ResumeLayout(False)
        Me.Panel3.PerformLayout()
        Me.Panel4.ResumeLayout(False)
        Me.Panel4.PerformLayout()
        Me.Panel5.ResumeLayout(False)
        Me.Panel5.PerformLayout()
        Me.Panel6.ResumeLayout(False)
        Me.Panel6.PerformLayout()
        Me.Panel7.ResumeLayout(False)
        Me.Panel7.PerformLayout()
        Me.Panel9.ResumeLayout(False)
        Me.Panel9.PerformLayout()
        Me.Panel10.ResumeLayout(False)
        Me.Panel10.PerformLayout()
        Me.Panel11.ResumeLayout(False)
        Me.Panel11.PerformLayout()
        Me.ResumeLayout(False)

    End Sub
    Friend WithEvents TurnoALbl As System.Windows.Forms.Label
    Friend WithEvents TurnoBLbl As System.Windows.Forms.Label
    Friend WithEvents TurnoCLbl As System.Windows.Forms.Label
    Friend WithEvents SportelloALbl As System.Windows.Forms.Label
    Friend WithEvents Timer1 As System.Windows.Forms.Timer
    Friend WithEvents meteo As System.Windows.Forms.WebBrowser
    Friend WithEvents WebBrowser1 As System.Windows.Forms.WebBrowser
    Friend WithEvents WebBrowser2 As System.Windows.Forms.WebBrowser
    Friend WithEvents wmp As AxWMPLib.AxWindowsMediaPlayer
    Friend WithEvents Timer2 As System.Windows.Forms.Timer
    Friend WithEvents SchermoA As System.Windows.Forms.PictureBox
    Friend WithEvents SchermoB As System.Windows.Forms.PictureBox
    Friend WithEvents SchermoC As System.Windows.Forms.PictureBox
    Friend WithEvents Panel1 As Panel
    Friend WithEvents Panel2 As Panel
    Friend WithEvents Panel3 As Panel
    Friend WithEvents Panel4 As Panel
    Friend WithEvents Panel5 As Panel
    Friend WithEvents Panel6 As Panel
    Friend WithEvents Panel7 As Panel
    Friend WithEvents Panel9 As Panel
    Friend WithEvents Panel10 As Panel
    Friend WithEvents Panel11 As Panel
    Friend WithEvents Label1 As Label
    Friend WithEvents SportelloBLbl As Label
    Friend WithEvents Label2 As Label
    Friend WithEvents SportelloCLbl As Label
    Friend WithEvents Label3 As Label
    Friend WithEvents SportelloDLbl As Label
    Friend WithEvents Label4 As Label
    Friend WithEvents SportelloELbl As Label
    Friend WithEvents Label5 As Label
    Friend WithEvents SportelloFLbl As Label
    Friend WithEvents Label6 As Label
    Friend WithEvents SportelloGLbl As Label
    Friend WithEvents Label7 As Label
    Friend WithEvents SportelloHLbl As Label
    Friend WithEvents Label8 As Label
    Friend WithEvents SportelloILbl As Label
    Friend WithEvents Label9 As Label
    Friend WithEvents SportelloLLbl As Label
    Friend WithEvents Label10 As Label
    Friend WithEvents Label11 As Label
    Friend WithEvents NSpoALbl As Label
    Friend WithEvents Label13 As Label
    Friend WithEvents NSpoBLbl As Label
    Friend WithEvents Label15 As Label
    Friend WithEvents NSpoCLbl As Label
    Friend WithEvents Label27 As Label
    Friend WithEvents NSpoDLbl As Label
    Friend WithEvents Label29 As Label
    Friend WithEvents NSpoELbl As Label
    Friend WithEvents Label17 As Label
    Friend WithEvents NSpoFLbl As Label
    Friend WithEvents Label19 As Label
    Friend WithEvents NSpoGLbl As Label
    Friend WithEvents Label21 As Label
    Friend WithEvents NSpoHLbl As Label
    Friend WithEvents Label23 As Label
    Friend WithEvents NSpoILbl As Label
    Friend WithEvents Label25 As Label
    Friend WithEvents NSpoLLbl As Label
    Friend WithEvents Scorrimento As Timer
    Friend WithEvents Label12 As Label
    Friend WithEvents UC1 As Label
    Friend WithEvents UC2 As Label
    Friend WithEvents UC3 As Label
    Friend WithEvents UC6 As Label
    Friend WithEvents UC5 As Label
    Friend WithEvents UC4 As Label
    Friend WithEvents UC9 As Label
    Friend WithEvents UC8 As Label
    Friend WithEvents UC7 As Label
    Friend WithEvents UC10 As Label
    Friend WithEvents Label14 As Label
End Class
