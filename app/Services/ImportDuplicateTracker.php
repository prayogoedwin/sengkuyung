<?php

namespace App\Services;

use PDO;

/**
 * Pelacak duplikat nopol per sesi import — disimpan di SQLite, bukan cache DB.
 */
class ImportDuplicateTracker
{
    private PDO $pdo;

    private function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function create(string $dbPath): self
    {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('CREATE TABLE seen_keys (key TEXT PRIMARY KEY, source TEXT NOT NULL)');

        return new self($pdo);
    }

    public static function open(string $dbPath): self
    {
        if (!is_file($dbPath)) {
            throw new \RuntimeException('File pelacak duplikat import tidak ditemukan.');
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return new self($pdo);
    }

    /**
     * @param  list<string>  $keys
     */
    public function markDbKeys(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO seen_keys (key, source) VALUES (?, ?)');

            foreach ($keys as $key) {
                $stmt->execute([$key, 'db']);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getSource(string $key): ?string
    {
        $stmt = $this->pdo->prepare('SELECT source FROM seen_keys WHERE key = ? LIMIT 1');
        $stmt->execute([$key]);
        $source = $stmt->fetchColumn();

        return $source === false ? null : (string) $source;
    }

    public function markFile(string $key): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO seen_keys (key, source) VALUES (?, ?)');
        $stmt->execute([$key, 'file']);
    }
}
