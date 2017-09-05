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
     * Gets user by User ID.
     *
     * @param $id string UserId
     * @return array Result
     * @internal param int $userId User ID
     */

    public function findOneByLogin($login)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.login = :login')
            ->setParameter(':login', $login);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }


    public function findAll()
    {
        $queryBuilder = $this->queryAll();
        $result = $queryBuilder->execute()->fetchAll();

        return !$result ? [] : $result;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('u.id', 'u.login', 'u.email', 'u.role_id')->from('user', 'u');
    }

    /**
     * @return array
     */
    public function getAllRoles()
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('r.id', 'r.name')->from('role', 'r');

        $result = $queryBuilder->execute()->fetchAll();

        return $result;
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
     * @param array $user User
     *
     * @return boolean Result
     */
    public function save($user)
    {
        unset($user['role_name']);

        if (isset($user['id']) && ctype_digit((string) $user['id'])) {
            // update record
            $id = $user['id'];
            unset($user['id']);

            return $this->db->update('user', $user, ['id' => $id]);
        } else {
            // add new record
            $user['role_id'] = '2';
            return $this->db->insert('user', $user);
        }
    }

    /**
     * Remove record.
     *
     * @param array $user User
     *
     * @return boolean Result
     */
    public function delete($user)
    {
        if (isset($user['id']) && ctype_digit((string) $user['id'])) {
            return $this->db->delete('user', $user);
        } else {
            throw new \InvalidArgumentException('Invalid parameter type');
        }
    }
}
