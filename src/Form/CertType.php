<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;

class CertType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $notAfter = new \DateTime();
        $notAfter->add(\DateInterval::createFromDateString($options['duration']));
        //dump($options);
        $builder
        ->add(
            'domain',
            HiddenType::class,
            [
                'data' => $options['domain']
            ]
        )
        ->add('common',
            CertCommonType::class,
            [
                'label' => false,
                'certtype' => $options['certtype'],
                'subject' => $options['subject'],
                'domain' => $options['domain'],
            ]
        )
        ->add(
            'interval',
            CertIntervalType::class,
            [
                'duration' => $options['duration'],
                'interval' => $options['interval'],
                'label' => false,
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
            'domain' => null,
            'duration' => '1 years',
            'interval' => null,
            'certtype' => null,
            'subject' => null,
        ));
    }
}
