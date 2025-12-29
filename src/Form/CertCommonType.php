<?php

namespace App\Form;

use App\Dto\CertDto;
use App\Dto\CertCommonDto;
use App\Entity\Domain;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints\File;
use Doctrine\ORM\EntityRepository;

class CertCommonType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = !$options['dto']->isNew();
        $disabled = $options['dto']->isCAInherit();
        $builder
        ->add(
            'countryName',
            CountryType::class,
            [
                'label' => 'countryName',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'disabled' => $disabled,
                ]
            ]
        )
        ->add(
            'stateOrProvinceName',
            TextType::class,
            [
                'label' => 'stateOrProvinceName',
                'required' => false,
                'attr' => [
                    'disabled' => $disabled,
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ->add(
            'localityName',
            TextType::class,
            [
                'label' => 'localityName',
                'required' => false,
                'attr' => [
                    'disabled' => $disabled,
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ->add(
            'organizationalUnitName',
            TextType::class,
            [
                'label' => 'organizationalUnitName',
                'required' => false,
                'attr' => [
                    'disabled' => $disabled,
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ->add(
            'organizationName',
            TextType::class,
            [
                'label' => 'organizationName',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'disabled' => $disabled,
                ]
            ]
        );
        if ($options['dto']->getCerttype()!='client' || $readonly) {
            $builder
            ->add(
                'commonName',
                TextType::class,
                [
                    'label' => 'commonName',
                    'required' => false,
                    'attr' => [
                        'disabled' => $disabled && $readonly,
                        'autocomplete' => 'new-password'
                    ]
                ]
            )
            ;
            if ($options['dto']->getCerttype()!='client' && null==$options['dto']->getSubject()) {
                $builder
                ->add(
                    'customFile',
                    FileType::class,
                    [
                        'required' => false,
                        'constraints' => [
                            new File(
                                [
                                    'maxSize' => '10K'
                                ]
                            )
                        ]
                    ]
                )
                ->add(
                    'plainPassword',
                    RepeatedType::class,
                    [
                        'type' => PasswordType::class,
                        'label' => false,
                        'required' => false,
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
        if ($options['dto']->getCerttype()=='client') {
            $builder
            ->add(
                'emailAddress',
                TextType::class,
                [
                    'label' => 'emailAddress',
                    'required' => false,
                    'attr' => [
                        'disabled' => $disabled && $readonly,// && $options['dto']->getCerttype()=='ca',
                        //'autocomplete' => 'new-password'
                    ]
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
        $resolver->setDefaults(
            [
                'data_class' => CertCommonDto::class,
                'dto' => null,
            ]
        );
    }
}
