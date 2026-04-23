ALTER TABLE productos
    ADD COLUMN precio_mercadopago DECIMAL(10,2) NULL AFTER precio;

UPDATE productos
SET precio_mercadopago = precio + 500
WHERE precio_mercadopago IS NULL;

ALTER TABLE productos
    MODIFY COLUMN precio_mercadopago DECIMAL(10,2) NOT NULL;

ALTER TABLE combinaciones
    ADD COLUMN precio_mercadopago DECIMAL(10,2) NULL AFTER precio;

UPDATE combinaciones
SET precio_mercadopago = precio + 500
WHERE precio_mercadopago IS NULL;

ALTER TABLE combinaciones
    MODIFY COLUMN precio_mercadopago DECIMAL(10,2) NOT NULL;
