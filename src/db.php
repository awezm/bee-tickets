<?php
require_once __DIR__ . '/config.php';

function db() {
  static $pdo = null;
  if ($pdo) return $pdo;
  if (!is_dir(dirname(DB_PATH))) mkdir(dirname(DB_PATH), 0777, true);
  $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  $pdo->exec("CREATE TABLE IF NOT EXISTS tickets(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL, subject TEXT NOT NULL, body TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'open',
    public_token TEXT NOT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
  );");
  $pdo->exec("CREATE TABLE IF NOT EXISTS replies(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL, author TEXT NOT NULL, body TEXT NOT NULL, created_at TEXT NOT NULL,
    FOREIGN KEY(ticket_id) REFERENCES tickets(id)
  );");
  return $pdo;
}
