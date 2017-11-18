<?php
/**
 * Note type.
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class NoteType
 * @package Form
 */
class NoteType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'title',
            TextType::class,
            [
                'label' => 'label.title',
                'required' => true,
                'attr' => [
                    'class' => 'pure-u-1'
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        [
                            'groups' => ['note-default']
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'content',
            TextareaType::class,
            [
                'label' => 'label.content',
                'required' => true,
                'attr' => [
                    'class' => 'pure-u-1',
                    'style' => 'resize: vertical'
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['message-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['message-default'],
                            'min' => 3,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'project_id',
            HiddenType::class,
            [
                'data' => $options['project_id'],
                'constraints' => [
                    new Assert\NotBlank([
                        'groups' => ['message-default']
                    ])
                ]
            ]
        );
        $builder->add(
            'user_id',
            HiddenType::class,
            [
                'data' => $options['user_id'],
                'constraints' => [
                    new Assert\NotBlank([
                        'groups' => ['message-default']
                    ])
                ]
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
                'validation_groups' => 'message-default',
                'project_repository' => null,
                'project_id' => null,
                'user_id' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'message_type';
    }
}