CREATE TABLE IF NOT EXISTS usuarios (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol VARCHAR(10) NOT NULL DEFAULT 'cliente' CHECK (rol IN ('admin','cliente')),
  creado_en TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS productos (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  precio NUMERIC(8,2) NOT NULL,
  imagen VARCHAR(255),
  etiqueta VARCHAR(20),
  creado_en TIMESTAMP DEFAULT NOW()
);