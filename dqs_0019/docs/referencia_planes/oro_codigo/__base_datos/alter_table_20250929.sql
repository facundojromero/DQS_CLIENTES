

ALTER TABLE cliente
  ADD COLUMN cbu_dolar VARCHAR(100) DEFAULT NULL AFTER plan,
  ADD COLUMN alias_dolar VARCHAR(100) DEFAULT NULL AFTER cbu_dolar,
  ADD COLUMN cotizacion_dolar INT(10) DEFAULT NULL AFTER alias_dolar;
  
  
 ALTER TABLE regalos
  ADD COLUMN pago_con INT(10) DEFAULT NULL AFTER mensaje;

UPDATE regalos
  SET pago_con = 1;
  

Copiar Archivos de TIENDA y admin_tmp