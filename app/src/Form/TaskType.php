<?php
/**
 * Task type.
 */
namespace Form;

use Repository\PriorityRepository;
use Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType as SymfonyDateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\CallbackTransformer;

/**
 * Class TaskType
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
                    'class' => 'pure-u-1',
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
                'attr' => [
                    'class' => 'pure-u-1',
                ],
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
                    new Assert\Date(
                        [
                            'groups' => ['task-default']
                        ]
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
                'attr' => [
                    'class' => 'pure-u-1',
                ],
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
                'label' => 'label.task_user',
                'required' => false,
                'attr' => [
                    'class' => 'pure-u-1',
                ],
                'choice_translation_domain' => 'messages',
                'choices' => $this->prepareUsersForChoices($options['project_repository'], $options['project_id']),
            ]
        );
        $builder->add(
            'author_id',
            HiddenType::class,
            [
                'data' => $options['current_user_id']
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

        $builder->get('date')
            ->addModelTransformer(new CallbackTransformer(
                function ($date) {
                    return isset($date) ? \DateTime::createFromFormat("Y-m-d", $date) : null;
                },
                function ($date) {
                    return isset($date) ? date_format($date, 'Y-m-d') : '';
                }
            ));
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
                'current_user_id' => null,
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

    /**
     * Prepare priorities for choice
     *
     * @param $priorityRepository PriorityRepository
     * @return array Array of priorities
     */
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
     * Prepare all users for choice
     *
     * @param $projectRepository
     * @param int $projectId Project ID
     * @return array Array of users
     */
    protected function prepareUsersForChoices($projectRepository, $projectId)
    {
        $users = $projectRepository->findLinkedUsersDetails($projectId);
        $choices = [];

        foreach ($users as $user) {
            $choices[$user['login']] = $user['id'];
        }

        return $choices;
    }
}