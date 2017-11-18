<?php
/**
 * Unique login validator.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueLoginValidator.
 *
 * @package Validator\Constraints
 */
class UniqueLoginValidator extends ConstraintValidator
{

    /**
     * {@inheritdoc}
     *
     * @param mixed $value Value
     * @param Constraint $constraint Constraint
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
                ->setParameter('{{ login }}', $value)
                ->addViolation();
        }
    }
}