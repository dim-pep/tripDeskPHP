<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;
use Throwable;

final readonly class TransactionManager
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Выполнить callback внутри транзакции.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback();

            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
