<?php

namespace Stdimitrov\Orm;


use Exception;
use Stdimitrov\Orm\Tools\Helper;

abstract class AbstractDatabase extends Database
{
    /**
     * Paginate the results of a query.
     *
     * @param string $query The SQL query to paginate.
     * @param array|null $params The parameters for the SQL query.
     * @param int|null $page The current page number.
     * @param int|null $prePage The number of items per page.
     * @return array|null The paginated results.
     * @throws Exception
     */
    protected function paginate(string $query, ?array $params = [], ?int $page = 1, ?int $prePage = 24): ?array
    {
        preg_match('/FROM\s+([a-zA-Z0-9_]+)/i', $query, $matches);
        $table = $matches[1];

        $totalItems = $this->count($query, $params);

        $offset = ($page - 1) * $prePage;

        $query .= " LIMIT $offset, $prePage";

        $items = parent::fetchAll($query, $params);
        $paginate = Helper::calculatePagination($totalItems, $page, $prePage);

        return [
            'pagination' => $paginate,
            Helper::toCamelCase($table) => $items,
        ];
    }

    /**
     * Count the total number of items for a given query.
     *
     * @param string $query The SQL query to count items for.
     * @param array|null $params The parameters for the SQL query.
     * @return int The total number of items.
     * @throws Exception
     */
    protected function count(string $query, ?array $params = []): int
    {
        $countQuery = trim($this->convertToCountQuery($query), " \t\n\r\0\x0B;");

        if (stripos($countQuery, 'WHERE') === false && count($params) > 0) {
            $countQueryParams = [];
        } else {
            $countQueryParams = $params;
        }

        return parent::fetchColumn($countQuery, $countQueryParams);
    }

    /**
     * Converts a SQL query into a COUNT query.
     *
     * This method modifies the given SQL query to count the total number of rows
     * that match the query's conditions. It removes any LIMIT or ORDER BY clauses
     * and determines whether a subquery is needed based on the presence of DISTINCT,
     * GROUP BY, or HAVING clauses.
     *
     * @param string $query The original SQL query.
     * @return string The modified COUNT query.
     * @throws Exception If the query does not contain SELECT or FROM clauses.
     */
    private function convertToCountQuery(string $query): string
    {
        $query = preg_replace('/\s+limit\s+[\d,\s]+$/i', '', $query);
        $query = preg_replace('/\s+order\s+by\s+.+$/i', '', $query);

        $selectPos = stripos($query, 'select');
        $fromPos = stripos($query, 'from');

        if ($selectPos === false || $fromPos === false) {
            throw new Exception('Invalid SQL query: SELECT or FROM not found.');
        }

        $selectPart = substr($query, $selectPos + 6, $fromPos - ($selectPos + 6));
        $fromPart = substr($query, $fromPos);

        $needsSubquery = false;

        if (stripos($selectPart, 'distinct') !== false) {
            $needsSubquery = true;
        }

        if (preg_match('/\bgroup\s+by\b/i', $fromPart)) {
            $needsSubquery = true;
        }

        if (preg_match('/\bhaving\b/i', $fromPart)) {
            $needsSubquery = true;
        }

        if ($needsSubquery) {
            $countQuery = sprintf("SELECT COUNT(%s) FROM (%s) AS temp_count", '*', trim($query));
        } else {
            $countQuery = sprintf("SELECT COUNT(%s) %s", '*', $fromPart);
        }

        return trim($countQuery);
    }

}