<?php
/**
 * Unique Bookmark validator.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueTagValidator.
 *
 * @package Validator\Constraints
 */
class UniqueBookmarkValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint->repository) {
            return;
        }

        $result = $constraint->repository->findForUniqueness(
            $value,
            $constraint->elementId
        );

        if ($result && count($result)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ bookmark }}', $value)
                ->addViolation();
        }
    }
}