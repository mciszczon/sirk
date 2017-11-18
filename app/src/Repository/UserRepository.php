<?php
/**
 * User repository
 */
namespace Repository;

use Silex\Application;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Utils\Paginator;

/**
 * Class UserRepository.
 *
 * @package Repository
 */
class UserRepository
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
     * Loads user by login.
     *
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        try {
            $user = $this->getUserByLogin($login);

            if (!$user || !count($user)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            $roles = $this->getUserRoles($user['id']);

            if (!$roles || !count($roles)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            return [
                'login' => $user['login'],
                'password' => $user['password'],
                'roles' => $roles,
            ];
        } catch (DBALException $exception) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        } catch (UsernameNotFoundException $exception) {
            throw $exception;
        }
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.id', 'u.login', 'u.password')
                ->from('user', 'u')
                ->where('u.login = :login')
                ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Get user ID
     *
     * @param \Silex\Application $app Silex application
     * @return String $userData['id'] User ID
     */

    public function getUserId(Application $app)
    {
        $token = $app['security.token_storage']->getToken();

        if (null !== $token) {
            $user = $token->getUser();

            if ($user instanceof User) {
                $username = $user->getUsername();
            } elseif ($user !== null && $user !== 'anon.') {
                $username = $user;
            }
            $userRepository = new UserRepository($app['db']);
            $userData = $userRepository->getUserByLogin($username);
            return $userData['id'];
        }
    }

    /**
     * Gets user data by login.
     *
     * @param int $id User id
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserById($id)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.id', 'u.login', 'u.email', 'u.password')
                ->from('user', 'u')
                ->where('u.id = :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Gets user roles by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.name')
                ->from('user', 'u')
                ->innerJoin('u', 'role', 'r', 'u.role_id = r.id')
                ->where('u.id = :id')
                ->setParameter(':id', $userId, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'name');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }

    /**
     * Gets user roles IDs by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRolesIds($userId)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.id')
                ->from('user', 'u')
                ->innerJoin('u', 'role', 'r', 'u.role_id = r.id')
                ->where('u.id = :id')
                ->setParameter(':id', $userId, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'id');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }

    /**
     * Gets user by User ID.
     *
     * @param $id string UserId
     * @return array Result
     * @internal param int $userId User ID
     */

    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.id = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Gets user by User login.
     *
     * @param string $login User login
     * @return array|mixed Result
     */
    public function findOneByLogin($login)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.login = :login')
            ->setParameter(':login', $login);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }


    /**
     * Fetch all users.
     *
     * @return array
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();
        $result = $queryBuilder->execute()->fetchAll();

        return !$result ? [] : $result;
    }

    /**
     * Query all users.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('u.id', 'u.login', 'u.email', 'u.role_id')->from('user', 'u');
    }

    /**
     * Fetch all roles.
     *
     * @return array Result
     */
    public function getAllRoles()
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('r.id', 'r.name')->from('role', 'r');

        $result = $queryBuilder->execute()->fetchAll();

        return $result;
    }

    /**
     * Get users paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT u.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Find for uniqueness.
     *
     * @param string          $login Element login
     * @param int|string|null $id    Element id
     *
     * @return array Result
     */
    public function findForUniqueness($login, $id = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.login = :login')
            ->setParameter(':login', $login, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('u.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Save record.
     *
     * @param Application $app Application
     * @param array $user User
     *
     * @return boolean Result
     */
    public function save($app, $user)
    {
        unset($user['role_name']);

        if (isset($user['password'])) {
            $user['password'] = $app['security.encoder.bcrypt']->encodePassword($user['password'], '');
        }

        if (isset($user['id']) && ctype_digit((string) $user['id'])) {
            // update record
            $id = $user['id'];
            unset($user['id']);

            if ($user['password'] === null) unset($user['password']);

            return $this->db->update('user', $user, ['id' => $id]);
        } else {
            // add new record
            $user['role_id'] = '2';
            return $this->db->insert('user', $user);
        }
    }

    /**
     * Delete user.
     *
     * @param array $user User
     * @throws DBALException
     */
    function delete($user)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedProjects($user['id']);
            $this->removeLinkedFiles($user['id']);
            $this->removeLinkedMessages($user['id']);
            $this->removeLinkedNotes($user['id']);
            $this->removeLinkedTasks($user['id']);
            $this->removeAssignedTasks($this->db, $user['id']);
            $this->db->delete('user', ['id' => $user['id']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Removed links between removed user and projects.
     *
     * @param int $userId User ID
     * @return int
     */
    protected function removeLinkedProjects($userId)
    {
        return $this->db->delete('user_has_project', ['user_id' => $userId]);
    }

    /**
     * Remove messages written by removed user.
     *
     * @param int $userId User ID
     * @return int
     */
    protected function removeLinkedMessages($userId)
    {
        return $this->db->delete('message', ['user_id' => $userId]);
    }

    /**
     * Remove notes owned by removed user.
     *
     * @param int $userId User ID
     * @return int
     */
    protected function removeLinkedNotes($userId)
    {
        return $this->db->delete('note', ['user_id' => $userId]);
    }

    /**
     * Remove files uploaded by removed user.
     *
     * @param int $userId User ID
     * @return int
     */
    protected function removeLinkedFiles($userId)
    {
        return $this->db->delete('file', ['user_id' => $userId]);
    }

    /**
     * Removed tasks created by removed user.
     *
     * @param int $userId User ID
     * @return int
     */
    protected function removeLinkedTasks($userId)
    {
        return $this->db->delete('task', ['author_id' => $userId]);
    }

    /**
     * Remove all assignments to removed user.
     *
     * @param $db
     * @param int $userId User ID
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    protected function removeAssignedTasks($db, $userId)
    {
        $taskRepository = new TaskRepository($db);

        return $taskRepository->deleteUserAssignments($userId);
    }
}
