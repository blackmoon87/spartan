<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Fluent Query Builder — zero dependencies, pure PHP 8.0+.
 *
 * Returned by Model::table(). Chain methods and close with
 * get(), first(), count(), insert(), update(), or delete().
 *
 * Example:
 *   $this->table('users')
 *        ->where('active', 1)
 *        ->orderBy('name')
 *        ->limit(10)
 *        ->get();
 */
class QueryBuilder
{
    private PDO    $db;
    private string $table;

    private array   $selects   = ['*'];
    private array   $wheres    = [];
    private array   $joins     = [];        // ['type'=>'INNER|LEFT|RIGHT', 'table'=>'t', 'first'=>'a', 'second'=>'b']
    private array   $bindings  = [];

    private ?string $orderCol  = null;
    private string  $orderDir  = 'ASC';
    private ?int    $limitVal  = null;
    private ?int    $offsetVal = null;

    private array   $groupBys  = [];
    private array   $havings   = [];        // ['sql'=>string, 'value'=>mixed]
    private array   $havingBindings = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Construction (called by Model::table())
    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(PDO $db, string $table)
    {
        if (empty($table)) {
            throw new RuntimeException('QueryBuilder requires a table name.');
        }
        $this->db    = $db;
        $this->table = $table;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Clauses
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Restrict which columns are fetched.
     * select('id', 'name', 'email')
     */
    public function select(string ...$columns): static
    {
        $this->selects = $columns;
        return $this;
    }

    /**
     * Add an AND WHERE condition.
     * Supported operators: =, !=, <>, <, >, <=, >=, LIKE, NOT LIKE, IN, NOT IN
     */
    public function where(string $column, mixed $value, string $operator = '='): static
    {
        return $this->addWhere('AND', $column, $value, $operator);
    }

    /**
     * Add an OR WHERE condition.
     */
    public function orWhere(string $column, mixed $value, string $operator = '='): static
    {
        return $this->addWhere('OR', $column, $value, $operator);
    }

    /**
     * Add an INNER JOIN clause.
     * join('users', 'orders.user_id', 'users.id')
     */
    public function join(string $table, string $first, string $second): static
    {
        $this->joins[] = ['type' => 'INNER', 'table' => $table, 'first' => $first, 'second' => $second];
        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     */
    public function leftJoin(string $table, string $first, string $second): static
    {
        $this->joins[] = ['type' => 'LEFT', 'table' => $table, 'first' => $first, 'second' => $second];
        return $this;
    }

    /**
     * Add a RIGHT JOIN clause.
     */
    public function rightJoin(string $table, string $first, string $second): static
    {
        $this->joins[] = ['type' => 'RIGHT', 'table' => $table, 'first' => $first, 'second' => $second];
        return $this;
    }

    /**
     * Add an ORDER BY clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderCol = $column;
        $this->orderDir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        return $this;
    }

    /**
     * Add a GROUP BY clause.
     * groupBy('status', 'role')
     */
    public function groupBy(string ...$columns): static
    {
        $this->groupBys = array_merge($this->groupBys, $columns);
        return $this;
    }

    /**
     * Add a HAVING condition (used after groupBy).
     * having('total', 100, '>=')
     */
    public function having(string $column, mixed $value, string $operator = '='): static
    {
        $operator = strtoupper(trim($operator));
        $col = $this->escapeColumn($column);
        $this->havings[]        = "{$col} {$operator} ?";
        $this->havingBindings[] = $value;
        return $this;
    }

    /**
     * Limit the number of rows returned.
     */
    public function limit(int $limit): static
    {
        $this->limitVal = $limit;
        return $this;
    }

    /**
     * Skip N rows (use with limit() for pagination).
     */
    public function offset(int $offset): static
    {
        $this->offsetVal = $offset;
        return $this;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Read Terminators
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Execute SELECT and return all matching rows.
     */
    public function get(): array
    {
        [$sql, $bindings] = $this->buildSelect();
        $stmt = $this->execute($sql, $bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute SELECT and return only the first row, or null.
     */
    public function first(): ?array
    {
        $this->limitVal = 1;
        [$sql, $bindings] = $this->buildSelect();
        $stmt = $this->execute($sql, $bindings);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Paginate results. Returns a structured array:
     *   [
     *     'data'         => array of rows,
     *     'total'        => total matching rows (int),
     *     'per_page'     => rows per page (int),
     *     'current_page' => current page number (int),
     *     'last_page'    => total pages (int),
     *   ]
     *
     * paginate(15)        // page 1, 15 per page
     * paginate(15, 2)     // page 2, 15 per page
     */
    public function paginate(int $perPage, int $page = 1): array
    {
        if ($perPage < 1) {
            throw new RuntimeException('paginate() perPage must be >= 1.');
        }
        if ($page < 1) {
            $page = 1;
        }

        // COUNT query (respects wheres + joins + groupBy)
        [$whereSQL, $bindings] = $this->buildWhere();
        $countSql = "SELECT COUNT(*) as cnt FROM `{$this->table}`";
        foreach ($this->joins as $join) {
            $countSql .= " {$join['type']} JOIN `{$join['table']}` ON {$join['first']} = {$join['second']}";
        }
        $countSql .= $whereSQL;
        $total = (int) ($this->execute($countSql, $bindings)->fetch(\PDO::FETCH_ASSOC)['cnt'] ?? 0);

        // Apply pagination bounds and fetch data
        $this->limitVal  = $perPage;
        $this->offsetVal = ($page - 1) * $perPage;
        [$sql, $allBindings] = $this->buildSelect();
        $data = $this->execute($sql, $allBindings)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Find a single record by primary key.
     */
    public function find(int|string $id): ?array
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Return the count of matching rows.
     */
    public function count(): int
    {
        [$whereSQL, $bindings] = $this->buildWhere();
        $sql  = "SELECT COUNT(*) as cnt FROM `{$this->table}`";
        $sql .= $whereSQL;
        $stmt = $this->execute($sql, $bindings);
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
    }

    /**
     * Return true if at least one matching row exists.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Write Terminators
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Insert a new row. Returns the last inserted ID.
     *
     * insert(['name' => 'Ali', 'email' => 'ali@example.com'])
     */
    public function insert(array $data): string|false
    {
        if (empty($data)) {
            throw new RuntimeException('insert() requires at least one column.');
        }

        $columns  = array_keys($data);
        $colSQL   = implode(', ', array_map(fn($c) => "`{$c}`", $columns));
        $valSQL   = implode(', ', array_fill(0, count($columns), '?'));
        $sql      = "INSERT INTO `{$this->table}` ({$colSQL}) VALUES ({$valSQL})";

        $this->execute($sql, array_values($data));
        return $this->db->lastInsertId();
    }

    /**
     * Update matching rows. Returns the number of affected rows.
     * REQUIRES at least one where() condition to prevent accidental full-table updates.
     * Use ->where('1','1') only if you explicitly intend to update all rows.
     *
     * ->where('id', 5)->update(['status' => 'active'])
     */
    public function update(array $data): int
    {
        if (empty($this->wheres)) {
            throw new \LogicException(
                "update() requires at least one where() condition to prevent accidental full-table updates. "
              . "Add a where() clause, or use whereAll()->update() only if you intentionally mean all rows."
            );
        }

        if (empty($data)) {
            throw new RuntimeException('update() requires at least one column.');
        }

        $setSQL   = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        [$whereSQL, $whereBindings] = $this->buildWhere();

        $sql      = "UPDATE `{$this->table}` SET {$setSQL}{$whereSQL}";
        $bindings = array_merge(array_values($data), $whereBindings);

        $stmt = $this->execute($sql, $bindings);
        return $stmt->rowCount();
    }

    /**
     * Delete matching rows. Returns the number of deleted rows.
     * REQUIRES at least one where() condition to prevent accidental full-table deletes.
     * To intentionally delete all rows in a table, use truncate() instead.
     *
     * ->where('id', 5)->delete()
     */
    public function delete(): int
    {
        if (empty($this->wheres)) {
            throw new \LogicException(
                "delete() requires at least one where() condition to prevent accidental full-table deletes. "
              . "Use truncate() if you intentionally want to remove all rows from [{$this->table}]."
            );
        }

        [$whereSQL, $bindings] = $this->buildWhere();
        $sql  = "DELETE FROM `{$this->table}`{$whereSQL}";
        $stmt = $this->execute($sql, $bindings);
        return $stmt->rowCount();
    }

    /**
     * Delete ALL rows from the table — explicit and intentional.
     * Use this instead of delete() when you need to clear an entire table.
     * Returns the number of deleted rows.
     *
     * $this->table('temp_jobs')->truncate();
     */
    public function truncate(): int
    {
        $stmt = $this->execute("DELETE FROM `{$this->table}`", []);
        return $stmt->rowCount();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal Builders
    // ─────────────────────────────────────────────────────────────────────────

    private function addWhere(string $type, string $column, mixed $value, string $operator): static
    {
        $operator = strtoupper(trim($operator));
        $col = $this->escapeColumn($column);

        // Handle IN / NOT IN with array values
        if (is_array($value)) {
            $placeholders = implode(', ', array_fill(0, count($value), '?'));
            $op  = ($operator === 'NOT IN') ? 'NOT IN' : 'IN';
            $sql = "{$col} {$op} ({$placeholders})";
            $this->wheres[]   = ['type' => $type, 'sql' => $sql];
            $this->bindings   = array_merge($this->bindings, $value);
        } else {
            $this->wheres[]   = ['type' => $type, 'sql' => "{$col} {$operator} ?"];
            $this->bindings[] = $value;
        }

        return $this;
    }

    private function buildSelect(): array
    {
        $cols = implode(', ', array_map(fn($c) => $this->escapeColumn($c), $this->selects));
        [$whereSQL, $bindings] = $this->buildWhere();

        $sql  = "SELECT {$cols} FROM `{$this->table}`";

        // Append JOIN clauses
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN `{$join['table']}` ON {$join['first']} = {$join['second']}";
        }

        $sql .= $whereSQL;

        if (!empty($this->groupBys)) {
            $cols  = implode(', ', array_map(fn($c) => $this->escapeColumn($c), $this->groupBys));
            $sql  .= " GROUP BY {$cols}";
        }
        if (!empty($this->havings)) {
            $sql     .= ' HAVING ' . implode(' AND ', $this->havings);
            $bindings = array_merge($bindings, $this->havingBindings);
        }
        if ($this->orderCol !== null) {
            $col  = $this->escapeColumn($this->orderCol);
            $sql .= " ORDER BY {$col} {$this->orderDir}";
        }
        if ($this->limitVal !== null) {
            $sql .= " LIMIT {$this->limitVal}";
        }
        if ($this->offsetVal !== null) {
            $sql .= " OFFSET {$this->offsetVal}";
        }

        return [$sql, $bindings];
    }

    private function buildWhere(): array
    {
        if (empty($this->wheres)) {
            return ['', []];
        }

        $parts = [];
        foreach ($this->wheres as $i => $w) {
            $prefix   = ($i === 0) ? ' WHERE ' : " {$w['type']} ";
            $parts[]  = $prefix . $w['sql'];
        }

        return [implode('', $parts), $this->bindings];
    }

    private function execute(string $sql, array $bindings): \PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /**
     * Escape a column name with backticks, handling table prefixes safely.
     * e.g. "users.id" -> "`users`.`id`"
     *      "id"       -> "`id`"
     */
    private function escapeColumn(string $column): string
    {
        $column = trim($column);
        if ($column === '*' || $column === '') {
            return $column;
        }

        if (str_contains($column, '(') || str_contains($column, ')')) {
            return $column;
        }

        // Handle alias: "users.id as user_id" -> "`users`.`id` AS `user_id`"
        if (preg_match('/\s+as\s+/i', $column)) {
            $parts = preg_split('/\s+as\s+/i', $column);
            return $this->escapeColumn($parts[0]) . ' AS ' . $this->escapeColumn($parts[1]);
        }

        $parts = explode('.', $column);
        $escaped = array_map(fn($p) => $p === '*' ? '*' : '`' . str_replace('`', '', $p) . '`', $parts);
        return implode('.', $escaped);
    }
}
