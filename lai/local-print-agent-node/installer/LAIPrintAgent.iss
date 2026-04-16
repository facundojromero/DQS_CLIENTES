#define MyAppName "LAI Local Print Agent"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "LAI"
#define MyAppExeName "run-agent.cmd"

[Setup]
AppId={{A13D9C7C-9D4A-4C5A-917C-9B2B6E0A5A31}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\LAI Print Agent
DefaultGroupName=LAI Print Agent
OutputDir=.
OutputBaseFilename=LAI-Print-Agent-Setup
Compression=lzma
SolidCompression=yes
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
DisableProgramGroupPage=yes
WizardStyle=modern
PrivilegesRequired=admin

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "autostart"; Description: "Iniciar automáticamente al iniciar sesión"; GroupDescription: "Opciones:"; Flags: unchecked

[Files]
Source: "..\agent.js"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\config.example.json"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\run-agent.cmd"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\scripts\print_ticket.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\scripts\install_agent.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\scripts\uninstall_agent.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\runtime\*"; DestDir: "{app}\runtime"; Flags: ignoreversion recursesubdirs createallsubdirs

[Run]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\scripts\install_agent.ps1"" -InstallDir ""{app}"" -ServerHost ""127.0.0.1"" -ServerPort 5399 -ApiKey ""{code:GetApiKey}"" -PrinterName ""{code:GetPrinterName}"" -TicketWidthMm {code:GetTicketWidth} {code:GetAutoStartFlag}"; Flags: runhidden waituntilterminated
Filename: "{app}\run-agent.cmd"; Description: "Iniciar ahora el agente"; Flags: nowait postinstall skipifsilent

[UninstallRun]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\scripts\uninstall_agent.ps1"""; Flags: runhidden

[Code]
var
  ApiKeyPage: TInputQueryWizardPage;
  PrintPage: TInputQueryWizardPage;

function GetApiKey(Value: string): string;
begin
  Result := ApiKeyPage.Values[0];
end;

function GetPrinterName(Value: string): string;
begin
  Result := PrintPage.Values[0];
end;

function GetTicketWidth(Value: string): string;
begin
  Result := PrintPage.Values[1];
end;

function GetAutoStartFlag(Value: string): string;
begin
  if WizardIsTaskSelected('autostart') then
    Result := '-AutoStart'
  else
    Result := '';
end;

procedure InitializeWizard;
begin
  ApiKeyPage := CreateInputQueryPage(
    wpSelectTasks,
    'Configuración local del agente',
    'Definí credenciales y parámetros base',
    'Estos valores se guardan en config.json para esta PC.'
  );
  ApiKeyPage.Add('API Key local del agente:', False);
  ApiKeyPage.Values[0] := 'CAMBIAR_ESTA_CLAVE_LOCAL';

  PrintPage := CreateInputQueryPage(
    ApiKeyPage.ID,
    'Configuración de impresión',
    'Definí impresora y ancho del ticket',
    'Dejá impresora vacía para usar la predeterminada de Windows.'
  );
  PrintPage.Add('Nombre de impresora:', False);
  PrintPage.Add('Ancho ticket en mm (58/80):', False);
  PrintPage.Values[0] := '';
  PrintPage.Values[1] := '58';
end;
