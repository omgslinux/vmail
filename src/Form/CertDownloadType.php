<?php

namespace App\Form;

use App\Dto\CertDto;
use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;

class CertDownloadType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add(
            'user',
            HiddenType::class,
            [
                'data' => $options['entity']
            ]
        );

        if ($options['certtype']=='client') {
            $builder
            ->add(
                'pkcs12',
                SubmitType::class,
                [
                    'label' => 'PCKS12 (for browsers)',
                    'attr' => [
                        'class' => 'btn btn-primary'
                    ]
                ]
            )
            ->add(
                'pem',
                SubmitType::class,
                [
                    'label' => 'PEM (other uses)',
                    'attr' => [
                        'class' => 'btn btn-primary'
                    ]
                ]
            );
        }


        $builder
        ->add(
            'setkey',
            ManagePasswordType::class,
            [
                'label' => false,
                'data' => $options['entity'],
            ]
        )
        ;

        if ($options['certtype'] == 'export') {
            $builder->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'label' => false,
                    'first_options' =>
                    [
                        'label' => 'Password',
                        'attr' => [
                            'autocomplete' => 'new-password'
                        ]
                    ],
                    'second_options' =>
                    [
                        'label' => 'Confirm password',
                        'attr' => [
                            'autocomplete' => 'new-password'
                        ]
                    ],
                ]
            )
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'entity' => null,
            'certtype' => null,
        ));
    }
}
