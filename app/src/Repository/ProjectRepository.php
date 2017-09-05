<?php
/**
 * User repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Utils\Paginator;

/**
 * Class UserRepository.
 *
 * @package Repository
 */
class ProjectRepository
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
     * TagRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
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
            ->select('COUNT(DISTINCT p.id) AS total_results')
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
        $queryBuilder->where('p.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return $result;
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
     * Save record.
     *
     * @param array $project Project
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($project)
    {
        $this->db->beginTransaction();

        try {
            $usersIds = isset($project['users']) ? $project['users'] : [];
            unset($project['users']);

            if (isset($project['id']) && ctype_digit((string) $project['id'])) {
                // update record
                $projectId = $project['id'];
                unset($project['id']);
                $this->removeLinkedUsers($projectId);
                $this->addLinkedUsers($projectId, $usersIds);
                $this->db->update('project', $project, ['id' => $projectId]);
            } else {
                // add new record
                $this->db->insert('project', $project);
                $projectId = $this->db->lastInsertId();
                $this->addLinkedUsers($projectId, $usersIds);
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove linked tags.
     *
     * @param int $bookmarkId Bookmark Id
     *
     * @return boolean Result
     */
    protected function removeLinkedUsers($projectId)
    {
        return $this->db->delete('user_has_project', ['project_id' => $projectId]);
    }

    /**
     * Add linked tags.
     *
     * @param int $bookmarkId Bookmark Id
     * @param array $tagsIds Tags Ids
     */
    protected function addLinkedUsers($projectId, $usersIds)
    {
        if (!is_array($usersIds)) {
            $usersIds = [$usersIds];
        }

        foreach ($usersIds as $userLogin => $userId) {
            $this->db->insert(
                'user_has_project',
                [
                    'project_id' => $projectId,
                    'user_id' => $userId,
                ]
            );
        }
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('p.id', 'p.name', 'p.subtitle', 'p.description')->from('project', 'p');
    }

    protected function queryConnections()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('up.user_id', 'up.project_id')->from('user_has_project', 'up');
    }
}
