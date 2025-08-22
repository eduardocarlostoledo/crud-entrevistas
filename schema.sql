-- Base de datos
-- CREATE DATABASE bolsa_trabajo ENCODING 'UTF8';

-- Tabla de postulantes a la convocatoria
CREATE TABLE IF NOT EXISTS postulantes (
  id SERIAL PRIMARY KEY,
  nombres VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  dni VARCHAR(20) UNIQUE NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  telefono VARCHAR(30),
  edad SMALLINT CHECK (edad BETWEEN 18 AND 45) NOT NULL,

  tiene_titulo BOOLEAN NOT NULL DEFAULT FALSE,
  titulo_detalle VARCHAR(160), -- Lic. en Sistemas, Analista, etc.

  experiencia_php BOOLEAN DEFAULT FALSE,
  experiencia_js BOOLEAN DEFAULT FALSE,
  experiencia_sql BOOLEAN DEFAULT FALSE,
  experiencia_postgres BOOLEAN DEFAULT FALSE,
  experiencia_siu_toba BOOLEAN DEFAULT FALSE,

  observaciones TEXT,
  creado_en TIMESTAMP NOT NULL DEFAULT NOW(),
  actualizado_en TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Trigger para mantener actualizado actualizado_en
CREATE OR REPLACE FUNCTION set_updated_timestamp()
RETURNS TRIGGER AS $$
BEGIN
  NEW.actualizado_en = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_postulantes_updated ON postulantes;
CREATE TRIGGER trg_postulantes_updated
BEFORE UPDATE ON postulantes
FOR EACH ROW EXECUTE FUNCTION set_updated_timestamp();