<?php
/**
 * Task repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Utils\Paginator;

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
    const NUM_ITEMS = 5;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * User repository.
     *
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
     * @param int $projectId Project ID
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1, $projectId)
    {
        $countQueryBuilder = $this->queryAllForProject($projectId)
            ->select('COUNT(DISTINCT t.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator(
            $this->queryAllForProject($projectId), $countQueryBuilder
        );
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
     * Find IDs of tasks from a project.
     *
     * @param int $project_id Project ID
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
     * Fetch tasks linked to a project.
     *
     * @param int $project_id Project ID
     * @return array Result
     */
    public function findLinkedTasks($project_id)
    {
        $tasksIds = $this->findLinkedTasksIds($project_id);

        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.id IN (:ids)')
            ->setParameter(':ids', $tasksIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find tasks linked to a project, that are not finished yet.
     *
     * @param int $project_id Project ID
     * @return array Result
     */
    public function findLinkedTasksNotDone($project_id)
    {
        $tasksIds = $this->findLinkedTasksIds($project_id);

        $queryBuilder = $this->queryAll();
        $queryBuilder
            ->where('t.id IN (:ids)')
            ->andWhere('t.done = 0')
            ->setParameter(':ids', $tasksIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find ID of a user assigned to a task.
     *
     * @param int $task_id Task ID
     * @return array
     */
    public function findLinkedUserId($task_id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->select('t.user_id')
            ->where('t.id = :task_id')
            ->setParameter(':task_id', $task_id, \PDO::PARAM_INT);

        $result = $queryBuilder->execute()->fetch();

        return isset($result) ? $result['user_id'] : [];
    }

    /**
     * Fetch user assigned to a task.
     *
     * @param int $task_id Task ID
     * @return mixed
     */
    public function findLinkedUser($task_id)
    {
        $userId = $this->findLinkedUserId($task_id);

        $queryBuilder = $this->userRepository->queryAll();
        $queryBuilder->where('u.id = :id')
            ->setParameter(':id', $userId, \PDO::PARAM_INT);

        return $queryBuilder->execute()->fetch();
    }

    /**
     * Empty all assignments of removed user to a task.
     *
     * @param int $userId User ID
     * @return \Doctrine\DBAL\Driver\Statement|bool
     */
    public function deleteUserAssignments($userId)
    {
        if (isset($userId) && ctype_digit((string) $userId)) {
            // update record
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder
                ->update('task', 't')
                ->set('t.user_id', ':null')
                ->where('t.user_id = :id')
                ->setParameter('null', null)
                ->setParameter('id', $userId);

            return $queryBuilder->execute();
        }

        return false;
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
            // avoid overwriting author when editing as admin
            unset($task['author_id']);

            return $this->db->update('task', $task, ['id' => $id]);
        } else {
            // add new record
            if (!is_string($task['date'])) $task['date'] = date_format($task['date'], 'Y-m-d');

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
            return $this->db->delete('task', ['id' => $task['id']]);
        } else {
            throw new \InvalidArgumentException('Invalid parameter type');
        }
    }

    /**
     * Finish task.
     *
     * @param array $task Task
     *
     * @return boolean Result
     */
    public function finish($task)
    {
        if (isset($task['id']) && ctype_digit((string) $task['id'])) {
            $id = $task['id'];
            unset($task['id']);

            return $this->db->update('task', $task, ['id' => $id]);
        }
        else return null;
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('t.id', 't.name', 't.description', 't.done', 't.date', 't.priority_id', 't.project_id', 't.user_id', 't.author_id')
            ->from('task', 't');
    }

    /**
     * Query all records for a project.
     *
     * @param int $projectId Project ID
     * @return QueryBuilder
     */
    protected function queryAllForProject($projectId)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('t.id', 't.name', 't.description', 't.done', 't.date', 't.priority_id', 't.project_id', 't.user_id', 't.author_id')
            ->from('task', 't')
            ->where('t.project_id = :id')
            ->setParameter('id', $projectId, \PDO::PARAM_INT);
    }

    /**
     * Find users linked to a task (author and assigned user)
     *
     * @param int $taskId Task ID
     * @return array Linked users' IDs
     */
    public function findLinkedUsers($taskId)
    {
        $queryBuilder = $this->findOneById($taskId);
        $result = [];
        $result[] = $queryBuilder['author_id'];
        $result[] = $queryBuilder['user_id'];

        return $result;
    }


    /**
     * Check if user is author of a tas or is assigned to it.
     *
     * @param int $user User ID
     * @param int $task Task ID
     * @return bool
     */
    public function checkIfUserHasTask($user, $task)
    {
        $linkedUsersIds = $this->findLinkedUsers($task);

        if (in_array($user, $linkedUsersIds)) return true;
        return false;
    }

    /**
     * Gets all dates for the current week
     *
     * @return array
     */
    function getCurrentWeekDates()
    {
        if (date('D') != 'Mon') {
            $startdate = date('Y-m-d', strtotime('last Monday'));
        } else {
            $startdate = date('Y-m-d');
        }

        //always next saturday
        if (date('D') != 'Sat') {
            $enddate = date('Y-m-d', strtotime('next Saturday'));
        } else {
            $enddate = date('Y-m-d');
        }

        $DateArray = array();
        $timestamp = strtotime($startdate);
        while ($startdate <= $enddate) {
            $startdate = date('Y-m-d', $timestamp);
            $DateArray[] = $startdate;
            $timestamp = strtotime('+1 days', strtotime($startdate));
        }
        return $DateArray;
    }
}