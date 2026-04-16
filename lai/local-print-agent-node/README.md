# LAI Local Print Agent (Windows)

Instalación y ejecución real del agente local de impresión en Windows con `.exe`.

## Qué instala `LAI-Print-Agent-Setup.exe`

En `C:\Program Files\LAI Print Agent`:

- `agent.js` (API local `GET /health` y `POST /print`)
- `runtime\node.exe` (runtime embebido)
- `scripts\install_agent.ps1` (configura + arranca)
- `scripts\start_agent.ps1` (inicia proceso oculto)
- `scripts\stop_agent.ps1` (detiene proceso)
- `scripts\validate_agent.ps1` (prueba health + print)
- `scripts\set_base_url.ps1` (actualiza dominio/URL del sistema sin reinstalar)
- `config.json` (generado desde `config.example.json`)
- `logs\agent.log`, `logs\agent-stdout.log`, `logs\agent-stderr.log`

## Cómo generar el `.exe` instalador

El archivo `.exe` no se guarda en el repo (binario), se compila desde `installer/LAIPrintAgent.iss`.

En Windows con Inno Setup 6 instalado:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File ".\scripts\build_installer.ps1"
```

Salida esperada:

- `lai\local-print-agent-node\installer\LAI-Print-Agent-Setup.exe`

## Flujo real en Windows (PC limpia)

1. Ejecutar `LAI-Print-Agent-Setup.exe` como administrador.
2. Completar asistente:
   - URL base del sistema web (dominio online, opcional y editable).
   - API Key local.
   - Nombre de impresora (o vacío para predeterminada de Windows).
   - Ancho ticket (solo 58 u 80).
   - (Opcional) activar “Iniciar automáticamente al iniciar sesión”.
3. Finalizar instalación.
4. El instalador ejecuta `install_agent.ps1`, que:
   - escribe `config.json`,
   - crea Scheduled Task (si se marcó autostart),
   - arranca el agente oculto inmediatamente.

## Validación obligatoria (en la PC Windows)

### 1) Verificar que responde health

Abrir navegador:

- `http://127.0.0.1:3000/health`

Respuesta esperada:

```json
{"status":"ok","host":"127.0.0.1","port":3000}
```

### 2) Verificar impresión real

PowerShell:

```powershell
Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:3000/print" `
  -Headers @{ "x-lai-api-key" = "TU_API_KEY"; "Content-Type" = "application/json" } `
  -Body '{
    "ticketText":"*** TEST LAI ***\nIMPRESION OK\n",
    "printConfig":{"ticketWidthMm":58,"copies":1,"printerName":""}
  }'
```

También podés usar:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File "C:\Program Files\LAI Print Agent\scripts\validate_agent.ps1" -ApiKey "TU_API_KEY"
```

## Diagnóstico rápido si algo falla

1. Ver logs:
   - `C:\Program Files\LAI Print Agent\logs\agent.log`
   - `C:\Program Files\LAI Print Agent\logs\agent-stderr.log`
2. Reiniciar agente:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File "C:\Program Files\LAI Print Agent\scripts\stop_agent.ps1"
powershell -NoProfile -ExecutionPolicy Bypass -File "C:\Program Files\LAI Print Agent\scripts\start_agent.ps1"
```

3. Revisar tarea programada (si autostart): `LAI-Print-Agent`.

## Integración con PHP (`/lai/`)

Desde el sistema web (servidor online, por ejemplo `https://tu-dominio.com/lai/`) enviar tickets a:

- `http://127.0.0.1:3000/print`

con header:

- `x-lai-api-key: <api_key_configurada_en_esa_pc>`

Si cambia el dominio, no hace falta reinstalar. Actualizalo así:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File "C:\Program Files\LAI Print Agent\scripts\set_base_url.ps1" -BaseUrl "https://nuevo-dominio.com/lai/"
```

## Resumen del `.exe` (lo que pediste)

El `LAI-Print-Agent-Setup.exe` permite configurar en el asistente:

- dominio/URL base del sistema web,
- impresora cuponera,
- ancho de ticket (58/80),
- API key local,
- auto inicio del agente.
