-- =====================================================================
-- La Plantilla · Base de datos "empresa"
-- Compatible con MariaDB 10.4+ (XAMPP) y MySQL 8.0.16+
-- =====================================================================

DROP DATABASE IF EXISTS empresa;
CREATE DATABASE empresa
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE empresa;

CREATE TABLE empleado (
    documento        VARCHAR(10)   NOT NULL,
    nombre           VARCHAR(60)   NOT NULL,
    sexo             CHAR(1)       NOT NULL,
    domicilio        VARCHAR(80)   NOT NULL,
    fechaingreso     DATE          NOT NULL,
    fechanacimiento  DATE          NOT NULL,
    sueldobasico     DECIMAL(10,2) NOT NULL,

    CONSTRAINT pk_empleado          PRIMARY KEY (documento),
    CONSTRAINT ck_documento_formato CHECK (documento REGEXP '^[0-9]{6,10}$'),
    CONSTRAINT ck_nombre_largo      CHECK (CHAR_LENGTH(TRIM(nombre)) BETWEEN 3 AND 60),
    CONSTRAINT ck_sexo              CHECK (sexo IN ('m', 'f')),
    CONSTRAINT ck_domicilio_largo   CHECK (CHAR_LENGTH(TRIM(domicilio)) BETWEEN 5 AND 80),
    CONSTRAINT ck_fechas_orden      CHECK (fechaingreso > fechanacimiento),
    CONSTRAINT ck_sueldo_positivo   CHECK (sueldobasico > 0),

    INDEX idx_empleado_nombre (nombre)
) ENGINE = InnoDB;

-- Datos del enunciado (se cargan tal cual, aunque 2 registros no cumplan
-- la regla de 18 años al ingreso: esa regla se valida en PHP, no aquí).
INSERT INTO empleado
  (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico)
VALUES
  ('22333111', 'Juan Perez',         'm', 'Colon 123',       '1990-02-01', '1970-05-10', 3210000.00),
  ('25444444', 'Susana Morales',     'f', 'Avellaneda 345',  '1995-04-01', '1975-11-06', 3790000.00),
  ('20111222', 'Hector Pereyra',     'm', 'Caseros 987',     '1995-04-01', '1965-03-25', 2980000.00),
  ('30000222', 'Luis Luque',         'm', 'Urquiza 456',     '1980-09-01', '1980-03-29', 4090000.00),
  ('20555444', 'Maria Laura Torres', 'f', 'San Martin 1122', '2000-05-15', '1965-12-22', 4090000.00),
  ('30000234', 'Alberto Soto',       'm', 'Peru 232',        '2003-08-15', '1989-10-10', 2450000.00),
  ('20125478', 'Ana Gomez',          'f', 'Sarmiento 975',   '2004-06-14', '1976-09-21', 2040000.00),
  ('24154269', 'Ofelia Garcia',      'f', 'Triunvirato 628', '2004-09-23', '1974-05-12', 2280000.00),
  ('30154269', 'Oscar Mendez',       'm', 'Colon 1245',      '2004-06-23', '1984-05-14', 1750905.00);
