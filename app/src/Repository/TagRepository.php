<?php
/**
 * Tag repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class TagRepository.
 *
 * @package Repository
 */
class TagRepository
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
    public function findLinkedBookmarksIds($id)
    {
        $queryBuilder = $this->queryConnections();
        $queryBuilder->select('bt.bookmark_id')
            ->where('bt.tag_id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);

        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'bookmark_id') : [];
    }

    /**
     * Find bookmarks names with current tag.
     *
     * @param int $id Id of the current tag.
     * @return array
     */
    public function findLinkedBookmarks($id)
    {
        $bookmarksIds = $this->findLinkedBookmarksIds($id);

        $queryBuilder = $this->queryBookmarks();
        $queryBuilder->where('b.id IN (:ids)')
            ->setParameter(':ids', $bookmarksIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Save record.
     *
     * @param array $tag Tag
     *
     * @return boolean Result
     */
    public function save($tag)
    {
        if (isset($tag['id']) && ctype_digit((string) $tag['id'])) {
            // update record
            $id = $tag['id'];
            unset($tag['id']);

            return $this->db->update('si_tags', $tag, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('si_tags', $tag);
        }
    }

    /**
     * Remove record.
     *
     * @param array $tag Tag
     *
     * @return boolean Result
     */
    public function delete($tag)
    {
        if (isset($tag['id']) && ctype_digit((string) $tag['id'])) {
            $linkedBookmarks = $this->findLinkedBookmarksIds($tag['id']);

            return empty($linkedBookmarks) ? $this->db->delete('si_tags', $tag) : false;
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

        return $queryBuilder->select('t.id', 't.name')
            ->from('si_tags', 't');
    }

    protected function queryConnections()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('bt.bookmark_id', 'bt.tag_id')
            ->from('si_bookmarks_tags', 'bt');
    }

    protected function queryBookmarks()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('b.id', 'b.title')
            ->from('si_bookmarks', 'b');
    }
}