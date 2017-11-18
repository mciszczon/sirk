<?php
/**
 * Message type.
 */
namespace Form;

use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as SymfonyDateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\CallbackTransformer;
use Validator\Constraints as CustomAssert;
use Repository\PriorityRepository;

/**
 * Class MessageType
 * @package Form
 */
class MessageType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
        $today = new \DateTime();
        $formattedDate = $today->format('Y-m-d H:i:s');
        $builder->add(
            'date',
            HiddenType::class,
            [
                'data' => $formattedDate,
                'constraints' => [
                    new Assert\NotBlank([
                        'groups' => ['message-default']
                    ]),
                    new Assert\DateTime([
                        'groups' => ['message-default']
                    ]),
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