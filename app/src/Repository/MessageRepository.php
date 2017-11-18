<?php
/**
 * Message repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class TagRepository.
 *
 * @package Repository
 */
class MessageRepository
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
     * User repository.
     *
     * @var null
     */
    protected $userRepository = null;

    /**
     * MessageRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->userRepository = new UserRepository($db);
    }

    /**
     * Get paginated messages in a project.
     *
     * @param int $page Current page number
     * @param int $projectId Project ID
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1, $projectId)
    {
        $countQueryBuilder = $this->queryAllForProject($projectId)
            ->select('COUNT(DISTINCT m.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator(
            $this->queryAllForProject($projectId), $countQueryBuilder
        );
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Find one message.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('m.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Check whether user is owner of a message.
     *
     * @param int $userId ID of a user
     * @param int $messageId ID of a message
     * @return bool Boolean information
     */
    public function checkIfUserHasMessage($userId, $messageId)
    {
        $message = $this->findOneById($messageId);

        if ($userId == $message['user_id']) return true;
        return false;
    }

    /**
     * Find all messages for a specific project.
     *
     * @param int $projectId ID of a project
     * @return array Result
     */
    public function findMessagesForProject($projectId)
    {
        $queryBuilder = $this->queryAllForProject($projectId);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find 3 newest messages for a specific project.
     *
     * @param int $projectId Project ID
     * @return array Result
     */
    public function findLastMessagesForProject($projectId)
    {
        $queryBuilder = $this->queryAllForProject($projectId);
        $queryBuilder->setMaxResults(3);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Save record.
     *
     * @param array $message Message
     *
     * @return boolean Result
     */
    public function save($message)
    {
        if (isset($message['login'])) unset($message['login']);

        if (isset($message['id']) && ctype_digit((string) $message['id'])) {
            // update record
            $id = $message['id'];
            unset($message['id']);
            // unset user_id to prevent overwriting author because of admin's edits
            unset($message['user_id']);

            if (isset($message['date'])) unset($message['date']);

            return $this->db->update('message', $message, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('message', $message);
        }
    }

    /**
     * Remove record.
     *
     * @param array $message Message
     *
     * @return boolean Result
     */
    public function delete($message)
    {
        if (isset($message['id']) && ctype_digit((string) $message['id'])) {
            return $this->db->delete('message', ['id' => $message['id']]);
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

        return $queryBuilder->select('m.id', 'm.content', 'm.date', 'm.project_id', 'm.user_id', 'u.login')
            ->orderBy('m.date', 'DESC')
            ->from('message', 'm')
            ->innerJoin('m', 'user', 'u', 'm.user_id = u.id');
    }

    /**
     * Query all records for a specific project.
     *
     * @param int $projectId ID of a project
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllForProject($projectId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('m.project_id = :id')
            ->setParameter(':id', $projectId, \PDO::PARAM_INT);

        return $queryBuilder;
    }
}