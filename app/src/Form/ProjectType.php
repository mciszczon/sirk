<?php
/**
 * Project type.
 */
namespace Form;

use Repository\ProjectRepository;
use Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProjectType
 * @package Form
 */
class ProjectType extends AbstractType
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
                        ['groups' => ['project-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['project-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'subtitle',
            TextType::class,
            [
                'label' => 'label.subtitle',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                    'class' => 'pure-u-1',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['project-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['project-default'],
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
                    'style' => 'resize: vertial',
                    'class' => 'pure-u-1',
                ],
                'constraints' => [
                    new Assert\Length([
                        'groups' => ['project-default'],
                        'max' => 128,
                    ]),
                ],
            ]
        );
        $builder->add(
            'users',
            ChoiceType::class,
            [
                'label' => 'label.users',
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'choices' => $this->prepareUsersForChoices($options['user_repository']),
                'data' => $this->findLinkedUsers($options['project_repository'], $options['project_id']),
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
                'validation_groups' => 'project-default',
                'user_repository' => null,
                'project_repository' => null,
                'project_id' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'project_type';
    }

    /**
     * Prepare users for linking with the project.
     *
     * @param $userRepository UserRepository
     * @return array Formatted users
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

    /**
     * Find users linked to the project.
     *
     * @param $projectRepository ProjectRepository
     * @param int $projectId Project ID
     * @return mixed
     */
    public function findLinkedUsers($projectRepository, $projectId)
    {
        $linkedUsers = $projectRepository->findLinkedUsers($projectId);

        return $linkedUsers;
    }
}