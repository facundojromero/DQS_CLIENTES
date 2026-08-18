# UNI-049.2 — contrato JSON del executor

## Contrato estable

Toda llamada devuelve un objeto con `contract_version: DQS_INSTALL_EXECUTOR_V1`, `operation`, `mode: dry-run`, `status`, `exit_code`, `timed_out`, `duration_ms`, un `run_id` aleatorio, `summary`, `checks` y `errors`. No contiene comando, stdout, stderr ni paths de entrada.

```json
{
  "contract_version": "DQS_INSTALL_EXECUTOR_V1",
  "operation": "preflight",
  "mode": "dry-run",
  "status": "WARN",
  "exit_code": 0,
  "timed_out": false,
  "duration_ms": 42,
  "run_id": "1fc92fe829b24d61a492e21289407f15",
  "summary": {
    "source_status": "WARN",
    "check_count": 2,
    "warning_count": 1,
    "blocked_count": 0,
    "failed_count": 0
  },
  "checks": [
    {"id": "php.version", "status": "OK", "message": "PHP compatible.", "details": {}}
  ],
  "errors": []
}
```

`exit_code` es `null` si no se inició un hijo. `source_status` es `null` si no hubo fuente válida. Los errores locales son mensajes cerrados y estables, sin copiar datos del proceso.

## Estados y mapping

* `OK`: fuente `OK`, exit 0.
* `WARN`: fuente `WARN`, exit 0.
* `BLOCKED`/`FAILED`: fuente equivalente, exit 1.
* `FAILED` local: exit 2 o diferente de 0/1, combinación incoherente, JSON inválido/vacío/múltiple, check mal formado, stderr, timeout o límite excedido.
* Un exit 0 con fuente `BLOCKED/FAILED`, o exit 1 con fuente `OK/WARN`, nunca se normaliza optimistamente.

El resumen cuenta los checks ya aceptados. Se aceptan los ids reales de UNI-048 (incluidos separadores como `=`) siempre que sean strings acotados y no contengan controles, paths absolutos ni tokens largos; las palabras sensibles dentro de un id se sustituyen por `[REDACTED]`. El status es obligatorio y reconocido; un mensaje ausente o escalar se convierte de forma segura y se censura, mientras que un mensaje estructurado se sustituye por una etiqueta neutra. `details` ausente, `null` o no-array se normaliza a `{}`; los arrays se censuran recursivamente. Ante un check inválido, el error solo indica su índice y nunca copia su contenido. Campos adicionales del reporte fuente se descartan.

## Redacción defensiva

Claves y términos sueltos relacionados con `password`, `passwd`, `pwd`, `dbname`, `username`, `servername`, `host` o `email` se reemplazan. En strings también se censuran asignaciones sensibles, emails, usuarios Hostinger `u123456789`, rutas bajo el repositorio, `/tmp` o `/home`, y tokens alfanuméricos largos. La capa no confía en la redacción de UNI-048 y jamás incluye contenidos de archivos.

## Límite de UNI-049.2

El contrato representa únicamente observación/dry-run. `apply` y todos los flags de confirmación están deshabilitados y se resuelven como `BLOCKED` local antes de invocar un CLI. No hay excepción web ni endpoint en esta fase.

## Modo apply limitado de UNI-049.5

El campo `mode` puede ser `apply` exclusivamente para `schema_runner` con su policy UNI-049.5 o para `bootstrap` con la policy dedicada UNI-049.6 y su confirmación tipada. La forma, estados, coherencia de exit code, límites y redacción son idénticos. La respuesta nunca incorpora comando, stdout, stderr, credenciales ni paths. Admin publish, Finalize y cualquier combinación sin su policy siguen devolviendo `BLOCKED` local; el probe no concede policies.

## Modos apply limitados de UNI-049.7 y UNI-049.8

El mismo contrato puede representar `admin_publish` o `finalize` en modo `apply` solo con sus confirmaciones y policies dedicadas. Se descartan los campos fuente no allowlisted, incluido cualquier path; el slug se conserva aparte como estado validado del caller web. Además de las redacciones existentes, `admin_publish.json`, `admin_config.json`, `bootstrap.json`, `connection.php`, `install.lock` y `.install.lock.pending` se sustituyen, sin distinguir mayúsculas, por etiquetas seguras. El probe no habilita ninguna escritura, incluido Finalize.
