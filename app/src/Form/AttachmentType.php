<?php
/**
 * Attachment type.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AttachmentType
 * @package Form
 */
class AttachmentType extends AbstractType
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
                    'class' => 'pure-u-1'
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        [
                            'groups' => ['file-default'],
                        ]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['file-default'],
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
                    'style' => 'resize: none'
                ],
            ]
        );
        $builder->add(
            'file',
            FileType::class,
            [
                'label' => 'label.file',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        [
                            'groups' => ['file-default'],
                        ]
                    ),
                    new Assert\File(
                        [
                            'groups' => ['file-default'],
                            'maxSize' => '4096k',
                            'mimeTypes' => [
                                'image/png',
                                'image/jpeg',
                                'image/pjpeg',
                                'image/jpeg',
                                'image/pjpeg',
                                'text/plain',
                                'text/rtf',
                                'application/pdf'
                            ],
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
                        'groups' => ['file-default']
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
                        'groups' => ['file-default']
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
                'validation_groups' => 'file-default',
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
        return 'file_type';
    }
}