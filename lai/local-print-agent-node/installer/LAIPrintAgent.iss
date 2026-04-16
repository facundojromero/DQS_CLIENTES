#define MyAppName "LAI Local Print Agent"
#define MyAppVersion "1.0.1"
#define MyAppPublisher "LAI"
#define MyAppCreatorEmail "facundoj.romero@gmail.com"
#define MyAppExeName "run-agent.cmd"

[Setup]
AppId={{A13D9C7C-9D4A-4C5A-917C-9B2B6E0A5A31}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher} - {#MyAppCreatorEmail}
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
Source: "..\scripts\start_agent.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\scripts\stop_agent.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\scripts\validate_agent.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\scripts\set_base_url.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: "..\runtime\*"; DestDir: "{app}\runtime"; Flags: ignoreversion recursesubdirs createallsubdirs

[Run]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\scripts\install_agent.ps1"" -InstallDir ""{app}"" -ServerHost ""127.0.0.1"" -ServerPort 3000 -ApiKey ""{code:GetApiKey}"" -BaseUrl ""{code:GetBaseUrl}"" -PrinterName ""{code:GetPrinterName}"" -TicketWidthMm {code:GetTicketWidth} {code:GetAutoStartFlag}"; Flags: runhidden waituntilterminated

[UninstallRun]
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\scripts\uninstall_agent.ps1"" -InstallDir ""{app}"""; Flags: runhidden

[Code]
var
  ServerPage: TInputQueryWizardPage;
  ApiKeyPage: TInputQueryWizardPage;
  PrintPage: TInputQueryWizardPage;

function GetBaseUrl(Value: string): string;
begin
  Result := ServerPage.Values[0];
end;

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

function IsValidHttpUrl(const Value: string): Boolean;
var
  LowerValue: string;
begin
  LowerValue := LowerCase(Trim(Value));
  Result := (LowerValue = '') or Pos('http://', LowerValue) = 1 or Pos('https://', LowerValue) = 1;
end;

function IsValidTicketWidth(const Value: string): Boolean;
begin
  Result := (Trim(Value) = '58') or (Trim(Value) = '80');
end;

function NextButtonClick(CurPageID: Integer): Boolean;
begin
  Result := True;

  if CurPageID = ServerPage.ID then
  begin
    if not IsValidHttpUrl(ServerPage.Values[0]) then
    begin
      MsgBox('La URL base debe comenzar con http:// o https://, o quedar vacía.', mbError, MB_OK);
      Result := False;
      exit;
    end;
  end;

  if CurPageID = PrintPage.ID then
  begin
    if not IsValidTicketWidth(PrintPage.Values[1]) then
    begin
      MsgBox('El ancho del ticket debe ser 58 o 80.', mbError, MB_OK);
      Result := False;
      exit;
    end;
  end;
end;

procedure InitializeWizard;
begin
  WizardForm.WelcomeLabel2.Caption :=
    WizardForm.WelcomeLabel2.Caption + #13#10 + #13#10 +
    'Creador: {#MyAppCreatorEmail}';

  ServerPage := CreateInputQueryPage(
    wpSelectTasks,
    'Integración con servidor web',
    'Definí la URL de tu sistema web (puede cambiar luego)',
    'Este valor se guarda en config.json y es editable en cualquier momento.'
  );
  ServerPage.Add('URL base del sistema web (https://...):', False);
  ServerPage.Values[0] := '';

  ApiKeyPage := CreateInputQueryPage(
    ServerPage.ID,
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
