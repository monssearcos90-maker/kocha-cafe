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