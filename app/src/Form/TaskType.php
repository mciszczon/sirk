<?php
/**
 * Tag type.
 */
namespace Form;

use Doctrine\DBAL\Types\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType as SymfonyDateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;
use Repository\PriorityRepository;

/**
 * Class TagType.
 *
 * @package Form
 */
class TaskType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['task-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['task-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'description',
            TextareaType::class,
            [
                'label' => 'label.description',
                'required' => false,
            ]
        );
        $builder->add(
            'date',
            SymfonyDateType::class,
            [
                'label' => 'label.due_date',
                'required' => true,
                'years' => $this->prepareYearsForChoices(),
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['task-default'],]
                    ),
                ],
            ]
        );
        $builder->add(
            'priority_id',
            ChoiceType::class,
            [
                'label' => 'label.priority',
                'required' => true,
                'choices' => $this->preparePrioritiesForChoices($options['priorities_repository']),
                'choice_translation_domain' => 'messages',
                'data' => 4,
                'constraints' => [
                    new Assert\NotNull(
                        ['groups' => ['task-default'],]
                    ),
                ],
            ]
        );
        $builder->add(
            'user_id',
            ChoiceType::class,
            [
                'label' => 'label.users',
                'required' => false,
                'choice_translation_domain' => 'messages',
                'choices' => $this->prepareUsersForChoices($options['user_repository']),
            ]
        );
        $builder->add(
            'project_id',
            HiddenType::class,
            [
                'data' => $options['project_id'],
            ]
        );
        $builder->add(
            'done',
            HiddenType::class,
            [
                'data' => 0,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'task-default',
                'task_repository' => null,
                'project_repository' => null,
                'priorities_repository' => null,
                'user_repository' => null,
                'project_id' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'task_type';
    }


    protected function preparePrioritiesForChoices($priorityRepository)
    {
        $priorities = $priorityRepository->findAll();
        $choices = [];

        foreach ($priorities as $priority) {
            $choices[$priority['name']] = $priority['id'];
        }

        return $choices;
    }

    /**
     * Takes current year and creates an array with 2 next years.
     *
     * @return array
     */
    protected function prepareYearsForChoices() {
        $currentYear = date('Y');
        $years = [];

        for($i = 0; $i < 3; $i++) {
            $years[$i] = $currentYear++;
        }

        return $years;
    }

    /**
     * @param $userRepository
     * @return array
     */
    protected function prepareUsersForChoices($userRepository)
    {
        $users = $userRepository->findAll();
        $choices = [];

        foreach ($users as $user) {
            $choices[$user['login']] = $user['id'];
        }

        return $choices;
    }
}