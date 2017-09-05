<?php
/**
 * Unique Bookmark constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueTag.
 *
 * @package Validator\Constraints
 */
class UniqueBookmark extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = '{{ bookmark }} is not unique bookmark.';

    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;

    /**
     * Tag repository.
     *
     * @var null|\Repository\TagRepository $repository
     */
    public $repository = null;
}