<?php
/**
 * Bookmark type.
 */
namespace Form;

use Doctrine\DBAL\Connection;
use Repository\ProjectRepository;
use Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class BookmarkType.
 *
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
                'data' => $this->prepareUsersForChoices($options['user_repository']),
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
     * Find linked tags.
     *
     * @param int $bookmarkId Bookmark Id
     *
     * @return array Result
     */
    public function findLinkedUsers($projectId)
    {
        $usersIds = $this->findLinkedUsers($projectId);

        return is_array($usersIds)
            ? $this->userRepository->findById($usersIds)
            : [];
    }
}