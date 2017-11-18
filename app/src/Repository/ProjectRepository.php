<?php
/**
 * Project repository
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

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
     * ProjectRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Find projects that are linked to a user.
     *
     * @param $userId
     * @return array
     */
    public function findAllForUser($userId)
    {
        $chosenProjects = $this->findLinkedProjectsIds($userId);

        $queryBuilder = $this->queryAll();
        $queryBuilder->where('p.id IN (:ids)')
        ->setParameter(':ids', $chosenProjects, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find all projects
     *
     * @return array
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find one project.
     *
     * @param string $id Project ID
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
     * Delete project.
     *
     * @param array $project Project
     * @throws DBALException
     */
    public function delete($project)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedUsers($project['id']);
            $this->removeLinkedNotes($project['id']);
            $this->removeLinkedFiles($project['id']);
            $this->removeLinkedMessages($project['id']);
            $this->removeLinkedTasks($project['id']);
            $this->db->delete('project', ['id' => $project['id']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Find users that are linked to a project.
     *
     * @param int $projectId ID of a project
     * @return array Array of users' IDs
     */
    public function findLinkedUsers($projectId)
    {
        $queryBuilder = $this->queryConnections();
        $queryBuilder->where('up.project_id = :id')
            ->setParameter(':id', $projectId, \PDO::PARAM_INT);
        $connections = $queryBuilder->execute()->fetchAll();

        return isset($connections) ? array_column($connections, 'user_id') : [];
    }

    /**
     * Find users linked to a project and fetch them entirely.
     *
     * @param int $projectId Project ID
     * @return array Array of users
     */
    public function findLinkedUsersDetails($projectId)
    {
        $usersIds = $this->findLinkedUsers($projectId);
        $userRepository = new UserRepository($this->db);

        $queryBuilder = $userRepository->queryAll()
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $usersIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find projects' IDs that user is linked to.
     *
     * @param int $userId User ID
     * @return array Result
     */
    public function findLinkedProjectsIds($userId)
    {
        $queryBuilder = $this->queryConnections();
        $queryBuilder->where('up.user_id = :id')
            ->setParameter(':id', $userId, \PDO::PARAM_INT);
        $connections = $queryBuilder->execute()->fetchAll();

        return isset($connections) ? array_column($connections, 'project_id') : [];
    }

    /**
     * Remove links between users and a project
     *
     * @param int $projectId Project ID
     * @return int Result
     */
    protected function removeLinkedUsers($projectId)
    {
        return $this->db->delete('user_has_project', ['project_id' => $projectId]);
    }

    /**
     * Remove notes for a project.
     *
     * @param int $projectId Project ID
     * @return int Result
     */
    protected function removeLinkedNotes($projectId)
    {
        return $this->db->delete('note', ['project_id' => $projectId]);
    }

    /**
     * Remove files for a project.
     *
     * @param int $projectId Project ID
     * @return int Result
     */
    protected function removeLinkedFiles($projectId)
    {
        return $this->db->delete('file', ['project_id' => $projectId]);
    }

    /**
     * Remove messages for a project.
     *
     * @param int $projectId Project ID
     * @return int Result
     */
    protected function removeLinkedMessages($projectId)
    {
        return $this->db->delete('message', ['project_id' => $projectId]);
    }

    /**
     * Remove tasks for a project.
     *
     * @param int $projectId Project ID
     * @return int Result
     */
    protected function removeLinkedTasks($projectId)
    {
        return $this->db->delete('task', ['project_id' => $projectId]);
    }

    /**
     * Add links between a project and users
     *
     * @param int $projectId Project ID
     * @param array $usersIds Array of user's IDs
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
     * Check if user is linked to a project.
     *
     * @param int $user User ID
     * @param int $project Project ID
     * @return bool Boolean information
     */
    public function checkIfUserHasProject($user, $project)
    {
        $linkedUsersIds = $this->findLinkedUsers($project);

        if (in_array($user, $linkedUsersIds)) return true;
        return false;
    }

    /**
     * Query all projects.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('p.id', 'p.name', 'p.subtitle', 'p.description')->from('project', 'p');
    }

    /**
     * Query connections between projects and users
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function queryConnections()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('up.user_id', 'up.project_id')->from('user_has_project', 'up');
    }
}
