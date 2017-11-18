<?php
/**
 * User edit type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class UserType.
 *
 * @package Form
 */
class UserEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'login',
            TextType::class,
            [
                'label' => 'label.login',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                    'class' => 'pure-u-1',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['user-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['user-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                    new CustomAssert\UniqueLogin(
                        [
                            'groups' => ['user-default'],
                            'repository' => isset($options['user_repository']) ? $options['user_repository'] : null,
                            'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'role_id',
            ChoiceType::class,
            [
                'label' => 'label.roles',
                'required' => true,
                'attr' => [
                    'class' => 'pure-u-1',
                ],
                'placeholder' => 'label.none',
                'choices' => $this->prepareRolesForChoices($options['user_repository']),
                'data' => $this->getCurrentRole($options['user_repository'], $options['user_id']),
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['user-default']]
                    ),
                ],
            ]
        );
        $builder->add(
            'email',
            EmailType::class,
            [
                'label' => 'label.email',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                    'class' => 'pure-u-1',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['user-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['user-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                    new Assert\Email(
                        [
                            'groups' => ['user-default']
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'password',
            RepeatedType::class,
            [
                'type' => PasswordType::class,
                'invalid_message' => 'message.password_not_repeated',
                'options' => array('attr' => array('class' => 'password-field pure-u-1')),
                'required' => false,
                'first_options' => array('label' => 'label.password'),
                'second_options' => array('label' => 'label.repeat_password'),
                'constraints' => [
                    new Assert\Length(
                        [
                            'groups' => ['user-password'],
                            'min' => 6,
                            'max' => 32,
                        ]
                    ),
                ],
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
                'validation_groups' =>
                    [
                        'user-default',
                        'user-password',
                    ],
                'user_repository' => null,
                'user_id' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_type';
    }

    /**
     * Prepare all user roles for choice.
     *
     * @param /Repository/UserRepository $userRepository User Repository
     * @return array Array of roles
     */
    protected function prepareRolesForChoices($userRepository)
    {
        $roles = $userRepository->getAllRoles();
        $choices = [];

        foreach ($roles as $role) {
            $choices[$role['name']] = $role['id'];
        }

        return $choices;
    }

    /**
     * Get current role of edited user.
     *
     * @param /Repository/UserRepository $userRepository User Repository
     * @param int $userId User ID
     * @return int|bool Role ID
     */
    protected function getCurrentRole($userRepository, $userId)
    {
        $result = $userRepository->getUserRolesIds($userId);

        if (isset($result) and is_int($result[0])) return $result[0];

        return null;
    }
}