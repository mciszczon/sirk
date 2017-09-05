<?php
/**
 * Tag repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Silex\Application;
use Utils\Paginator;
use Repository\UserRepository;

/**
 * Class TagRepository.
 *
 * @package Repository
 */
class TaskRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 10;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * @var null
     */
    protected $userRepository = null;

    /**
     * TaskRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->userRepository = new UserRepository($db);
    }

    /**
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT t.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Find for uniqueness.
     *
     * @param string          $name Element name
     * @param int|string|null $id   Element id
     *
     * @return array Result
     */
    public function findForUniqueness($name, $id = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.name = :name')
            ->setParameter(':name', $name, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('t.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
    * Find one record by name.
    *
    * @param string $name Name
    *
    * @return array|mixed Result
    */
    public function findOneByName($name)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.name = :name')
            ->setParameter(':name', $name, \PDO::PARAM_STR);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Find tags by Ids.
     *
     * @param array $ids Tags Ids.
     *
     * @return array
     */
    public function findById($ids)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.id IN (:ids)')
            ->setParameter(':ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find ids of bookmarks with current tag.
     *
     * @param int $id Id of the current tag.
     * @return array
     */
    public function findLinkedTasksIds($project_id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->select('t.id')
            ->where('t.project_id = :project_id')
            ->setParameter(':project_id', $project_id, \PDO::PARAM_INT);

        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'id') : [];
    }

    /**
     * Find bookmarks names with current tag.
     *
     * @param int $id Id of the current tag.
     * @return array
     */
    public function findLinkedTasks($project_id)
    {
        $tasksIds = $this->findLinkedTasksIds($project_id);

        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.id IN (:ids)')
            ->setParameter(':ids', $tasksIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    public function findLinkedUserId($task_id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->select('t.user_id')
            ->where('t.id = :task_id')
            ->setParameter(':task_id', $task_id, \PDO::PARAM_INT);

        $result = $queryBuilder->execute()->fetch();

        return isset($result) ? $result['user_id'] : [];
    }

    public function findLinkedUser($task_id)
    {
        $userId = $this->findLinkedUserId($task_id);

        $queryBuilder = $this->userRepository->queryAll();
        $queryBuilder->where('u.id = :id')
            ->setParameter(':id', $userId, \PDO::PARAM_INT);

        return $queryBuilder->execute()->fetch();
    }

    /**
     * Save record.
     *
     * @param array $task Task
     *
     * @return boolean Result
     */
    public function save($task)
    {
        if (isset($task['id']) && ctype_digit((string) $task['id'])) {
            // update record
            $id = $task['id'];
            unset($task['id']);

            $task['date'] = date_format($task['date'], 'Y-m-d');

            return $this->db->update('task', $task, ['id' => $id]);
        } else {
            // add new record
            $task['date'] = date_format($task['date'], 'Y-m-d');

            return $this->db->insert('task', $task);
        }
    }

    /**
     * Remove record.
     *
     * @param array $task task
     *
     * @return boolean Result
     */
    public function delete($task)
    {
        if (isset($task['id']) && ctype_digit((string) $task['id'])) {
            return $this->db->delete('task', $task);
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

        return $queryBuilder->select('t.id', 't.name', 't.description', 't.done', 't.date', 't.priority_id', 't.project_id', 't.user_id')
            ->from('task', 't');
    }
}