<?php
/**
 * File repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class TagRepository.
 *
 * @package Repository
 */
class FileRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 8;

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
     * FileRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->userRepository = new UserRepository($db);
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     * @param int $projectId ID of a project
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1, $projectId)
    {
        $countQueryBuilder = $this->queryAllForProject($projectId)
            ->select('COUNT(DISTINCT f.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator(
            $this->queryAllForProject($projectId), $countQueryBuilder
        );
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Get one file by its ID
     *
     * @param int $id ID of a file
     *
     * @return array|mixed File details
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('f.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Find four newest files uploaded to a project
     *
     * @param int $projectId ID of a project
     *
     * @return array Details of 4 newest files
     */
    public function findLastFilesForProject($projectId)
    {
        $queryBuilder = $this->queryAllForProject($projectId);
        $queryBuilder->orderBy('f.id', 'DESC')->setMaxResults(4);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Check whether user is the uploader of a file
     *
     * @param int $userId ID of a user
     * @param int $fileId ID of a file
     * @return bool Boolean information
     */
    public function checkIfUserHasFile($userId, $fileId)
    {
        $file = $this->findOneById($fileId);

        if ($userId == $file['user_id']) return true;
        return false;
    }

    /**
     * Save record.
     *
     * @param array $file File
     *
     * @return boolean Result
     */
    public function save($file)
    {
        if (isset($file['id']) && ctype_digit((string) $file['id'])) {
            // update record
            $id = $file['id'];
            unset($file['id']);
            unset($file['user_id']);

            return $this->db->update('file', $file, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('file', $file);
        }
    }

    /**
     * Remove record.
     *
     * @param array $file File
     *
     * @return boolean Result
     */
    public function delete($file)
    {
        if (isset($file['id']) && ctype_digit((string) $file['id'])) {
            return $this->db->delete('file', ['id' => $file['id']]);
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

        return $queryBuilder->select('f.id', 'f.name', 'f.description', 'f.file', 'f.project_id', 'f.user_id', 'u.login')
            ->from('file', 'f')
            ->innerJoin('f', 'user', 'u', 'f.user_id = u.id');
    }

    /**
     * Query all records for a specific project
     *
     * @param int $projectId ID of a proejct
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllForProject($projectId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('f.project_id = :id')
            ->setParameter(':id', $projectId, \PDO::PARAM_INT);

        return $queryBuilder;
    }
}