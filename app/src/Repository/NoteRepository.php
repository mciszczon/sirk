<?php
/**
 * Note repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class TagRepository.
 *
 * @package Repository
 */
class NoteRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 5;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * User Repository.
     *
     * @var null
     */
    protected $userRepository = null;

    /**
     * NoteRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->userRepository = new UserRepository($db);
    }

    /**
     * Get user's notes for a specific project and paginates them.
     *
     * @param int $page Page number
     * @param int $userId User ID
     * @param int $projectId Project ID
     * @return array Result
     */
    public function findAllPaginatedForUserAndProject($page = 1, $userId, $projectId)
    {
        $countQueryBuilder = $this->queryAllForUserAndProject($userId, $projectId)
            ->select('COUNT(DISTINCT n.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator(
            $this->queryAllForUserAndProject($userId, $projectId), $countQueryBuilder
        );
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Find one note.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('n.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Save record.
     *
     * @param array $note Note
     *
     * @return boolean Result
     */
    public function save($note)
    {
        if (isset($note['id']) && ctype_digit((string) $note['id'])) {
            // update record
            $id = $note['id'];
            unset($note['id']);

            return $this->db->update('note', $note, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('note', $note);
        }
    }

    /**
     * Remove record.
     *
     * @param array $note Note
     *
     * @return boolean Result
     */
    public function delete($note)
    {
        if (isset($note['id']) && ctype_digit((string) $note['id'])) {
            return $this->db->delete('note', ['id' => $note['id']]);
        } else {
            throw new \InvalidArgumentException('Invalid parameter type');
        }
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('n.id', 'n.title', 'n.content', 'n.project_id', 'n.user_id')->from('note', 'n');
    }

    /**
     * Query all user's notes for a specific project.
     *
     * @param int $userId User ID
     * @param int $projectId Project ID
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllForUserAndProject($userId, $projectId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder
            ->where('n.user_id = :user_id')
            ->andWhere('n.project_id = :project_id')
            ->setParameter(':user_id', $userId, \PDO::PARAM_INT)
            ->setParameter(':project_id', $projectId, \PDO::PARAM_INT);

        return $queryBuilder;
    }
}