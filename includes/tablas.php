<?php
$pdo->exec("CREATE TABLE IF NOT EXISTS encuestas (
  id SERIAL PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE REFERENCES usuarios(id) ON DELETE CASCADE,
  bebida VARCHAR(20) NOT NULL,
  frecuencia VARCHAR(20) NOT NULL,
  calificacion SMALLINT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
  recomienda BOOLEAN NOT NULL,
  comentario TEXT,
  creado_en TIMESTAMP DEFAULT NOW()
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS mensajes (
  id SERIAL PRIMARY KEY,
  usuario_id INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  texto VARCHAR(300) NOT NULL,
  creado_en TIMESTAMP DEFAULT NOW()
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS pedidos (
  id SERIAL PRIMARY KEY,
  usuario_id INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  total NUMERIC(10,2) NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  creado_en TIMESTAMP DEFAULT NOW()
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS pedido_items (
  id SERIAL PRIMARY KEY,
  pedido_id INT NOT NULL REFERENCES pedidos(id) ON DELETE CASCADE,
  nombre VARCHAR(100) NOT NULL,
  precio NUMERIC(8,2) NOT NULL,
  cantidad INT NOT NULL
)");